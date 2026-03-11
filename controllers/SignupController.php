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
            $this->redirect('index.php?page=dashboard');
            return;
        }

        $error = '';
        $success = '';
        $formData = [];

        if ($this->isPost()) {
            $this->validateCSRF();

            $companyName = trim($this->sanitize($this->post('company_name', '')));
            $ownerName   = trim($this->sanitize($this->post('full_name', '')));
            $email       = trim(strtolower($this->post('email', '')));
            $phone       = trim($this->sanitize($this->post('phone', '')));
            $username    = trim(strtolower($this->sanitize($this->post('username', ''))));
            $password    = $this->post('password', '');
            $confirmPass = $this->post('confirm_password', '');
            $referralCode = strtoupper(trim((string)$this->post('referral_code', '')));

            $formData = compact('companyName', 'ownerName', 'email', 'phone', 'username', 'referralCode');

            // Validation
            if (empty($companyName) || strlen($companyName) < 2) {
                $error = 'Company name is required (minimum 2 characters).';
            } elseif (empty($ownerName) || strlen($ownerName) < 2) {
                $error = 'Full name is required (minimum 2 characters).';
            } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'A valid email address is required.';
            } elseif (empty($username) || strlen($username) < 3 || !preg_match('/^[a-z0-9_]+$/', $username)) {
                $error = 'Username must be at least 3 characters (lowercase letters, numbers, underscore only).';
            } elseif (empty($password) || strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirmPass) {
                $error = 'Passwords do not match.';
            } else {
                // Check email uniqueness (globally)
                $userModel = new UserModel();
                if ($userModel->emailExists($email)) {
                    $error = 'This email is already registered. Please log in or use a different email.';
                }
            }

            if (empty($error)) {
                $db = Database::getInstance();
                $db->beginTransaction();

                try {
                // 1. Create company
                $slug = $this->generateSlug($companyName);
                $db->query(
                    "INSERT INTO companies (name, slug, plan, status, max_users, max_products) VALUES (?, ?, 'starter', 'active', 3, 500)",
                    [$companyName, $slug]
                );
                $companyId = $db->lastInsertId();

                // 2. Create owner user (tenant_owner = role_id=1 Administrator, NEVER is_super_admin)
                // SECURITY: is_super_admin is ALWAYS 0 here. Platform super-admins are set via DB ONLY.
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $db->query(
                    "INSERT INTO users (company_id, username, email, password, full_name, phone, role, role_id, is_active, is_super_admin) VALUES (?, ?, ?, ?, ?, ?, 'admin', 1, 1, 0)",
                    [$companyId, $username, $email, $hashedPassword, $ownerName, $phone]
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
                error_log('[SIGNUP] Error: ' . $e->getMessage());
                $error = 'Registration failed. Please try again or contact support.';
            }
            }
        }

        $this->renderPartial('auth.signup', [
            'error' => $error,
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

        // Ensure uniqueness
        $db = Database::getInstance();
        $original = $slug;
        $counter = 1;
        while ($db->query("SELECT COUNT(*) FROM companies WHERE slug = ?", [$slug])->fetchColumn() > 0) {
            $slug = $original . '-' . $counter++;
        }
        return $slug;
    }
}
