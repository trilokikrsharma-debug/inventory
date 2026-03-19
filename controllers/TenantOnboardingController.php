<?php
/**
 * Tenant Onboarding Controller
 *
 * Public API endpoint for creating a tenant company + owner user.
 */
class TenantOnboardingController extends Controller {
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

        if (!preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            http_response_code(400);
            echo json_encode(['error' => 'Subdomain can only contain lowercase letters, numbers, and dashes.']);
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
            $db->query(
                "INSERT INTO companies
                 (name, subdomain, saas_plan_id, subscription_status, trial_ends_at, plan, status, max_users, max_products, slug)
                 VALUES (?, ?, 1, 'trial', DATE_ADD(NOW(), INTERVAL 14 DAY), 'starter', 'active', 3, 500, ?)",
                [$companyName, $subdomain, $subdomain]
            );
            $tenantId = (int)$db->lastInsertId();

            $roleId = $this->createOrResolveAdminRole($db, $tenantId);

            $username = $this->generateUniqueUsername($db, $tenantId, $email, $subdomain);
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

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

    private function createOrResolveAdminRole(Database $db, int $tenantId): int {
        try {
            $db->query(
                "INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system)
                 VALUES (?, 'admin', 'Administrator', 'Full tenant-level access', 0, 1)",
                [$tenantId]
            );
            return (int)$db->lastInsertId();
        } catch (\Throwable $e) {
            $role = $db->query(
                "SELECT id
                 FROM roles
                 WHERE (company_id = ? OR company_id IS NULL)
                   AND IFNULL(is_super_admin, 0) = 0
                   AND name = 'admin'
                 ORDER BY (company_id = ?) DESC, id ASC
                 LIMIT 1",
                [$tenantId, $tenantId]
            )->fetch();

            $resolvedId = (int)($role['id'] ?? 0);
            if ($resolvedId > 0) {
                return $resolvedId;
            }

            throw new \RuntimeException('No assignable tenant admin role is available.');
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
}
