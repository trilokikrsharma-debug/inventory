<?php
/**
 * Router - Controller Dispatch
 *
 * Maps page names to controller classes and dispatches actions.
 */
class Router {
    /** @var array<string, string> */
    private array $controllerMap = [
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
    ];

    /**
     * Dispatch request to the correct controller/action.
     */
    public function dispatch(Request $request): void {
        $page = trim((string)$request->page());
        $action = trim((string)$request->action());

        // Friendly URL mapping support for Nginx/Apache rewrite to index.php.
        if (empty($_GET['page'])) {
            $mapped = $this->resolveFriendlyRoute();
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
            $page = 'dashboard';
        }
        if ($action === '') {
            $action = 'index';
        }

        // Super-admins without tenant context should always land on platform dashboard.
        if ($page === 'dashboard' && Session::isLoggedIn() && Session::isSuperAdmin() && Tenant::id() === null) {
            $this->redirect('index.php?page=platform&action=dashboard');
            return;
        }

        try {
            if ($this->dispatchApiRoute()) {
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
                if ($page === 'dashboard') {
                    $this->redirect('index.php?page=platform&action=dashboard');
                    return;
                }
                $this->renderErrorPage(500);
                return;
            }

            require_once $controllerFile;

            if (!class_exists($controllerName, false)) {
                $this->logRouteError("Controller class not found after include: {$controllerName}");
                if ($page === 'dashboard') {
                    $this->redirect('index.php?page=platform&action=dashboard');
                    return;
                }
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

            // Module-routing fallback: never hard fail for dashboard.
            if ($page === 'dashboard') {
                $this->redirect('index.php?page=platform&action=dashboard');
                return;
            }

            $this->logRouteError("Action '{$action}' is not allowed for controller {$controllerName}");
            $this->renderErrorPage(404);
        } catch (\Throwable $e) {
            $this->logRouteThrowable("Dispatch failed for page='{$page}', action='{$action}'", $e);

            if ($page === 'dashboard') {
                $this->redirect('index.php?page=platform&action=dashboard');
                return;
            }

            $this->renderErrorPage(500);
        }
    }

    /**
     * Dispatch explicit API endpoints that bypass ?page routing.
     */
    private function dispatchApiRoute(): bool {
        $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');

        $apiRoutes = [
            '/api/v1/saas/register'               => ['TenantOnboardingController', 'register'],
            '/api/v1/saas/plans'                  => ['SaaSBillingController', 'plans'],
            '/api/v1/saas/webhook'                => ['SaaSBillingController', 'webhook'],
            '/api/v1/webhook/razorpay'            => ['SaaSBillingController', 'webhook'],
            '/api/v1/tenant/subscription/upgrade' => ['SaaSBillingController', 'upgrade'],
        ];

        foreach ($apiRoutes as $routePath => $handler) {
            if ($uri !== $routePath && !str_ends_with($uri, $routePath)) {
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
    private function resolveFriendlyRoute(): ?array {
        $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
        if ($uri === '') {
            return null;
        }

        $routes = [
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

        foreach ($routes as $routePath => $target) {
            if ($uri === $routePath || str_ends_with($uri, $routePath)) {
                return $target;
            }
        }

        return null;
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
        $target = ltrim($location, '/');

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
        $this->redirect('index.php?page=login');
    }
}
