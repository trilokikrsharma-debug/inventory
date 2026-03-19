<?php
/**
 * User Model — Multi-Tenant Aware
 * 
 * Authentication flow:
 *  - authenticate() scopes user lookup by username within a company
 *  - For login, we first find the user by username/email, then verify company is active
 *  - Email is globally unique (for password reset etc), username is unique per company
 */
class UserModel extends Model {
    protected $table = 'users';

    /**
     * Authenticate user by username/email and password.
     * Note: At login, we don't have tenant context yet, so this is NOT tenant-scoped.
     * The AuthController will verify company status separately.
     */
    /**
     * Authenticate user by username/email and password.
     * 
     * SECURITY FIX: Returns first user with matching credentials AND an active company.
     * Old code used LIMIT 1, which caused cross-tenant login leakage if the same
     * username existed in multiple companies.
     * 
     * @param string $username Username or email
     * @param string $password Raw password
     * @return array|false  User row on success, false on failure
     */
    public function authenticate($username, $password, $companyId = null) {
        $username = trim((string)$username);
        $password = (string)$password;
        $companyId = $companyId !== null ? (int)$companyId : null;

        if ($username === '' || $password === '') {
            return false;
        }

        // If a tenant host resolved a company, search that tenant first so
        // usernames cannot bleed across tenants. Super-admins are checked after.
        if ($companyId !== null && $companyId > 0) {
            $tenantUsers = $this->db->query(
                "SELECT u.*, c.status AS company_status, c.name AS company_name
                 FROM {$this->table} u
                 INNER JOIN companies c ON c.id = u.company_id
                 WHERE c.id = ?
                   AND c.status = 'active'
                   AND (u.username = ? OR u.email = ?)
                   AND u.is_active = 1
                   AND u.deleted_at IS NULL
                 ORDER BY u.id ASC",
                [$companyId, $username, $username]
            )->fetchAll();

            foreach ($tenantUsers as $user) {
                if (password_verify($password, $user['password'])) {
                    unset($user['company_status'], $user['company_name']);
                    return $user;
                }
            }
        }

        $superAdmins = $this->db->query(
            "SELECT u.*, NULL AS company_status, NULL AS company_name
             FROM {$this->table} u
             WHERE (u.username = ? OR u.email = ?)
               AND u.is_active = 1
               AND u.deleted_at IS NULL
               AND u.is_super_admin = 1
             ORDER BY u.id ASC",
            [$username, $username]
        )->fetchAll();

        foreach ($superAdmins as $user) {
            if (password_verify($password, $user['password'])) {
                unset($user['company_status'], $user['company_name']);
                return $user;
            }
        }

        // Fallback for legacy deployments without host-based tenant resolution.
        if ($companyId !== null && $companyId > 0) {
            return false;
        }

        $users = $this->db->query(
            "SELECT u.*, c.status AS company_status, c.name AS company_name
             FROM {$this->table} u
             LEFT JOIN companies c ON u.company_id = c.id
             WHERE (u.username = ? OR u.email = ?)
               AND u.is_active = 1
               AND u.deleted_at IS NULL
             ORDER BY c.status = 'active' DESC, u.is_super_admin DESC, u.id ASC",
            [$username, $username]
        )->fetchAll();

        foreach ($users as $user) {
            if (!password_verify($password, $user['password'])) {
                continue;
            }

            if (!empty($user['is_super_admin'])) {
                unset($user['company_status'], $user['company_name']);
                return $user;
            }

            if (($user['company_status'] ?? '') !== 'active') {
                continue;
            }

            unset($user['company_status'], $user['company_name']);
            return $user;
        }

        return false;
    }

    /**
     * Create a new user with validation (returns structured result).
     * Called by UserController::create().
     *
     * @param array $data User data including raw password
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    public function createUser($data) {
        // Validate uniqueness before insert
        if (empty($data['username']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Username and password are required.'];
        }

        if ($this->usernameExists($data['username'])) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }

        if (!empty($data['email']) && $this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email already in use.'];
        }

        $minLen = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8;
        if (strlen($data['password']) < $minLen) {
            return ['success' => false, 'message' => "Password must be at least {$minLen} characters."];
        }

        // Enterprise password complexity: at least 1 uppercase + 1 digit
        if (defined('PASSWORD_COMPLEXITY') && PASSWORD_COMPLEXITY) {
            if (!preg_match('/[A-Z]/', $data['password']) || !preg_match('/[0-9]/', $data['password'])) {
                return ['success' => false, 'message' => 'Password must contain at least 1 uppercase letter and 1 number.'];
            }
        }

        $limitCheck = $this->checkUserLimitBeforeCreate();
        if (!$limitCheck['allowed']) {
            return ['success' => false, 'message' => $limitCheck['message']];
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        try {
            $id = $this->create($data);
            return ['success' => true, 'message' => 'User created.', 'id' => $id];
        } catch (\Exception $e) {
            error_log('[UserModel] createUser failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create user. Please try again.'];
        }
    }

    /**
     * Get all users with pagination (tenant-scoped).
     * Called by UserController::index().
     */
    public function getAllUsers($search = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $where = ["u.deleted_at IS NULL"];
        $params = [];

        if (Tenant::id() !== null) {
            $where[] = "u.company_id = ?";
            $params[] = Tenant::id();
        }
        if ($search) {
            $where[] = "(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $s = "%{$search}%";
            $params = array_merge($params, [$s, $s, $s]);
        }

        $whereClause = implode(' AND ', $where);

        $total = $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} u WHERE {$whereClause}", $params
        )->fetchColumn();

        $data = $this->db->query(
            "SELECT u.*, r.display_name as role_name
             FROM {$this->table} u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE {$whereClause}
             ORDER BY u.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        )->fetchAll();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage),
        ];
    }

    /**
     * Admin password reset — no current password required.
     * Called by UserController::resetPassword().
     */
    public function resetPassword($userId, $newPassword) {
        return $this->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }

    /**
     * Change password with current password verification.
     * Called by ProfileController::password().
     *
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->find($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        $minLen = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8;
        if (strlen($newPassword) < $minLen) {
            return ['success' => false, 'message' => "New password must be at least {$minLen} characters."];
        }
        // Enforce same complexity rules as createUser()
        if (defined('PASSWORD_COMPLEXITY') && PASSWORD_COMPLEXITY) {
            if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
                return ['success' => false, 'message' => 'Password must contain at least 1 uppercase letter and 1 number.'];
            }
        }
        $this->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
        return ['success' => true, 'message' => 'Password changed successfully.'];
    }

    /**
     * Update user theme preference (tenant-scoped via base update).
     * Called by ProfileController::updateTheme().
     */
    public function updateTheme($userId, $mode) {
        return $this->update($userId, ['theme_mode' => $mode]);
    }

    /**
     * Check if username exists within current tenant
     */
    public function usernameExists($username, $excludeId = null) {
        $where = ["username = ?", "deleted_at IS NULL"];
        $params = [$username];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        if ($excludeId) {
            $where[] = "id != ?";
            $params[] = $excludeId;
        }
        return $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetchColumn() > 0;
    }

    /**
     * Check if email exists globally (emails are globally unique)
     */
    public function emailExists($email, $excludeId = null) {
        $where = ["email = ?"];
        $params = [$email];
        if ($excludeId) {
            $where[] = "id != ?";
            $params[] = $excludeId;
        }
        return $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetchColumn() > 0;
    }

    /**
     * Get user count for current company (for plan limits)
     */
    public function getCompanyUserCount() {
        $where = ["deleted_at IS NULL", "is_active = 1"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetchColumn();
    }

    /**
     * Enforce tenant user limits before creating a new account.
     */
    private function checkUserLimitBeforeCreate(): array {
        $tenantId = Tenant::id();
        if ($tenantId === null) {
            return ['allowed' => true, 'message' => ''];
        }

        $currentUsers = (int)$this->getCompanyUserCount();
        if (Tenant::canUse('max_users', $currentUsers, 1)) {
            return ['allowed' => true, 'message' => ''];
        }

        $limit = (int)(Tenant::usageLimit('max_users') ?? 0);
        $message = $limit > 0
            ? 'User limit reached (' . $limit . '). Please upgrade to add more users.'
            : 'User limit reached for your plan. Please upgrade to add more users.';

        return ['allowed' => false, 'message' => $message];
    }
}
