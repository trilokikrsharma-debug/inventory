<?php
/**
 * Tenant Middleware
 * 
 * Resolves the current tenant (company) from the user's session
 * after authentication has been verified.
 * 
 * Extracted from index.php line 210.
 */
class TenantMiddleware implements MiddlewareInterface {
    public function handle(Request $request, callable $next): void {
        // Only resolve tenant for authenticated users
        if (Session::isLoggedIn()) {
            Tenant::resolve();

            if (!Session::isSuperAdmin()) {
                $page = $request->page();
                $sessionUser = Session::get('user') ?? [];
                $sessionCompanyId = (int)($sessionUser['company_id'] ?? 0);
                $resolvedCompanyId = (int)(Tenant::id() ?? 0);

                if ($sessionCompanyId <= 0 || $resolvedCompanyId <= 0 || $sessionCompanyId !== $resolvedCompanyId) {
                    $this->forceTenantLogout('Tenant session could not be verified.');
                    return;
                }

                if (defined('TENANT_HOST_ENFORCEMENT') ? TENANT_HOST_ENFORCEMENT : false) {
                    if (!Tenant::hostMatchesCurrentTenant()) {
                        $this->forceTenantLogout('Please sign in on the correct tenant domain.');
                        return;
                    }
                }
            }
        }

        $next($request);
    }

    private function forceTenantLogout(string $message): void {
        error_log('[TENANT] ' . $message);
        Tenant::reset();
        Session::destroy();

        if (!headers_sent()) {
            header('Location: ' . APP_URL . '/index.php?page=login&tenant_mismatch=1');
        } else {
            echo '<script>window.location.href=' . json_encode(APP_URL . '/index.php?page=login&tenant_mismatch=1') . ';</script>';
        }

        exit;
    }
}
