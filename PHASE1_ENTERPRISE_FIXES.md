# PHASE 1 Enterprise Fix Delivery

Generated on: 2026-03-18 13:55:37

## Fix 1 - 2FA Authentication End-to-End

### F:\xampp\htdocs\inventory\core\Router.php

```php
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

        // Pending 2FA logins are intentionally partial sessions.
        // Keep them fenced into the verification flow until completion.
        if ($this->hasPendingTwoFactorLogin()) {
            if ($page === 'logout') {
                $this->handleLogout();
                return;
            }

            if (!$this->isPendingTwoFactorRoute($page, $action)) {
                $this->redirect('index.php?page=twoFactor&action=verify');
                return;
            }
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

        // Dynamic routing fallback (matches keys in $_GET created by .htaccess RewriteRule ^(.*)$ index.php?/$1)
        foreach ($_GET as $key => $value) {
            if (str_starts_with($key, '/')) {
                $path = trim($key, '/');
                if ($path === '') continue;

                $parts = explode('/', $path);
                $page = $parts[0];
                
                // Allow routing ONLY if the module is registered.
                if (isset($this->controllerMap[$page])) {
                    return [
                        'page' => $page,
                        'action' => $parts[1] ?? 'index'
                    ];
                }
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
     * Whether the current session is waiting for 2FA completion.
     */
    private function hasPendingTwoFactorLogin(): bool {
        return Session::isLoggedIn() && !empty(Session::get('twofa_pending_user_id'));
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

```

### F:\xampp\htdocs\inventory\controllers\AuthController.php

```php
<?php
/**
 * Authentication Controller
 * 
 * Handles login/logout functionality.
 * Includes persistent IP-based rate limiting with exponential backoff.
 */
class AuthController extends Controller {

    protected $allowedActions = ['index'];

    // Rate limit settings (configurable)
    private const MAX_ATTEMPTS = 5;          // Lockout after this many failures
    private const BASE_LOCKOUT_SECONDS = 60; // Initial lockout: 1 minute
    private const MAX_LOCKOUT_SECONDS = 900; // Max lockout: 15 minutes
    private const ATTEMPT_WINDOW = 600;      // Reset counter after 10 min of no attempts

    public function index() {
        // If already logged in, redirect to dashboard
        if (Session::isLoggedIn()) {
            if (!empty(Session::get('twofa_pending_user_id'))) {
                $this->redirect('index.php?page=twoFactor&action=verify');
                return;
            }
            $this->redirect('index.php?page=dashboard');
        }

        // Handle login POST
        if ($this->isPost()) {
            $username = $this->sanitize($this->post('username'));
            $password = $this->post('password');

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            // â”€â”€ Tenant-Aware IP+Username Rate Limiting â”€â”€
            $rateData = $this->getRateLimit($ip, $username);

            // Check if currently locked out
            if ($rateData['lockout_until'] > time()) {
                $remaining = $rateData['lockout_until'] - time();
                $minutes = ceil($remaining / 60);
                $error = "Too many failed attempts. Try again in {$minutes} minute(s).";
                $this->renderPartial('auth.login', ['error' => $error, 'username' => $username]);
                return;
            }

            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
                $this->renderPartial('auth.login', ['error' => $error, 'username' => $username]);
                return;
            }

            $userModel = new UserModel();
            $user = $userModel->authenticate($username, $password);

            if ($user) {
                // Successful login â€” clear rate limit data
                $this->clearRateLimit($ip, $username);

                // â”€â”€ RBAC: Determine super-admin status first (before company check) â”€â”€
                // Platform super-admins are set directly in DB and may have no company_id.
                $isSuperAdmin = !empty($user['is_super_admin']);
                if (!$isSuperAdmin && !empty($user['role_id'])) {
                    try {
                        $role = Database::getInstance()->query(
                            "SELECT is_super_admin FROM roles WHERE id = ?",
                            [$user['role_id']]
                        )->fetch();
                        if ($role && $role['is_super_admin']) {
                            $isSuperAdmin = true;
                        }
                    } catch (\Exception $e) {
                        error_log('[RBAC] Failed to load role on login: ' . $e->getMessage());
                    }
                }

                // â”€â”€ Multi-Tenant: Resolve user's company â”€â”€
                // Platform super-admins are exempt from the company requirement.
                $companyId = (int)($user['company_id'] ?? 0);
                $company   = null;

                if (!$isSuperAdmin) {
                    // Tenant users MUST belong to an active company
                    if ($companyId <= 0) {
                        $error = 'Your account is not associated with any company. Please contact support.';
                        $this->renderPartial('auth.login', ['error' => $error, 'username' => $username]);
                        return;
                    }

                    try {
                        $company = Database::getInstance()->query(
                            "SELECT id, name, status, is_demo, plan, saas_plan_id, subscription_status, trial_ends_at, max_users, max_products
                             FROM companies
                             WHERE id = ? AND status = 'active'",
                            [$companyId]
                        )->fetch();
                    } catch (\Exception $e) {
                        $company = null;
                    }

                    if (!$company) {
                        $error = 'Your company account has been suspended or deactivated. Contact support.';
                        $this->renderPartial('auth.login', ['error' => $error, 'username' => $username]);
                        return;
                    }
                }

                if (!empty($user['twofa_enabled'])) {
                    $this->beginTwoFactorChallenge($user, $companyId, $company, $isSuperAdmin);
                    return;
                }

                $this->finalizeLogin($user, $companyId, $company, $isSuperAdmin);
                return;
            } else {
                // Failed login â€” update rate limit with exponential backoff
                $rateData['attempts'] = ($rateData['attempts'] ?? 0) + 1;
                $rateData['last_attempt'] = time();

                if ($rateData['attempts'] >= self::MAX_ATTEMPTS) {
                    // Exponential backoff: 1 min, 2 min, 4 min, 8 min... capped at MAX_LOCKOUT
                    $escalation = max(0, $rateData['attempts'] - self::MAX_ATTEMPTS);
                    $lockoutSeconds = min(
                        self::BASE_LOCKOUT_SECONDS * pow(2, $escalation),
                        self::MAX_LOCKOUT_SECONDS
                    );
                    $rateData['lockout_until'] = time() + (int)$lockoutSeconds;
                    Helper::securityLog('BRUTE_FORCE_LOCKOUT', "User: $username locked out for {$lockoutSeconds}s from $ip (attempt #{$rateData['attempts']})");
                } else {
                    Helper::securityLog('LOGIN_FAILED', "Failed attempt {$rateData['attempts']} for User: $username from $ip");
                }

                $this->setRateLimit($ip, $username, $rateData);

                $error = 'Invalid username or password.';
                $this->renderPartial('auth.login', ['error' => $error, 'username' => $username]);
                return;
            }
        }

        $this->renderPartial('auth.login', ['error' => '', 'username' => '']);
    }

    // =========================================================
    // Persistent Rate Limiting (file-based, per-IP)
    // =========================================================

    /**
     * Get rate limit data for an IP + Username combination.
     * Uses file-based storage in the cache directory.
     * Ensure shared-offices aren't globally blocked on false passwords.
     *
     * @param string $ip
     * @param string $username
     * @return array
     */
    private function getRateLimit($ip, $username) {
        $default = ['attempts' => 0, 'lockout_until' => 0, 'last_attempt' => 0];
        $file = $this->getRateLimitFile($ip, $username);

        if (!file_exists($file)) return $default;

        $data = @file_get_contents($file);
        if ($data === false) return $default;

        $parsed = @json_decode($data, true);
        if (!is_array($parsed)) {
            @unlink($file);
            return $default;
        }

        // Auto-expire: if last attempt was beyond the window, reset
        if (time() - ($parsed['last_attempt'] ?? 0) > self::ATTEMPT_WINDOW) {
            @unlink($file);
            return $default;
        }

        return array_merge($default, $parsed);
    }

    /**
     * Save rate limit data for an IP + Username.
     *
     * @param string $ip
     * @param string $username
     * @param array $data
     */
    private function setRateLimit($ip, $username, $data) {
        $file = $this->getRateLimitFile($ip, $username);
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Clear rate limit on successful login.
     *
     * @param string $ip
     * @param string $username
     */
    private function clearRateLimit($ip, $username) {
        $file = $this->getRateLimitFile($ip, $username);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Get the file path for a rate limit record.
     * Uses SHA256 of IP + Username + Tenant to prevent directory traversal and shared-IP blocking.
     *
     * @param string $ip
     * @param string $username
     * @return string
     */
    private function getRateLimitFile($ip, $username) {
        $dir = defined('BASE_PATH') ? BASE_PATH . '/cache' : __DIR__ . '/../cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $tenantId = class_exists('Tenant') ? (Tenant::id() ?? 0) : 0;
        $key = hash('sha256', $tenantId . '_' . $ip . '_' . strtolower(trim($username)));
        return $dir . '/ratelimit_' . $key . '.json';
    }

    /**
     * Start the pending 2FA flow without creating a fully authorized session.
     */
    private function beginTwoFactorChallenge(array $user, int $companyId, ?array $company, bool $isSuperAdmin): void {
        $pendingUser = $this->sanitizeSessionUser($user, $isSuperAdmin);
        $pendingUser['twofa_pending'] = true;
        $pendingUser['twofa_verified'] = false;

        // Keep a partial session so AuthMiddleware can pass the verification page,
        // but fence all other routes in Router until verification completes.
        Session::set('user', $pendingUser);
        Session::set('twofa_pending_user_id', (int)$pendingUser['id']);
        Session::set('twofa_pending_is_super_admin', $isSuperAdmin ? 1 : 0);
        Session::set('twofa_pending_company_id', $companyId > 0 ? $companyId : null);
        Session::set('twofa_pending_company', $company);
        Session::clearPermissionCache();

        if ($isSuperAdmin) {
            Tenant::reset();
        } elseif ($companyId > 0 && $company) {
            Tenant::set($companyId, $company);
        } else {
            Tenant::reset();
        }

        $this->logActivity('Login pending 2FA', 'auth', $pendingUser['id'], 'Two-factor verification required');
        $this->redirect('index.php?page=twoFactor&action=verify');
    }

    /**
     * Finalize a successful login after all checks have passed.
     */
    private function finalizeLogin(array $user, int $companyId, ?array $company, bool $isSuperAdmin): void {
        $sessionUser = $this->sanitizeSessionUser($user, $isSuperAdmin);
        $sessionUser['twofa_pending'] = false;
        $sessionUser['twofa_verified'] = true;

        session_regenerate_id(true);
        CSRF::rotateToken();
        Session::initFingerprint();
        Session::clearPermissionCache();

        Session::remove('twofa_pending_user_id');
        Session::remove('twofa_pending_is_super_admin');
        Session::remove('twofa_pending_company_id');
        Session::remove('twofa_pending_company');

        Session::set('user', $sessionUser);

        if ($isSuperAdmin) {
            Tenant::reset();
        } elseif ($companyId > 0 && $company) {
            Tenant::set($companyId, $company);
        } else {
            Tenant::reset();
        }

        $this->logActivity('Login', 'auth', $sessionUser['id'],
            $isSuperAdmin ? 'Platform super-admin logged in' : 'Tenant user logged in');

        if ($isSuperAdmin) {
            $this->redirect('index.php?page=platform&action=dashboard');
        }

        $this->redirect('index.php?page=dashboard');
    }

    /**
     * Strip sensitive DB fields before storing the user in session.
     */
    private function sanitizeSessionUser(array $user, bool $isSuperAdmin): array {
        unset(
            $user['password'],
            $user['twofa_secret'],
            $user['twofa_recovery_codes'],
            $user['company_status'],
            $user['company_name']
        );
        $user['is_super_admin'] = $isSuperAdmin || !empty($user['is_super_admin']);
        return $user;
    }
}

```

### F:\xampp\htdocs\inventory\controllers\TwoFactorController.php

```php
<?php
/**
 * Two-Factor Authentication Controller
 * 
 * Handles 2FA setup (QR code + recovery codes) and
 * OTP verification during login flow.
 * 
 * Routes:
 *   ?page=twoFactor&action=setup         Show QR code for setup
 *   ?page=twoFactor&action=enable        POST: verify + enable 2FA
 *   ?page=twoFactor&action=disable       POST: disable 2FA
 *   ?page=twoFactor&action=verify        OTP verification during login
 *   ?page=twoFactor&action=verifyPost    POST: check OTP code
 *   ?page=twoFactor&action=recovery      Recovery code input during login
 *   ?page=twoFactor&action=recoveryPost  POST: verify recovery code
 */
class TwoFactorController extends Controller {

    protected $allowedActions = [
        'setup', 'enable', 'disable',
        'verify', 'verifyPost',
        'recovery', 'recoveryPost'
    ];

    /**
     * Show 2FA setup page with QR code.
     * Requires authenticated user.
     */
    public function setup() {
        $this->requireAuth();

        $user = Session::get('user');
        $userId = $user['id'];
        $email = $user['email'] ?? $user['username'];

        $isEnabled = TwoFactorService::isEnabled($userId);

        // Generate a new secret for setup
        $secret = TwoFactorService::generateSecret();
        Session::set('twofa_setup_secret', $secret);

        $qrUrl = TwoFactorService::getQrCodeUrl($secret, $email);
        $otpAuthUrl = TwoFactorService::getOtpAuthUrl($secret, $email);

        $this->view('twoFactor.setup', [
            'pageTitle' => 'Two-Factor Authentication',
            'secret' => $secret,
            'qrUrl' => $qrUrl,
            'otpAuthUrl' => $otpAuthUrl,
            'isEnabled' => $isEnabled,
        ]);
    }

    /**
     * POST: Enable 2FA after verifying the first OTP code.
     */
    public function enable() {
        $this->requireAuth();
        $this->requirePost();

        $user = Session::get('user');
        $userId = $user['id'];
        $code = trim($_POST['otp_code'] ?? '');
        $secret = Session::get('twofa_setup_secret');

        if (!$secret) {
            Session::setFlash('error', 'Setup session expired. Please try again.');
            $this->redirect('index.php?page=twoFactor&action=setup');
            return;
        }

        // Verify the code against the setup secret
        if (!TwoFactorService::verifyCode($secret, $code)) {
            Session::setFlash('error', 'Invalid verification code. Please try again.');
            $this->redirect('index.php?page=twoFactor&action=setup');
            return;
        }

        // Generate recovery codes
        $recovery = TwoFactorService::generateRecoveryCodes();

        // Enable 2FA
        TwoFactorService::enable($userId, $secret, $recovery['hashed']);

        // Send security alert
        try { EmailService::send2faAlert($userId, 'enabled'); } catch (\Exception $e) {}

        // Clear setup session
        Session::remove('twofa_setup_secret');

        // Show recovery codes (one-time display)
        $this->view('twoFactor.recoveryCodes', [
            'pageTitle' => '2FA Recovery Codes',
            'codes' => $recovery['plain'],
        ]);
    }

    /**
     * POST: Disable 2FA.
     */
    public function disable() {
        $this->requireAuth();
        $this->requirePost();

        $user = Session::get('user');
        $userId = $user['id'];
        $password = $_POST['password'] ?? '';

        // Require password confirmation to disable 2FA
        $db = Database::getInstance();
        $dbUser = $db->query("SELECT password FROM users WHERE id = ?", [$userId])->fetch(\PDO::FETCH_ASSOC);

        if (!$dbUser || !password_verify($password, $dbUser['password'])) {
            Session::setFlash('error', 'Incorrect password. 2FA was not disabled.');
            $this->redirect('index.php?page=twoFactor&action=setup');
            return;
        }

        TwoFactorService::disable($userId);

        try { EmailService::send2faAlert($userId, 'disabled'); } catch (\Exception $e) {}

        Session::setFlash('success', 'Two-factor authentication has been disabled.');
        $this->redirect('index.php?page=twoFactor&action=setup');
    }

    /**
     * Show OTP verification form during login.
     */
    public function verify() {
        // User must have completed username/password but not yet full auth
        if (!Session::get('twofa_pending_user_id')) {
            $this->redirect('index.php?page=login');
            return;
        }

        $this->view('twoFactor.verify', [
            'pageTitle' => 'Enter Verification Code',
        ]);
    }

    /**
     * POST: Verify OTP code during login.
     */
    public function verifyPost() {
        $userId = Session::get('twofa_pending_user_id');
        if (!$userId) {
            $this->redirect('?page=login');
            return;
        }

        $code = trim($_POST['otp_code'] ?? '');
        $secret = TwoFactorService::getSecret($userId);

        if (!$secret || !TwoFactorService::verifyCode($secret, $code)) {
            Session::setFlash('error', 'Invalid verification code. Please try again.');
            Logger::security('2FA verification failed', ['user_id' => $userId]);
            $this->redirect('index.php?page=twoFactor&action=verify');
            return;
        }

        // 2FA passed â€” complete the login
        $this->completeLogin($userId);
    }

    /**
     * Show recovery code input form.
     */
    public function recovery() {
        if (!Session::get('twofa_pending_user_id')) {
            $this->redirect('index.php?page=login');
            return;
        }

        $this->view('twoFactor.recovery', [
            'pageTitle' => 'Recovery Code',
        ]);
    }

    /**
     * POST: Verify recovery code during login.
     */
    public function recoveryPost() {
        $userId = Session::get('twofa_pending_user_id');
        if (!$userId) {
            $this->redirect('index.php?page=login');
            return;
        }

        $code = trim($_POST['recovery_code'] ?? '');

        if (!TwoFactorService::verifyRecoveryCode($userId, $code)) {
            Session::setFlash('error', 'Invalid recovery code.');
            Logger::security('2FA recovery code failed', ['user_id' => $userId]);
            $this->redirect('index.php?page=twoFactor&action=recovery');
            return;
        }

        Logger::security('2FA recovery code used', ['user_id' => $userId]);

        // Recovery code valid â€” complete login
        $this->completeLogin($userId);
    }

    // â”€â”€â”€ Internal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Complete the login after 2FA verification.
     */
    private function completeLogin(int $userId): void {
        $pendingUserId = (int)Session::get('twofa_pending_user_id');
        if ($pendingUserId <= 0 || $pendingUserId !== $userId) {
            $this->redirect('index.php?page=login');
            return;
        }

        $context = $this->loadLoginContext($userId);
        if (!$context) {
            Session::remove('twofa_pending_user_id');
            Session::remove('twofa_pending_is_super_admin');
            Session::remove('twofa_pending_company_id');
            Session::remove('twofa_pending_company');
            Session::remove('user');
            Session::clearPermissionCache();
            Tenant::reset();
            Session::setFlash('error', 'Your login session expired. Please sign in again.');
            $this->redirect('index.php?page=login');
            return;
        }

        $this->finalizeLogin($context['user'], $context['company_id'], $context['company'], $context['is_super_admin']);
    }

    private function requirePost(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }
    }

    /**
     * Rebuild the login context from the database after 2FA succeeds.
     */
    private function loadLoginContext(int $userId): ?array {
        $db = Database::getInstance();
        $row = $db->query(
            "SELECT u.*, c.name AS company_name, c.status AS company_status, c.is_demo, c.plan, c.saas_plan_id,
                    c.subscription_status, c.trial_ends_at, c.max_users, c.max_products
             FROM users u
             LEFT JOIN companies c ON u.company_id = c.id
             WHERE u.id = ?",
            [$userId]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $isSuperAdmin = !empty($row['is_super_admin']);
        if (!$isSuperAdmin && !empty($row['role_id'])) {
            try {
                $role = $db->query(
                    "SELECT is_super_admin FROM roles WHERE id = ?",
                    [$row['role_id']]
                )->fetch(\PDO::FETCH_ASSOC);
                if ($role && !empty($role['is_super_admin'])) {
                    $isSuperAdmin = true;
                }
            } catch (\Throwable $e) {
                error_log('[RBAC] Failed to load role during 2FA login: ' . $e->getMessage());
            }
        }

        $companyId = (int)($row['company_id'] ?? 0);
        $company = null;

        if (!$isSuperAdmin) {
            if ($companyId <= 0) {
                return null;
            }

            try {
                $company = $db->query(
                    "SELECT id, name, status, is_demo, plan, saas_plan_id, subscription_status, trial_ends_at, max_users, max_products
                     FROM companies
                     WHERE id = ? AND status = 'active'",
                    [$companyId]
                )->fetch(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $company = null;
            }

            if (!$company) {
                return null;
            }
        }

        return [
            'user' => $row,
            'company' => $company,
            'company_id' => $companyId,
            'is_super_admin' => $isSuperAdmin,
        ];
    }

    /**
     * Final login session rebuild used after 2FA succeeds.
     */
    private function finalizeLogin(array $user, int $companyId, ?array $company, bool $isSuperAdmin): void {
        $sessionUser = $this->sanitizeSessionUser($user, $isSuperAdmin);
        $sessionUser['twofa_pending'] = false;
        $sessionUser['twofa_verified'] = true;

        session_regenerate_id(true);
        CSRF::rotateToken();
        Session::initFingerprint();
        Session::clearPermissionCache();

        Session::remove('twofa_pending_user_id');
        Session::remove('twofa_pending_is_super_admin');
        Session::remove('twofa_pending_company_id');
        Session::remove('twofa_pending_company');

        Session::set('user', $sessionUser);

        if ($isSuperAdmin) {
            Tenant::reset();
        } elseif ($companyId > 0 && $company) {
            Tenant::set($companyId, $company);
        } else {
            Tenant::reset();
        }

        $this->logActivity('Login', 'auth', $sessionUser['id'],
            $isSuperAdmin ? 'Platform super-admin logged in after 2FA' : 'Tenant user logged in after 2FA');

        if ($isSuperAdmin) {
            $this->redirect('index.php?page=platform&action=dashboard');
        }

        $this->redirect('index.php?page=dashboard');
    }

    /**
     * Strip sensitive fields before placing the user in session.
     */
    private function sanitizeSessionUser(array $user, bool $isSuperAdmin): array {
        unset(
            $user['password'],
            $user['twofa_secret'],
            $user['twofa_recovery_codes'],
            $user['company_status'],
            $user['company_name']
        );
        $user['is_super_admin'] = $isSuperAdmin || !empty($user['is_super_admin']);
        return $user;
    }
}

```

## Fix 2 - Migration System Schema Consistency

### F:\xampp\htdocs\inventory\cli\migrate.php

```php
<?php
/**
 * InvenBill Pro - CLI Migration Runner
 *
 * Executes SQL migration files from database/ in a deterministic order.
 * Tracks executed migrations in the migrations table to prevent double execution.
 *
 * Usage:
 *   php cli/migrate.php           Run all pending migrations
 *   php cli/migrate.php --status  Show migration status
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

function green(string $text): string
{
    return "\033[32m{$text}\033[0m";
}

function red(string $text): string
{
    return "\033[31m{$text}\033[0m";
}

function yellow(string $text): string
{
    return "\033[33m{$text}\033[0m";
}

function bold(string $text): string
{
    return "\033[1m{$text}\033[0m";
}

function migrationManifest(string $migrationDir): array
{
    $orderedFiles = [
        ['filename' => 'schema.sql', 'freshOnly' => true],
        ['filename' => '003_migrations_table.sql'],
        ['filename' => 'multi_tenant_migration.sql'],
        ['filename' => 'quotations.sql'],
        ['filename' => 'rbac_migration.sql'],
        ['filename' => 'super_admin_migration.sql'],
        ['filename' => '002_tenant_isolation_fix.sql'],
        ['filename' => '004_composite_indexes.sql'],
        ['filename' => '005_audit_trail.sql'],
        ['filename' => '006_financial_precision.sql'],
        ['filename' => '007_invoice_tenant_isolation.sql'],
        ['filename' => '008_job_queue.sql'],
        ['filename' => '009_saas_foundation.sql'],
        ['filename' => '009_two_factor_auth.sql'],
        ['filename' => '010_fix_tenant_role_hierarchy.sql'],
        ['filename' => '011_fix_superadmin_login.sql'],
        ['filename' => '012_roles_tenant_scoping.sql'],
        ['filename' => '013_final_security_hardening.sql'],
        ['filename' => '014_saas_billing_system.sql'],
        ['filename' => '015_sales_tax_charge_breakup.sql'],
        ['filename' => '016_company_settings_invoice_display_options.sql'],
        ['filename' => '017_gst_hsn_and_roundoff_settings.sql'],
        ['filename' => 'enterprise_hardening.sql'],
        ['filename' => 'enterprise_platform.sql'],
        ['filename' => 'performance_indexes.sql'],
    ];

    $manifest = [];
    foreach ($orderedFiles as $entry) {
        $path = $migrationDir . DIRECTORY_SEPARATOR . $entry['filename'];
        $entry['path'] = $path;
        $entry['missing'] = !is_file($path);
        $manifest[] = $entry;
    }

    return $manifest;
}

function splitSqlStatements(string $sqlContent): array
{
    $sqlContent = preg_replace('/^\xEF\xBB\xBF/', '', $sqlContent) ?? $sqlContent;
    $sqlContent = preg_replace('~/\*.*?\*/~s', '', $sqlContent) ?? $sqlContent;

    $lines = preg_split('/\R/', $sqlContent) ?: [];
    $cleanLines = [];

    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '') {
            continue;
        }
        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $cleanLines[] = $line;
    }

    $normalized = trim(implode("\n", $cleanLines));
    if ($normalized === '') {
        return [];
    }

    $parts = preg_split('/;\s*(?:\R|$)/', $normalized) ?: [];
    $statements = [];

    foreach ($parts as $part) {
        $statement = trim($part);
        if ($statement !== '') {
            $statements[] = $statement;
        }
    }

    return $statements;
}

function applicationTableCount(PDO $pdo): int
{
    $stmt = $pdo->query("\n        SELECT COUNT(*)\n        FROM information_schema.TABLES\n        WHERE TABLE_SCHEMA = DATABASE()\n          AND TABLE_NAME <> 'migrations'\n    ");

    return (int)($stmt ? $stmt->fetchColumn() : 0);
}

function runSqlFile(PDO $pdo, string $path): void
{
    $sqlContent = file_get_contents($path);
    if ($sqlContent === false) {
        throw new RuntimeException('Unable to read SQL file.');
    }

    $statements = splitSqlStatements($sqlContent);
    if (empty($statements)) {
        throw new RuntimeException('SQL file is empty or contains no executable statements.');
    }

    foreach ($statements as $statement) {
        $result = $pdo->query($statement);
        if ($result instanceof PDOStatement) {
            do {
                $result->fetchAll();
            } while ($result->nextRowset());
            $result->closeCursor();
        }
    }
}

echo bold('InvenBill Pro - Migration Runner') . PHP_EOL;
echo str_repeat('-', 50) . PHP_EOL;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    echo red('Database connection failed: ' . $e->getMessage()) . PHP_EOL;
    exit(1);
}

try {
    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS `migrations` (\n            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            `filename` VARCHAR(255) NOT NULL,\n            `batch` INT UNSIGNED NOT NULL DEFAULT 1,\n            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n            UNIQUE KEY `uq_migration_filename` (`filename`)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");
} catch (Exception $e) {
    // The table may already exist. Keep going.
}

$migrationDir = BASE_PATH . '/database';
$manifest = migrationManifest($migrationDir);
$mode = $argv[1] ?? '--run';
$hasExistingApplicationTables = applicationTableCount($pdo) > 0;

$executed = [];
try {
    $stmt = $pdo->query('SELECT filename FROM migrations');
    $executed = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
} catch (Exception $e) {
    echo yellow('Could not read migrations table yet; continuing with an empty execution cache.') . PHP_EOL;
}
$executedMap = array_fill_keys($executed, true);

if ($mode === '--status') {
    echo PHP_EOL . bold('Migration Status:') . PHP_EOL;
    foreach ($manifest as $entry) {
        $basename = $entry['filename'];
        if (!empty($entry['missing'])) {
            echo '  ' . yellow("! {$basename}") . ' (missing)' . PHP_EOL;
            continue;
        }

        if ($basename === 'schema.sql' && $hasExistingApplicationTables && empty($executedMap[$basename])) {
            echo '  ' . yellow("SKIP {$basename}") . ' (skipped on non-empty database)' . PHP_EOL;
            continue;
        }

        if (!empty($executedMap[$basename])) {
            echo '  ' . green("OK {$basename}") . ' (executed)' . PHP_EOL;
            continue;
        }

        echo '  ' . yellow("PENDING {$basename}") . ' (pending)' . PHP_EOL;
    }

    $pendingCount = 0;
    $executedCount = count($executedMap);
    $availableCount = 0;
    foreach ($manifest as $entry) {
        if (!empty($entry['missing'])) {
            continue;
        }
        $availableCount++;
        if ($entry['filename'] === 'schema.sql' && $hasExistingApplicationTables) {
            continue;
        }
        if (empty($executedMap[$entry['filename']])) {
            $pendingCount++;
        }
    }

    echo PHP_EOL . 'Total: ' . $availableCount . ' | Executed: ' . $executedCount . ' | Pending: ' . $pendingCount . PHP_EOL;
    exit(0);
}

$pending = [];
foreach ($manifest as $entry) {
    if (!empty($entry['missing'])) {
        continue;
    }

    if ($entry['filename'] === 'schema.sql' && $hasExistingApplicationTables) {
        continue;
    }

    if (empty($executedMap[$entry['filename']])) {
        $pending[] = $entry;
    }
}

if (empty($pending)) {
    echo green('All migrations are up to date.') . PHP_EOL;
    exit(0);
}

$currentBatch = 1;
try {
    $maxBatch = $pdo->query('SELECT MAX(batch) FROM migrations')->fetchColumn();
    $currentBatch = ((int)($maxBatch ?? 0)) + 1;
} catch (Exception $e) {
    // Keep default batch number.
}

echo 'Pending: ' . yellow((string)count($pending) . ' migration(s)') . PHP_EOL;
echo 'Batch:   ' . $currentBatch . PHP_EOL . PHP_EOL;

$success = 0;
$failed = 0;

foreach ($pending as $entry) {
    $basename = $entry['filename'];
    $path = $entry['path'];
    echo "  Running: {$basename} ... ";

    try {
        if ($basename === 'schema.sql' && $hasExistingApplicationTables) {
            echo yellow('SKIP (database already contains application tables)') . PHP_EOL;
            continue;
        }

        runSqlFile($pdo, $path);

        $pdo->prepare('INSERT INTO migrations (filename, batch) VALUES (?, ?)')
            ->execute([$basename, $currentBatch]);

        echo green('OK') . PHP_EOL;
        $success++;
    } catch (Exception $e) {
        echo red('FAILED') . PHP_EOL;
        echo '    ' . red('Error: ' . $e->getMessage()) . PHP_EOL;
        $failed++;
        echo PHP_EOL . red('Migration halted. Fix the error and re-run.') . PHP_EOL;
        break;
    }
}

echo PHP_EOL . str_repeat('-', 50) . PHP_EOL;
echo 'Results: ' . green("{$success} succeeded") . ' | ' . ($failed > 0 ? red("{$failed} failed") : "{$failed} failed") . PHP_EOL;
exit($failed > 0 ? 1 : 0);

```

## Fix 3 - Security Leaks (Health, Backups, XSS, CSRF)

### F:\xampp\htdocs\inventory\middleware\AuthMiddleware.php

```php
<?php
/**
 * Auth Middleware
 * 
 * Checks if the user is authenticated for protected pages.
 * Public pages bypass this check.
 * 
 * Extracted from index.php lines 198-205.
 */
class AuthMiddleware implements MiddlewareInterface {
    /** @var string[] Pages that do not require authentication */
    private array $publicPages = [
        'login', 'install', 'signup', 'pricing', 'demo_login'
    ];

    public function handle(Request $request, callable $next): void {
        $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
        if ($uri !== '' && str_contains($uri, '/api/')) {
            // API endpoints enforce auth in their controller/action as needed.
            $next($request);
            return;
        }

        $page = $request->page();

        if ($page === 'health' && $this->isPublicHealthModeEnabled()) {
            $next($request);
            return;
        }

        if (!in_array($page, $this->publicPages, true) && !Session::isLoggedIn()) {
            header("Location: " . APP_URL . "/index.php?page=login");
            exit;
        }

        $next($request);
    }

    /**
     * Public health mode must be explicitly enabled by config or environment.
     * Default is secure/private.
     */
    private function isPublicHealthModeEnabled(): bool {
        $flag = defined('HEALTH_PUBLIC_MODE') ? HEALTH_PUBLIC_MODE : getenv('HEALTH_PUBLIC_MODE');
        if ($flag === false || $flag === null || $flag === '') {
            $flag = getenv('HEALTH_ALLOW_PUBLIC');
        }

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }
}




```

### F:\xampp\htdocs\inventory\controllers\HealthController.php

```php
<?php
/**
 * Enhanced Health Check Endpoint â€” Production Monitoring
 * 
 * Provides deep system health visibility for load balancers,
 * monitoring tools (Prometheus, Datadog), and ops teams.
 * 
 * Endpoint: /index.php?page=health
 * Returns: JSON with component-level status
 */
class HealthController extends Controller {

    protected $allowedActions = ['index'];

    public function index() {
        $startTime = microtime(true);
        $publicMode = $this->isPublicHealthModeEnabled();
        $isPrivileged = Session::isLoggedIn() && Session::isSuperAdmin();

        if (!$publicMode && !$isPrivileged) {
            $this->requireSuperAdmin();
            return;
        }

        $payload = $isPrivileged
            ? $this->buildDetailedPayload($startTime)
            : $this->buildPublicPayload($startTime);

        http_response_code($payload['http_status']);
        header('Content-Type: application/json');
        header('Cache-Control: no-store');

        unset($payload['http_status']);
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function buildDetailedPayload(float $startTime): array {
        $checks = $this->collectChecks();
        $summary = $this->evaluateChecks($checks);

        return [
            'http_status'   => $summary['http_status'],
            'status'        => $summary['status'],
            'version'       => $this->appVersion(),
            'environment'   => $this->appEnvironment(),
            'timestamp'     => date('c'),
            'response_ms'   => round((microtime(true) - $startTime) * 1000, 2),
            'uptime_s'      => isset($_SERVER['REQUEST_TIME']) ? time() - (int)$_SERVER['REQUEST_TIME'] : null,
            'checks'        => $checks,
        ];
    }

    private function buildPublicPayload(float $startTime): array {
        $summary = $this->evaluateChecks($this->collectChecks());

        return [
            'http_status' => $summary['http_status'],
            'status'      => $summary['status'],
            'version'     => $this->appVersion(),
            'timestamp'   => date('c'),
            'response_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'uptime_s'    => isset($_SERVER['REQUEST_TIME']) ? time() - (int)$_SERVER['REQUEST_TIME'] : null,
        ];
    }

    private function collectChecks(): array {
        return [
            'database'    => $this->checkDatabase(),
            'cache'       => $this->checkCache(),
            'redis'       => $this->checkRedis(),
            'queue'       => $this->checkQueue(),
            'disk'        => $this->checkDisk(),
            'memory'      => $this->checkMemory(),
            'uploads'     => $this->checkUploads(),
            'logs'        => $this->checkLogs(),
            'php'         => $this->checkPhp(),
        ];
    }

    private function evaluateChecks(array $checks): array {
        $allHealthy = !array_filter($checks, fn($c) => $c['status'] === 'error');
        $hasWarnings = (bool)array_filter($checks, fn($c) => $c['status'] === 'warning');
        $overallStatus = $allHealthy ? ($hasWarnings ? 'degraded' : 'healthy') : 'unhealthy';

        return [
            'status'      => $overallStatus,
            'http_status' => $allHealthy ? 200 : 503,
        ];
    }

    private function isPublicHealthModeEnabled(): bool {
        $flag = defined('HEALTH_PUBLIC_MODE') ? HEALTH_PUBLIC_MODE : getenv('HEALTH_PUBLIC_MODE');
        if ($flag === false || $flag === null || $flag === '') {
            $flag = getenv('HEALTH_ALLOW_PUBLIC');
        }

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }

    private function appEnvironment(): string {
        if (defined('APP_ENV')) {
            return (string)APP_ENV;
        }
        $env = getenv('APP_ENV');
        return $env !== false && $env !== '' ? $env : 'production';
    }

    private function appVersion(): string {
        if (defined('APP_VERSION')) {
            return (string)APP_VERSION;
        }
        return '2.0.0';
    }

    private function checkDatabase(): array {
        try {
            $start = microtime(true);
            $db = Database::getInstance();
            $result = $db->query("SELECT 1 AS ok")->fetch();
            $latency = round((microtime(true) - $start) * 1000, 2);
            
            // Check connection pool info
            $threads = $db->query("SHOW STATUS LIKE 'Threads_connected'")->fetch();
            $maxConn = $db->query("SHOW VARIABLES LIKE 'max_connections'")->fetch();
            
            $connUsage = ($threads && $maxConn) 
                ? round(($threads['Value'] / $maxConn['Value']) * 100, 1) 
                : null;

            return [
                'status'        => 'ok',
                'latency_ms'    => $latency,
                'connections'   => $threads ? (int)$threads['Value'] : null,
                'max_connections' => $maxConn ? (int)$maxConn['Value'] : null,
                'usage_pct'     => $connUsage,
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database unreachable: ' . $e->getMessage()];
        }
    }

    private function checkCache(): array {
        try {
            $cacheDir = defined('CACHE_PATH') ? CACHE_PATH : BASE_PATH . '/cache';
            $writable = is_writable($cacheDir);
            
            // Check Redis if available
            $redisStatus = 'not_configured';
            if (extension_loaded('redis') && getenv('REDIS_HOST')) {
                try {
                    $r = new \Redis();
                    $r->connect(getenv('REDIS_HOST'), (int)(getenv('REDIS_PORT') ?: 6379), 1.0);
                    $r->ping();
                    $redisStatus = 'connected';
                    $r->close();
                } catch (\Exception $e) {
                    $redisStatus = 'error: ' . $e->getMessage();
                }
            }

            return [
                'status'       => $writable ? 'ok' : 'warning',
                'file_cache'   => $writable ? 'writable' : 'not writable',
                'redis'        => $redisStatus,
            ];
        } catch (\Exception $e) {
            return ['status' => 'warning', 'message' => $e->getMessage()];
        }
    }

    private function checkDisk(): array {
        $path = defined('BASE_PATH') ? BASE_PATH : __DIR__;
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);
        
        if ($total === false || $free === false) {
            return ['status' => 'warning', 'message' => 'Unable to read disk space'];
        }

        $usedPct = round((1 - ($free / $total)) * 100, 1);
        $status = $usedPct > 90 ? 'error' : ($usedPct > 80 ? 'warning' : 'ok');

        return [
            'status'    => $status,
            'total_gb'  => round($total / 1073741824, 2),
            'free_gb'   => round($free / 1073741824, 2),
            'used_pct'  => $usedPct,
        ];
    }

    private function checkMemory(): array {
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = ini_get('memory_limit');
        
        // Parse memory limit to bytes
        $limitBytes = $this->parseBytes($limit);
        $usagePct = $limitBytes > 0 ? round(($usage / $limitBytes) * 100, 1) : null;
        $status = ($usagePct !== null && $usagePct > 80) ? 'warning' : 'ok';
        
        return [
            'status'    => $status,
            'current_mb'=> round($usage / 1048576, 2),
            'peak_mb'   => round($peak / 1048576, 2),
            'limit'     => $limit,
            'usage_pct' => $usagePct,
        ];
    }

    private function checkUploads(): array {
        $uploadDir = defined('UPLOAD_PATH') ? UPLOAD_PATH : BASE_PATH . '/uploads';
        $writable = is_writable($uploadDir);
        
        return [
            'status'    => $writable ? 'ok' : 'error',
            'writable'  => $writable,
            'path'      => basename($uploadDir),
        ];
    }

    private function checkLogs(): array {
        $logDir = BASE_PATH . '/logs';
        $writable = is_writable($logDir);
        
        // Count log files and total size
        $files = glob($logDir . '/*.{log,json}', GLOB_BRACE);
        $totalSize = 0;
        foreach ($files ?: [] as $f) {
            $totalSize += filesize($f);
        }
        
        return [
            'status'     => $writable ? 'ok' : 'warning',
            'writable'   => $writable,
            'file_count' => count($files ?: []),
            'total_mb'   => round($totalSize / 1048576, 2),
        ];
    }

    private function checkPhp(): array {
        return [
            'status'     => 'ok',
            'version'    => PHP_VERSION,
            'sapi'       => PHP_SAPI,
            'extensions' => [
                'pdo'     => extension_loaded('pdo'),
                'gd'      => extension_loaded('gd'),
                'curl'    => extension_loaded('curl'),
                'mbstring'=> extension_loaded('mbstring'),
                'redis'   => extension_loaded('redis'),
                'opcache' => extension_loaded('Zend OPcache'),
            ],
            'opcache_enabled' => function_exists('opcache_get_status') ? (bool)@opcache_get_status() : false,
        ];
    }

    private function parseBytes(string $value): int {
        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $num = (int)$value;
        return match($unit) {
            'g' => $num * 1073741824,
            'm' => $num * 1048576,
            'k' => $num * 1024,
            default => $num,
        };
    }

    private function checkRedis(): array {
        if (!defined('REDIS_ENABLED') || !REDIS_ENABLED) {
            return ['status' => 'ok', 'driver' => 'disabled'];
        }
        if (!extension_loaded('redis')) {
            return ['status' => 'warning', 'message' => 'Redis extension not loaded'];
        }

        try {
            $r = new \Redis();
            $connected = $r->connect(
                defined('REDIS_HOST') ? REDIS_HOST : '127.0.0.1',
                defined('REDIS_PORT') ? REDIS_PORT : 6379,
                2.0
            );
            if (!$connected) {
                return ['status' => 'error', 'message' => 'Connection failed'];
            }

            $password = defined('REDIS_PASSWORD') ? REDIS_PASSWORD : null;
            if ($password) $r->auth($password);

            $pong = $r->ping();
            $info = $r->info('memory');
            $r->close();

            return [
                'status'    => 'ok',
                'ping'      => $pong === true || $pong === '+PONG' ? 'PONG' : $pong,
                'memory_mb' => isset($info['used_memory']) ? round($info['used_memory'] / 1048576, 2) : null,
                'driver'    => 'connected',
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Redis: ' . $e->getMessage()];
        }
    }

    private function checkQueue(): array {
        try {
            $db = Database::getInstance();

            // Check if jobs table exists
            $tables = $db->query("SHOW TABLES LIKE 'jobs'")->fetchAll();
            if (empty($tables)) {
                return ['status' => 'ok', 'message' => 'Queue table not created yet'];
            }

            $stats = $db->query(
                "SELECT status, COUNT(*) as cnt FROM `jobs` GROUP BY status"
            )->fetchAll(\PDO::FETCH_KEY_PAIR);

            $pending = (int)($stats['pending'] ?? 0);
            $processing = (int)($stats['processing'] ?? 0);
            $failed = (int)($stats['failed'] ?? 0);
            $completed = (int)($stats['completed'] ?? 0);

            // Check for stuck jobs (processing > 30 min)
            $stuck = $db->query(
                "SELECT COUNT(*) FROM `jobs` WHERE status = 'processing' AND started_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
            )->fetchColumn();

            $status = 'ok';
            if ($failed > 10) $status = 'warning';
            if ((int)$stuck > 0) $status = 'warning';

            return [
                'status'     => $status,
                'pending'    => $pending,
                'processing' => $processing,
                'completed'  => $completed,
                'failed'     => $failed,
                'stuck'      => (int)$stuck,
            ];
        } catch (\Exception $e) {
            return ['status' => 'warning', 'message' => 'Queue check failed: ' . $e->getMessage()];
        }
    }
}

```

### F:\xampp\htdocs\inventory\controllers\BackupController.php

```php
<?php
/**
 * Backup & Restore Controller â€” Multi-Tenant Safe
 * 
 * SECURITY ARCHITECTURE:
 *  - NON-super-admin users can ONLY export their own company's data
 *    via tenant-filtered CSV/SQL export (per-company logical backup).
 *  - SUPER-ADMIN users can perform full database backup/restore
 *    (for platform-level disaster recovery only).
 *  - Restore is restricted to super-admin only (prevents one tenant
 *    from overwriting the entire shared database).
 *  - Backup files are stored in per-company subdirectories to prevent
 *    cross-tenant file access.
 * 
 * MEMORY SAFETY:
 *  - All exports use streaming writes (chunked queries + fwrite)
 *  - No full-table load into memory
 * 
 * @version 2.0 â€” Tenant-safe rewrite
 */
class BackupController extends Controller {

    protected $allowedActions = ['index', 'create', 'download', 'delete', 'restore'];

    private string $backupDir;

    /**
     * Tables that contain per-tenant data (have company_id column).
     * These are exported with WHERE company_id = ? for tenant backups.
     */
    private static $tenantTables = [
        'products', 'categories', 'brands', 'units',
        'customers', 'suppliers',
        'sales', 'sale_items', 'sale_returns', 'sale_return_items',
        'purchases', 'purchase_items',
        'payments', 'quotations', 'quotation_items',
        'stock_history', 'activity_log',
        'users', 'company_settings',
    ];

    /**
     * System-level tables that should NOT be included in tenant exports.
     * These are only exported in super-admin full backups.
     */
    private static $systemOnlyTables = [
        'companies', 'roles', 'permissions', 'role_permissions', 'migrations',
    ];

    public function __construct() {
        $this->backupDir = $this->resolveBackupRoot();
        $this->ensureDir($this->backupDir);
        $this->ensureDir($this->getFullBackupDir());
    }

    // =========================================================
    // INDEX â€” Show backup page
    // =========================================================

    public function index() {
        $this->requirePermission('backup.manage');

        $companyId = Tenant::require();
        $isSuperAdmin = Session::isSuperAdmin();
        $backups = $this->getBackupList($companyId, $isSuperAdmin);

        // Get tenant data stats
        $db = Database::getInstance();
        $stats = $this->getTenantStats($db, $companyId);

        $this->view('backup.index', [
            'pageTitle'    => 'Backup & Restore',
            'backups'      => $backups,
            'tableCount'   => $stats['tableCount'],
            'dbSize'       => $stats['estimatedSize'],
            'dbName'       => $stats['label'],
            'isSuperAdmin' => $isSuperAdmin,
            'companyId'    => $companyId,
        ]);
    }

    // =========================================================
    // CREATE â€” Generate tenant-scoped or full backup
    // =========================================================

    public function create() {
        $this->requirePermission('backup.manage');

        if (!$this->isPost()) {
            $this->redirect('index.php?page=backup');
            return;
        }

        $this->validateCSRF();

        $companyId = Tenant::require();
        $isSuperAdmin = Session::isSuperAdmin();
        $backupType = $this->post('backup_type', 'tenant'); // 'tenant' or 'full'

        // SECURITY: Only super-admin can create full backups
        if ($backupType === 'full') {
            $this->requireSuperAdmin();
        }

        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            $timestamp = date('Y-m-d_H-i-s');

            if ($backupType === 'full' && $isSuperAdmin) {
                $filepath = $this->getFullBackupDir() . '/full_backup_' . $timestamp . '.sql';
                $this->ensureDir(dirname($filepath));
                $this->createFullBackup($pdo, $filepath);
                $displayName = basename($filepath);
            } else {
                $filepath = $this->getTenantBackupDir($companyId) . '/company_' . $companyId . '_backup_' . $timestamp . '.sql';
                $this->ensureDir(dirname($filepath));
                $this->createTenantBackup($pdo, $companyId, $filepath);
                $displayName = basename($filepath);
            }

            $this->logActivity('Created backup: ' . $displayName, 'backup', null, $backupType);
            $this->setFlash('success', 'Backup created successfully! File: ' . $displayName);

        } catch (Exception $e) {
            if (isset($filepath) && file_exists($filepath)) {
                @unlink($filepath);
            }
            error_log('[Backup] Create failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to create backup. Please try again.');
        }

        $this->redirect('index.php?page=backup');
    }

    // =========================================================
    // DOWNLOAD â€” Serve backup file (tenant-isolated)
    // =========================================================

    public function download() {
        $this->requirePermission('backup.manage');

        $file = $this->get('file');
        if (!$file) {
            $this->setFlash('error', 'No file specified.');
            $this->redirect('index.php?page=backup');
            return;
        }

        // Sanitize filename â€” prevent directory traversal
        $file = basename($file);
        $filepath = $this->resolveFilePath($file, Tenant::require(), Session::isSuperAdmin());

        if (!$filepath || !file_exists($filepath)) {
            $this->setFlash('error', 'Backup file not found or access denied.');
            $this->redirect('index.php?page=backup');
            return;
        }

        // Force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        ob_clean();
        flush();
        readfile($filepath);
        exit;
    }

    // =========================================================
    // DELETE â€” Remove backup file (tenant-isolated)
    // =========================================================

    public function delete() {
        $this->requirePermission('backup.manage');

        if (!$this->isPost()) {
            $this->redirect('index.php?page=backup');
            return;
        }

        $this->validateCSRF();

        $file = $this->post('file');
        if (!$file) {
            $this->setFlash('error', 'No file specified.');
            $this->redirect('index.php?page=backup');
            return;
        }

        $file = basename($file);
        $filepath = $this->resolveFilePath($file, Tenant::require(), Session::isSuperAdmin());

        if (!$filepath || !file_exists($filepath)) {
            $this->setFlash('error', 'Backup file not found or access denied.');
            $this->redirect('index.php?page=backup');
            return;
        }

        if (unlink($filepath)) {
            $this->logActivity('Deleted backup: ' . $file, 'backup', null, $file);
            $this->setFlash('success', 'Backup file deleted successfully.');
        } else {
            $this->setFlash('error', 'Failed to delete backup file.');
        }

        $this->redirect('index.php?page=backup');
    }

    // =========================================================
    // RESTORE â€” Super-admin only (full DB restore)
    // =========================================================

    public function restore() {
        $this->requirePermission('backup.manage');

        // SECURITY: Restore is super-admin ONLY â€” it affects all tenants
        $this->requireSuperAdmin();

        if (!$this->isPost()) {
            $this->redirect('index.php?page=backup');
            return;
        }

        $this->validateCSRF();

        try {
            $source = $this->post('restore_source'); // 'upload' or 'existing'
            $sqlContent = '';

            if ($source === 'existing') {
                $file = basename($this->post('backup_file'));
                // Only allow restoring from known full backup directories
                $filepath = $this->resolveFullBackupPath($file);

                if (!$filepath || !file_exists($filepath)) {
                    throw new Exception("Backup file not found in full backup directory.");
                }

                $sqlContent = file_get_contents($filepath);
            } else {
                if (empty($_FILES['backup_file']['tmp_name']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Please upload a valid SQL backup file.");
                }

                $uploadedFile = $_FILES['backup_file'];
                $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

                if ($ext !== 'sql') {
                    throw new Exception("Only .sql files are allowed.");
                }

                // Max 50MB
                if ($uploadedFile['size'] > 50 * 1024 * 1024) {
                    throw new Exception("File too large. Maximum size is 50MB.");
                }

                $sqlContent = file_get_contents($uploadedFile['tmp_name']);
            }

            if (empty(trim($sqlContent))) {
                throw new Exception("The backup file is empty.");
            }

            $db = Database::getInstance();
            $pdo = $db->getConnection();

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            }

            // SECURITY: Execute SQL safely â€” block dangerous patterns, run statement-by-statement
            $executed = $this->executeSafeRestore($pdo, $sqlContent);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            $this->logActivity('Restored full database from backup', 'backup', null, $source === 'existing' ? $file : ($_FILES['backup_file']['name'] ?? 'uploaded file'));
            $this->setFlash('success', 'Database restored successfully! You may need to re-login.');

        } catch (Exception $e) {
            try {
                $pdo = Database::getInstance()->getConnection();
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Exception $ex) {}

            error_log('[Backup] Restore failed: ' . $e->getMessage());
            $this->setFlash('error', 'Restore failed: ' . $e->getMessage());
        }

        $this->redirect('index.php?page=backup');
    }

    // =========================================================
    // PRIVATE: Safe SQL Restore (Statement-by-Statement with Blocklist)
    // =========================================================

    /**
     * Execute SQL restore safely by scanning for dangerous patterns
     * and running statements one at a time.
     *
     * SECURITY: Blocks GRANT, REVOKE, DROP DATABASE, CREATE USER,
     * INTO OUTFILE/DUMPFILE, LOAD_FILE, and shell-related commands.
     *
     * @param  PDO    $pdo        Database connection
     * @param  string $sqlContent Raw SQL content from backup file
     * @return int    Number of statements executed
     * @throws \RuntimeException if prohibited SQL is detected
     */
    private function executeSafeRestore(\PDO $pdo, string $sqlContent): int {
        // Blocklist: patterns that should NEVER appear in a legitimate backup
        $blocked = [
            '/\bGRANT\b/i',
            '/\bREVOKE\b/i',
            '/\bINTO\s+OUTFILE\b/i',
            '/\bINTO\s+DUMPFILE\b/i',
            '/\bLOAD_FILE\s*\(/i',
            '/\bDROP\s+DATABASE\b/i',
            '/\bCREATE\s+USER\b/i',
            '/\bALTER\s+USER\b/i',
            '/\bSET\s+PASSWORD\b/i',
            '/\bSYSTEM\s*\(/i',
            '/\bSHELL\b/i',
        ];

        foreach ($blocked as $pattern) {
            if (preg_match($pattern, $sqlContent)) {
                Helper::securityLog('RESTORE_BLOCKED', 'Prohibited SQL pattern detected: ' . $pattern);
                throw new \RuntimeException('Restore blocked: SQL file contains prohibited statements.');
            }
        }

        // Split by semicolons followed by newlines (preserves multi-line CREATE TABLEs)
        $statements = preg_split('/;\s*\n/', $sqlContent);
        $executed = 0;

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (empty($stmt)) continue;

            // Skip pure comment lines
            if (str_starts_with($stmt, '--') || str_starts_with($stmt, '/*')) continue;

            $pdo->exec($stmt);
            $executed++;
        }

        return $executed;
    }

    // =========================================================
    // PRIVATE: Tenant-Scoped Backup (Logical Export)
    // =========================================================

    /**
     * Create a per-company backup containing ONLY the current tenant's data.
     * Uses prepared statements for company_id filtering and streams output.
     *
     * @param PDO    $pdo       Database connection
     * @param int    $companyId Company to export
     * @param string $filepath  Output file path
     */
    private function createTenantBackup($pdo, $companyId, $filepath) {
        $fp = fopen($filepath, 'w');
        if ($fp === false) {
            throw new Exception("Failed to open backup file for writing.");
        }

        try {
            // Header
            $companyName = Tenant::company()['name'] ?? 'Unknown';
            fwrite($fp, "-- ================================================\n");
            fwrite($fp, "-- InvenBill Pro â€” Tenant Backup\n");
            fwrite($fp, "-- Company: " . $companyName . " (ID: {$companyId})\n");
            fwrite($fp, "-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- Generated by: InvenBill Pro v" . APP_VERSION . "\n");
            fwrite($fp, "-- Type: Per-Company Logical Export\n");
            fwrite($fp, "-- ================================================\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

            // Get actual tables in the database
            $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach (self::$tenantTables as $table) {
                // Skip tables that don't exist (forward compatibility)
                if (!in_array($table, $existingTables, true)) {
                    continue;
                }

                // Check if table has company_id column
                $hasCompanyId = $this->tableHasColumn($pdo, $table, 'company_id');

                if (!$hasCompanyId) {
                    // Table exists but has no company_id â€” skip (shouldn't happen for tenant tables)
                    fwrite($fp, "-- Skipped `{$table}` (no company_id column)\n\n");
                    continue;
                }

                fwrite($fp, "-- -------------------------------------------\n");
                fwrite($fp, "-- Table: `{$table}` (company_id = {$companyId})\n");
                fwrite($fp, "-- -------------------------------------------\n\n");

                // Count rows for this tenant (prepared statement)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE company_id = ?");
                $stmt->execute([$companyId]);
                $totalRows = (int)$stmt->fetchColumn();

                if ($totalRows === 0) {
                    fwrite($fp, "-- (no data)\n\n");
                    continue;
                }

                // Get column names
                $colStmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE company_id = ? LIMIT 1");
                $colStmt->execute([$companyId]);
                $firstRow = $colStmt->fetch(PDO::FETCH_ASSOC);
                $columns = array_keys($firstRow);
                $columnList = implode('`, `', $columns);

                // Stream data in chunks of 200 rows
                $chunkSize = 200;
                $offset = 0;

                while ($offset < $totalRows) {
                    $dataStmt = $pdo->prepare(
                        "SELECT * FROM `{$table}` WHERE company_id = ? ORDER BY id LIMIT ? OFFSET ?"
                    );
                    $dataStmt->execute([$companyId, $chunkSize, $offset]);
                    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($rows)) break;

                    fwrite($fp, "INSERT INTO `{$table}` (`{$columnList}`) VALUES\n");
                    $values = [];
                    foreach ($rows as $row) {
                        $rowValues = [];
                        foreach ($row as $value) {
                            $rowValues[] = ($value === null) ? "NULL" : $pdo->quote($value);
                        }
                        $values[] = "(" . implode(", ", $rowValues) . ")";
                    }
                    fwrite($fp, implode(",\n", $values) . ";\n\n");

                    $offset += $chunkSize;
                    unset($rows, $values, $dataStmt);
                }
            }

            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($fp, "\n-- End of tenant backup (Company ID: {$companyId})\n");
            fclose($fp);

        } catch (Exception $e) {
            if (is_resource($fp)) fclose($fp);
            if (file_exists($filepath)) @unlink($filepath);
            throw $e;
        }
    }

    // =========================================================
    // PRIVATE: Full Database Backup (Super-Admin Only)
    // =========================================================

    /**
     * Create a full database backup (all tables, all tenants).
     * Only callable by super-admin.
     */
    private function createFullBackup($pdo, $filepath) {
        $fp = fopen($filepath, 'w');
        if ($fp === false) {
            throw new Exception("Failed to open backup file for writing.");
        }

        try {
            $dbConfig = require CONFIG_PATH . '/database.php';

            fwrite($fp, "-- ================================================\n");
            fwrite($fp, "-- InvenBill Pro â€” FULL Database Backup\n");
            fwrite($fp, "-- Database: " . $dbConfig['database'] . "\n");
            fwrite($fp, "-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- Generated by: InvenBill Pro v" . APP_VERSION . "\n");
            fwrite($fp, "-- Type: Full Platform Backup (Super Admin)\n");
            fwrite($fp, "-- ================================================\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($fp, "SET AUTOCOMMIT = 0;\n");
            fwrite($fp, "START TRANSACTION;\n\n");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                fwrite($fp, "-- -------------------------------------------\n");
                fwrite($fp, "-- Table: `{$table}`\n");
                fwrite($fp, "-- -------------------------------------------\n");
                fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n\n");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
                fwrite($fp, $createStmt['Create Table'] . ";\n\n");

                $countResult = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                if ($countResult > 0) {
                    $firstRow = $pdo->query("SELECT * FROM `{$table}` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    $columns = array_keys($firstRow);
                    $columnList = implode('`, `', $columns);

                    $chunkSize = 100;
                    $offset = 0;

                    while ($offset < $countResult) {
                        $rows = $pdo->query("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
                        if (empty($rows)) break;

                        fwrite($fp, "INSERT INTO `{$table}` (`{$columnList}`) VALUES\n");
                        $values = [];
                        foreach ($rows as $row) {
                            $rowValues = [];
                            foreach ($row as $value) {
                                $rowValues[] = ($value === null) ? "NULL" : $pdo->quote($value);
                            }
                            $values[] = "(" . implode(", ", $rowValues) . ")";
                        }
                        fwrite($fp, implode(",\n", $values) . ";\n\n");

                        $offset += $chunkSize;
                        unset($rows, $values);
                    }
                }
            }

            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($fp, "COMMIT;\n");
            fwrite($fp, "\n-- End of full backup\n");
            fclose($fp);

        } catch (Exception $e) {
            if (is_resource($fp)) fclose($fp);
            if (file_exists($filepath)) @unlink($filepath);
            throw $e;
        }
    }

    // =========================================================
    // PRIVATE: File Path Helpers (Tenant Isolation)
    // =========================================================

    /**
     * Get per-tenant backup directory.
     * Each company's backups are stored in a separate subdirectory
     * to prevent cross-tenant file access.
     */
    private function getTenantBackupDir($companyId) {
        return $this->backupDir . '/company_' . (int)$companyId;
    }

    /**
     * Get full backup directory (super-admin only).
     */
    private function getFullBackupDir() {
        return $this->backupDir . '/full';
    }

    /**
     * Legacy full-backup directory from older deployments.
     */
    private function getLegacyFullBackupDir() {
        return $this->legacyBackupRoot() . '/full';
    }

    /**
     * Ensure a directory exists.
     */
    private function ensureDir($dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException('Unable to create backup directory: ' . $dir);
            }
        }
    }

    /**
     * Resolve the safest writable backup root.
     *
     * Preference order:
     *  1. Outside the web root
     *  2. System temp directory
     *  3. Legacy uploads path for compatibility
     */
    private function resolveBackupRoot(): string {
        $candidates = [
            dirname(dirname(BASE_PATH)) . '/inventory_backups',
            rtrim(sys_get_temp_dir(), '\\/') . '/invenbill_backups',
            $this->legacyBackupRoot(),
        ];

        foreach ($candidates as $candidate) {
            try {
                if (!is_dir($candidate) && !mkdir($candidate, 0755, true) && !is_dir($candidate)) {
                    continue;
                }
                if (is_writable($candidate)) {
                    return $candidate;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        // Final fallback keeps the app functional even in constrained environments.
        return $this->legacyBackupRoot();
    }

    /**
     * Legacy upload-based backup location kept for restore compatibility.
     */
    private function legacyBackupRoot(): string {
        return BASE_PATH . '/uploads/backups';
    }

    /**
     * Resolve a filename to an absolute path, ensuring the current user
     * has access rights. Returns null if access is denied.
     *
     * @param string $filename  Sanitized basename
     * @param int    $companyId Current tenant
     * @param bool   $isSuperAdmin
     * @return string|null  Absolute path or null
     */
    private function resolveFilePath($filename, $companyId, $isSuperAdmin) {
        // Check tenant backup directory first
        $tenantPath = $this->getTenantBackupDir($companyId) . '/' . $filename;
        if (file_exists($tenantPath)) {
            return $tenantPath;
        }

        // Check legacy root backup directory (pre-migration backups)
        $legacyRoot = $this->legacyBackupRoot();
        $legacyPath = $legacyRoot . '/' . $filename;
        if (file_exists($legacyPath) && $isSuperAdmin) {
            return $legacyPath;
        }

        // Check full backup directory (super-admin only)
        if ($isSuperAdmin) {
            $fullPath = $this->getFullBackupDir() . '/' . $filename;
            if (file_exists($fullPath)) {
                return $fullPath;
            }

            $legacyFullPath = $this->getLegacyFullBackupDir() . '/' . $filename;
            if (file_exists($legacyFullPath)) {
                return $legacyFullPath;
            }
        }

        return null;
    }

    // =========================================================
    // PRIVATE: Backup Listing (Tenant-Scoped)
    // =========================================================

    /**
     * Get the backup list visible to the current user.
     * Regular users see only their company's backups.
     * Super-admins additionally see full platform backups.
     */
    private function getBackupList($companyId, $isSuperAdmin) {
        $backups = [];

        // Always include tenant-specific backups
        $tenantDir = $this->getTenantBackupDir($companyId);
        $this->scanBackupDir($tenantDir, $backups, 'tenant');

        // Super-admin: also include full backups and legacy backups
        if ($isSuperAdmin) {
            $this->scanBackupDir($this->getFullBackupDir(), $backups, 'full');

            // Legacy: root-level backup files (from before tenant isolation)
            $legacyRoot = $this->legacyBackupRoot();
            if ($legacyRoot !== $this->backupDir) {
                $legacyFiles = glob($legacyRoot . '/*.sql');
                if ($legacyFiles) {
                    foreach ($legacyFiles as $file) {
                        $backups[] = [
                            'filename' => basename($file),
                            'size'     => filesize($file),
                            'created'  => date('Y-m-d H:i:s', filemtime($file)),
                            'path'     => $file,
                            'type'     => 'legacy',
                        ];
                    }
                }
            }

            if ($this->getLegacyFullBackupDir() !== $this->getFullBackupDir()) {
                $this->scanBackupDir($this->getLegacyFullBackupDir(), $backups, 'legacy_full');
            }
        }

        // Sort newest first
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });

        return $backups;
    }

    /**
     * Scan a directory for .sql files and append to the results array.
     */
    private function scanBackupDir($dir, &$backups, $type) {
        if (!is_dir($dir)) return;

        $files = glob($dir . '/*.sql');
        if (!$files) return;

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size'     => filesize($file),
                'created'  => date('Y-m-d H:i:s', filemtime($file)),
                'path'     => $file,
                'type'     => $type,
            ];
        }
    }

    /**
     * Resolve full-backup files from current and legacy locations.
     */
    private function resolveFullBackupPath(string $file): ?string {
        $current = $this->getFullBackupDir() . '/' . $file;
        if (file_exists($current)) {
            return $current;
        }

        $legacy = $this->getLegacyFullBackupDir() . '/' . $file;
        if (file_exists($legacy)) {
            return $legacy;
        }

        return null;
    }

    // =========================================================
    // PRIVATE: Utility
    // =========================================================

    /**
     * Check if a table has a specific column.
     * Used to verify company_id existence before filtering.
     */
    private function tableHasColumn($pdo, $table, $column) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get statistics about the current tenant's data.
     */
    private function getTenantStats($db, $companyId) {
        $pdo = $db->getConnection();
        $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $tableCount = 0;
        $totalRows = 0;

        foreach (self::$tenantTables as $table) {
            if (!in_array($table, $existingTables, true)) continue;
            if (!$this->tableHasColumn($pdo, $table, 'company_id')) continue;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE company_id = ?");
            $stmt->execute([$companyId]);
            $count = (int)$stmt->fetchColumn();
            if ($count > 0) {
                $tableCount++;
                $totalRows += $count;
            }
        }

        // Rough size estimate: avg 200 bytes per row
        $estimatedSize = $totalRows * 200;

        $companyName = Tenant::company()['name'] ?? 'Company #' . $companyId;

        return [
            'tableCount'    => $tableCount,
            'totalRows'     => $totalRows,
            'estimatedSize' => $estimatedSize,
            'label'         => $companyName . ' (Tenant Data)',
        ];
    }
}

```

### F:\xampp\htdocs\inventory\uploads\.htaccess

```htaccess
Options -ExecCGI -Indexes
php_flag engine off
AddHandler cgi-script .php .phtml .php3 .php4 .php5 .pl .py .jsp .asp .sh .cgi

<FilesMatch "\.(sql|sql\.gz|dump|bak)$">
    Require all denied
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$">
    Require all denied
    Order allow,deny
    Deny from all
</FilesMatch>

```

### F:\xampp\htdocs\inventory\views\dashboard\index.php

```php
<?php $pageTitle = 'Dashboard'; ?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-success animate-fade-in-up">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($salesAll['total_amount'] ?? 0) ?></div>
            <div class="stat-label">Total Sales</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-primary animate-fade-in-up" style="animation-delay:0.1s">
            <div class="stat-icon"><i class="fas fa-cart-shopping"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($purchaseAll['total_amount'] ?? 0) ?></div>
            <div class="stat-label">Total Purchases</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-warning animate-fade-in-up" style="animation-delay:0.2s">
            <div class="stat-icon"><i class="fas fa-sun"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($salesToday['total_amount'] ?? 0) ?></div>
            <div class="stat-label">Today's Sales</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-info animate-fade-in-up" style="animation-delay:0.3s">
            <div class="stat-icon"><i class="fas fa-boxes-stacked"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($stockValue['total_value'] ?? 0) ?></div>
            <div class="stat-label">Stock Value</div>
        </div>
    </div>
</div>

<!-- Second Row - More Stats -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-danger animate-fade-in-up" style="animation-delay:0.1s">
            <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($customerDues ?? 0) ?></div>
            <div class="stat-label">Customer Dues</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-warning animate-fade-in-up" style="animation-delay:0.15s">
            <div class="stat-icon"><i class="fas fa-truck-clock"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($supplierDues ?? 0) ?></div>
            <div class="stat-label">Supplier Dues</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-success animate-fade-in-up" style="animation-delay:0.2s">
            <div class="stat-icon"><i class="fas fa-calendar"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($salesMonth['total_amount'] ?? 0) ?></div>
            <div class="stat-label">This Month Sales</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-primary animate-fade-in-up" style="animation-delay:0.25s">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-value"><?= Helper::formatCurrency($purchaseMonth['total_amount'] ?? 0) ?></div>
            <div class="stat-label">This Month Purchase</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-chart-area me-2 text-primary"></i>Monthly Sales vs Purchase (<?= date('Y') ?>)</h6>
            </div>
            <div class="card-body">
                <canvas id="salesPurchaseChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-trophy me-2 text-warning"></i>Top Selling Products</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Product</th><th class="text-end">Qty Sold</th></tr></thead>
                        <tbody>
                        <?php if (!empty($topProducts)): foreach ($topProducts as $tp): ?>
                        <tr>
                            <td><?= Helper::escape($tp['name']) ?></td>
                            <td class="text-end fw-bold"><?= Helper::formatQty($tp['total_qty']) ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="2" class="text-center text-muted py-3">No sales data</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sales & Low Stock -->
<div class="row g-3">
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-receipt me-2 text-success"></i>Recent Sales</h6>
                <a href="<?= APP_URL ?>/index.php?page=sales" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if (!empty($recentSales)): foreach ($recentSales as $s): ?>
                        <tr>
                            <td><a href="<?= APP_URL ?>/index.php?page=sales&action=view_sale&id=<?= $s['id'] ?>"><?= Helper::escape($s['invoice_number']) ?></a></td>
                            <td><?= Helper::escape($s['customer_name']) ?></td>
                            <td><?= Helper::formatDate($s['sale_date']) ?></td>
                            <td class="text-end fw-bold"><?= Helper::formatCurrency($s['grand_total']) ?></td>
                            <td><?= Helper::paymentBadge($s['payment_status']) ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">No recent sales</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Low Stock Alert</h6>
                <a href="<?= APP_URL ?>/index.php?page=reports&action=stock" class="btn btn-sm btn-outline-danger">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Product</th><th class="text-end">Stock</th><th>Unit</th></tr></thead>
                        <tbody>
                        <?php if (!empty($lowStockProducts)): foreach ($lowStockProducts as $lp): ?>
                        <tr>
                            <td><?= Helper::escape($lp['name']) ?></td>
                            <td class="text-end"><span class="badge bg-danger"><?= Helper::formatQty($lp['current_stock']) ?></span></td>
                            <td><?= Helper::escape($lp['unit_name'] ?? 'pcs') ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="3" class="text-center text-muted py-3"><i class="fas fa-check-circle text-success me-1"></i>All stock levels OK</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AI Business Insights -->
<div class="row g-3 mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-brain me-2" style="color:#f6c23e;"></i>AI Business Insights</h6>
                <a href="<?= APP_URL ?>/index.php?page=insights" class="btn btn-sm btn-outline-info">View All</a>
            </div>
            <div class="card-body" id="insightsContainer">
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                    <span class="text-muted ms-2">Analyzing your data...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="<?= $cspNonce ?>">
// Load insights asynchronously
document.addEventListener('DOMContentLoaded', function() {
    const allowedInsightColors = new Set(['primary', 'success', 'warning', 'danger', 'info', 'secondary']);

    function clearElement(element) {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    function getSafeColor(color) {
        return allowedInsightColors.has(color) ? color : 'info';
    }

    function isSafeInsightAction(action) {
        if (typeof action !== 'string') {
            return false;
        }

        const trimmed = action.trim();
        if (!trimmed || /^(javascript|data|vbscript):/i.test(trimmed)) {
            return false;
        }

        try {
            const url = new URL(trimmed, window.location.origin);
            return url.origin === window.location.origin && (url.protocol === 'http:' || url.protocol === 'https:');
        } catch (e) {
            return false;
        }
    }

    function renderEmptyState(container, message, iconClass, iconColorClass) {
        clearElement(container);

        const paragraph = document.createElement('p');
        paragraph.className = 'text-muted text-center mb-0';

        if (iconClass) {
            const icon = document.createElement('i');
            icon.className = iconClass + (iconColorClass ? ' ' + iconColorClass : '') + ' me-1';
            paragraph.appendChild(icon);
        }

        paragraph.appendChild(document.createTextNode(message));
        container.appendChild(paragraph);
    }

    function renderInsightCard(insight) {
        const col = document.createElement('div');
        col.className = 'col-md-4';

        const card = document.createElement('div');
        card.className = 'p-2 rounded';
        card.style.background = 'rgba(255,255,255,0.03)';
        card.style.border = '1px solid rgba(255,255,255,0.05)';

        const header = document.createElement('div');
        header.className = 'd-flex align-items-center mb-1';

        const icon = document.createElement('span');
        icon.style.fontSize = '1.2rem';
        icon.textContent = insight.icon || '';
        header.appendChild(icon);

        const title = document.createElement('strong');
        title.className = 'ms-2 text-' + getSafeColor(insight.color);
        title.style.fontSize = '0.85rem';
        title.textContent = insight.title || '';
        header.appendChild(title);

        if (insight.priority === 'high') {
            const badge = document.createElement('span');
            badge.className = 'badge bg-danger ms-1';
            badge.style.fontSize = '0.6rem';
            badge.textContent = 'URGENT';
            header.appendChild(badge);
        }

        const message = document.createElement('p');
        message.style.fontSize = '0.8rem';
        message.style.color = '#b7b9cc';
        message.style.marginBottom = '0.25rem';
        message.textContent = insight.message || '';

        const footer = document.createElement('div');
        footer.className = 'd-flex justify-content-between align-items-center';

        const value = document.createElement('span');
        value.className = 'fw-bold text-' + getSafeColor(insight.color);
        value.textContent = insight.value || '';
        footer.appendChild(value);

        if (isSafeInsightAction(insight.action)) {
            const link = document.createElement('a');
            link.className = 'text-muted';
            link.style.fontSize = '0.75rem';
            link.href = insight.action.trim();
            link.setAttribute('aria-label', 'View insight details');

            const arrow = document.createElement('i');
            arrow.className = 'fas fa-arrow-right';
            link.appendChild(arrow);

            footer.appendChild(link);
        }

        card.appendChild(header);
        card.appendChild(message);
        card.appendChild(footer);
        col.appendChild(card);
        return col;
    }

    fetch('<?= APP_URL ?>/index.php?page=insights&action=get_insights', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('insightsContainer');
        if (!data.success || !data.insights || data.insights.length === 0) {
            renderEmptyState(container, 'Everything looks good! No urgent insights.', 'fas fa-check-circle', 'text-success');
            return;
        }

        const top3 = data.insights.slice(0, 3);

        clearElement(container);
        const row = document.createElement('div');
        row.className = 'row g-2';
        top3.forEach((insight) => {
            row.appendChild(renderInsightCard(insight));
        });
        container.appendChild(row);
    })
    .catch(() => {
        const container = document.getElementById('insightsContainer');
        if (container) {
            renderEmptyState(container, 'Unable to load insights.', null, null);
        }
    });
});
</script>

<?php $inlineScript = "
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('salesPurchaseChart');
    if (!ctx) return;

    const initChart = function () {
        if (typeof Chart === 'undefined') {
            setTimeout(initChart, 60);
            return;
        }

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
        const textColor = isDark ? '#a8adc0' : '#858796';
        const currencySymbol = " . json_encode(Helper::normalizeCurrencySymbol($company['currency_symbol'] ?? '₹')) . ";

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                datasets: [{
                    label: 'Sales',
                    data: {$salesChartData},
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28,200,138,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#1cc88a',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                },{
                    label: 'Purchase',
                    data: {$purchaseChartData},
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78,115,223,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4e73df',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { labels: { color: textColor, usePointStyle: true, padding: 20 } }
                },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor } },
                    y: {
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            callback: function (value) {
                                return currencySymbol + ' ' + Number(value).toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    };

    initChart();
});
"; ?>


```

### F:\xampp\htdocs\inventory\assets\js\app.js

```js
/**
 * InvenBill Pro - Main Application JavaScript
 * 
 * Handles sidebar, theme toggle, common interactions,
 * and utility functions used across all pages.
 */

// ============================================================
// SIDEBAR FUNCTIONALITY
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const topNavbar = document.getElementById('topNavbar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // Sidebar toggle
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            if (window.innerWidth >= 992) {
                // Desktop: collapse sidebar
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('sidebar-collapsed');
                topNavbar.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            } else {
                // Mobile: show/hide sidebar
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            }
        });
    }

    // Close sidebar on overlay click (mobile)
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // Restore sidebar state
    if (window.innerWidth >= 992 && localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('sidebar-collapsed');
        topNavbar.classList.add('sidebar-collapsed');
    }

    // ============================================================
    // THEME TOGGLE (Dark/Light Mode)
    // ============================================================
    const themeSwitch = document.getElementById('themeSwitch');
    if (themeSwitch) {
        themeSwitch.addEventListener('change', function () {
            const mode = this.checked ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', mode);

            const csrfToken = getCsrfToken();
            const payload = new URLSearchParams();
            payload.set('theme_mode', mode);
            if (csrfToken) {
                payload.set('_csrf_token', csrfToken);
            }

            // Save to server
            fetch(APP_URL + '/index.php?page=profile&action=updateTheme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                },
                body: payload.toString(),
                credentials: 'same-origin'
            });

            localStorage.setItem('theme', mode);
        });

        // Restore theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
            themeSwitch.checked = savedTheme === 'dark';
        }
    }

    // ============================================================
    // FULLSCREEN TOGGLE
    // ============================================================
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function () {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
                this.querySelector('i').classList.replace('fa-expand', 'fa-compress');
            } else {
                document.exitFullscreen();
                this.querySelector('i').classList.replace('fa-compress', 'fa-expand');
            }
        });
    }

    // ============================================================
    // AUTO-DISMISS FLASH MESSAGES
    // ============================================================
    const flashContainer = document.getElementById('flashContainer');
    if (flashContainer) {
        setTimeout(() => {
            flashContainer.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s, transform 0.5s';
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100%)';
                setTimeout(() => alert.remove(), 500);
            });
        }, 4000);
    }

    // ============================================================
    // CONFIRM DIALOGS (SweetAlert2 with native fallback)
    // ============================================================
    // Confirm for anchor tags (GET)
    document.querySelectorAll('a[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            if (typeof Swal !== 'undefined') {
                e.preventDefault();
                Swal.fire({
                    title: 'Confirm',
                    text: msg,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, proceed'
                }).then(result => {
                    if (result.isConfirmed) window.location.href = this.href;
                });
            } else if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // Confirm for POST forms (delete, convert, etc.)
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function (e) {
            const msg = this.dataset.confirm || 'Are you sure?';
            const isConvert = msg.toLowerCase().includes('convert');

            if (typeof Swal !== 'undefined') {
                e.preventDefault();
                Swal.fire({
                    title: isConvert ? 'Convert to Sale?' : 'Are you sure?',
                    text: msg,
                    icon: isConvert ? 'warning' : 'warning',
                    showCancelButton: true,
                    confirmButtonColor: isConvert ? '#198754' : '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: isConvert ? '<i class="fas fa-check me-1"></i> Yes, convert' : 'Yes, proceed',
                    cancelButtonText: 'Cancel',
                    focusCancel: true
                }).then(result => {
                    if (result.isConfirmed) {
                        // Disable button + show spinner to prevent double submit
                        const btn = form.querySelector('button[type="submit"]');
                        if (btn) {
                            btn.disabled = true;
                            const origHTML = btn.innerHTML;
                            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (btn.textContent.trim() ? 'Processing...' : '');
                        }
                        form.submit();
                    }
                });
            } else if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // ============================================================
    // TOOLTIP INITIALIZATION
    // ============================================================
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

    // ============================================================
    // TABLE ROW CLICK (if data-href attribute exists)
    // ============================================================
    document.querySelectorAll('tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (e) {
            if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON' && !e.target.closest('.action-btns')) {
                window.location.href = this.dataset.href;
            }
        });
    });

    // ============================================================
    // PRINT BUTTONS (CSP-safe data attributes)
    // ============================================================
    document.querySelectorAll('[data-print-target]').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-print-target');
            if (targetId) {
                printElement(targetId);
            }
        });
    });

    // ============================================================
    // ENTERPRISE FORM VALIDATION
    // ============================================================
    document.querySelectorAll('form').forEach(form => {
        // Skip forms with data-confirm â€” they have their own handler above
        if (form.hasAttribute('data-confirm')) return;
        // Prevent double submit
        form.addEventListener('submit', function (e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            } else {
                const btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                }
            }
            this.classList.add('was-validated');
        });

        // Block negative numbers on inputs
        form.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', function () {
                if (this.hasAttribute('min') && parseFloat(this.value) < parseFloat(this.getAttribute('min'))) {
                    this.value = this.getAttribute('min');
                }
                // Specifically for financial/quantity bounds if min not set explicitly
                if (this.classList.contains('qty') || this.classList.contains('price') || this.classList.contains('tax') || this.classList.contains('disc')) {
                    if (parseFloat(this.value) < 0) this.value = 0;
                }
            });
        });
    });

    // ============================================================
    // ACCESSIBILITY & MOBILE UI TWEAKS
    // ============================================================
    document.querySelectorAll('.btn-icon, .btn:not(:empty)').forEach(btn => {
        if (!btn.hasAttribute('aria-label') && !btn.hasAttribute('title') && btn.innerText.trim() === '') {
            if (btn.querySelector('.fa-eye')) btn.setAttribute('aria-label', 'View details');
            else if (btn.querySelector('.fa-edit')) btn.setAttribute('aria-label', 'Edit record');
            else if (btn.querySelector('.fa-trash')) btn.setAttribute('aria-label', 'Delete record');
            else if (btn.querySelector('.fa-times') || btn.classList.contains('btn-close')) btn.setAttribute('aria-label', 'Close dialog');
            else if (btn.querySelector('.fa-plus')) btn.setAttribute('aria-label', 'Add item');
        }
        if (!btn.hasAttribute('type') && btn.tagName === 'BUTTON' && !btn.closest('form')) {
            btn.setAttribute('type', 'button');
        }
    });

    // Make main action buttons full-width when on small screens dynamically
    document.querySelectorAll('.card-footer button[type="submit"], .modal-footer button, .btn-primary').forEach(btn => {
        if (!btn.classList.contains('btn-sm') && !btn.classList.contains('btn-icon')) {
            btn.classList.add('btn-mobile-full');
        }
    });

});

// ============================================================
// GLOBAL VARIABLES & CONSTANTS
// ============================================================
const APP_URL = document.querySelector('link[href*="style.css"]')?.href.split('/assets/')[0] || '';

/**
 * Resolve the CSRF token from the injected meta tag or a hidden form field.
 */
function getCsrfToken() {
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken && metaToken.getAttribute('content')) {
        return metaToken.getAttribute('content');
    }

    const inputToken = document.querySelector('input[name="_csrf_token"], input[name="csrf_token"]');
    return inputToken ? inputToken.value : '';
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

/**
 * Format number as currency
 */
function formatCurrency(amount, symbol = 'â‚¹') {
    return symbol + ' ' + parseFloat(amount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Format number
 */
function formatNumber(num, decimals = 2) {
    return parseFloat(num || 0).toFixed(decimals);
}

/**
 * Show loading overlay
 */
function showLoading() {
    document.getElementById('loadingOverlay').classList.add('show');
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const alertClass = type === 'error' ? 'danger' : type;
    const icons = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };

    let container = document.getElementById('flashContainer');
    if (!container) {
        container = document.createElement('div');
        container.className = 'alert-container';
        container.id = 'flashContainer';
        document.body.appendChild(container);
    }

    const alert = document.createElement('div');
    alert.className = `alert alert-${alertClass} alert-dismissible fade show`;
    alert.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'} me-2"></i>${message}
        <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>`;
    container.appendChild(alert);

    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s, transform 0.5s';
        alert.style.opacity = '0';
        alert.style.transform = 'translateX(100%)';
        setTimeout(() => alert.remove(), 500);
    }, 4000);
}

/**
 * Debounce function
 */
function debounce(func, wait = 300) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

/**
 * AJAX helper
 */
function ajaxRequest(url, options = {}) {
    const defaults = {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    };
    return fetch(url, { ...defaults, ...options })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        });
}

/**
 * Print a specific element
 */
function printElement(elementId) {
    const printContent = document.getElementById(elementId);
    if (!printContent) return;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="${APP_URL}/assets/css/style.css" rel="stylesheet">
            <style>
                body { background: #fff !important; color: #333 !important; padding: 0; margin: 0; }
                @media print { body { padding: 0; } }
            </style>
        </head>
        <body>${printContent.innerHTML}</body>
        </html>
    `);
    printWindow.document.close();
    setTimeout(() => { printWindow.print(); }, 500);
}

```

## Fix 4 - Stock + Financial Calculation Bugs

### F:\xampp\htdocs\inventory\services\SaleService.php

```php
<?php
/**
 * Sale Service
 * 
 * Orchestrates the complex business logic for creating and managing sales.
 */
class SaleService {
    private Database $db;
    private SaleRepository $saleRepo;
    private CustomerRepository $customerRepo;
    private StockService $stockService;

    public function __construct(
        Database $db, 
        SaleRepository $saleRepo, 
        CustomerRepository $customerRepo, 
        StockService $stockService
    ) {
        $this->db = $db;
        $this->saleRepo = $saleRepo;
        $this->customerRepo = $customerRepo;
        $this->stockService = $stockService;
    }

    /**
     * Create a sale transaction
     */
    public function createSale(array $data, array $items, int $userId): int {
        // Validation handled by Controller DTO/Validator before reaching here
        if (empty($items)) {
            throw new Exception("Sale must have at least one item.");
        }

        $this->db->beginTransaction();
        try {
            // 1. Calculate totals dynamically logic if needed, but assuming DTO gave calculated inputs
            // Create the sale
            $saleId = $this->saleRepo->insertSale($data);

            // 2. Insert items and adjust stock
            foreach ($items as &$item) {
                // Ensure array shape matches repository expectations
                if (!isset($item['subtotal'])) {
                    $item['subtotal'] = $item['quantity'] * $item['unit_price'];
                }
                if (!isset($item['total'])) {
                    $item['total'] = $item['subtotal'] + ($item['tax_amount'] ?? 0) - ($item['discount_amount'] ?? 0);
                }

                $this->stockService->deduct(
                    $item['product_id'], 
                    $item['quantity'], 
                    'sale', 
                    $userId, 
                    $saleId
                );
            }
            unset($item);

            $this->saleRepo->insertItems($saleId, $items);

            // 3. Update customer balance if there is a due amount
            if ($data['due_amount'] > 0 && $data['customer_id']) {
                $success = $this->customerRepo->updateBalance($data['customer_id'], $data['due_amount']);
                if (!$success) {
                    throw new Exception("Failed to update customer balance.");
                }
            }

            $this->db->commit();

            // 4. Dispatch Async Webhooks & Audit
            WebhookDispatcher::dispatch('sale.created', [
                'sale_id' => $saleId,
                'invoice' => $data['invoice_number'],
                'total'   => $data['grand_total'],
            ]);

            Logger::audit('sale_created', 'sales', $saleId, [
                'total' => $data['grand_total'], 
                'items' => count($items)
            ]);

            return $saleId;

        } catch (Exception $e) {
            $this->db->rollback();
            Logger::error('sale_creation_failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }
}

```

### F:\xampp\htdocs\inventory\models\SalesModel.php

```php
<?php
/**
 * Sales Model â€” Multi-Tenant Aware
 * 
 * Manages sales transactions, items, and related operations.
 * All queries scoped by company_id via Tenant::id().
 */
class SalesModel extends Model {
    protected $table = 'sales';
    /**
     * Cached products table columns for optional HSN compatibility.
     *
     * @var array<string, bool>|null
     */
    private static $productColumnMap = null;

    /**
     * Get all sales with customer info (tenant-scoped)
     */
    public function getAllWithCustomer($search = '', $fromDate = '', $toDate = '', $customerId = '', $status = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["s.deleted_at IS NULL"];

        if (Tenant::id() !== null) {
            $where[] = "s.company_id = ?";
            $params[] = Tenant::id();
        }

        if ($search) {
            $where[] = "(s.invoice_number LIKE ? OR c.name LIKE ?)";
            $t = "%{$search}%";
            $params = array_merge($params, [$t, $t]);
        }
        if ($fromDate) { $where[] = "s.sale_date >= ?"; $params[] = $fromDate; }
        if ($toDate) { $where[] = "s.sale_date <= ?"; $params[] = $toDate; }
        if ($customerId) { $where[] = "s.customer_id = ?"; $params[] = $customerId; }
        if ($status) { $where[] = "s.payment_status = ?"; $params[] = $status; }

        $whereClause = implode(' AND ', $where);

        $total = $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} s LEFT JOIN customers c ON s.customer_id = c.id WHERE {$whereClause}",
            $params
        )->fetchColumn();

        $data = $this->db->query(
            "SELECT s.*, c.name as customer_name
             FROM {$this->table} s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE {$whereClause}
             ORDER BY s.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        )->fetchAll();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage),
        ];
    }

    /**
     * Get sale with all details (tenant-scoped)
     */
    public function getWithDetails($id) {
        $where = ["s.id = ?", "s.deleted_at IS NULL"];
        $params = [$id];
        if (Tenant::id() !== null) {
            $where[] = "s.company_id = ?";
            $params[] = Tenant::id();
        }

        $sale = $this->db->query(
            "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                    c.address as customer_address, c.city as customer_city, c.state as customer_state,
                    c.tax_number as customer_tax, c.tax_number as customer_tax_number,
                    u.full_name as created_by_name
             FROM {$this->table} s
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN users u ON s.created_by = u.id
             WHERE " . implode(' AND ', $where),
            $params
        )->fetch();

        if ($sale) {
            $hsnSelect = $this->productColumnExists('hsn_code')
                ? ", p.hsn_code as hsn_code"
                : ", NULL as hsn_code";
            $sale['items'] = $this->db->query(
                "SELECT si.*, p.name as product_name, p.sku, un.short_name as unit_name{$hsnSelect}
                 FROM sale_items si
                 LEFT JOIN products p ON si.product_id = p.id
                 LEFT JOIN units un ON p.unit_id = un.id
                 WHERE si.sale_id = ?" . (Tenant::id() !== null ? " AND si.company_id = ?" : ""),
                Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
            )->fetchAll();
        }

        return $sale;
    }

    /**
     * Create a complete sale with items (auto-injects company_id)
     */
    public function createSale($saleData, $items, $userId) {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $saleData['created_by'] = $userId;
            $saleId = $this->create($saleData);
            $companyId = Tenant::id() ?? 1;

            $productModel = new ProductModel();
            foreach ($items as $item) {
                $db->query(
                    "INSERT INTO sale_items (company_id, sale_id, product_id, quantity, unit_price, discount, tax_rate, tax_amount, subtotal, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$companyId, $saleId, $item['product_id'], $item['quantity'], $item['unit_price'], $item['discount'], $item['tax_rate'], $item['tax_amount'], $item['subtotal'], $item['total']]
                );

                // Decrease stock (negative quantity)
                $productModel->updateStock($item['product_id'], -$item['quantity'], 'sale', $saleId, $userId, 'Sale #' . $saleData['invoice_number']);
            }

            // Distribute any unapplied advance payments to this new sale
            $paymentModel = new PaymentModel();
            $paymentModel->recalculateCustomerSalesPublic((int)$saleData['customer_id']);

            // Update customer balance
            $customerModel = new CustomerModel();
            $customerModel->recalculateBalance((int)$saleData['customer_id']);

            $db->commit();
            Cache::flushPrefix('c' . (Tenant::id() ?? 0) . '_dash_');
            return $saleId;
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Update an existing sale with full stock and balance reconciliation.
     */
    public function updateSale($id, $saleData, $items, $userId) {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $old = $this->getWithDetails($id);
            if (!$old) throw new Exception('Sale not found.');

            $productModel  = new ProductModel();
            $customerModel = new CustomerModel();
            $invoiceNum    = $old['invoice_number'];
            $companyId     = Tenant::id() ?? 1;

            // 1. Restore stock from old items
            foreach ($old['items'] as $item) {
                $productModel->updateStock(
                    $item['product_id'], +$item['quantity'],
                    'sale_edit_reverse', $id, $userId,
                    'Edit Reversal: Sale #' . $invoiceNum
                );
            }

            // 2. Delete old items
            $db->query("DELETE FROM sale_items WHERE sale_id = ?" . (Tenant::id() !== null ? " AND company_id = ?" : ""),
                Tenant::id() !== null ? [$id, Tenant::id()] : [$id]);

            // 3. Insert new items + deduct new stock
            foreach ($items as $item) {
                $db->query(
                    "INSERT INTO sale_items (company_id, sale_id, product_id, quantity, unit_price, discount, tax_rate, tax_amount, subtotal, total)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$companyId, $id, $item['product_id'], $item['quantity'], $item['unit_price'],
                     $item['discount'], $item['tax_rate'], $item['tax_amount'], $item['subtotal'], $item['total']]
                );
                $productModel->updateStock(
                    $item['product_id'], -$item['quantity'],
                    'sale_edit', $id, $userId,
                    'Edited Sale #' . $invoiceNum
                );
            }

            // 4. Update sale header
            $this->update($id, $saleData);

            // 5. Recalculate payments and customer balance from scratch
            $paymentModel = new PaymentModel();
            $paymentModel->recalculateCustomerSalesPublic((int)$saleData['customer_id']);
            $customerModel->recalculateBalance((int)$saleData['customer_id']);

            if ((int)$old['customer_id'] !== (int)$saleData['customer_id']) {
                $paymentModel->recalculateCustomerSalesPublic((int)$old['customer_id']);
                $customerModel->recalculateBalance((int)$old['customer_id']);
            }

            $db->commit();
            Cache::flushPrefix('c' . (Tenant::id() ?? 0) . '_dash_');
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Delete sale and reverse all its effects
     */
    public function deleteSale($id, $userId) {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $sale = $this->getWithDetails($id);
            if (!$sale) throw new Exception('Sale not found.');

            $productModel = new ProductModel();

            foreach ($sale['items'] as $item) {
                $productModel->updateStock(
                    $item['product_id'], +$item['quantity'],
                    'sale_cancel', $id, $userId,
                    'Sale Cancelled #' . $sale['invoice_number']
                );
            }

            $this->delete($id);

            $paymentModel = new PaymentModel();
            $paymentModel->recalculateCustomerSalesPublic((int)$sale['customer_id']);

            $customerModel = new CustomerModel();
            $customerModel->recalculateBalance((int)$sale['customer_id']);

            $db->commit();
            Cache::flushPrefix('c' . (Tenant::id() ?? 0) . '_dash_');
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Get sale totals (tenant-scoped)
     */
    public function getTotals($period = 'all') {
        $where = ["deleted_at IS NULL"];
        $params = [];

        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }

        if ($period === 'today') {
            $where[] = "sale_date = CURDATE()";
        } elseif ($period === 'month') {
            $where[] = "sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        } elseif ($period === 'year') {
            $where[] = "sale_date >= DATE_FORMAT(CURDATE(), '%Y-01-01')";
        }

        return $this->db->query(
            "SELECT COUNT(*) as total_count, COALESCE(SUM(grand_total), 0) as total_amount, COALESCE(SUM(due_amount), 0) as total_due, COALESCE(SUM(paid_amount), 0) as total_paid
             FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetch();
    }

    /**
     * Get all dashboard totals in a single query (tenant-scoped)
     */
    public function getDashboardTotals() {
        $where = ["deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }

        return $this->db->query(
            "SELECT 
                COALESCE(SUM(grand_total), 0) as all_amount,
                COALESCE(SUM(CASE WHEN sale_date = CURDATE() THEN grand_total ELSE 0 END), 0) as today_amount,
                COALESCE(SUM(CASE WHEN sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN grand_total ELSE 0 END), 0) as month_amount
             FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetch();
    }

    /**
     * Get monthly sales data for chart (tenant-scoped)
     */
    public function getMonthlyData($year = null) {
        $year = (int)($year ?? date('Y'));
        $startDate = "$year-01-01";
        $endDate = "$year-12-31";
        $where = ["sale_date BETWEEN ? AND ?", "deleted_at IS NULL"];
        $params = [$startDate, $endDate];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT MONTH(sale_date) as month, COALESCE(SUM(grand_total), 0) as total
             FROM {$this->table}
             WHERE " . implode(' AND ', $where) . "
             GROUP BY MONTH(sale_date)
             ORDER BY month",
            $params
        )->fetchAll();
    }

    /**
     * Get profit data (tenant-scoped)
     */
    public function getProfitData($fromDate = null, $toDate = null) {
        $where = ["s.deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "s.company_id = ?";
            $params[] = Tenant::id();
        }
        if ($fromDate) { $where[] = "s.sale_date >= ?"; $params[] = $fromDate; }
        if ($toDate) { $where[] = "s.sale_date <= ?"; $params[] = $toDate; }

        return $this->db->query(
            "SELECT
                COALESCE(SUM(t.total_sales), 0) as total_sales,
                COALESCE(SUM(t.total_cost), 0) as total_cost,
                COALESCE(SUM(t.gross_profit), 0) as gross_profit,
                COALESCE(SUM(t.discount_amount), 0) as total_discount,
                COALESCE(SUM(t.gross_profit - t.discount_amount), 0) as net_profit
             FROM (
                SELECT
                    s.id,
                    COALESCE(SUM(si.total), 0) as total_sales,
                    COALESCE(SUM(si.quantity * p.purchase_price), 0) as total_cost,
                    COALESCE(SUM(si.total - (si.quantity * p.purchase_price)), 0) as gross_profit,
                    COALESCE(MAX(s.discount_amount), 0) as discount_amount
                FROM {$this->table} s
                JOIN sale_items si ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY s.id
             ) t",
            $params
        )->fetch();
    }

    /**
     * Get top selling products (tenant-scoped)
     */
    public function getTopProducts($limit = 10) {
        $where = ["s.deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "s.company_id = ?";
            $params[] = Tenant::id();
        }
        $params[] = $limit;
        return $this->db->query(
            "SELECT p.name, p.sku, SUM(si.quantity) as total_qty, SUM(si.total) as total_amount
             FROM sale_items si
             JOIN {$this->table} s ON si.sale_id = s.id
             JOIN products p ON si.product_id = p.id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY si.product_id
             ORDER BY total_qty DESC
             LIMIT ?",
            $params
        )->fetchAll();
    }

    /**
     * Check products table column existence with cached schema lookup.
     */
    private function productColumnExists(string $column): bool {
        if (self::$productColumnMap === null) {
            self::$productColumnMap = [];
            try {
                $rows = Database::getInstance()->query("SHOW COLUMNS FROM products")->fetchAll();
                foreach ($rows as $row) {
                    if (!empty($row['Field'])) {
                        self::$productColumnMap[$row['Field']] = true;
                    }
                }
            } catch (Throwable $e) {
                self::$productColumnMap = [];
            }
        }
        return !empty(self::$productColumnMap[$column]);
    }
}

```

### F:\xampp\htdocs\inventory\tests\Unit\LineItemProcessorTest.php

```php
<?php
/**
 * Unit Tests â€” LineItemProcessor
 */

require_once __DIR__ . '/../BaseTestCase.php';

class LineItemProcessorTest extends BaseTestCase {
    private LineItemProcessor $processor;

    protected function setUp(): void {
        parent::setUp();
        $this->processor = new LineItemProcessor();
    }

    // â”€â”€ parseFromPost Tests â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function testParseValidItems(): void {
        $post = [
            'product_id' => [1, 2, 3],
            'quantity' => [10, 5, 2],
            'unit_price' => [100.00, 200.00, 50.00],
            'item_discount' => [0, 10.00, 0],
            'item_tax_rate' => [18, 18, 0],
        ];

        $items = $this->processor->parseFromPost($post);

        $this->assertCount(3, $items);
        $this->assertEquals(1, $items[0]['product_id']);
        $this->assertEquals(10, $items[0]['quantity']);
        $this->assertEquals(100.00, $items[0]['unit_price']);
        $this->assertEquals(180.00, $items[0]['tax_amount']); // (10*100)*18% = 180
        $this->assertEquals(1000.00, $items[0]['subtotal']);
        $this->assertEquals(1180.00, $items[0]['total']);
    }

    public function testParseSkipsEmptyProductIds(): void {
        $post = [
            'product_id' => [1, '', 3],
            'quantity' => [10, 5, 2],
            'unit_price' => [100.00, 200.00, 50.00],
            'item_discount' => [0, 0, 0],
            'item_tax_rate' => [0, 0, 0],
        ];

        $items = $this->processor->parseFromPost($post);
        $this->assertCount(2, $items);
        $this->assertEquals(1, $items[0]['product_id']);
        $this->assertEquals(3, $items[1]['product_id']);
    }

    public function testParseThrowsOnEmptyItems(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one valid line item');

        $this->processor->parseFromPost(['product_id' => ['', '']]);
    }

    public function testParseThrowsOnNegativeQuantity(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('quantity must be greater than zero');

        $post = [
            'product_id' => [1],
            'quantity' => [-5],
            'unit_price' => [100.00],
            'item_discount' => [0],
            'item_tax_rate' => [0],
        ];
        $this->processor->parseFromPost($post);
    }

    public function testParseThrowsOnDiscountExceedingSubtotal(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('discount cannot exceed subtotal');

        $post = [
            'product_id' => [1],
            'quantity' => [1],
            'unit_price' => [100.00],
            'item_discount' => [200.00],
            'item_tax_rate' => [0],
        ];
        $this->processor->parseFromPost($post);
    }

    public function testParseHandlesDiscountCorrectly(): void {
        $post = [
            'product_id' => [1],
            'quantity' => [2],
            'unit_price' => [100.00],
            'item_discount' => [50.00],
            'item_tax_rate' => [10],
        ];

        $items = $this->processor->parseFromPost($post);
        $this->assertEquals(50.00, $items[0]['discount_amount']);
        $this->assertEquals(150.00, $items[0]['subtotal']); // (2*100)-50 = 150
        $this->assertEquals(15.00, $items[0]['tax_amount']); // 150*10% = 15
        $this->assertEquals(165.00, $items[0]['total']);
    }

    // â”€â”€ calculateTotals Tests â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function testCalculateTotals(): void {
        $items = [
            ['subtotal' => 1000.00, 'tax_amount' => 180.00],
            ['subtotal' => 500.00, 'tax_amount' => 90.00],
            ['subtotal' => 200.00, 'tax_amount' => 0.00],
        ];

        $totals = $this->processor->calculateTotals($items);

        $this->assertEquals(1700.00, $totals['subtotal']);
        $this->assertEquals(270.00, $totals['total_tax']);
        $this->assertEquals(1970.00, $totals['grand_total']);
        $this->assertEquals(3, $totals['item_count']);
    }

    public function testCalculateTotalsEmpty(): void {
        $totals = $this->processor->calculateTotals([]);
        $this->assertEquals(0.00, $totals['subtotal']);
        $this->assertEquals(0.00, $totals['grand_total']);
        $this->assertEquals(0, $totals['item_count']);
    }

    // â”€â”€ reconcile Tests â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function testReconcileIdentifiesAddUpdateRemove(): void {
        $existing = [
            ['id' => 1, 'product_id' => 10, 'quantity' => 5],
            ['id' => 2, 'product_id' => 20, 'quantity' => 3],
            ['id' => 3, 'product_id' => 30, 'quantity' => 1],
        ];

        $new = [
            ['id' => 1, 'product_id' => 10, 'quantity' => 8],  // update
            ['id' => 2, 'product_id' => 20, 'quantity' => 3],  // update (no change)
            ['product_id' => 40, 'quantity' => 2],              // add
        ];

        $result = $this->processor->reconcile($new, $existing);

        $this->assertCount(1, $result['add']);
        $this->assertCount(2, $result['update']);
        $this->assertCount(1, $result['remove']);
        $this->assertEquals(3, $result['remove'][0]['id']); // id=3 removed
    }
}

```

## Fix 5 - Reliability Baseline (test suite path)

### F:\xampp\htdocs\inventory\tests\Integration\.gitkeep

```gitkeep

```

