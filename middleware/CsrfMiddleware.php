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

    public function handle(Request $request, callable $next): void {
        $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
        $isApiRoute = $uri !== '' && str_contains($uri, '/api/');

        if (!$isApiRoute && !in_array($request->page(), $this->excludePages, true)) {
            CSRF::verifyGlobal();
        }

        $next($request);
    }
}



