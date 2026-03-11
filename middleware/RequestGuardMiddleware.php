<?php
/**
 * Request Guard Middleware
 * 
 * Blocks oversized POST requests (20MB limit).
 * Extracted from index.php lines 96-113.
 */
class RequestGuardMiddleware implements MiddlewareInterface {
    private const MAX_POST_BYTES = 20 * 1024 * 1024; // 20MB

    public function handle(Request $request, callable $next): void {
        if ($request->isPost() && $request->contentLength() > self::MAX_POST_BYTES) {
            http_response_code(413);
            if ($request->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Request too large. Maximum 20MB allowed.']);
            } else {
                echo '<!DOCTYPE html><html><head><title>413</title></head>'
                   . '<body style="display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;background:#0f0f1a;color:#e2e8f0;">'
                   . '<div style="text-align:center;"><h1 style="font-size:3rem;">413</h1><p>Request too large (max 20MB)</p></div>'
                   . '</body></html>';
            }
            exit;
        }

        $next($request);
    }
}
