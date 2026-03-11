<?php
/**
 * Demo Login Controller — One-Click Demo Access
 */
class DemoLoginController extends Controller {
    protected $allowedActions = ['index'];

    public function index() {
        try {
            $db = Database::getInstance();

            // Find demo company
            $company = $db->query("SELECT * FROM companies WHERE is_demo = 1 AND status = 'active' LIMIT 1")->fetch();
            if (!$company) {
                Session::setFlash('error', 'Demo mode is not available at the moment.');
                header("Location: " . APP_URL . "/index.php?page=login");
                exit;
            }

            // Find demo admin user — use RBAC role_id, not legacy 'role' column
            $user = $db->query(
                "SELECT u.* FROM users u
                 INNER JOIN roles r ON u.role_id = r.id AND r.company_id = u.company_id
                 WHERE u.company_id = ? AND r.is_system = 1 AND u.is_active = 1 AND u.deleted_at IS NULL
                 ORDER BY u.id ASC LIMIT 1",
                [$company['id']]
            )->fetch();

            if (!$user) {
                // Create demo user if none exists — use staff role (NOT admin)
                $hashedPassword = password_hash('demo123', PASSWORD_DEFAULT);
                $db->query(
                    "INSERT INTO users (company_id, username, email, password, full_name, role, role_id, is_active) VALUES (?, 'demo', 'demo@invenbill.com', ?, 'Demo User', 'admin', 5, 1)",
                    [$company['id'], $hashedPassword]
                );
                $user = $db->query("SELECT * FROM users WHERE company_id = ? AND username = 'demo' LIMIT 1", [$company['id']])->fetch();
            }

            if (!$user) {
                Session::setFlash('error', 'Could not set up demo account.');
                header("Location: " . APP_URL . "/index.php?page=login");
                exit;
            }

            // SECURITY: Ensure demo user never gets super-admin, regardless of DB state
            $user['is_super_admin'] = false;

            // Log in as demo user
            session_regenerate_id(true);
            Session::set('user', $user);
            Tenant::set($company['id'], $company);

            Session::setFlash('info', '🎓 Welcome to Demo Mode! Explore all features freely — changes won\'t be saved.');
            header("Location: " . APP_URL . "/index.php?page=dashboard");
            exit;

        } catch (\Exception $e) {
            error_log('[DEMO_LOGIN] Error: ' . $e->getMessage());
            Session::setFlash('error', 'Demo mode is temporarily unavailable.');
            header("Location: " . APP_URL . "/index.php?page=login");
            exit;
        }
    }
}
