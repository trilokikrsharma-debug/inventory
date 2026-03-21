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
            // Pending 2FA sessions are intentionally partial and may not have
            // complete tenant context yet (especially super-admin logins).
            if (Session::isTwoFactorPending()) {
                $next($request);
                return;
            }

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
                    // Allow apex-host logins for single-domain deployments.
                    // Strict host matching still applies on tenant subdomains.
                    if (!$this->isApexHostRequest() && !Tenant::hostMatchesCurrentTenant()) {
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
            header('Location: ' . APP_URL . '/login?tenant_mismatch=1');
        } else {
            echo '<script>window.location.href=' . json_encode(APP_URL . '/login?tenant_mismatch=1') . ';</script>';
        }

        exit;
    }

    private function isApexHostRequest(): bool {
        $appHost = strtolower((string)(parse_url((string)APP_URL, PHP_URL_HOST) ?? ''));
        $currentHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));

        if ($currentHost !== '') {
            $parts = explode(':', $currentHost, 2);
            $currentHost = $parts[0];
        }

        if ($appHost === '' || $currentHost === '') {
            return false;
        }

        return hash_equals($appHost, $currentHost);
    }
}
