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
            if (!empty(Session::get('twofa_pending_user_id'))) {
                $this->redirect('index.php?page=twoFactor&action=verify');
                return;
            }
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
            $loginTenantId = $this->resolveLoginTenantId();
            $user = $userModel->authenticate($username, $password, $loginTenantId);

            if ($user) {
                // Successful login — clear rate limit data
                $this->clearRateLimit($ip, $username);

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
                            "SELECT id, name, status, is_demo, plan, saas_plan_id, subscription_status, trial_ends_at, max_users, max_products
                             FROM companies
                             WHERE id = ? AND status = 'active'",
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

                if (!empty($user['twofa_enabled'])) {
                    $this->beginTwoFactorChallenge($user, $companyId, $company, $isSuperAdmin);
                    return;
                }

                $this->finalizeLogin($user, $companyId, $company, $isSuperAdmin);
                return;
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

    /**
     * Start the pending 2FA flow without creating a fully authorized session.
     */
    private function beginTwoFactorChallenge(array $user, int $companyId, ?array $company, bool $isSuperAdmin): void {
        $pendingUser = $this->sanitizeSessionUser($user, $isSuperAdmin);
        $pendingUser['twofa_pending'] = true;
        $pendingUser['twofa_verified'] = false;

        // Keep a partial session so AuthMiddleware can pass the verification page,
        // but fence all other routes in Router until verification completes.
        Session::set('user', $pendingUser);
        Session::set('twofa_pending_user_id', (int)$pendingUser['id']);
        Session::set('twofa_pending_is_super_admin', $isSuperAdmin ? 1 : 0);
        Session::set('twofa_pending_company_id', $companyId > 0 ? $companyId : null);
        Session::set('twofa_pending_company', $company);
        Session::clearPermissionCache();

        if ($isSuperAdmin) {
            Tenant::reset();
        } elseif ($companyId > 0 && $company) {
            Tenant::set($companyId, $company);
        } else {
            Tenant::reset();
        }

        $this->logActivity('Login pending 2FA', 'auth', $pendingUser['id'], 'Two-factor verification required');
        $this->redirect('index.php?page=twoFactor&action=verify');
    }

    /**
     * Finalize a successful login after all checks have passed.
     */
    private function finalizeLogin(array $user, int $companyId, ?array $company, bool $isSuperAdmin): void {
        $sessionUser = $this->sanitizeSessionUser($user, $isSuperAdmin);
        $sessionUser['twofa_pending'] = false;
        $sessionUser['twofa_verified'] = true;

        session_regenerate_id(true);
        CSRF::rotateToken();
        Session::initFingerprint();
        Session::clearPermissionCache();

        Session::remove('twofa_pending_user_id');
        Session::remove('twofa_pending_is_super_admin');
        Session::remove('twofa_pending_company_id');
        Session::remove('twofa_pending_company');

        Session::set('user', $sessionUser);

        if ($isSuperAdmin) {
            Tenant::reset();
        } elseif ($companyId > 0 && $company) {
            Tenant::set($companyId, $company);
        } else {
            Tenant::reset();
        }

        $this->logActivity('Login', 'auth', $sessionUser['id'],
            $isSuperAdmin ? 'Platform super-admin logged in' : 'Tenant user logged in');

        if ($isSuperAdmin) {
            $this->redirect('index.php?page=platform&action=dashboard');
        }

        $this->redirect('index.php?page=dashboard');
    }

    /**
     * Strip sensitive DB fields before storing the user in session.
     */
    private function sanitizeSessionUser(array $user, bool $isSuperAdmin): array {
        unset(
            $user['password'],
            $user['twofa_secret'],
            $user['twofa_recovery_codes'],
            $user['company_status'],
            $user['company_name']
        );
        $user['is_super_admin'] = $isSuperAdmin || !empty($user['is_super_admin']);
        return $user;
    }

    /**
     * Resolve tenant context from the current host when the app is served on a
     * subdomain. This keeps logins tenant-scoped without breaking apex-domain
     * or localhost deployments.
     */
    private function resolveLoginTenantId(): ?int {
        try {
            $company = Tenant::resolveFromHost();
            if (!is_array($company)) {
                return null;
            }

            $companyId = (int)($company['id'] ?? 0);
            if ($companyId <= 0) {
                return null;
            }

            $status = strtolower(trim((string)($company['status'] ?? 'active')));
            if ($status !== 'active') {
                return null;
            }

            return $companyId;
        } catch (\Throwable $e) {
            error_log('[Auth] Failed to resolve login tenant from host: ' . $e->getMessage());
            return null;
        }
    }
}
