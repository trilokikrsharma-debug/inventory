<?php
/**
 * Subscription Guard Middleware
 * 
 * Restricts access to premium modules if subscription_status != active.
 * 
 * SECURITY FIX (API-6): Uses Tenant::company() instead of Session::get('company')
 * which was never set — causing the guard to NEVER activate.
 * Super-admins bypass this guard (they don't have tenant subscriptions).
 */
class SubscriptionGuardMiddleware implements MiddlewareInterface {
    public function handle(Request $request, callable $next): void {
        $page = $request->page();
        
        // Define premium modules that require an active subscription
        $premiumModules = ['reports', 'insights', 'quotations', 'saas_dashboard', 'backup'];
        
        if (in_array($page, $premiumModules, true) && Session::isLoggedIn()) {
            // Super-admins bypass subscription checks (platform-level access)
            if (Session::isSuperAdmin()) {
                $next($request);
                return;
            }

            // SECURITY FIX (API-6): Use Tenant::company() — the actual source of truth.
            // The old code read Session::get('company') which is NEVER set anywhere,
            // meaning the guard never blocked anyone — all users had unlimited premium access.
            $company = Tenant::company();
            
            if ($company) {
                $status = $company['subscription_status'] ?? ($company['status'] ?? 'active');
                
                // Allow 'active' and 'trial' subscriptions
                if (!in_array($status, ['active', 'trial'], true)) {
                    if ($request->isAjax()) {
                        header('Content-Type: application/json');
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Your subscription is inactive. Please upgrade to access this feature.']);
                    } else {
                        Session::setFlash('error', 'Your subscription is inactive. Please upgrade to access this feature.');
                        header('Location: ' . APP_URL . '/index.php?page=pricing');
                    }
                    exit;
                }
            }
        }
        
        $next($request);
    }
}
