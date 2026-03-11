<?php
/**
 * Security Headers Middleware
 * 
 * Sets enterprise-grade security headers on every response.
 * Extracted from index.php lines 78-90.
 */
class SecurityHeadersMiddleware implements MiddlewareInterface {
    public function handle(Request $request, callable $next): void {
        // Standard security headers
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        // Generate per-request CSP nonce for inline scripts
        $GLOBALS['csp_nonce'] = base64_encode(random_bytes(16));
        $nonce = $GLOBALS['csp_nonce'];

        header("Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
            . "img-src 'self' data: blob:; "
            . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
            . "connect-src 'self'; frame-ancestors 'self';");

        $next($request);
    }
}
