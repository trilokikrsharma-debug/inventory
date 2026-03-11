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
        }

        $next($request);
    }
}
