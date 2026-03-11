<?php
/**
 * CSRF Protection Class
 * 
 * Generates and validates CSRF tokens to prevent
 * Cross-Site Request Forgery attacks.
 */
class CSRF {
    
    /**
     * Generate a new CSRF token
     */
    public static function generateToken() {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('csrf_token');
    }

    /**
     * Get the current token
     */
    public static function getToken() {
        return self::generateToken();
    }

    /**
     * Validate CSRF token (Session persistent for multi-tab/AJAX safety)
     */
    public static function validateToken($token) {
        $stored = Session::get('csrf_token');
        if (empty($token) || empty($stored)) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    /**
     * Rotate CSRF token — force-regenerate after critical mutations.
     * Call after delete, payment, restore operations for defense-in-depth.
     * The old token becomes invalid; new forms/AJAX will use the new token.
     */
    public static function rotateToken() {
        Session::set('csrf_token', bin2hex(random_bytes(32)));
    }

    /**
     * Output hidden input field for forms
     */
    public static function field() {
        $token = self::generateToken();
        $name = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
        return '<input type="hidden" name="' . $name . '" value="' . $token . '">';
    }

    /**
     * Global verifier for index.php
     */
    public static function verifyGlobal() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            $name = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
            $token = $_POST[$name] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!self::validateToken($token)) {
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'CSRF verification failed. Please refresh the page.']);
                    exit;
                }
                http_response_code(403);
                die('CSRF token validation failed. Please go back and try again.');
            }
        }
    }
}
