<?php
/**
 * User-management policy service.
 *
 * Keeps RBAC role resolution and protected-target checks out of the controller
 * so the controller only handles request flow and presentation concerns.
 */
class UserManagementService {
    private $db;
    private $userModel;

    public function __construct($db = null, $userModel = null) {
        $this->db = $db ?: Database::getInstance();
        $this->userModel = $userModel ?: new UserModel();
    }

    public function loadAssignableRoles(): array {
        try {
            $tenantId = Tenant::id();

            if (Session::isSuperAdmin()) {
                return $this->db->query(
                    "SELECT id, name, display_name, company_id, is_super_admin
                     FROM roles
                     ORDER BY company_id IS NULL DESC, company_id ASC, display_name ASC, id ASC"
                )->fetchAll();
            }

            if ($tenantId !== null) {
                $roles = $this->db->query(
                    "SELECT id, name, display_name, company_id, is_super_admin
                     FROM roles
                     WHERE (company_id IS NULL OR company_id = ?)
                       AND IFNULL(is_super_admin, 0) = 0
                     ORDER BY company_id IS NULL ASC, display_name ASC, id ASC",
                    [$tenantId]
                )->fetchAll();

                return $this->deduplicateAssignableRoles($roles, $tenantId);
            }

            return $this->db->query(
                "SELECT id, name, display_name, company_id, is_super_admin
                 FROM roles
                 WHERE company_id IS NULL
                   AND IFNULL(is_super_admin, 0) = 0
                 ORDER BY display_name ASC, id ASC"
            )->fetchAll();
        } catch (\Exception $e) {
            error_log('[RBAC] Failed to load roles: ' . $e->getMessage());
            return [];
        }
    }

    public function resolveAssignableRole(int $roleId): array {
        $default = $this->resolveFallbackRole();
        if ($roleId <= 0) {
            return $default;
        }

        try {
            $role = $this->db->query(
                "SELECT id, name, display_name, company_id, is_super_admin
                 FROM roles
                 WHERE id = ?",
                [$roleId]
            )->fetch();

            if (!$role || !$this->isRoleAssignable($role)) {
                if ($role && !$this->isRoleAssignable($role)) {
                    error_log('[RBAC] Blocked cross-tenant role assignment for role ID ' . $roleId);
                }
                return $default;
            }

            $isSuperAdminRole = (bool)($role['is_super_admin'] ?? false);
            if ($isSuperAdminRole && !Session::isSuperAdmin()) {
                $username = (string)(Session::get('user')['username'] ?? '?');
                error_log('[SECURITY] Privilege escalation blocked. User ' . $username . ' attempted to assign super admin role.');
                return $default;
            }

            return [
                'role_id' => (int)$role['id'],
                'role_name' => (string)$role['display_name'],
                'legacy_role' => $this->legacyRoleFor($role),
                'is_super_admin' => $isSuperAdminRole,
            ];
        } catch (\Exception $e) {
            error_log('[RBAC] Failed to resolve role: ' . $e->getMessage());
            return $default;
        }
    }

    public function guardManagedUserTarget(int $userId, string $action): array {
        $targetUser = $this->userModel->find($userId);
        if (!$targetUser) {
            return [
                'allowed' => false,
                'message' => 'User not found.',
                'user' => null,
            ];
        }

        if ($this->targetUserHasSuperAdminPrivileges($targetUser) && !Session::isSuperAdmin()) {
            $username = (string)(Session::get('user')['username'] ?? '?');
            $event = $action === 'delete' ? 'delete' : 'reset password for';
            Helper::securityLog(
                'PRIVILEGE_VIOLATION',
                'User ' . $username . ' attempted to ' . $event . ' super-admin user ID: ' . $userId
            );

            return [
                'allowed' => false,
                'message' => $action === 'delete'
                    ? 'Cannot delete a super admin account.'
                    : 'You cannot reset the password of a super admin account.',
                'user' => $targetUser,
            ];
        }

        return [
            'allowed' => true,
            'message' => '',
            'user' => $targetUser,
        ];
    }

    public function applyRoleSessionState(array $currentUser, array $role): array {
        $currentUser['role'] = $role['legacy_role'];
        $currentUser['role_id'] = $role['role_id'];
        $currentUser['is_super_admin'] = $role['is_super_admin'];
        return $currentUser;
    }

    private function deduplicateAssignableRoles(array $roles, int $tenantId): array {
        usort($roles, static function (array $a, array $b) use ($tenantId): int {
            $aCompany = isset($a['company_id']) && $a['company_id'] !== null ? (int)$a['company_id'] : null;
            $bCompany = isset($b['company_id']) && $b['company_id'] !== null ? (int)$b['company_id'] : null;
            $aScore = ($aCompany === $tenantId) ? 0 : 1;
            $bScore = ($bCompany === $tenantId) ? 0 : 1;
            if ($aScore !== $bScore) {
                return $aScore <=> $bScore;
            }

            $byName = strcasecmp((string)($a['display_name'] ?? ''), (string)($b['display_name'] ?? ''));
            if ($byName !== 0) {
                return $byName;
            }

            return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
        });

        $seen = [];
        $deduped = [];
        foreach ($roles as $role) {
            $keys = [];
            $nameKey = strtolower(trim((string)($role['name'] ?? '')));
            $displayKey = strtolower(trim((string)($role['display_name'] ?? '')));
            if ($nameKey !== '') {
                $keys[] = 'name:' . $nameKey;
            }
            if ($displayKey !== '') {
                $keys[] = 'display:' . $displayKey;
            }

            $skip = false;
            foreach ($keys as $key) {
                if (isset($seen[$key])) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            foreach ($keys as $key) {
                $seen[$key] = true;
            }
            $deduped[] = $role;
        }

        return $deduped;
    }

    private function resolveFallbackRole(): array {
        try {
            $tenantId = Tenant::id();
            $sql = "SELECT id, name, display_name, company_id, is_super_admin
                    FROM roles
                    WHERE IFNULL(is_super_admin, 0) = 0";
            $params = [];

            if ($tenantId !== null) {
                $sql .= " AND (company_id IS NULL OR company_id = ?)";
                $params[] = $tenantId;
            } else {
                $sql .= " AND company_id IS NULL";
            }

            $sql .= " ORDER BY
                        CASE
                            WHEN LOWER(name) IN ('admin', 'tenant_admin', 'owner', 'administrator') THEN 0
                            WHEN LOWER(display_name) LIKE '%admin%' THEN 1
                            ELSE 2
                        END,
                        company_id IS NULL DESC,
                        id ASC
                      LIMIT 1";

            $role = $this->db->query($sql, $params)->fetch();
            if ($role) {
                return [
                    'role_id' => (int)$role['id'],
                    'role_name' => (string)$role['display_name'],
                    'legacy_role' => $this->legacyRoleFor($role),
                    'is_super_admin' => false,
                ];
            }
        } catch (\Throwable $e) {
            error_log('[RBAC] Failed to resolve fallback role: ' . $e->getMessage());
        }

        return [
            'role_id' => null,
            'role_name' => 'Staff',
            'legacy_role' => 'staff',
            'is_super_admin' => false,
        ];
    }

    private function isRoleAssignable(array $role): bool {
        if (Session::isSuperAdmin()) {
            return true;
        }

        if (!empty($role['is_super_admin'])) {
            return false;
        }

        $tenantId = Tenant::id();
        $companyId = isset($role['company_id']) && $role['company_id'] !== null ? (int)$role['company_id'] : null;

        if ($tenantId === null) {
            return $companyId === null;
        }

        return $companyId === null || $companyId === (int)$tenantId;
    }

    private function legacyRoleFor(array $role): string {
        $name = strtolower(trim((string)($role['name'] ?? '')));
        $display = strtolower(trim((string)($role['display_name'] ?? '')));

        if (!empty($role['is_super_admin'])) {
            return 'admin';
        }

        if (
            $name === 'admin'
            || $name === 'tenant_admin'
            || $name === 'owner'
            || $name === 'administrator'
            || strpos($display, 'admin') !== false
        ) {
            return 'admin';
        }

        return 'staff';
    }

    private function targetUserHasSuperAdminPrivileges(array $targetUser): bool {
        if (!empty($targetUser['is_super_admin'])) {
            return true;
        }

        $roleId = (int)($targetUser['role_id'] ?? 0);
        if ($roleId <= 0) {
            return false;
        }

        try {
            $targetRole = $this->db->query(
                "SELECT is_super_admin FROM roles WHERE id = ?",
                [$roleId]
            )->fetch();

            return !empty($targetRole['is_super_admin']);
        } catch (\Throwable $e) {
            error_log('[RBAC] Failed to check target role: ' . $e->getMessage());
            return false;
        }
    }
}
