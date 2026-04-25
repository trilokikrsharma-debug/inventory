<?php
/**
 * Demo Guard Middleware
 * 
 * Restricts only sensitive account/platform mutations for demo companies.
 * Product workflow modules remain interactive so the demo feels real.
 * 
 * Extracted from index.php lines 212-225.
 */
class DemoGuardMiddleware implements MiddlewareInterface {
    public function handle(Request $request, callable $next): void {
        $restrictedPages = [
            'company',
            'platform',
            'saas_billing',
            'saas_plans',
            'promos',
            'referrals',
        ];

        if (
            Session::isLoggedIn()
            && Tenant::isDemo()
            && $request->isPost()
            && in_array($request->page(), $restrictedPages, true)
        ) {
            if ($request->isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Demo mode: Billing, settings, and account-management actions are disabled. Operational workflows remain available for testing.'
                ]);
                exit;
            }

            Session::setFlash('warning', 'Demo mode: Billing, settings, and account-management actions are disabled. Operational workflows remain available for testing.');
            $referer = $request->referer() ?: APP_URL . '/index.php?page=dashboard';
            header("Location: {$referer}");
            exit;
        }

        $next($request);
    }
}
