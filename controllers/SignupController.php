<?php
/**
 * Signup Controller - Public Self-Registration Flow
 *
 * Handles new company + owner user creation in a single atomic transaction.
 * No authentication required.
 */
class SignupController extends Controller {
    private const RESERVED_SUBDOMAINS = [
        'www', 'admin', 'api', 'app', 'mail', 'smtp', 'imap', 'pop', 'cpanel',
        'webmail', 'ftp', 'sftp', 'ssh', 'root', 'support', 'help', 'blog',
        'status', 'billing', 'platform', 'dashboard', 'login', 'signup',
        'demo', 'staging', 'dev', 'test', 'autodiscover', 'm', 'mobile'
    ];

    protected $allowedActions = ['index'];

    public function index() {
        $isDemoSignupExit = Session::isLoggedIn()
            && Tenant::isDemo()
            && $this->get('from_demo', '') === '1';

        if ($isDemoSignupExit) {
            RememberMeService::revokeCurrentToken();
            Session::clearPermissionCache();
            Session::destroy();
            $this->redirect('signup');
            return;
        }

        if (Session::isLoggedIn()) {
            if (Session::isTwoFactorPending()) {
                $this->redirect('twoFactor/verify');
                return;
            }

            $this->redirect('dashboard');
            return;
        }

        $error = '';
        $errors = [];
        $success = '';
        $formData = [
            'email' => trim(strtolower((string)$this->get('email', ''))),
        ];

        if ($this->isPost()) {
            $this->validateCSRF();

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            if (!RateLimiter::attempt('signup_ip:' . $ip, 10, 3600)) {
                $error = 'Too many signup attempts from your network. Please try again in some time.';
                $this->renderPartial('auth.signup', [
                    'error' => $error,
                    'errors' => ['general' => $error],
                    'success' => $success,
                    'formData' => $formData,
                ]);
                return;
            }

            if (!RateLimiter::attempt('signup_global', 200, 3600)) {
                $error = 'Signup service is temporarily busy. Please retry after some time.';
                $this->renderPartial('auth.signup', [
                    'error' => $error,
                    'errors' => ['general' => $error],
                    'success' => $success,
                    'formData' => $formData,
                ]);
                return;
            }

            $companyName = trim($this->sanitize($this->post('company_name', '')));
            $ownerName = trim($this->sanitize($this->post('full_name', '')));
            $email = trim(strtolower($this->post('email', '')));
            $phone = trim($this->sanitize($this->post('phone', '')));
            $username = trim(strtolower($this->sanitize($this->post('username', ''))));
            $password = $this->post('password', '');
            $confirmPass = $this->post('confirm_password', '');
            $referralCode = strtoupper(trim((string)$this->post('referral_code', '')));

            $formData = compact('companyName', 'ownerName', 'email', 'phone', 'username', 'referralCode');
            $minPasswordLength = defined('PASSWORD_MIN_LENGTH')
                ? max(6, (int)PASSWORD_MIN_LENGTH)
                : 6;

            if ($companyName === '' || strlen($companyName) < 2) {
                $errors['company_name'] = 'Company name is required (minimum 2 characters).';
            } elseif (strlen($companyName) > 120) {
                $errors['company_name'] = 'Company name must be 120 characters or fewer.';
            }

            if ($ownerName === '' || strlen($ownerName) < 2) {
                $errors['full_name'] = 'Full name is required (minimum 2 characters).';
            } elseif (strlen($ownerName) > 120) {
                $errors['full_name'] = 'Full name must be 120 characters or fewer.';
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email address is required.';
            } elseif (strlen($email) > 190) {
                $errors['email'] = 'Email address is too long.';
            }

            if ($phone !== '' && !preg_match('/^\+?[0-9\s\-()]{7,20}$/', $phone)) {
                $errors['phone'] = 'Phone number format looks invalid.';
            }

            if ($username === '' || strlen($username) < 3 || strlen($username) > 40 || !preg_match('/^[a-z0-9_]+$/', $username)) {
                $errors['username'] = 'Username must be 3-40 characters (lowercase letters, numbers, underscore only).';
            }

            if ($password === '' || strlen($password) < $minPasswordLength) {
                $errors['password'] = "Password must be at least {$minPasswordLength} characters.";
            } elseif (defined('PASSWORD_COMPLEXITY') && PASSWORD_COMPLEXITY) {
                if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                    $errors['password'] = 'Password must contain at least 1 uppercase letter and 1 number.';
                }
            }

            if ($confirmPass === '') {
                $errors['confirm_password'] = 'Please confirm your password.';
            } elseif ($password !== $confirmPass) {
                $errors['confirm_password'] = 'Passwords do not match.';
            }

            if ($referralCode !== '' && !preg_match('/^[A-Z0-9_-]{4,40}$/', $referralCode)) {
                $errors['referral_code'] = 'Referral code format is invalid.';
            }

            if (empty($errors)) {
                $userModel = new UserModel();
                if ($userModel->emailExists($email)) {
                    $errors['email'] = 'This email is already registered. Please log in or use a different email.';
                }
            }

            if (empty($errors)) {
                $db = Database::getInstance();
                $db->beginTransaction();

                try {
                    $slug = $this->generateSlug($companyName);
                    $signupPlan = $this->resolveSignupPlan($db);
                    $companyId = $this->createCompanyRecord($db, $companyName, $slug, $signupPlan);

                    $tenantAdminRoleId = $this->resolveTenantAdminRoleId($db, $companyId, $signupPlan);
                    if ($tenantAdminRoleId <= 0) {
                        throw new RuntimeException('Unable to resolve a safe tenant admin role.');
                    }

                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $db->query(
                        "INSERT INTO users (company_id, username, email, password, full_name, phone, role, role_id, is_active, is_super_admin) VALUES (?, ?, ?, ?, ?, ?, 'admin', ?, 1, 0)",
                        [$companyId, $username, $email, $hashedPassword, $ownerName, $phone, $tenantAdminRoleId]
                    );
                    $userId = (int)$db->lastInsertId();

                    $db->query('UPDATE companies SET owner_user_id = ? WHERE id = ?', [$userId, $companyId]);

                    $db->query(
                        "INSERT INTO company_settings (company_id, company_name, company_email, company_phone, company_address, company_city, company_state, company_country, currency_symbol, currency_code, enable_gst, enable_tax, tax_rate, low_stock_threshold, invoice_prefix, purchase_prefix, payment_prefix, receipt_prefix)
                         VALUES (?, ?, ?, ?, '', '', '', 'India', 'Rs', 'INR', 1, 1, 18, 10, 'INV-', 'PUR-', 'PAY-', 'REC-')",
                        [$companyId, $companyName, $email, $phone]
                    );

                    $defaults = [
                        'categories' => ['General', 'Electronics', 'Groceries', 'Clothing'],
                        'brands' => ['Generic', 'Unbranded'],
                        'units' => $this->defaultUnits(),
                    ];

                    foreach ($defaults['categories'] as $category) {
                        $db->query('INSERT INTO categories (company_id, name) VALUES (?, ?)', [$companyId, $category]);
                    }

                    foreach ($defaults['brands'] as $brand) {
                        $db->query('INSERT INTO brands (company_id, name) VALUES (?, ?)', [$companyId, $brand]);
                    }

                    foreach ($defaults['units'] as $unit) {
                        $db->query(
                            'INSERT INTO units (company_id, name, short_name) VALUES (?, ?, ?)',
                            [$companyId, $unit['name'], $unit['short_name']]
                        );
                    }

                    $db->query(
                        "INSERT INTO customers (company_id, name, phone, email, address) VALUES (?, 'Walk-In Customer', '', '', '')",
                        [$companyId]
                    );

                    $referralModel = new Referral();
                    $referralModel->ensureCompanyReferralCode($companyId);
                    if ($referralCode !== '') {
                        $refAssign = $referralModel->assignReferralToCompany($companyId, $referralCode);
                        if (empty($refAssign['success'])) {
                            throw new RuntimeException($refAssign['message'] ?? 'Invalid referral code.');
                        }
                    }

                    $db->commit();

                    $user = $db->query('SELECT * FROM users WHERE id = ?', [$userId])->fetch();
                    $company = $db->query('SELECT * FROM companies WHERE id = ?', [$companyId])->fetch();

                    session_regenerate_id(true);
                    CSRF::rotateToken();
                    Session::initFingerprint();
                    Session::clearPermissionCache();
                    unset($user['password'], $user['twofa_secret'], $user['twofa_recovery_codes']);
                    $user['is_super_admin'] = false;

                    Session::set('user', $user);
                    Tenant::set($companyId, $company);
                    Session::setFlash('success', 'Welcome to ' . APP_NAME . '! Your account has been created.');

                    header('Location: ' . APP_URL . '/dashboard');
                    exit;
                } catch (Exception $e) {
                    $db->rollback();
                    $message = trim((string)$e->getMessage());

                    if ($message !== '' && stripos($message, 'referral') !== false) {
                        $errors['referral_code'] = $message;
                    } elseif ($message !== '' && stripos($message, 'pricing plans are not configured') !== false) {
                        $errors['general'] = 'Signup is temporarily unavailable. Please contact support while billing setup is being finished.';
                    } elseif ($message !== '' && stripos($message, 'duplicate') !== false && stripos($message, 'email') !== false) {
                        $errors['email'] = 'This email is already registered. Please log in or use a different email.';
                    } elseif ($message !== '' && stripos($message, 'duplicate') !== false && stripos($message, 'username') !== false) {
                        $errors['username'] = 'This username is not available. Please choose another one.';
                    } else {
                        $errors['general'] = 'Registration failed. Please try again or contact support.';
                    }

                    error_log('[SIGNUP] Error: ' . $message);
                }
            }

            if (!empty($errors)) {
                $error = (string)(reset($errors) ?: 'Please fix the form errors and try again.');
            }
        }

        $this->renderPartial('auth.signup', [
            'error' => $error,
            'errors' => $errors,
            'success' => $success,
            'formData' => $formData,
        ]);
    }

    /**
     * Generate URL-safe slug from company name.
     */
    private function generateSlug($name) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        $slug = substr($slug, 0, 50);
        if ($slug === '') {
            $slug = 'company';
        }
        if (strlen($slug) < 3) {
            $slug = str_pad($slug, 3, 'x');
        }
        if (in_array($slug, self::RESERVED_SUBDOMAINS, true)) {
            $slug .= '-account';
        }

        $db = Database::getInstance();
        $original = $slug;
        $counter = 1;
        while ($db->query('SELECT COUNT(*) FROM companies WHERE slug = ?', [$slug])->fetchColumn() > 0) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    /**
     * Create the tenant company record while tolerating additive schema drift.
     */
    private function createCompanyRecord(Database $db, string $companyName, string $slug, array $signupPlan): int {
        $columns = ['name', 'slug'];
        $valueSql = ['?', '?'];
        $params = [$companyName, $slug];

        if ($this->tableHasColumn($db, 'companies', 'saas_plan_id')) {
            $planId = isset($signupPlan['id']) ? (int)$signupPlan['id'] : null;
            if ($planId === null) {
                throw new RuntimeException('Signup is temporarily unavailable because pricing plans are not configured.');
            }

            $columns[] = 'saas_plan_id';
            $valueSql[] = '?';
            $params[] = $planId;
        }

        if ($this->tableHasColumn($db, 'companies', 'subscription_status')) {
            $columns[] = 'subscription_status';
            $valueSql[] = '?';
            $params[] = 'trial';
        }

        if ($this->tableHasColumn($db, 'companies', 'trial_ends_at')) {
            $columns[] = 'trial_ends_at';
            $valueSql[] = 'DATE_ADD(NOW(), INTERVAL 14 DAY)';
        }

        if ($this->tableHasColumn($db, 'companies', 'plan')) {
            $columns[] = 'plan';
            $valueSql[] = '?';
            $params[] = (string)($signupPlan['slug'] ?? 'starter');
        }

        if ($this->tableHasColumn($db, 'companies', 'status')) {
            $columns[] = 'status';
            $valueSql[] = '?';
            $params[] = 'active';
        }

        if ($this->tableHasColumn($db, 'companies', 'max_users')) {
            $columns[] = 'max_users';
            $valueSql[] = '?';
            $params[] = max(1, (int)($signupPlan['max_users'] ?? 3));
        }

        if ($this->tableHasColumn($db, 'companies', 'max_products')) {
            $columns[] = 'max_products';
            $valueSql[] = '?';
            $params[] = max(1, (int)($signupPlan['max_products'] ?? 500));
        }

        $db->query(
            'INSERT INTO companies (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $valueSql) . ')',
            $params
        );

        return (int)$db->lastInsertId();
    }

    private function resolveSignupPlan(Database $db): array {
        $fallback = [
            'id' => null,
            'slug' => 'starter',
            'name' => 'Starter',
            'max_users' => 3,
            'max_products' => 500,
            'features' => null,
        ];

        if (!$this->tableExists($db, 'saas_plans')) {
            return $fallback;
        }

        $queries = [
            'SELECT id, slug, name, max_users, max_products, features FROM saas_plans WHERE IFNULL(is_active, 1) = 1 ORDER BY IFNULL(is_default, 0) DESC, id ASC LIMIT 1',
            'SELECT id, slug, name, max_users, max_products, features FROM saas_plans ORDER BY id ASC LIMIT 1',
        ];

        foreach ($queries as $sql) {
            try {
                $plan = $db->query($sql)->fetch();
                if ($plan) {
                    $slug = strtolower(trim((string)($plan['slug'] ?? $plan['name'] ?? 'starter')));
                    if ($slug === '') {
                        $slug = 'starter';
                    }
                    $plan['slug'] = $slug;
                    return array_merge($fallback, $plan);
                }
            } catch (Throwable $e) {
                error_log('[Signup] Plan lookup failed: ' . $e->getMessage());
            }
        }

        return $fallback;
    }

    private function tableExists(Database $db, string $table): bool {
        static $tableCache = [];
        $cacheKey = strtolower($table);
        if (array_key_exists($cacheKey, $tableCache)) {
            return $tableCache[$cacheKey];
        }

        $exists = (bool)$db->query(
            'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
            [$table]
        )->fetchColumn();

        $tableCache[$cacheKey] = $exists;
        return $exists;
    }

    private function tableHasColumn(Database $db, string $table, string $column): bool {
        static $columnCache = [];
        $cacheKey = strtolower($table . '.' . $column);
        if (array_key_exists($cacheKey, $columnCache)) {
            return $columnCache[$cacheKey];
        }

        $exists = (bool)$db->query(
            'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$table, $column]
        )->fetchColumn();

        $columnCache[$cacheKey] = $exists;
        return $exists;
    }

    /**
     * Resolve a tenant-local admin role, creating one when needed.
     */
    private function resolveTenantAdminRoleId(Database $db, int $tenantId, array $signupPlan): int {
        try {
            $role = $db->query(
                "SELECT id, name, display_name, company_id
                 FROM roles
                 WHERE company_id = ? AND IFNULL(is_super_admin, 0) = 0
                 ORDER BY CASE
                            WHEN LOWER(name) IN ('admin', 'owner', 'administrator') OR LOWER(name) LIKE 'tenant_admin_%' THEN 0
                            WHEN LOWER(display_name) LIKE '%admin%' THEN 1
                            ELSE 2
                          END,
                          id ASC
                 LIMIT 1",
                [$tenantId]
            )->fetch();

            if ($role) {
                $this->syncTenantAdminPermissions($db, (int)$role['id'], $signupPlan);
                return (int)$role['id'];
            }

            $roleName = 'tenant_admin_' . $tenantId;
            $db->query(
                "INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system)
                 VALUES (?, ?, 'Administrator', 'Full tenant-level access', 0, 1)",
                [$tenantId, $roleName]
            );
            $roleId = (int)$db->lastInsertId();

            $this->syncTenantAdminPermissions($db, $roleId, $signupPlan);
            return $roleId;
        } catch (Throwable $e) {
            error_log('[Signup] Failed to resolve tenant admin role: ' . $e->getMessage());

            try {
                $globalRole = $db->query(
                    "SELECT id
                     FROM roles
                     WHERE company_id IS NULL AND IFNULL(is_super_admin, 0) = 0
                     ORDER BY CASE
                                WHEN LOWER(name) = 'admin' THEN 0
                                WHEN LOWER(display_name) LIKE '%admin%' THEN 1
                                ELSE 2
                              END,
                              id ASC
                     LIMIT 1"
                )->fetch();

                if ($globalRole) {
                    return (int)$globalRole['id'];
                }
            } catch (Throwable $fallbackError) {
                error_log('[Signup] Global role fallback failed: ' . $fallbackError->getMessage());
            }

            $fallback = $db->query(
                "SELECT id
                 FROM roles
                 WHERE IFNULL(is_super_admin, 0) = 0
                 ORDER BY CASE
                            WHEN LOWER(name) = 'admin' THEN 0
                            WHEN LOWER(display_name) LIKE '%admin%' THEN 1
                            ELSE 2
                          END,
                          id ASC
                 LIMIT 1"
            )->fetch();

            return (int)($fallback['id'] ?? 0);
        }
    }

    /**
     * Seed all permissions into a freshly-created tenant admin role.
     */
    private function syncTenantAdminPermissions(Database $db, int $roleId, array $signupPlan): void {
        try {
            $permissionNames = SaaSBillingHelper::tenantAdminPermissionNames($signupPlan);
            $db->query('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);

            if (empty($permissionNames)) {
                return;
            }

            $placeholders = implode(',', array_fill(0, count($permissionNames), '?'));
            $permissions = $db->query(
                "SELECT id FROM permissions WHERE name IN ($placeholders) ORDER BY id ASC",
                $permissionNames
            )->fetchAll();

            foreach ($permissions as $permission) {
                $db->query(
                    'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                    [$roleId, (int)$permission['id']]
                );
            }
        } catch (Throwable $e) {
            error_log('[Signup] Failed to seed role permissions: ' . $e->getMessage());
        }
    }

    /**
     * Common starter units seeded for new tenants.
     *
     * @return array<int, array{name: string, short_name: string}>
     */
    private function defaultUnits(): array {
        return [
            ['name' => 'Pieces', 'short_name' => 'pcs'],
            ['name' => 'Kilograms', 'short_name' => 'kg'],
            ['name' => 'Grams', 'short_name' => 'g'],
            ['name' => 'Meters', 'short_name' => 'mtr'],
            ['name' => 'Centimeters', 'short_name' => 'cm'],
            ['name' => 'Millimeters', 'short_name' => 'mm'],
            ['name' => 'Liters', 'short_name' => 'ltr'],
            ['name' => 'Milliliters', 'short_name' => 'ml'],
            ['name' => 'Boxes', 'short_name' => 'box'],
            ['name' => 'Packets', 'short_name' => 'pkt'],
            ['name' => 'Packs', 'short_name' => 'pac'],
            ['name' => 'Bags', 'short_name' => 'bag'],
            ['name' => 'Bottles', 'short_name' => 'btl'],
            ['name' => 'Cartons', 'short_name' => 'ctn'],
            ['name' => 'Dozens', 'short_name' => 'doz'],
            ['name' => 'Pairs', 'short_name' => 'pair'],
            ['name' => 'Sets', 'short_name' => 'set'],
            ['name' => 'Rolls', 'short_name' => 'roll'],
            ['name' => 'Sheets', 'short_name' => 'sheet'],
            ['name' => 'Units', 'short_name' => 'unit'],
        ];
    }
}
