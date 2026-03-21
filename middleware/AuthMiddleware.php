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
        '', 'login', 'install', 'signup', 'pricing', 'demo_login', 'home', 'privacy', 'terms', 'refund'
    ];

    public function handle(Request $request, callable $next): void {
        if ($request->isApiPath()) {
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
            header("Location: " . APP_URL . "/login");
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
