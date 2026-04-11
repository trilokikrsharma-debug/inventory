<?php
class TenantOnboardingService {
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

    public function validateRegistrationInput(array $input): array {
        $companyName = $this->sanitize($input['company_name'] ?? '');
        $subdomain = strtolower($this->sanitize($input['subdomain'] ?? ''));
        $email = trim(strtolower((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');
        $referralCode = strtoupper(trim((string)($input['referral_code'] ?? '')));

        if ($companyName === '' || $subdomain === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('All fields (company_name, subdomain, email, password) are required.');
        }

        if (!preg_match('/^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]$/', $subdomain)) {
            throw new InvalidArgumentException('Subdomain must be 3-63 characters and use lowercase letters, numbers, or internal dashes only.');
        }

        if (in_array($subdomain, self::RESERVED_SUBDOMAINS, true)) {
            throw new InvalidArgumentException('This subdomain is reserved. Please choose another one.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email address is required.');
        }

        $minPasswordLength = defined('PASSWORD_MIN_LENGTH') ? max(6, (int)PASSWORD_MIN_LENGTH) : 8;
        if (strlen($password) < $minPasswordLength) {
            throw new InvalidArgumentException("Password must be at least {$minPasswordLength} characters.");
        }

        if (defined('PASSWORD_COMPLEXITY') && PASSWORD_COMPLEXITY) {
            if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                throw new InvalidArgumentException('Password must contain at least 1 uppercase letter and 1 number.');
            }
        }

        return [
            'company_name' => $companyName,
            'subdomain' => $subdomain,
            'email' => $email,
            'password' => $password,
            'referral_code' => $referralCode,
        ];
    }

    public function ensureAvailability(array $normalized): void {
        if ($this->db->query("SELECT id FROM companies WHERE subdomain = ? LIMIT 1", [$normalized['subdomain']])->fetch()) {
            throw new RuntimeException('Subdomain is already taken.', 409);
        }

        if ($this->db->query("SELECT id FROM users WHERE email = ? LIMIT 1", [$normalized['email']])->fetch()) {
            throw new RuntimeException('This email is already registered.', 409);
        }
    }

    public function registerTenant(array $normalized): array {
        $this->db->beginTransaction();

        try {
            $starterPlan = $this->resolveStarterPlan();
            $tenantId = $this->createCompanyRecord($normalized['company_name'], $normalized['subdomain'], $starterPlan);
            $roleId = $this->createOrResolveAdminRole($tenantId, $starterPlan);
            $username = $this->generateUniqueUsername($tenantId, $normalized['email'], $normalized['subdomain']);
            $passwordHash = password_hash($normalized['password'], PASSWORD_BCRYPT, ['cost' => 12]);

            $this->db->query(
                "INSERT INTO users
                 (company_id, username, email, password, full_name, role, role_id, is_active, is_super_admin)
                 VALUES (?, ?, ?, ?, 'Admin User', 'admin', ?, 1, 0)",
                [$tenantId, $username, $normalized['email'], $passwordHash, $roleId]
            );
            $userId = (int)$this->db->lastInsertId();

            $this->db->query("UPDATE companies SET owner_user_id = ? WHERE id = ?", [$userId, $tenantId]);
            $this->seedCompanySettings($tenantId, $normalized['company_name'], $normalized['email']);
            $this->seedDefaultUnits($tenantId);

            $this->referralModel->ensureCompanyReferralCode($tenantId);
            if ($normalized['referral_code'] !== '') {
                $assign = $this->referralModel->assignReferralToCompany($tenantId, $normalized['referral_code']);
                if (empty($assign['success'])) {
                    throw new RuntimeException($assign['message'] ?? 'Invalid referral code.');
                }
            }

            $this->db->commit();

            return [
                'tenant_id' => $tenantId,
                'subdomain' => $normalized['subdomain'],
                'username' => $username,
                'user_id' => $userId,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function resolveStarterPlan(): array {
        $starterPlan = $this->db->query(
            "SELECT id, slug, name, max_users, max_products
             FROM saas_plans
             WHERE id = 1
             LIMIT 1"
        )->fetch() ?: [];

        $starterPlanId = (int)($starterPlan['id'] ?? 1);
        $starterLegacy = strtolower(trim((string)($starterPlan['slug'] ?? $starterPlan['name'] ?? 'starter')));
        if ($starterLegacy === '' || !in_array($starterLegacy, ['starter', 'professional', 'enterprise'], true)) {
            $starterLegacy = 'starter';
        }

        return [
            'id' => $starterPlanId,
            'slug' => $starterLegacy,
            'max_users' => max(1, (int)($starterPlan['max_users'] ?? 3)),
            'max_products' => max(1, (int)($starterPlan['max_products'] ?? 500)),
        ];
    }

    private function createCompanyRecord(string $companyName, string $subdomain, array $starterPlan): int {
        $this->db->query(
            "INSERT INTO companies
             (name, subdomain, saas_plan_id, subscription_status, trial_ends_at, plan, status, max_users, max_products, slug)
             VALUES (?, ?, ?, 'trial', DATE_ADD(NOW(), INTERVAL 14 DAY), ?, 'active', ?, ?, ?)",
            [$companyName, $subdomain, $starterPlan['id'], $starterPlan['slug'], $starterPlan['max_users'], $starterPlan['max_products'], $subdomain]
        );

        return (int)$this->db->lastInsertId();
    }

    private function createOrResolveAdminRole(int $tenantId, array $plan): int {
        try {
            $this->db->query(
                "INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system)
                 VALUES (?, ?, 'Administrator', 'Full tenant-level access', 0, 1)",
                [$tenantId, 'tenant_admin_' . $tenantId]
            );
            $roleId = (int)$this->db->lastInsertId();
            $this->syncTenantAdminPermissions($roleId, $plan);
            return $roleId;
        } catch (Throwable $e) {
            $role = $this->db->query(
                "SELECT id
                 FROM roles
                 WHERE company_id = ?
                   AND IFNULL(is_super_admin, 0) = 0
                   AND (
                        LOWER(name) IN ('admin', 'administrator', 'owner')
                        OR LOWER(name) LIKE 'tenant_admin_%'
                        OR LOWER(display_name) LIKE '%admin%'
                   )
                 ORDER BY (company_id = ?) DESC, id ASC
                 LIMIT 1",
                [$tenantId, $tenantId]
            )->fetch();

            $resolvedId = (int)($role['id'] ?? 0);
            if ($resolvedId > 0) {
                $this->syncTenantAdminPermissions($resolvedId, $plan);
                return $resolvedId;
            }

            throw new RuntimeException('No assignable tenant admin role is available.');
        }
    }

    private function syncTenantAdminPermissions(int $roleId, array $plan): void {
        $permissionNames = SaaSBillingHelper::tenantAdminPermissionNames($plan);
        $this->db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
        if (empty($permissionNames)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($permissionNames), '?'));
        $permissions = $this->db->query(
            "SELECT id FROM permissions WHERE name IN ($placeholders) ORDER BY id ASC",
            $permissionNames
        )->fetchAll();
        if (!is_array($permissions)) {
            return;
        }

        foreach ($permissions as $permission) {
            $this->db->query(
                "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                [$roleId, (int)$permission['id']]
            );
        }
    }

    private function generateUniqueUsername(int $tenantId, string $email, string $subdomain): string {
        $base = strtolower((string)preg_replace('/[^a-z0-9_]/', '_', strstr($email, '@', true) ?: $subdomain));
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'owner';
        }
        $base = substr($base, 0, 28);

        $candidate = $base;
        $counter = 1;
        while ((int)$this->db->query(
            "SELECT COUNT(*) FROM users WHERE company_id = ? AND username = ?",
            [$tenantId, $candidate]
        )->fetchColumn() > 0) {
            $counter++;
            $candidate = substr($base, 0, 24) . '_' . $counter;
            if ($counter > 999) {
                $candidate = substr($base, 0, 22) . '_' . bin2hex(random_bytes(2));
                break;
            }
        }

        return $candidate;
    }

    private function seedCompanySettings(int $tenantId, string $companyName, string $email): void {
        $this->db->query(
            "INSERT INTO company_settings
             (company_id, company_name, company_email, company_phone, company_address, company_city, company_state, company_country, currency_symbol, currency_code, enable_gst, enable_tax, tax_rate, low_stock_threshold, invoice_prefix, purchase_prefix, payment_prefix, receipt_prefix)
             VALUES (?, ?, ?, '', '', '', '', 'India', 'Rs', 'INR', 1, 1, 18, 10, 'INV-', 'PUR-', 'PAY-', 'REC-')",
            [$tenantId, $companyName, $email]
        );
    }

    private function seedDefaultUnits(int $tenantId): void {
        foreach ($this->defaultUnits() as $unit) {
            $this->db->query(
                "INSERT INTO units (company_id, name, short_name) VALUES (?, ?, ?)",
                [$tenantId, $unit['name'], $unit['short_name']]
            );
        }
    }

    private function sanitize($value): string {
        if ($value === null || is_array($value)) {
            return '';
        }

        $clean = Helper::decodeHtmlEntities((string)$value);
        $clean = strip_tags($clean);
        return trim($clean);
    }

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
