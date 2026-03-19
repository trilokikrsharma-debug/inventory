<?php
/**
 * Signup Controller — Public Self-Registration Flow
 * 
 * Handles new company + owner user creation in a single atomic transaction.
 * No authentication required.
 */
class SignupController extends Controller {

    protected $allowedActions = ['index'];

    public function index() {
        // If already logged in, redirect to dashboard
        if (Session::isLoggedIn()) {
            if (Session::isTwoFactorPending()) {
                $this->redirect('index.php?page=twoFactor&action=verify');
                return;
            }
            $this->redirect('index.php?page=dashboard');
            return;
        }

        $error = '';
        $errors = [];
        $success = '';
        $formData = [];

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
            $ownerName   = trim($this->sanitize($this->post('full_name', '')));
            $email       = trim(strtolower($this->post('email', '')));
            $phone       = trim($this->sanitize($this->post('phone', '')));
            $username    = trim(strtolower($this->sanitize($this->post('username', ''))));
            $password    = $this->post('password', '');
            $confirmPass = $this->post('confirm_password', '');
            $referralCode = strtoupper(trim((string)$this->post('referral_code', '')));

            $formData = compact('companyName', 'ownerName', 'email', 'phone', 'username', 'referralCode');
            $minPasswordLength = defined('PASSWORD_MIN_LENGTH')
                ? max(6, (int)PASSWORD_MIN_LENGTH)
                : 6;

            // Validation
            if (empty($companyName) || strlen($companyName) < 2) {
                $errors['company_name'] = 'Company name is required (minimum 2 characters).';
            } elseif (strlen($companyName) > 120) {
                $errors['company_name'] = 'Company name must be 120 characters or fewer.';
            }

            if (empty($ownerName) || strlen($ownerName) < 2) {
                $errors['full_name'] = 'Full name is required (minimum 2 characters).';
            } elseif (strlen($ownerName) > 120) {
                $errors['full_name'] = 'Full name must be 120 characters or fewer.';
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'A valid email address is required.';
            } elseif (strlen($email) > 190) {
                $errors['email'] = 'Email address is too long.';
            }

            if (!empty($phone) && !preg_match('/^\+?[0-9\s\-()]{7,20}$/', $phone)) {
                $errors['phone'] = 'Phone number format looks invalid.';
            }

            if (empty($username) || strlen($username) < 3 || strlen($username) > 40 || !preg_match('/^[a-z0-9_]+$/', $username)) {
                $errors['username'] = 'Username must be 3-40 characters (lowercase letters, numbers, underscore only).';
            }

            if (empty($password) || strlen($password) < $minPasswordLength) {
                $errors['password'] = "Password must be at least {$minPasswordLength} characters.";
            } elseif (defined('PASSWORD_COMPLEXITY') && PASSWORD_COMPLEXITY) {
                if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                    $errors['password'] = 'Password must contain at least 1 uppercase letter and 1 number.';
                }
            }

            if (empty($confirmPass)) {
                $errors['confirm_password'] = 'Please confirm your password.';
            } elseif ($password !== $confirmPass) {
                $errors['confirm_password'] = 'Passwords do not match.';
            }

            if ($referralCode !== '' && !preg_match('/^[A-Z0-9_-]{4,40}$/', $referralCode)) {
                $errors['referral_code'] = 'Referral code format is invalid.';
            }

            if (empty($errors)) {
                // Check email uniqueness (globally)
                $userModel = new UserModel();
                if ($userModel->emailExists($email)) {
                    $errors['email'] = 'This email is already registered. Please log in or use a different email.';
                }
            }

            if (empty($errors)) {
                $db = null;
                $db = Database::getInstance();
                $db->beginTransaction();

                try {
                // 1. Create company
                $slug = $this->generateSlug($companyName);
                $db->query(
                    "INSERT INTO companies
                     (name, slug, saas_plan_id, subscription_status, trial_ends_at, plan, status, max_users, max_products)
                     VALUES (?, ?, 1, 'trial', DATE_ADD(NOW(), INTERVAL 14 DAY), 'starter', 'active', 3, 500)",
                    [$companyName, $slug]
                );
                $companyId = $db->lastInsertId();

                // 2. Create owner user using a tenant-safe admin role.
                // SECURITY: is_super_admin is ALWAYS 0 here. Platform super-admins are set via DB ONLY.
                $tenantAdminRoleId = $this->resolveTenantAdminRoleId($db, (int)$companyId);
                if ($tenantAdminRoleId <= 0) {
                    throw new \RuntimeException('Unable to resolve a safe tenant admin role.');
                }
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $db->query(
                    "INSERT INTO users (company_id, username, email, password, full_name, phone, role, role_id, is_active, is_super_admin) VALUES (?, ?, ?, ?, ?, ?, 'admin', ?, 1, 0)",
                    [$companyId, $username, $email, $hashedPassword, $ownerName, $phone, $tenantAdminRoleId]
                );
                $userId = $db->lastInsertId();

                // 3. Update company with owner
                $db->query("UPDATE companies SET owner_user_id = ? WHERE id = ?", [$userId, $companyId]);

                // 4. Create default settings
                $db->query(
                    "INSERT INTO company_settings (company_id, company_name, company_email, company_phone, company_address, company_city, company_state, company_country, currency_symbol, currency_code, enable_gst, enable_tax, tax_rate, low_stock_threshold, invoice_prefix, purchase_prefix, payment_prefix, receipt_prefix) 
                     VALUES (?, ?, ?, ?, '', '', '', 'India', '₹', 'INR', 1, 1, 18, 10, 'INV-', 'PUR-', 'PAY-', 'REC-')",
                    [$companyId, $companyName, $email, $phone]
                );

                // 5. Seed default data (categories, units, brands)
                $defaults = [
                    'categories' => ['General', 'Electronics', 'Groceries', 'Clothing'],
                    'brands'     => ['Generic', 'Unbranded'],
                    'units'      => [
                        ['name' => 'Pieces', 'short_name' => 'pcs'],
                        ['name' => 'Kilograms', 'short_name' => 'kg'],
                        ['name' => 'Liters', 'short_name' => 'ltr'],
                        ['name' => 'Meters', 'short_name' => 'mtr'],
                        ['name' => 'Boxes', 'short_name' => 'box'],
                    ],
                ];

                foreach ($defaults['categories'] as $cat) {
                    $db->query("INSERT INTO categories (company_id, name) VALUES (?, ?)", [$companyId, $cat]);
                }
                foreach ($defaults['brands'] as $brand) {
                    $db->query("INSERT INTO brands (company_id, name) VALUES (?, ?)", [$companyId, $brand]);
                }
                foreach ($defaults['units'] as $unit) {
                    $db->query("INSERT INTO units (company_id, name, short_name) VALUES (?, ?, ?)", [$companyId, $unit['name'], $unit['short_name']]);
                }

                // 6. Seed Walk-In Customer
                $db->query(
                    "INSERT INTO customers (company_id, name, phone, email, address) VALUES (?, 'Walk-In Customer', '', '', '')",
                    [$companyId]
                );

                // 7. Create own referral code and optionally map referred-by relationship.
                $referralModel = new Referral();
                $referralModel->ensureCompanyReferralCode($companyId);
                if ($referralCode !== '') {
                    $refAssign = $referralModel->assignReferralToCompany($companyId, $referralCode);
                    if (empty($refAssign['success'])) {
                        throw new \RuntimeException($refAssign['message'] ?? 'Invalid referral code.');
                    }
                }

                $db->commit();

                // Auto-login after signup
                $user = $db->query("SELECT * FROM users WHERE id = ?", [$userId])->fetch();
                
                // Fetch full company data to properly set Tenant context
                $company = $db->query("SELECT * FROM companies WHERE id = ?", [$companyId])->fetch();

                session_regenerate_id(true);

                // SECURITY: Signup users are NEVER super-admins.
                // is_super_admin can only be set manually in the database by the platform owner.
                Session::clearPermissionCache();
                $user['is_super_admin'] = false;

                Session::set('user', $user);
                Tenant::set($companyId, $company);

                Session::setFlash('success', 'Welcome to ' . APP_NAME . '! Your account has been created.');
                header("Location: " . APP_URL . "/index.php?page=dashboard");
                exit;

            } catch (\Exception $e) {
                if ($db) {
                    $db->rollback();
                }
                $message = trim((string)$e->getMessage());

                if ($message !== '' && stripos($message, 'referral') !== false) {
                    $errors['referral_code'] = $message;
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
     * Generate URL-safe slug from company name
     */
    private function generateSlug($name) {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        $slug = substr($slug, 0, 50);
        if ($slug === '') {
            $slug = 'company';
        }

        // Ensure uniqueness
        $db = Database::getInstance();
        $original = $slug;
        $counter = 1;
        while ($db->query("SELECT COUNT(*) FROM companies WHERE slug = ?", [$slug])->fetchColumn() > 0) {
            $slug = $original . '-' . $counter++;
        }
        return $slug;
    }

    /**
     * Resolve a tenant-local admin role, creating one when needed.
     * This keeps signup away from hardcoded role_id assumptions.
     */
    private function resolveTenantAdminRoleId(Database $db, int $tenantId): int {
        try {
            $role = $db->query(
                "SELECT id, name, display_name, company_id
                 FROM roles
                 WHERE company_id = ? AND IFNULL(is_super_admin, 0) = 0
                 ORDER BY CASE
                            WHEN LOWER(name) IN ('admin', 'tenant_admin', 'owner', 'administrator') THEN 0
                            WHEN LOWER(display_name) LIKE '%admin%' THEN 1
                            ELSE 2
                          END,
                          id ASC
                 LIMIT 1",
                [$tenantId]
            )->fetch();

            if ($role) {
                return (int)$role['id'];
            }

            $roleName = 'tenant_admin_' . $tenantId;
            $db->query(
                "INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system)
                 VALUES (?, ?, 'Administrator', 'Full tenant-level access', 0, 1)",
                [$tenantId, $roleName]
            );
            $roleId = (int)$db->lastInsertId();

            $this->grantAllPermissionsToRole($db, $roleId);
            return $roleId;
        } catch (\Throwable $e) {
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
            } catch (\Throwable $fallbackError) {
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
    private function grantAllPermissionsToRole(Database $db, int $roleId): void {
        try {
            $permissions = $db->query("SELECT id FROM permissions ORDER BY id ASC")->fetchAll();
            foreach ($permissions as $permission) {
                $db->query(
                    "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                    [$roleId, (int)$permission['id']]
                );
            }
        } catch (\Throwable $e) {
            error_log('[Signup] Failed to seed role permissions: ' . $e->getMessage());
        }
    }
}
