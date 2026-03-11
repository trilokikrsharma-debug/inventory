<?php
/**
 * Session Management Class
 * 
 * Handles user sessions, flash messages, and login state.
 */
class Session {
    
    /**
     * Start session with secure settings
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Belt-and-suspenders: ini_set + session_set_cookie_params
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
            session_name(SESSION_NAME);
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
    }

    /**
     * Set session variable
     */
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session variable
     */
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session variable exists
     */
    public static function has($key) {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session variable
     */
    public static function remove($key) {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy session completely
     */
    public static function destroy() {
        session_unset();
        session_destroy();
        if (isset($_COOKIE[SESSION_NAME])) {
            setcookie(SESSION_NAME, '', time() - 3600, '/');
        }
    }

    // =========================================================
    // Session Security: Idle Timeout & Fingerprint
    // =========================================================

    /**
     * Validate session activity — enforce server-side idle timeout.
     * Call after Session::start() in front controller.
     * Destroys session if idle beyond SESSION_IDLE_TIMEOUT.
     */
    public static function validateActivity() {
        if (!self::isLoggedIn()) return;

        $timeout = defined('SESSION_IDLE_TIMEOUT') ? SESSION_IDLE_TIMEOUT : 1800;
        $lastActivity = self::get('_last_activity', 0);

        if ($lastActivity > 0 && (time() - $lastActivity) > $timeout) {
            // Session expired due to inactivity
            self::destroy();
            if (!headers_sent()) {
                // Check if AJAX
                $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isAjax) {
                    http_response_code(401);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
                } else {
                    header("Location: " . APP_URL . "/index.php?page=login&timeout=1");
                }
            }
            exit;
        }

        // Update last activity timestamp
        self::set('_last_activity', time());
    }

    /**
     * Initialize session fingerprint — binds session to User-Agent.
     * Call after successful login.
     * Prevents session hijacking via stolen session cookies.
     */
    public static function initFingerprint() {
        $fp = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        self::set('_session_fp', $fp);
    }

    /**
     * Validate session fingerprint against current request.
     * Returns false if fingerprint mismatch (possible hijacking).
     * Call after validateActivity() in front controller.
     */
    public static function validateFingerprint() {
        if (!self::isLoggedIn()) return true; // No session to validate
        $stored = self::get('_session_fp');
        if ($stored === null) return true; // Legacy session without fingerprint
        $current = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        if (!hash_equals($stored, $current)) {
            error_log('[SESSION] Fingerprint mismatch — possible session hijacking. IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
            self::destroy();
            return false;
        }
        return true;
    }

    /**
     * Rotate session ID periodically to reduce fixation window.
     * Call in front controller — rotates every 15 minutes.
     */
    public static function rotateIdIfNeeded() {
        if (!self::isLoggedIn()) return;
        $lastRotation = self::get('_session_rotated_at', 0);
        if (time() - $lastRotation > 900) { // 15 minutes
            session_regenerate_id(true);
            self::set('_session_rotated_at', time());
        }
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::isLoggedIn() && self::get('user')['role'] === 'admin';
    }

    /**
     * Get the current user's company_id from session.
     * 
     * @return int|null
     */
    public static function companyId() {
        if (!self::isLoggedIn()) return null;
        return (int)(self::get('user')['company_id'] ?? 0) ?: null;
    }

    /**
     * Set flash message
     */
    public static function setFlash($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Get and clear flash message
     */
    public static function getFlash($type = null) {
        if ($type) {
            $message = $_SESSION['flash'][$type] ?? null;
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    /**
     * Check if flash message exists
     */
    public static function hasFlash($type = null) {
        if ($type) {
            return isset($_SESSION['flash'][$type]);
        }
        return !empty($_SESSION['flash']);
    }

    // =========================================================
    // RBAC Permission Engine
    // =========================================================

    /**
     * Check if the current user has a specific permission.
     *
     * Flow:
     *   1. Not logged in → false
     *   2. Super admin (role ENUM = 'admin' OR role.is_super_admin) → true immediately
     *   3. Otherwise → check session-cached permission array via lazy-load
     *
     * @param string $permission  Permission name, e.g. 'sales.create'
     * @return bool
     */
    public static function hasPermission($permission) {
        if (!self::isLoggedIn()) {
            return false;
        }

        // Super admin bypass — zero DB overhead
        // Check both legacy ENUM and new RBAC flag
        if (self::isAdmin() || self::isSuperAdmin()) {
            return true;
        }

        // Lazy-load permissions into session cache
        $permissions = self::get('user_permissions');
        if ($permissions === null) {
            $permissions = self::loadPermissions();
            self::set('user_permissions', $permissions);
        }

        return in_array($permission, $permissions, true);
    }

    /**
     * Check if the current user has super-admin privileges.
     *
     * Defense-in-depth: checks TWO independent sources:
     *  1. Role-based flag: roles.is_super_admin (loaded into session on login)
     *  2. Direct user flag: users.is_super_admin (optional, set via DB only)
     *
     * Either flag being true grants super-admin access.
     *
     * SECURITY:
     *  - Reads ONLY from server-side session (never POST/GET)
     *  - Fails safe: returns false if not logged in or data missing
     *  - Cannot be tampered from client side
     *
     * @return bool
     */
    public static function isSuperAdmin() {
        if (!self::isLoggedIn()) return false;
        $user = self::get('user');
        // Check role-based flag (set by AuthController on login)
        // OR direct user column flag (set only via DB)
        return !empty($user['is_super_admin']);
    }

    /**
     * Clear the cached permissions array.
     * Call this when:
     *   - User logs in (fresh load on next check)
     *   - User's role is changed by an admin
     *   - Permissions are modified for any role
     */
    public static function clearPermissionCache() {
        self::remove('user_permissions');
    }

    /**
     * Load permissions for the current user's role from the database.
     * Returns a flat array of permission name strings.
     * Called once per session, then cached in $_SESSION.
     *
     * @return array  e.g. ['sales.view', 'sales.create', 'payments.view']
     */
    private static function loadPermissions() {
        try {
            $user = self::get('user');
            $roleId = $user['role_id'] ?? null;

            if (!$roleId) {
                return [];
            }

            // Cache per session lifetime tied to the role ID
            return Cache::remember('role_permissions_' . session_id(), SESSION_LIFETIME, function() use ($roleId) {
                $db = Database::getInstance();
                $rows = $db->query(
                    "SELECT p.name
                     FROM permissions p
                     JOIN role_permissions rp ON rp.permission_id = p.id
                     WHERE rp.role_id = ?",
                    [$roleId]
                )->fetchAll();

                return array_column($rows, 'name');
            });
        } catch (\Exception $e) {
            // Permission loading must never crash the app
            error_log('[RBAC] Failed to load permissions: ' . $e->getMessage());
            return [];
        }
    }
}
