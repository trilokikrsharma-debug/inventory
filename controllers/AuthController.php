<?php
/**
 * Authentication Controller
 * 
 * Handles login/logout functionality.
 * Includes persistent IP-based rate limiting with exponential backoff.
 */
class AuthController extends Controller {

    protected $allowedActions = ['index'];

    // Rate limit settings (configurable)
    private const MAX_ATTEMPTS = 5;          // Lockout after this many failures
    private const BASE_LOCKOUT_SECONDS = 60; // Initial lockout: 1 minute
    private const MAX_LOCKOUT_SECONDS = 900; // Max lockout: 15 minutes
    private const ATTEMPT_WINDOW = 600;      // Reset counter after 10 min of no attempts

    public function index() {
        // If already logged in, redirect to dashboard
        if (Session::isLoggedIn()) {
            $this->redirect('index.php?page=dashboard');
        }

        // Handle login POST
        if ($this->isPost()) {
            $username = $this->sanitize($this->post('username'));
            $password = $this->post('password');

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            // ── Tenant-Aware IP+Username Rate Limiting ──
            $rateData = $this->getRateLimit($ip, $username);

            // Check if currently locked out
            if ($rateData['lockout_until'] > time()) {
                $remaining = $rateData['lockout_until'] - time();
                $minutes = ceil($remaining / 60);
                $error = "Too many failed attempts. Try again in {$minutes} minute(s).";
                $this->renderPartial('auth.login', ['error' => $error, 'username' => $username]);
                return;
            }

            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
                $this->renderPartial('auth.login', ['error' => $error, 'username' => $username]);
                return;
            }

            $userModel = new UserModel();
            $user = $userModel->authenticate($username, $password);

            if ($user) {
                // Successful login — clear rate limit data
                $this->clearRateLimit($ip, $username);

                session_regenerate_id(true);

                // SECURITY: Rotate CSRF token to prevent pre-auth token reuse
                CSRF::rotateToken();

                // Initialize session fingerprint (prevents session hijacking)
                Session::initFingerprint();

                // ── RBAC: Determine super-admin status first (before company check) ──
                // Platform super-admins are set directly in DB and may have no company_id.
                $isSuperAdmin = !empty($user['is_super_admin']);
                if (!$isSuperAdmin && !empty($user['role_id'])) {
                    try {
                        $role = Database::getInstance()->query(
                            "SELECT is_super_admin FROM roles WHERE id = ?",
                            [$user['role_id']]
                        )->fetch();
                        if ($role && $role['is_super_admin']) {
                            $isSuperAdmin = true;
                        }
                    } catch (\Exception $e) {
                        error_log('[RBAC] Failed to load role on login: ' . $e->getMessage());
                    }
                }

                // ── Multi-Tenant: Resolve user's company ──
                // Platform super-admins are exempt from the company requirement.
                $companyId = (int)($user['company_id'] ?? 0);
                $company   = null;

                if (!$isSuperAdmin) {
                    // Tenant users MUST belong to an active company
                    if ($companyId <= 0) {
                        $error = 'Your account is not associated with any company. Please contact support.';
                        $this->renderPartial('auth.login', ['error' => $error, 'username' => $username]);
                        return;
                    }

                    try {
                        $company = Database::getInstance()->query(
                            "SELECT id, name, status, is_demo, plan FROM companies WHERE id = ? AND status = 'active'",
                            [$companyId]
                        )->fetch();
                    } catch (\Exception $e) {
                        $company = null;
                    }

                    if (!$company) {
                        $error = 'Your company account has been suspended or deactivated. Contact support.';
                        $this->renderPartial('auth.login', ['error' => $error, 'username' => $username]);
                        return;
                    }
                }

                // Store user in session (includes company_id)
                Session::set('user', $user);

                // Set tenant context for this request
                Tenant::set($companyId, $company);

                // RBAC: Enrich session with resolved is_super_admin flag
                Session::clearPermissionCache();
                $user['is_super_admin'] = $isSuperAdmin;
                Session::set('user', $user);

                // Set tenant context only for non-super-admins who have a company
                if ($companyId > 0 && $company) {
                    Tenant::set($companyId, $company);
                }

                $this->logActivity('Login', 'auth', $user['id'],
                    $isSuperAdmin ? 'Platform super-admin logged in' : 'Tenant user logged in');

                // ── Route to correct dashboard based on role ──
                if ($isSuperAdmin) {
                    // Platform owner → platform dashboard
                    header("Location: " . APP_URL . "/index.php?page=platform&action=dashboard");
                } else {
                    // Tenant user → tenant dashboard
                    header("Location: " . APP_URL . "/index.php?page=dashboard");
                }
                exit;
            } else {
                // Failed login — update rate limit with exponential backoff
                $rateData['attempts'] = ($rateData['attempts'] ?? 0) + 1;
                $rateData['last_attempt'] = time();

                if ($rateData['attempts'] >= self::MAX_ATTEMPTS) {
                    // Exponential backoff: 1 min, 2 min, 4 min, 8 min... capped at MAX_LOCKOUT
                    $escalation = max(0, $rateData['attempts'] - self::MAX_ATTEMPTS);
                    $lockoutSeconds = min(
                        self::BASE_LOCKOUT_SECONDS * pow(2, $escalation),
                        self::MAX_LOCKOUT_SECONDS
                    );
                    $rateData['lockout_until'] = time() + (int)$lockoutSeconds;
                    Helper::securityLog('BRUTE_FORCE_LOCKOUT', "User: $username locked out for {$lockoutSeconds}s from $ip (attempt #{$rateData['attempts']})");
                } else {
                    Helper::securityLog('LOGIN_FAILED', "Failed attempt {$rateData['attempts']} for User: $username from $ip");
                }

                $this->setRateLimit($ip, $username, $rateData);

                $error = 'Invalid username or password.';
                $this->renderPartial('auth.login', ['error' => $error, 'username' => $username]);
                return;
            }
        }

        $this->renderPartial('auth.login', ['error' => '', 'username' => '']);
    }

    // =========================================================
    // Persistent Rate Limiting (file-based, per-IP)
    // =========================================================

    /**
     * Get rate limit data for an IP + Username combination.
     * Uses file-based storage in the cache directory.
     * Ensure shared-offices aren't globally blocked on false passwords.
     *
     * @param string $ip
     * @param string $username
     * @return array
     */
    private function getRateLimit($ip, $username) {
        $default = ['attempts' => 0, 'lockout_until' => 0, 'last_attempt' => 0];
        $file = $this->getRateLimitFile($ip, $username);

        if (!file_exists($file)) return $default;

        $data = @file_get_contents($file);
        if ($data === false) return $default;

        $parsed = @json_decode($data, true);
        if (!is_array($parsed)) {
            @unlink($file);
            return $default;
        }

        // Auto-expire: if last attempt was beyond the window, reset
        if (time() - ($parsed['last_attempt'] ?? 0) > self::ATTEMPT_WINDOW) {
            @unlink($file);
            return $default;
        }

        return array_merge($default, $parsed);
    }

    /**
     * Save rate limit data for an IP + Username.
     *
     * @param string $ip
     * @param string $username
     * @param array $data
     */
    private function setRateLimit($ip, $username, $data) {
        $file = $this->getRateLimitFile($ip, $username);
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Clear rate limit on successful login.
     *
     * @param string $ip
     * @param string $username
     */
    private function clearRateLimit($ip, $username) {
        $file = $this->getRateLimitFile($ip, $username);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Get the file path for a rate limit record.
     * Uses SHA256 of IP + Username + Tenant to prevent directory traversal and shared-IP blocking.
     *
     * @param string $ip
     * @param string $username
     * @return string
     */
    private function getRateLimitFile($ip, $username) {
        $dir = defined('BASE_PATH') ? BASE_PATH . '/cache' : __DIR__ . '/../cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $tenantId = class_exists('Tenant') ? (Tenant::id() ?? 0) : 0;
        $key = hash('sha256', $tenantId . '_' . $ip . '_' . strtolower(trim($username)));
        return $dir . '/ratelimit_' . $key . '.json';
    }
}
