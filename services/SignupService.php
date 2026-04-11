<?php
/**
 * Public self-signup provisioning service.
 *
 * Encapsulates tenant bootstrap, owner creation, starter data seeding, and
 * referral assignment so the controller only handles validation and session flow.
 */
class SignupService {
    private const RESERVED_SUBDOMAINS = [
        'www', 'admin', 'api', 'app', 'mail', 'smtp', 'imap', 'pop', 'cpanel',
        'webmail', 'ftp', 'sftp', 'ssh', 'root', 'support', 'help', 'blog',
        'status', 'billing', 'platform', 'dashboard', 'login', 'signup',
        'demo', 'staging', 'dev', 'test', 'autodiscover', 'm', 'mobile'
    ];

    private $db;
    private Referral $referralModel;

    public function __construct($db = null, ?Referral $referralModel = null) {
        $this->db = $db ?: Database::getInstance();
        $this->referralModel = $referralModel ?: new Referral();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{company_id:int,user_id:int,user:array<string,mixed>|mixed,company:array<string,mixed>|mixed}
     */
    public function registerTenant(array $payload): array {
        $companyName = trim((string)($payload['company_name'] ?? ''));
        $ownerName = trim((string)($payload['full_name'] ?? ''));
        $email = trim(strtolower((string)($payload['email'] ?? '')));
        $phone = trim((string)($payload['phone'] ?? ''));
        $username = trim(strtolower((string)($payload['username'] ?? '')));
        $password = (string)($payload['password'] ?? '');
        $referralCode = strtoupper(trim((string)($payload['referral_code'] ?? '')));

        $this->db->beginTransaction();

        try {
            $slug = $this->generateSlug($companyName);
            $signupPlan = $this->resolveSignupPlan();
            $companyId = $this->createCompanyRecord($companyName, $slug, $signupPlan);

            $tenantAdminRoleId = $this->resolveTenantAdminRoleId($companyId, $signupPlan);
            if ($tenantAdminRoleId <= 0) {
                throw new RuntimeException('Unable to resolve a safe tenant admin role.');
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->db->query(
                "INSERT INTO users (company_id, username, email, password, full_name, phone, role, role_id, is_active, is_super_admin) VALUES (?, ?, ?, ?, ?, ?, 'admin', ?, 1, 0)",
                [$companyId, $username, $email, $hashedPassword, $ownerName, $phone, $tenantAdminRoleId]
            );
            $userId = (int)$this->db->lastInsertId();

            $this->db->query('UPDATE companies SET owner_user_id = ? WHERE id = ?', [$userId, $companyId]);

            $this->db->query(
                "INSERT INTO company_settings (company_id, company_name, company_email, company_phone, company_address, company_city, company_state, company_country, currency_symbol, currency_code, enable_gst, enable_tax, tax_rate, low_stock_threshold, invoice_prefix, purchase_prefix, payment_prefix, receipt_prefix)
                 VALUES (?, ?, ?, ?, '', '', '', 'India', 'Rs', 'INR', 1, 1, 18, 10, 'INV-', 'PUR-', 'PAY-', 'REC-')",
                [$companyId, $companyName, $email, $phone]
            );

            $this->seedTenantDefaults($companyId);

            $this->referralModel->ensureCompanyReferralCode($companyId);
            if ($referralCode !== '') {
                $refAssign = $this->referralModel->assignReferralToCompany($companyId, $referralCode);
                if (empty($refAssign['success'])) {
                    throw new RuntimeException($refAssign['message'] ?? 'Invalid referral code.');
                }
            }

            $this->db->commit();

            $user = $this->db->query('SELECT * FROM users WHERE id = ?', [$userId])->fetch();
            $company = $this->db->query('SELECT * FROM companies WHERE id = ?', [$companyId])->fetch();

            return [
                'company_id' => $companyId,
                'user_id' => $userId,
                'user' => $user,
                'company' => $company,
            ];
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function generateSlug(string $name): string {
        $slug = strtolower(trim((string)(preg_replace('/[^a-zA-Z0-9]+/', '-', $name) ?? ''), '-'));
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

        $original = $slug;
        $counter = 1;
        while ((int)$this->db->query('SELECT COUNT(*) FROM companies WHERE slug = ?', [$slug])->fetchColumn() > 0) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    public function resolveSignupPlan(): array {
        $fallback = [
            'id' => null,
            'slug' => 'starter',
            'name' => 'Starter',
            'max_users' => 3,
            'max_products' => 500,
            'features' => null,
        ];

        $queries = [
            'SELECT id, slug, name, max_users, max_products, features FROM saas_plans WHERE IFNULL(is_active, 1) = 1 ORDER BY IFNULL(is_default, 0) DESC, id ASC LIMIT 1',
            'SELECT id, slug, name, max_users, max_products, features FROM saas_plans ORDER BY id ASC LIMIT 1',
        ];

        foreach ($queries as $sql) {
            try {
                $plan = $this->db->query($sql)->fetch();
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

    private function createCompanyRecord(string $companyName, string $slug, array $signupPlan): int {
        $planId = isset($signupPlan['id']) ? (int)$signupPlan['id'] : 0;
        if ($planId <= 0) {
            throw new RuntimeException('Signup is temporarily unavailable because pricing plans are not configured.');
        }

        $legacyPlan = (string)($signupPlan['slug'] ?? 'starter');
        $maxUsers = max(1, (int)($signupPlan['max_users'] ?? 3));
        $maxProducts = max(1, (int)($signupPlan['max_products'] ?? 500));

        $this->db->query(
            "INSERT INTO companies
             (name, subdomain, saas_plan_id, subscription_status, trial_ends_at, plan, status, max_users, max_products, slug)
             VALUES (?, ?, ?, 'trial', DATE_ADD(NOW(), INTERVAL 14 DAY), ?, 'active', ?, ?, ?)",
            [$companyName, $slug, $planId, $legacyPlan, $maxUsers, $maxProducts, $slug]
        );

        return (int)$this->db->lastInsertId();
    }

    private function resolveTenantAdminRoleId(int $tenantId, array $signupPlan): int {
        try {
            $role = $this->db->query(
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
                $this->syncTenantAdminPermissions((int)$role['id'], $signupPlan);
                return (int)$role['id'];
            }

            $roleName = 'tenant_admin_' . $tenantId;
            $this->db->query(
                "INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system)
                 VALUES (?, ?, 'Administrator', 'Full tenant-level access', 0, 1)",
                [$tenantId, $roleName]
            );
            $roleId = (int)$this->db->lastInsertId();

            $this->syncTenantAdminPermissions($roleId, $signupPlan);
            return $roleId;
        } catch (Throwable $e) {
            error_log('[Signup] Failed to resolve tenant admin role: ' . $e->getMessage());

            try {
                $globalRole = $this->db->query(
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

            $fallback = $this->db->query(
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

    private function syncTenantAdminPermissions(int $roleId, array $signupPlan): void {
        try {
            $permissionNames = SaaSBillingHelper::tenantAdminPermissionNames($signupPlan);
            $this->db->query('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);

            if (empty($permissionNames)) {
                return;
            }

            $placeholders = implode(',', array_fill(0, count($permissionNames), '?'));
            $permissions = $this->db->query(
                "SELECT id FROM permissions WHERE name IN ($placeholders) ORDER BY id ASC",
                $permissionNames
            )->fetchAll();

            foreach ($permissions as $permission) {
                $this->db->query(
                    'INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                    [$roleId, (int)$permission['id']]
                );
            }
        } catch (Throwable $e) {
            error_log('[Signup] Failed to seed role permissions: ' . $e->getMessage());
        }
    }

    private function seedTenantDefaults(int $companyId): void {
        foreach (['General', 'Electronics', 'Groceries', 'Clothing'] as $category) {
            $this->db->query('INSERT INTO categories (company_id, name) VALUES (?, ?)', [$companyId, $category]);
        }

        foreach (['Generic', 'Unbranded'] as $brand) {
            $this->db->query('INSERT INTO brands (company_id, name) VALUES (?, ?)', [$companyId, $brand]);
        }

        foreach ($this->defaultUnits() as $unit) {
            $this->db->query(
                'INSERT INTO units (company_id, name, short_name) VALUES (?, ?, ?)',
                [$companyId, $unit['name'], $unit['short_name']]
            );
        }

        $this->db->query(
            "INSERT INTO customers (company_id, name, phone, email, address) VALUES (?, 'Walk-In Customer', '', '', '')",
            [$companyId]
        );
    }

    /**
     * @return array<int, array{name:string, short_name:string}>
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
