<?php
/**
 * Subscription Guard Middleware
 *
 * Blocks tenant users when the company trial or subscription has expired.
 * Recovery routes remain available so the tenant can renew, log out, or
 * complete 2FA without being trapped.
 */
class SubscriptionGuardMiddleware implements MiddlewareInterface {
    /**
     * Routes that must stay reachable even when the tenant is blocked.
     *
     * @var array<string, string[]|null>
     */
    private array $recoveryRoutes = [
        'logout' => null,
        'profile' => ['index', 'edit', 'password', 'updateTheme'],
        'twoFactor' => ['verify', 'verifyPost', 'recovery', 'recoveryPost', 'setup', 'enable', 'disable'],
        'saas_billing' => ['index', 'subscribe', 'plans', 'create_checkout', 'verify_payment', 'validate_promo', 'upgrade', 'cancel'],
    ];

    public function handle(Request $request, callable $next): void {
        if (!Session::isLoggedIn() || Session::isSuperAdmin()) {
            $next($request);
            return;
        }

        $page = $request->page();
        $action = $request->action();
        $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');

        $companyId = Tenant::id();
        $company = Tenant::company();

        if ($companyId === null || !$company) {
            if ($this->isRecoveryRoute($page, $action, $uri)) {
                $next($request);
                return;
            }

            $this->deny($request, 'Tenant context is unavailable. Please contact support.');
        }

        $subscriptionModel = new TenantSubscription();
        $state = $subscriptionModel->syncLifecycleState((int)$companyId, true);

        $status = strtolower(trim((string)($state['status'] ?? ($company['subscription_status'] ?? $company['status'] ?? 'inactive'))));
        $isActive = !empty($state['is_active']);
        $isTrial = !empty($state['is_trial']);
        $isExpired = !empty($state['is_expired']);
        $isManuallyBlocked = !empty($state['is_manually_blocked']);

        if ($isActive || $isTrial) {
            $next($request);
            return;
        }

        if ($this->isRecoveryRoute($page, $action, $uri)) {
            $next($request);
            return;
        }

        $this->deny($request, $this->buildDeniedMessage($status, $isExpired, $isManuallyBlocked));
    }

    private function isRecoveryRoute(string $page, string $action, string $uri = ''): bool {
        if ($page === 'login' || $page === 'pricing' || $page === 'signup' || $page === 'demo_login' || $page === 'home') {
            return true;
        }

        if ($page === 'logout') {
            return true;
        }

        if ($page === 'twoFactor') {
            return in_array($action, (array)($this->recoveryRoutes['twoFactor'] ?? []), true);
        }

        if ($page === 'profile') {
            return in_array($action, (array)($this->recoveryRoutes['profile'] ?? []), true);
        }

        if ($page === 'saas_billing') {
            return in_array($action, (array)($this->recoveryRoutes['saas_billing'] ?? []), true);
        }

        if ($uri !== '' && str_starts_with($uri, '/api/v1/tenant/subscription/')) {
            return true;
        }

        return false;
    }

    private function buildDeniedMessage(string $status, bool $isExpired, bool $isManuallyBlocked): string {
        if ($isManuallyBlocked) {
            return 'Your account is currently suspended. Please contact support or renew your plan.';
        }

        if ($isExpired) {
            return 'Your trial or subscription has expired. Please renew to continue.';
        }

        if ($status === 'trial') {
            return 'Your trial period has expired. Please choose a plan to continue.';
        }

        return 'Your subscription is inactive. Please renew to continue.';
    }

    private function deny(Request $request, string $message): void {
        if ($request->isAjax() || $this->isApiRequest()) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }

        Session::setFlash('error', $message);
        header('Location: ' . APP_URL . '/index.php?page=saas_billing&action=subscribe');
        exit;
    }

    private function isApiRequest(): bool {
        $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
        return $uri !== '' && str_starts_with($uri, '/api/');
    }
}
