<?php
class DemoLoginService {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: Database::getInstance();
    }

    public function resolveDemoSession(): array {
        $company = $this->db->query(
            "SELECT * FROM companies WHERE is_demo = 1 AND status = 'active' LIMIT 1"
        )->fetch();

        if (!$company) {
            throw new RuntimeException('Demo mode is not available at the moment.');
        }

        $companyId = (int)($company['id'] ?? 0);
        $demoEmail = 'demo+' . $companyId . '@invenbill.com';

        $user = $this->findDemoUser($companyId, $demoEmail);
        if (!$user) {
            $user = $this->createDemoUser($companyId, $demoEmail);
        }

        if (!$user) {
            throw new RuntimeException('Could not set up demo account.');
        }

        return [
            'company' => $company,
            'user' => $this->sanitizeSessionUser($user),
        ];
    }

    private function findDemoUser(int $companyId, string $demoEmail): ?array {
        $user = $this->db->query(
            "SELECT * FROM users
             WHERE company_id = ?
               AND is_active = 1
               AND deleted_at IS NULL
               AND (username = 'demo' OR email = ?)
             ORDER BY id ASC
             LIMIT 1",
            [$companyId, $demoEmail]
        )->fetch();

        return is_array($user) ? $user : null;
    }

    private function createDemoUser(int $companyId, string $demoEmail): ?array {
        $roleId = $this->resolveDemoRoleId($companyId);
        $username = $this->generateDemoUsername($companyId);
        $demoPassword = trim((string)(getenv('DEMO_PASSWORD') ?: ''));
        if ($demoPassword === '') {
            $demoPassword = bin2hex(random_bytes(16));
        }
        $hashedPassword = password_hash($demoPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $this->db->query(
                "INSERT INTO users
                 (company_id, username, email, password, full_name, role, role_id, is_active, is_super_admin)
                 VALUES (?, ?, ?, ?, 'Demo User', 'admin', ?, 1, 0)",
                [$companyId, $username, $demoEmail, $hashedPassword, $roleId]
            );

            $user = $this->db->query(
                "SELECT * FROM users WHERE id = ? LIMIT 1",
                [(int)$this->db->lastInsertId()]
            )->fetch();

            return is_array($user) ? $user : null;
        } catch (\PDOException $e) {
            $user = $this->db->query(
                "SELECT * FROM users
                 WHERE company_id = ?
                   AND is_active = 1
                   AND deleted_at IS NULL
                   AND (username = ? OR email = ?)
                 ORDER BY id ASC
                 LIMIT 1",
                [$companyId, $username, $demoEmail]
            )->fetch();

            if (!is_array($user)) {
                throw $e;
            }

            return $user;
        }
    }

    private function resolveDemoRoleId(int $companyId): int {
        $role = $this->db->query(
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

    private function generateDemoUsername(int $companyId): string {
        $base = 'demo';
        $candidate = $base;
        $suffix = 1;

        while ((int)$this->db->query(
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

    private function sanitizeSessionUser(array $user): array {
        unset($user['password'], $user['twofa_secret'], $user['twofa_recovery_codes']);
        $user['is_super_admin'] = false;
        return $user;
    }
}
