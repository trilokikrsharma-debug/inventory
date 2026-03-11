<?php
/**
 * Rate Limiter Middleware
 * 
 * Enforces per-IP rate limiting (120 requests/minute).
 * Also configures structured logging level based on environment.
 * 
 * Extracted from index.php lines 168-188.
 */
class RateLimitMiddleware implements MiddlewareInterface {
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct(int $maxRequests = 120, int $windowSeconds = 60) {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(Request $request, callable $next): void {
        $clientIp = $request->ip();
        $key = 'ip:' . $clientIp;

        if (!RateLimiter::attempt($key, $this->maxRequests, $this->windowSeconds)) {
            http_response_code(429);
            RateLimiter::headers($key, $this->maxRequests, $this->windowSeconds);

            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Rate limit exceeded. Please try again later.']);
            } else {
                echo '<!DOCTYPE html><html><head><title>429</title></head>'
                   . '<body style="display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;background:#0f0f1a;color:#e2e8f0;">'
                   . '<div style="text-align:center;"><h1 style="font-size:3rem;">429</h1><p>Too many requests. Please slow down.</p></div>'
                   . '</body></html>';
            }

            Logger::security('Rate limit exceeded', ['ip' => $clientIp, 'page' => $request->page()]);
            exit;
        }

        // Configure log level based on environment
        Logger::setMinLevel(APP_ENV === 'production' ? 'WARNING' : 'DEBUG');

        $next($request);
    }
}
