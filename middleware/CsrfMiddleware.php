<?php
/**
 * CSRF Middleware
 * 
 * Verifies CSRF tokens on all state-changing requests.
 * Excludes stateless endpoints (API, webhook).
 * 
 * Extracted from index.php lines 190-193.
 */
class CsrfMiddleware implements MiddlewareInterface {
    /** @var string[] Pages excluded from CSRF verification */
    private array $excludePages = ['api', 'webhook'];
    /** @var string[] HTTP methods that mutate state and must be verified */
    private array $mutatingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, callable $next): void {
        $method = $request->method();
        $isApiRoute = $request->isApiPath();

        if (
            in_array($method, $this->mutatingMethods, true)
            && !$isApiRoute
            && !in_array($request->page(), $this->excludePages, true)
        ) {
            CSRF::verifyGlobal();
        }

        $next($request);
    }
}


