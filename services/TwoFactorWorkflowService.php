<?php

class TwoFactorWorkflowService {
    private $db;

    public function __construct($db = null) {
        $this->db = $db;
    }

    private function db() {
        if ($this->db === null) {
            $this->db = Database::getInstance();
        }

        return $this->db;
    }

    public function buildSetupPayload(array $user): array {
        $userId = (int)($user['id'] ?? 0);
        $email = (string)($user['email'] ?? $user['username'] ?? '');
        $secret = TwoFactorService::generateSecret();

        return [
            'user_id' => $userId,
            'email' => $email,
            'secret' => $secret,
            'otpAuthUrl' => TwoFactorService::getOtpAuthUrl($secret, $email),
            'isEnabled' => $userId > 0 ? TwoFactorService::isEnabled($userId) : false,
        ];
    }

    public function loadLoginContext(int $userId): ?array {
        $db = $this->db();
        $row = $db->query(
            "SELECT u.*, c.name AS company_name, c.status AS company_status, c.is_demo, c.plan, c.saas_plan_id,
                    c.subscription_status, c.trial_ends_at, c.max_users, c.max_products
             FROM users u
             LEFT JOIN companies c ON u.company_id = c.id
             WHERE u.id = ?",
            [$userId]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $isSuperAdmin = !empty($row['is_super_admin']);
        if (!$isSuperAdmin && !empty($row['role_id'])) {
            try {
                $role = $db->query(
                    "SELECT is_super_admin FROM roles WHERE id = ?",
                    [$row['role_id']]
                )->fetch(\PDO::FETCH_ASSOC);
                if ($role && !empty($role['is_super_admin'])) {
                    $isSuperAdmin = true;
                }
            } catch (\Throwable $e) {
                error_log('[RBAC] Failed to load role during 2FA login: ' . $e->getMessage());
            }
        }

        $companyId = (int)($row['company_id'] ?? 0);
        $company = null;

        if (!$isSuperAdmin) {
            if ($companyId <= 0) {
                return null;
            }

            try {
                $company = $db->query(
                    "SELECT id, name, status, is_demo, plan, saas_plan_id, subscription_status, trial_ends_at, max_users, max_products
                     FROM companies
                     WHERE id = ? AND status = 'active'",
                    [$companyId]
                )->fetch(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {
                $company = null;
            }

            if (!$company) {
                return null;
            }
        }

        return [
            'user' => $row,
            'company' => $company,
            'company_id' => $companyId,
            'is_super_admin' => $isSuperAdmin,
        ];
    }

    public function sanitizeSessionUser(array $user, bool $isSuperAdmin): array {
        unset(
            $user['password'],
            $user['twofa_secret'],
            $user['twofa_recovery_codes'],
            $user['company_status'],
            $user['company_name']
        );
        $user['is_super_admin'] = $isSuperAdmin || !empty($user['is_super_admin']);
        return $user;
    }
}
