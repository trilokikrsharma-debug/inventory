<?php
/**
 * Router - Controller Dispatch
 *
 * Maps page names to controller classes and dispatches actions.
 */
class Router {
    /** @var array<string, string> */
    private array $controllerMap = [
        'home'           => 'HomeController',
        'login'          => 'AuthController',
        'logout'         => 'AuthController',
        'dashboard'      => 'DashboardController',
        'products'       => 'ProductController',
        'categories'     => 'CategoryController',
        'brands'         => 'BrandController',
        'units'          => 'UnitController',
        'customers'      => 'CustomerController',
        'suppliers'      => 'SupplierController',
        'purchases'      => 'PurchaseController',
        'sales'          => 'SalesController',
        'payments'       => 'PaymentController',
        'reports'        => 'ReportController',
        'settings'       => 'SettingsController',
        'invoice'        => 'InvoiceController',
        'profile'        => 'ProfileController',
        'users'          => 'UserController',
        'twoFactor'      => 'TwoFactorController',
        'sale_returns'   => 'SaleReturnController',
        'quotations'     => 'QuotationController',
        'backup'         => 'BackupController',
        'roles'          => 'RoleController',
        'health'         => 'HealthController',
        'signup'         => 'SignupController',
        'pricing'        => 'PricingController',
        'demo_login'     => 'DemoLoginController',
        'insights'       => 'InsightController',
        'company'        => 'CompanySettingsController',
        'saas_dashboard' => 'TenantDashboardController',
        'platform'       => 'PlatformController',
        'saas_billing'   => 'SaaSBillingController',
        'saas_plans'     => 'SaaSPlanController',
        'promos'         => 'PromoCodeController',
        'referrals'      => 'ReferralController',
        'privacy'        => 'LegalController',
        'terms'          => 'LegalController',
        'refund'         => 'LegalController',
    ];

    /**
     * Dispatch request to the correct controller/action.
     */
    public function dispatch(Request $request): void {
        $page = trim((string)$request->page());
        $action = trim((string)$request->action());

        // Apply explicit pretty-route aliases without collapsing unknown URLs to home.
        if (!$request->hasExplicitPageQuery()) {
            $mapped = $this->resolveFriendlyRoute($request->path());
            if ($mapped) {
                $page = $mapped['page'];
                $action = $mapped['action'];
                $_GET['page'] = $page;
                $_GET['action'] = $action;
                foreach (($mapped['params'] ?? []) as $k => $v) {
                    $_GET[$k] = $v;
                }
            }
        }

        if ($page === '') {
            if (!$request->hasExplicitPageQuery() && $request->path() !== '/') {
                $this->renderErrorPage(404);
                return;
            }

            // Unauthenticated visitors see the landing page; logged-in users handled by HomeController
            $page = 'home';
        }
        if ($action === '') {
            $action = 'index';
        }

        $page = $this->normalizePage($page);

        // Pending 2FA logins are intentionally partial sessions.
        // Keep them fenced into the verification flow until completion.
        if ($this->hasPendingTwoFactorLogin()) {
            if ($page === 'logout') {
                $this->handleLogout();
                return;
            }

            if (!$this->isPendingTwoFactorRoute($page, $action)) {
                $this->redirect('/twoFactor/verify');
                return;
            }
        }

        // Super-admins should always land on the platform dashboard.
        if ($page === 'dashboard' && Session::isLoggedIn() && Session::isSuperAdmin()) {
            $this->redirect('/platform/dashboard');
            return;
        }

        try {
            if ($this->dispatchApiRoute($request->path())) {
                return;
            }

            if ($page === 'logout') {
                $this->handleLogout();
                return;
            }

            if ($page === 'backup') {
                @set_time_limit(300);
            }

            if (!isset($this->controllerMap[$page])) {
                $this->renderErrorPage(404);
                return;
            }

            $controllerName = $this->controllerMap[$page];
            $controllerFile = CONTROLLER_PATH . '/' . $controllerName . '.php';

            if (!is_file($controllerFile) || !is_readable($controllerFile)) {
                $this->logRouteError("Controller file not found or unreadable: {$controllerFile}");
                $this->renderErrorPage(500);
                return;
            }

            require_once $controllerFile;

            if (!class_exists($controllerName, false)) {
                $this->logRouteError("Controller class not found after include: {$controllerName}");
                $this->renderErrorPage(500);
                return;
            }

            $controller = new $controllerName();
            $allowedActions = method_exists($controller, 'getAllowedActions')
                ? (array)$controller->getAllowedActions()
                : ['index'];

            if (in_array($action, $allowedActions, true) && method_exists($controller, $action)) {
                $controller->$action();
                return;
            }

            if ($action === 'index' && method_exists($controller, 'index')) {
                $controller->index();
                return;
            }

            $this->logRouteError("Action '{$action}' is not allowed for controller {$controllerName}");
            $this->renderErrorPage(404);
        } catch (\Throwable $e) {
            $this->logRouteThrowable("Dispatch failed for page='{$page}', action='{$action}'", $e);
            $this->renderErrorPage(500);
        }
    }

    /**
     * Dispatch explicit API endpoints that bypass ?page routing.
     */
    private function dispatchApiRoute(string $requestPath): bool {
        $uri = $this->normalizeUriPath($requestPath);

        $apiRoutes = [
            '/api/v1/saas/register'               => ['TenantOnboardingController', 'register'],
            '/api/v1/saas/plans'                  => ['SaaSBillingController', 'plans'],
            '/api/v1/saas/webhook'                => ['SaaSBillingController', 'webhook'],
            '/api/v1/webhook/razorpay'            => ['SaaSBillingController', 'webhook'],
            '/api/v1/tenant/subscription/upgrade' => ['SaaSBillingController', 'upgrade'],
        ];

        foreach ($apiRoutes as $routePath => $handler) {
            if ($uri !== $routePath) {
                continue;
            }

            $controllerName = $handler[0];
            $actionName = $handler[1];
            $controllerFile = CONTROLLER_PATH . '/' . $controllerName . '.php';

            if (!is_file($controllerFile) || !is_readable($controllerFile)) {
                $this->logRouteError("API controller missing: {$controllerFile}");
                $this->renderErrorPage(500);
                return true;
            }

            require_once $controllerFile;

            if (!class_exists($controllerName, false)) {
                $this->logRouteError("API controller class missing: {$controllerName}");
                $this->renderErrorPage(500);
                return true;
            }

            $controller = new $controllerName();

            if (!method_exists($controller, $actionName)) {
                $this->logRouteError("API action '{$actionName}' missing in {$controllerName}");
                $this->renderErrorPage(404);
                return true;
            }

            $controller->$actionName();
            return true;
        }

        return false;
    }

    /**
     * Map pretty URLs to page/action for platform billing modules.
     */
    private function resolveFriendlyRoute(string $uri): ?array {
        $normalizedUri = $this->normalizeUriPath($uri);
        if ($normalizedUri === '') {
            return null;
        }

        $routes = [
            '/login' => ['page' => 'login', 'action' => 'index'],
            '/signup' => ['page' => 'signup', 'action' => 'index'],
            '/dashboard' => ['page' => 'dashboard', 'action' => 'index'],
            '/logout' => ['page' => 'logout', 'action' => 'index'],
            '/pricing' => ['page' => 'pricing', 'action' => 'index'],
            '/health' => ['page' => 'health', 'action' => 'index'],
            '/demo' => ['page' => 'demo_login', 'action' => 'index'],
            '/demo-login' => ['page' => 'demo_login', 'action' => 'index'],
            '/twoFactor/verify' => ['page' => 'twoFactor', 'action' => 'verify'],
            '/twoFactor/recovery' => ['page' => 'twoFactor', 'action' => 'recovery'],
            '/platform/plans' => ['page' => 'saas_plans', 'action' => 'index'],
            '/platform/plans/create' => ['page' => 'saas_plans', 'action' => 'create'],
            '/platform/plans/edit' => ['page' => 'saas_plans', 'action' => 'edit'],
            '/platform/promos' => ['page' => 'promos', 'action' => 'index'],
            '/platform/promos/create' => ['page' => 'promos', 'action' => 'create'],
            '/platform/promos/edit' => ['page' => 'promos', 'action' => 'edit'],
            '/platform/referrals' => ['page' => 'referrals', 'action' => 'index'],
            '/platform/referral-rewards' => ['page' => 'referrals', 'action' => 'rewards'],
            '/platform/subscribe' => ['page' => 'saas_billing', 'action' => 'subscribe'],
            '/platform/subscriptions' => ['page' => 'platform', 'action' => 'subscriptions'],
            '/platform/payments' => ['page' => 'platform', 'action' => 'payments'],
            '/platform/revenue' => ['page' => 'platform', 'action' => 'revenue'],
            '/platform/dashboard' => ['page' => 'platform', 'action' => 'dashboard'],
        ];

        return $routes[$normalizedUri] ?? null;
    }

    /**
     * Normalize URI path so route matching is exact and deterministic.
     */
    private function normalizeUriPath(string $uri): string {
        $path = (string)(parse_url($uri, PHP_URL_PATH) ?? '');
        if ($path === '') {
            return '/';
        }

        $normalized = '/' . ltrim($path, '/');
        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
            if ($normalized === '') {
                $normalized = '/';
            }
        }

        return $normalized;
    }

    /**
     * Safe error page include with graceful HTML fallback.
     */
    private function renderErrorPage(int $statusCode): void {
        if (!headers_sent()) {
            http_response_code($statusCode);
        }

        $view = $statusCode === 404
            ? VIEW_PATH . '/errors/404.php'
            : VIEW_PATH . '/errors/500.php';

        if (is_file($view) && is_readable($view)) {
            include $view;
            return;
        }

        $title = $statusCode === 404 ? '404 - Not Found' : '500 - Internal Server Error';
        $message = $statusCode === 404
            ? 'The requested page could not be found.'
            : 'An unexpected server error occurred.';

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $title . '</title></head><body style="font-family:Arial,sans-serif;padding:2rem;">'
            . '<h1>' . $title . '</h1><p>' . $message . '</p></body></html>';
    }

    /**
     * Relative redirect helper (resilient to APP_URL subfolder drift).
     */
    private function redirect(string $location): void {
        $target = trim($location);
        if (!preg_match('/^https?:\\/\\//i', $target)) {
            $target = rtrim(APP_URL, '/') . '/' . ltrim($target, '/');
        }

        if (!headers_sent()) {
            header('Location: ' . $target);
        } else {
            echo '<script>window.location.href=' . json_encode($target) . ';</script>';
        }

        exit;
    }

    private function logRouteError(string $message): void {
        error_log('[ROUTER] ' . $message);
    }

    private function logRouteThrowable(string $message, \Throwable $e): void {
        error_log('[ROUTER] ' . $message . ' | ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }

    /**
     * Whether the current session is waiting for 2FA completion.
     */
    private function hasPendingTwoFactorLogin(): bool {
        return Session::isTwoFactorPending();
    }

    /**
     * Allow only the 2FA verification flow while login is pending.
     */
    private function isPendingTwoFactorRoute(string $page, string $action): bool {
        if ($page !== 'twoFactor') {
            return false;
        }

        return in_array($action, ['verify', 'verifyPost', 'recovery', 'recoveryPost'], true);
    }

    /**
     * Normalize old or case-variant page names to the registered controller key.
     */
    private function normalizePage(string $page): string {
        $normalized = strtolower(trim($page));
        if ($normalized === 'twofactor') {
            return 'twoFactor';
        }

        return $page;
    }

    /**
     * Handle logout flow.
     */
    private function handleLogout(): void {
        try {
            $user = Session::get('user');
            if ($user) {
                Database::getInstance()->query(
                    'INSERT INTO activity_log (company_id, user_id, action, module, ip_address) VALUES (?, ?, ?, ?, ?)',
                    [$user['company_id'] ?? null, $user['id'], 'Logout', 'auth', $_SERVER['REMOTE_ADDR'] ?? null]
                );
            }
        } catch (\Exception $e) {
            error_log('[AuditLog] Failed to log logout: ' . $e->getMessage());
        }

        Session::destroy();
        $this->redirect('/login');
    }
}
