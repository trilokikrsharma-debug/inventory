<?php
/**
 * Tenant Onboarding Controller
 *
 * Public API endpoint for creating a tenant company + owner user.
 */
class TenantOnboardingController extends Controller {
    private const RESERVED_SUBDOMAINS = [
        'www', 'admin', 'api', 'app', 'mail', 'smtp', 'imap', 'pop', 'cpanel',
        'webmail', 'ftp', 'sftp', 'ssh', 'root', 'support', 'help', 'blog',
        'status', 'billing', 'platform', 'dashboard', 'login', 'signup',
        'demo', 'staging', 'dev', 'test', 'autodiscover', 'm', 'mobile'
    ];

    protected $allowedActions = ['index', 'register'];

    public function index() {
        $this->register();
    }

    public function register() {
        header('Content-Type: application/json');

        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::attempt('register_ip:' . $ip, 5, 3600)) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many registration attempts. Please try again in an hour.']);
            return;
        }
        if (!RateLimiter::attempt('register_global', 50, 3600)) {
            http_response_code(429);
            echo json_encode(['error' => 'Registration limit reached. Please try again later.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $companyName = trim($this->sanitize($input['company_name'] ?? ''));
        $subdomain = trim(strtolower($this->sanitize($input['subdomain'] ?? '')));
        $email = trim(strtolower((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');
        $referralCode = strtoupper(trim((string)($input['referral_code'] ?? '')));

        if ($companyName === '' || $subdomain === '' || $email === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['error' => 'All fields (company_name, subdomain, email, password) are required.']);
            return;
        }

        if (!preg_match('/^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]$/', $subdomain)) {
            http_response_code(400);
            echo json_encode(['error' => 'Subdomain must be 3-63 characters and use lowercase letters, numbers, or internal dashes only.']);
            return;
        }

        if (in_array($subdomain, self::RESERVED_SUBDOMAINS, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'This subdomain is reserved. Please choose another one.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'A valid email address is required.']);
            return;
        }

        $minPasswordLength = defined('PASSWORD_MIN_LENGTH') ? max(6, (int)PASSWORD_MIN_LENGTH) : 8;
        if (strlen($password) < $minPasswordLength) {
            http_response_code(400);
            echo json_encode(['error' => "Password must be at least {$minPasswordLength} characters."]);
            return;
        }
        if (defined('PASSWORD_COMPLEXITY') && PASSWORD_COMPLEXITY) {
            if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must contain at least 1 uppercase letter and 1 number.']);
                return;
            }
        }

        $db = Database::getInstance();

        $existsSubdomain = $db->query("SELECT id FROM companies WHERE subdomain = ? LIMIT 1", [$subdomain])->fetch();
        if ($existsSubdomain) {
            http_response_code(409);
            echo json_encode(['error' => 'Subdomain is already taken.']);
            return;
        }

        $existsEmail = $db->query("SELECT id FROM users WHERE email = ? LIMIT 1", [$email])->fetch();
        if ($existsEmail) {
            http_response_code(409);
            echo json_encode(['error' => 'This email is already registered.']);
            return;
        }

        $db->beginTransaction();
        try {
            $starterPlan = $db->query(
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
            $starterMaxUsers = max(1, (int)($starterPlan['max_users'] ?? 3));
            $starterMaxProducts = max(1, (int)($starterPlan['max_products'] ?? 500));

            $db->query(
                "INSERT INTO companies
                 (name, subdomain, saas_plan_id, subscription_status, trial_ends_at, plan, status, max_users, max_products, slug)
                 VALUES (?, ?, ?, 'trial', DATE_ADD(NOW(), INTERVAL 14 DAY), ?, 'active', ?, ?, ?)",
                [$companyName, $subdomain, $starterPlanId, $starterLegacy, $starterMaxUsers, $starterMaxProducts, $subdomain]
            );
            $tenantId = (int)$db->lastInsertId();

            $roleId = $this->createOrResolveAdminRole($db, $tenantId, $starterPlan);

            $username = $this->generateUniqueUsername($db, $tenantId, $email, $subdomain);
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $db->query(
                "INSERT INTO users
                 (company_id, username, email, password, full_name, role, role_id, is_active, is_super_admin)
                 VALUES (?, ?, ?, ?, 'Admin User', 'admin', ?, 1, 0)",
                [$tenantId, $username, $email, $passwordHash, $roleId]
            );
            $userId = (int)$db->lastInsertId();

            $db->query("UPDATE companies SET owner_user_id = ? WHERE id = ?", [$userId, $tenantId]);

            $db->query(
                "INSERT INTO company_settings
                 (company_id, company_name, company_email, company_phone, company_address, company_city, company_state, company_country, currency_symbol, currency_code, enable_gst, enable_tax, tax_rate, low_stock_threshold, invoice_prefix, purchase_prefix, payment_prefix, receipt_prefix)
                 VALUES (?, ?, ?, '', '', '', '', 'India', 'Rs', 'INR', 1, 1, 18, 10, 'INV-', 'PUR-', 'PAY-', 'REC-')",
                [$tenantId, $companyName, $email]
            );

            foreach ($this->defaultUnits() as $unit) {
                $db->query(
                    "INSERT INTO units (company_id, name, short_name) VALUES (?, ?, ?)",
                    [$tenantId, $unit['name'], $unit['short_name']]
                );
            }

            $referralModel = new Referral();
            $referralModel->ensureCompanyReferralCode($tenantId);
            if ($referralCode !== '') {
                $assign = $referralModel->assignReferralToCompany($tenantId, $referralCode);
                if (empty($assign['success'])) {
                    throw new \RuntimeException($assign['message'] ?? 'Invalid referral code.');
                }
            }

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Company registered successfully. You can now log in.',
                'tenant_id' => $tenantId,
                'subdomain' => $subdomain,
                'username' => $username,
            ]);
        } catch (\Throwable $e) {
            $db->rollback();
            error_log('[Onboarding] Failed to register tenant: ' . $e->getMessage());

            $error = 'An internal error occurred during registration.';
            if (stripos($e->getMessage(), 'duplicate') !== false && stripos($e->getMessage(), 'email') !== false) {
                $error = 'This email is already registered.';
            } elseif (stripos($e->getMessage(), 'duplicate') !== false && stripos($e->getMessage(), 'subdomain') !== false) {
                $error = 'Subdomain is already taken.';
            } elseif (stripos($e->getMessage(), 'referral') !== false) {
                $error = $e->getMessage();
            }

            http_response_code(500);
            echo json_encode(['error' => $error]);
        }
    }

    private function createOrResolveAdminRole(Database $db, int $tenantId, array $plan): int {
        try {
            $db->query(
                "INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system)
                 VALUES (?, ?, 'Administrator', 'Full tenant-level access', 0, 1)",
                [$tenantId, 'tenant_admin_' . $tenantId]
            );
            $roleId = (int)$db->lastInsertId();
            $this->syncTenantAdminPermissions($db, $roleId, $plan);
            return $roleId;
        } catch (\Throwable $e) {
            $role = $db->query(
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
                $this->syncTenantAdminPermissions($db, $resolvedId, $plan);
                return $resolvedId;
            }

            throw new \RuntimeException('No assignable tenant admin role is available.');
        }
    }

    private function syncTenantAdminPermissions(Database $db, int $roleId, array $plan): void {
        $permissionNames = SaaSBillingHelper::tenantAdminPermissionNames($plan);
        $db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
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
                "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                [$roleId, (int)$permission['id']]
            );
        }
    }

    private function generateUniqueUsername(Database $db, int $tenantId, string $email, string $subdomain): string {
        $base = strtolower((string)preg_replace('/[^a-z0-9_]/', '_', strstr($email, '@', true) ?: $subdomain));
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'owner';
        }
        $base = substr($base, 0, 28);

        $candidate = $base;
        $counter = 1;
        while ((int)$db->query(
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

    /**
     * Common starter units seeded for API onboarding tenants.
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
