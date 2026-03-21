<?php
/**
 * Session Middleware
 * 
 * Starts the session and enforces session security:
 * - Idle timeout validation
 * - Fingerprint validation (anti-hijacking)
 * - Periodic session ID rotation
 * 
 * Extracted from index.php lines 142-159.
 */
class SessionMiddleware implements MiddlewareInterface {
    public function handle(Request $request, callable $next): void {
        // Use Redis sessions if available (falls back to file-based)
        RedisSessionHandler::register();

        // Start session
        Session::start();

        // 1. Validate idle timeout (destroys session & redirects if expired)
        Session::validateActivity();

        // 2. Validate session fingerprint (prevents session hijacking)
        if (!Session::validateFingerprint()) {
            if ($request->isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'Session validation failed. Please login again.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                header("Location: " . APP_URL . "/login?hijack=1");
            }
            exit;
        }

        // 3. Periodic session ID rotation (every 15 min to reduce fixation window)
        Session::rotateIdIfNeeded();

        $next($request);
    }
}
