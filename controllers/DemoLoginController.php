<?php
/**
 * Demo Login Controller - One-click demo access.
 */
class DemoLoginController extends Controller {
    protected $allowedActions = ['index'];

    public function index() {
        try {
            $db = Database::getInstance();

            $company = $db->query(
                "SELECT * FROM companies WHERE is_demo = 1 AND status = 'active' LIMIT 1"
            )->fetch();

            if (!$company) {
                Session::setFlash('error', 'Demo mode is not available at the moment.');
                header("Location: " . APP_URL . "/index.php?page=login");
                exit;
            }

            $companyId = (int)$company['id'];
            $demoEmail = 'demo+' . $companyId . '@invenbill.com';

            $user = $db->query(
                "SELECT * FROM users
                 WHERE company_id = ?
                   AND is_active = 1
                   AND deleted_at IS NULL
                   AND (username = 'demo' OR email = ?)
                 ORDER BY id ASC
                 LIMIT 1",
                [$companyId, $demoEmail]
            )->fetch();

            if (!$user) {
                $roleId = $this->resolveDemoRoleId($db, $companyId);
                $username = $this->generateDemoUsername($db, $companyId);
                $demoPassword = getenv('DEMO_PASSWORD') ?: 'Demo@2026Secure';
                $hashedPassword = password_hash($demoPassword, PASSWORD_BCRYPT, ['cost' => 12]);

                try {
                    $db->query(
                        "INSERT INTO users
                         (company_id, username, email, password, full_name, role, role_id, is_active, is_super_admin)
                         VALUES (?, ?, ?, ?, 'Demo User', 'admin', ?, 1, 0)",
                        [$companyId, $username, $demoEmail, $hashedPassword, $roleId]
                    );

                    $user = $db->query(
                        "SELECT * FROM users WHERE id = ? LIMIT 1",
                        [(int)$db->lastInsertId()]
                    )->fetch();
                } catch (\PDOException $e) {
                    // Handle race conditions gracefully.
                    $user = $db->query(
                        "SELECT * FROM users
                         WHERE company_id = ?
                           AND is_active = 1
                           AND deleted_at IS NULL
                           AND (username = ? OR email = ?)
                         ORDER BY id ASC
                         LIMIT 1",
                        [$companyId, $username, $demoEmail]
                    )->fetch();

                    if (!$user) {
                        throw $e;
                    }
                }
            }

            if (!$user) {
                Session::setFlash('error', 'Could not set up demo account.');
                header("Location: " . APP_URL . "/index.php?page=login");
                exit;
            }

            // SECURITY: Strip sensitive fields before storing in session
            unset($user['password'], $user['twofa_secret'], $user['twofa_recovery_codes']);
            $user['is_super_admin'] = false;

            session_regenerate_id(true);
            Session::set('user', $user);
            Tenant::set($companyId, $company);

            Session::setFlash('info', "Welcome to Demo Mode. Explore freely, demo data may reset.");
            header("Location: " . APP_URL . "/index.php?page=dashboard");
            exit;
        } catch (\Throwable $e) {
            error_log('[DEMO_LOGIN] Error: ' . $e->getMessage());
            Session::setFlash('error', 'Demo mode is temporarily unavailable.');
            header("Location: " . APP_URL . "/index.php?page=login");
            exit;
        }
    }

    private function resolveDemoRoleId(Database $db, int $companyId): int {
        $role = $db->query(
            "SELECT id
             FROM roles
             WHERE (company_id = ? OR company_id IS NULL)
               AND IFNULL(is_super_admin, 0) = 0
               AND (name = 'admin' OR is_system = 1)
             ORDER BY (company_id = ?) DESC, is_system DESC, id ASC
             LIMIT 1",
            [$companyId, $companyId]
        )->fetch();

        return (int)($role['id'] ?? 1);
    }

    private function generateDemoUsername(Database $db, int $companyId): string {
        $base = 'demo';
        $candidate = $base;
        $suffix = 1;

        while ((int)$db->query(
            "SELECT COUNT(*) FROM users WHERE company_id = ? AND username = ?",
            [$companyId, $candidate]
        )->fetchColumn() > 0) {
            $suffix++;
            $candidate = $base . $suffix;
            if ($suffix > 999) {
                $candidate = $base . bin2hex(random_bytes(2));
                break;
            }
        }

        return $candidate;
    }
}
