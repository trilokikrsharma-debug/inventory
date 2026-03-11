<?php
/**
 * Tenant Onboarding Controller
 * 
 * Handles public company registration, admin account creation, and tenant provisioning.
 */
class TenantOnboardingController extends Controller {

    protected $allowedActions = ['index', 'register'];

    // If using ?page=api_saas_register, it might default to index or register
    public function index() {
        $this->register();
    }

    public function register() {
        // Must be POST for API
        if (!$this->isPost()) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        header('Content-Type: application/json');

        // ─── SECURITY FIX (API-5): Rate limit tenant registration ───
        // Without this, an attacker can create unlimited fake tenants via
        // automated POST requests, flooding the companies table (DoS).
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Per-IP limit: 5 registrations per hour
        if (!RateLimiter::attempt('register_ip:' . $ip, 5, 3600)) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many registration attempts. Please try again in an hour.']);
            return;
        }

        // Global limit: 50 registrations per hour across all IPs
        // Prevents distributed spam attacks
        if (!RateLimiter::attempt('register_global', 50, 3600)) {
            http_response_code(429);
            echo json_encode(['error' => 'Registration limit reached. Please try again later.']);
            return;
        }

        // Extract JSON payload if provided, else form data
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $companyName = $this->sanitize($input['company_name'] ?? '');
        $subdomain = $this->sanitize($input['subdomain'] ?? '');
        $email = $this->sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $referralCode = strtoupper(trim((string)($input['referral_code'] ?? '')));

        if (empty($companyName) || empty($subdomain) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'All fields (company_name, subdomain, email, password) are required.']);
            return;
        }

        // Validate subdomain format (alphanumeric and dashes only)
        if (!preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            http_response_code(400);
            echo json_encode(['error' => 'Subdomain can only contain lowercase letters, numbers, and dashes.']);
            return;
        }

        $db = Database::getInstance();

        // Check if subdomain exists
        $existsSubdomain = $db->query("SELECT id FROM companies WHERE subdomain = ?", [$subdomain])->fetch();
        if ($existsSubdomain) {
            http_response_code(409);
            echo json_encode(['error' => 'Subdomain is already taken.']);
            return;
        }

        // Note: we don't strictly check email existence globally if users can belong to multiple tenants,
        // but for simplicity of this MVP, let's just proceed. The unique constraint will catch it if same tenant.

        $db->beginTransaction();
        try {
            // 1. Create Tenant (Default plan = 1 Starter)
            $db->query(
                "INSERT INTO companies (name, subdomain, saas_plan_id, subscription_status, trial_ends_at, plan, slug) VALUES (?, ?, 1, 'trial', DATE_ADD(NOW(), INTERVAL 14 DAY), 'starter', ?)",
                [$companyName, $subdomain, $subdomain]
            );
            $tenantId = $db->getConnection()->lastInsertId();

            $referralModel = new Referral();
            $referralModel->ensureCompanyReferralCode((int)$tenantId);
            if ($referralCode !== '') {
                $assign = $referralModel->assignReferralToCompany((int)$tenantId, $referralCode);
                if (empty($assign['success'])) {
                    throw new \RuntimeException($assign['message'] ?? 'Invalid referral code.');
                }
            }

            // 2. Create Tenant-scoped Admin Role (is_super_admin=0 — tenant level only)
            // SECURITY: is_super_admin MUST remain 0. Platform superadmins are set via DB only.
            $db->query(
                "INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system) VALUES (?, 'admin', 'Administrator', 'Full tenant-level access', 0, 1)",
                [$tenantId]
            );
            $roleId = $db->getConnection()->lastInsertId();

            // 3. Create Admin User
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $db->query(
                "INSERT INTO users (company_id, name, email, password, role_id, status) VALUES (?, 'Admin User', ?, ?, ?, 'Active')",
                [$tenantId, $email, $passwordHash, $roleId]
            );
            $userId = $db->getConnection()->lastInsertId();

            // 4. Create default company settings
            $timezone = 'Asia/Kolkata';
            $db->query(
                "INSERT INTO company_settings (company_id, timezone, currency) VALUES (?, ?, ?)",
                [$tenantId, $timezone, 'INR']
            );

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Company registered successfully. You can now log in.',
                'tenant_id' => $tenantId,
                'subdomain' => $subdomain
            ]);

        } catch (Exception $e) {
            $db->rollback();
            error_log('[Onboarding] Failed to register tenant: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'An internal error occurred during registration.']);
        }
    }
}
