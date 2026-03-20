<?php
/**
 * Route-level RBAC guard.
 *
 * Enforces module/action permissions early, after authentication and tenant
 * resolution, but before the controller runs.
 */
class RbacMiddleware implements MiddlewareInterface {
    /**
     * Pages that are always allowed for authenticated sessions.
     */
    private const PUBLIC_PAGES = [
        'login',
        'signup',
        'pricing',
        'demo_login',
        'home',
        'health',
        'twoFactor',
        'logout',
    ];

    /**
     * Billing/recovery surfaces that must remain reachable during subscription issues.
     */
    private const RECOVERY_PAGES = [
        'saas_billing',
        'profile',
        'dashboard',
        'saas_dashboard',
    ];

    /**
     * Page/action permission map for tenant modules.
     *
     * If an action is not listed for a protected page, it is denied.
     */
    private const PERMISSIONS = [
        'products' => [
            'index' => 'products.view',
            'view_product' => 'products.view',
            'search' => 'products.view',
            'create' => 'products.create',
            'edit' => 'products.edit',
            'delete' => 'products.delete',
        ],
        'sales' => [
            'index' => 'sales.view',
            'view_sale' => 'sales.view',
            'create' => 'sales.create',
            'edit' => 'sales.edit',
            'delete' => 'sales.delete',
        ],
        'purchases' => [
            'index' => 'purchases.view',
            'view_purchase' => 'purchases.view',
            'create' => 'purchases.create',
            'edit' => 'purchases.edit',
            'delete' => 'purchases.delete',
        ],
        'payments' => [
            'index' => 'payments.view',
            'view_payment' => 'payments.view',
            'create' => 'payments.create',
            'delete' => 'payments.delete',
        ],
        'customers' => [
            'index' => 'customers.view',
            'view_customer' => 'customers.view',
            'create' => 'customers.create',
            'edit' => 'customers.edit',
            'delete' => 'customers.delete',
            'recalculate_balance' => 'customers.edit',
        ],
        'suppliers' => [
            'index' => 'suppliers.view',
            'view_supplier' => 'suppliers.view',
            'create' => 'suppliers.create',
            'edit' => 'suppliers.edit',
            'delete' => 'suppliers.delete',
        ],
        'users' => [
            'index' => 'users.view',
            'create' => 'users.create',
            'edit' => 'users.edit',
            'resetPassword' => 'users.edit',
            'toggleActive' => 'users.edit',
            'delete' => 'users.delete',
        ],
        'roles' => [
            'index' => 'roles.manage',
            'create' => 'roles.manage',
            'edit' => 'roles.manage',
            'delete' => 'roles.manage',
        ],
        'reports' => [
            'index' => 'reports.view',
            'sales' => 'reports.view',
            'purchases' => 'reports.view',
            'stock' => 'reports.view',
            'profit' => 'reports.view',
            'customer_dues' => 'reports.view',
            'supplier_dues' => 'reports.view',
        ],
        'backup' => [
            'index' => 'backup.manage',
            'create' => 'backup.manage',
            'download' => 'backup.manage',
            'delete' => 'backup.manage',
            'restore' => 'backup.manage',
        ],
        'quotations' => [
            'index' => 'quotations.view',
            'detail' => 'quotations.view',
            'create' => 'quotations.create',
            'updateStatus' => 'quotations.create',
            'convert' => 'quotations.convert',
            'delete' => 'quotations.delete',
        ],
        'sale_returns' => [
            'index' => 'returns.view',
            'detail' => 'returns.view',
            'create' => 'returns.create',
        ],
        'returns' => [
            'index' => 'returns.view',
            'detail' => 'returns.view',
            'create' => 'returns.create',
        ],
        'settings' => [
            'index' => 'settings.manage',
        ],
        'company' => [
            'index' => 'settings.manage',
        ],
        'categories' => [
            'index' => 'catalog.manage',
            'create' => 'catalog.manage',
            'edit' => 'catalog.manage',
            'delete' => 'catalog.manage',
            'fetch' => 'catalog.manage',
        ],
        'brands' => [
            'index' => 'catalog.manage',
            'create' => 'catalog.manage',
            'edit' => 'catalog.manage',
            'delete' => 'catalog.manage',
            'fetch' => 'catalog.manage',
        ],
        'units' => [
            'index' => 'catalog.manage',
            'create' => 'catalog.manage',
            'edit' => 'catalog.manage',
            'delete' => 'catalog.manage',
            'fetch' => 'catalog.manage',
        ],
        'insights' => [
            'index' => 'reports.view',
            'get_insights' => 'reports.view',
        ],
    ];

    public function handle(Request $request, callable $next): void {
        if ($this->shouldSkip($request)) {
            $next($request);
            return;
        }

        if (!Session::isLoggedIn()) {
            $next($request);
            return;
        }

        if (Session::isSuperAdmin()) {
            $next($request);
            return;
        }

        $page = $this->resolvePage();
        $action = $this->resolveAction();

        if ($page === 'health') {
            if ($this->isPublicHealthModeEnabled()) {
                $next($request);
                return;
            }

            $this->deny($request, 'Access to system health is restricted.');
            return;
        }

        if (Session::get('twofa_pending_user_id')) {
            if ($page === 'twoFactor' || $page === 'logout') {
                $next($request);
                return;
            }

            $this->deny($request, 'Please complete two-factor authentication first.');
            return;
        }

        if (in_array($page, self::PUBLIC_PAGES, true)) {
            $next($request);
            return;
        }

        if (in_array($page, self::RECOVERY_PAGES, true)) {
            $next($request);
            return;
        }

        $permission = $this->permissionFor($page, $action);
        if ($permission === null) {
            if ($this->isProtectedPage($page)) {
                $this->deny($request, 'This action is not allowed for your role.');
                return;
            }

            $next($request);
            return;
        }

        if (Session::hasPermission($permission)) {
            $next($request);
            return;
        }

        $this->deny($request, 'You do not have permission to access this page.');
    }

    private function shouldSkip(Request $request): bool {
        $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');

        if ($uri !== '' && str_starts_with($uri, '/api/')) {
            return true;
        }

        // Allow the front controller to route public assets and API-style pages
        // without RBAC interference.
        if (strtolower($request->page()) === 'health' && $this->isPublicHealthModeEnabled()) {
            return true;
        }

        return false;
    }

    private function resolvePage(): string {
        $page = trim((string)($_GET['page'] ?? ''));
        if ($page !== '') {
            return $page;
        }

        $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
        if ($uri !== '') {
            $mapped = $this->mapPrettyRoute($uri);
            if ($mapped !== null) {
                return $mapped['page'];
            }
        }

        return trim((string)($_GET['page'] ?? 'dashboard')) ?: 'dashboard';
    }

    private function resolveAction(): string {
        $action = trim((string)($_GET['action'] ?? ''));
        if ($action !== '') {
            return $action;
        }
        return trim((string)($_GET['action'] ?? 'index')) ?: 'index';
    }

    private function permissionFor(string $page, string $action): ?string {
        if (!isset(self::PERMISSIONS[$page])) {
            return null;
        }

        $actions = self::PERMISSIONS[$page];
        if (isset($actions[$action])) {
            return $actions[$action];
        }

        // Fallback to the page's primary permission for read-oriented actions.
        $readActions = ['index', 'view', 'detail', 'search', 'fetch', 'get_insights'];
        if (in_array($action, $readActions, true)) {
            return $actions['index'] ?? null;
        }

        return null;
    }

    private function isProtectedPage(string $page): bool {
        return isset(self::PERMISSIONS[$page]);
    }

    private function deny(Request $request, string $message): void {
        if ($request->isAjax()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }

        Session::setFlash('error', $message);
        header('Location: ' . APP_URL . '/index.php?page=dashboard');
        exit;
    }

    private function mapPrettyRoute(string $uri): ?array {
        $routes = [
            '/platform/subscribe' => ['page' => 'saas_billing', 'action' => 'subscribe'],
            '/platform/subscriptions' => ['page' => 'platform', 'action' => 'subscriptions'],
            '/platform/payments' => ['page' => 'platform', 'action' => 'payments'],
            '/platform/revenue' => ['page' => 'platform', 'action' => 'revenue'],
            '/platform/dashboard' => ['page' => 'platform', 'action' => 'dashboard'],
            '/platform/plans' => ['page' => 'saas_plans', 'action' => 'index'],
            '/platform/promos' => ['page' => 'promos', 'action' => 'index'],
            '/platform/referrals' => ['page' => 'referrals', 'action' => 'index'],
        ];

        foreach ($routes as $path => $target) {
            if ($uri === $path || str_ends_with($uri, $path)) {
                return $target;
            }
        }

        return null;
    }

    private function isPublicHealthModeEnabled(): bool {
        $flag = defined('HEALTH_PUBLIC_MODE') ? HEALTH_PUBLIC_MODE : getenv('HEALTH_PUBLIC_MODE');
        if ($flag === false || $flag === null || $flag === '') {
            $flag = getenv('HEALTH_ALLOW_PUBLIC');
        }

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }
}
