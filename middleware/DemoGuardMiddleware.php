<?php
/**
 * Demo Guard Middleware
 * 
 * Blocks all write operations (POST/PUT/DELETE) for demo companies,
 * except logout. Shows a flash message or JSON error for AJAX requests.
 * 
 * Extracted from index.php lines 212-225.
 */
class DemoGuardMiddleware implements MiddlewareInterface {
    public function handle(Request $request, callable $next): void {
        if (
            Session::isLoggedIn()
            && Tenant::isDemo()
            && $request->isPost()
            && $request->page() !== 'logout'
        ) {
            if ($request->isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Demo mode: Write operations are disabled. Sign up for a free account to get started!'
                ]);
                exit;
            }

            Session::setFlash('warning', 'Demo mode: Changes are not saved. Sign up for a free account!');
            $referer = $request->referer() ?: APP_URL . '/index.php?page=dashboard';
            header("Location: {$referer}");
            exit;
        }

        $next($request);
    }
}
