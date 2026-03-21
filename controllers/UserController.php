<?php
/**
 * User Controller - Admin user management
 */
class UserController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'resetPassword', 'toggleActive', 'delete'];


    public function index() {
        $this->requirePermission('users.view');
        $search = $this->get('search', '');
        $page   = max(1, (int)$this->get('pg', 1));
        $users  = (new UserModel())->getAllUsers($search, $page);
        $this->view('users.index', [
            'pageTitle' => 'User Management',
            'users'     => $users,
            'search'    => $search,
        ]);
    }

    public function create() {
        $this->requirePermission('users.create');
        if ($this->isPost()) {
            $this->validateCSRF();

            if (Tenant::id() !== null) {
                $currentUsers = (int)Tenant::usageCount('max_users');
                if (!Tenant::canUse('max_users', $currentUsers, 1)) {
                    $limit = (int)(Tenant::usageLimit('max_users') ?? 0);
                    $this->setFlash(
                        'error',
                        $limit > 0
                            ? 'User limit reached (' . $limit . '). Please upgrade your plan.'
                            : 'User limit reached for your plan. Please upgrade to add more users.'
                    );
                    $this->redirect('index.php?page=users&action=create');
                    return;
                }
            }

            $model  = new UserModel();

            // Resolve RBAC role
            $roleId = (int)$this->post('role_id', 0);
            $role = $this->resolveRoleById($roleId);

            $result = $model->createUser([
                'full_name' => $this->sanitize($this->post('full_name')),
                'username'  => $this->sanitize($this->post('username')),
                'email'     => $this->sanitize($this->post('email')),
                'phone'     => $this->sanitize($this->post('phone')),
                'role'      => $role['legacy_role'],
                'role_id'   => $role['role_id'],
                'password'  => $this->post('password'),
                'is_active' => (int)$this->post('is_active', 1),
            ]);

            if ($result['success']) {
                $this->logActivity('Created user: ' . $this->sanitize($this->post('username')), 'users', $result['id'] ?? null, 'Role: ' . $role['role_name']);
                $this->setFlash('success', 'User created successfully.');
                $this->redirect('index.php?page=users');
            } else {
                $this->setFlash('error', $result['message']);
                $this->redirect('index.php?page=users&action=create');
            }
        }

        $roles = $this->loadRoles();
        $this->view('users.create', ['pageTitle' => 'Add User', 'roles' => $roles]);
    }

    public function edit() {
        $this->requirePermission('users.edit');
        $id    = (int)$this->get('id');
        $model = new UserModel();
        $user  = $model->find($id);
        if (!$user) { $this->setFlash('error', 'User not found.'); $this->redirect('index.php?page=users'); }

        // Prevent editing self via this screen to avoid accidents
        $currentUser = Session::get('user');

        if ($this->isPost()) {
            $this->validateCSRF();

            // Duplicate checks
            if ($model->usernameExists($this->post('username'), $id)) {
                $this->setFlash('error', 'Username already taken.'); $this->redirect('index.php?page=users&action=edit&id=' . $id); return;
            }
            if ($model->emailExists($this->post('email'), $id)) {
                $this->setFlash('error', 'Email already taken.'); $this->redirect('index.php?page=users&action=edit&id=' . $id); return;
            }

            // Resolve RBAC role
            $roleId = (int)$this->post('role_id', 0);
            $role = $this->resolveRoleById($roleId);

            $data = [
                'full_name' => $this->sanitize($this->post('full_name')),
                'username'  => $this->sanitize($this->post('username')),
                'email'     => $this->sanitize($this->post('email')),
                'phone'     => $this->sanitize($this->post('phone')),
                'role'      => $role['legacy_role'],
                'role_id'   => $role['role_id'],
                'is_active' => (int)$this->post('is_active', 1),
            ];
            $model->update($id, $data);

            $roleChanged = ($user['role_id'] ?? null) != $role['role_id'];
            $this->logActivity('Updated user: ' . $data['username'], 'users', $id, $roleChanged ? 'Role changed to: ' . $role['role_name'] : null);

            // Clear permission cache if the edited user is currently logged in
            if ($roleChanged && $id === (int)($currentUser['id'] ?? 0)) {
                Session::clearPermissionCache();
                // Update session role info
                $currentUser['role'] = $role['legacy_role'];
                $currentUser['role_id'] = $role['role_id'];
                $currentUser['is_super_admin'] = $role['is_super_admin'];
                Session::set('user', $currentUser);
            }

            $this->setFlash('success', 'User updated successfully.');
            $this->redirect('index.php?page=users');
        }

        $roles = $this->loadRoles();
        $this->view('users.edit', ['pageTitle' => 'Edit User', 'user' => $user, 'roles' => $roles]);
    }

    public function resetPassword() {
        $this->requirePermission('users.edit');
        if (!$this->isPost()) { $this->redirect('index.php?page=users'); }
        $this->validateCSRF();

        $id       = (int)$this->post('id');
        $password = $this->post('new_password');
        if (strlen($password) < 6) {
            $this->setFlash('error', 'Password must be at least 6 characters.'); $this->redirect('index.php?page=users'); return;
        }

        // SECURITY FIX (IDOR-1): Verify target user belongs to current tenant.
        // Model::find() is tenant-scoped — returns null if user belongs to another company.
        $model = new UserModel();
        $targetUser = $model->find($id);
        if (!$targetUser) {
            $this->setFlash('error', 'User not found.');
            $this->redirect('index.php?page=users');
            return;
        }

        // SECURITY: Prevent non-super-admins from resetting super-admin passwords.
        // A tenant admin should never be able to reset the platform owner's password.
        if (!empty($targetUser['is_super_admin']) && !Session::isSuperAdmin()) {
            Helper::securityLog('PRIVILEGE_VIOLATION', 'User ' . (Session::get('user')['username'] ?? '?') . ' attempted to reset password for super-admin user ID: ' . $id);
            $this->setFlash('error', 'You cannot reset the password of a super admin account.');
            $this->redirect('index.php?page=users');
            return;
        }
        // Also check role-based super-admin flag
        if (!empty($targetUser['role_id'])) {
            try {
                $targetRole = Database::getInstance()->query(
                    "SELECT is_super_admin FROM roles WHERE id = ?", [$targetUser['role_id']]
                )->fetch();
                if ($targetRole && $targetRole['is_super_admin'] && !Session::isSuperAdmin()) {
                    Helper::securityLog('PRIVILEGE_VIOLATION', 'User ' . (Session::get('user')['username'] ?? '?') . ' attempted to reset password for super-admin role user ID: ' . $id);
                    $this->setFlash('error', 'You cannot reset the password of a super admin account.');
                    $this->redirect('index.php?page=users');
                    return;
                }
            } catch (\Exception $e) {
                error_log('[RBAC] Failed to check target role for password reset: ' . $e->getMessage());
            }
        }

        $model->resetPassword($id, $password);
        $this->logActivity('Reset password for user ID: ' . $id, 'users', $id);
        Helper::securityLog('PASSWORD_RESET', 'Admin reset password for user ID: ' . $id);
        $this->setFlash('success', 'Password reset successfully.');
        $this->redirect('index.php?page=users');
    }

    public function toggleActive() {
        $this->requirePermission('users.edit');
        if (!$this->isPost()) { $this->redirect('index.php?page=users'); }
        $this->validateCSRF();

        $id   = (int)$this->post('id');
        $user = (new UserModel())->find($id);
        if (!$user) { $this->setFlash('error', 'User not found.'); $this->redirect('index.php?page=users'); return; }

        // Prevent deactivating yourself
        $currentUser = Session::get('user');
        if ($id === (int)$currentUser['id']) {
            $this->setFlash('error', 'You cannot deactivate your own account.'); $this->redirect('index.php?page=users'); return;
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        (new UserModel())->update($id, ['is_active' => $newStatus]);
        $this->logActivity(($newStatus ? 'Activated' : 'Deactivated') . ' user: ' . ($user['username'] ?? $id), 'users', $id);
        $this->setFlash('success', 'User status updated.');
        $this->redirect('index.php?page=users');
    }

    public function delete() {
        $this->requirePermission('users.delete');
        if (!$this->isPost()) { $this->redirect('index.php?page=users'); }
        $this->validateCSRF();

        $id          = (int)$this->post('id');
        $currentUser = Session::get('user');
        if ($id === (int)$currentUser['id']) {
            $this->setFlash('error', 'You cannot delete your own account.'); $this->redirect('index.php?page=users'); return;
        }
        $user = (new UserModel())->find($id);
        if (!$user) {
            $this->setFlash('error', 'User not found.'); $this->redirect('index.php?page=users'); return;
        }

        // SECURITY: Prevent deleting super-admin accounts unless you are also super-admin
        if (!empty($user['role_id'])) {
            try {
                $targetRole = Database::getInstance()->query(
                    "SELECT is_super_admin FROM roles WHERE id = ?", [$user['role_id']]
                )->fetch();
                if ($targetRole && $targetRole['is_super_admin'] && !Session::isSuperAdmin()) {
                    $this->setFlash('error', 'Cannot delete a super admin account.');
                    Helper::securityLog('PRIVILEGE_VIOLATION', 'User '.$currentUser['username'].' tried to delete super admin user ID: '.$id);
                    $this->redirect('index.php?page=users');
                    return;
                }
            } catch (\Exception $e) {
                error_log('[RBAC] Failed to check target role: ' . $e->getMessage());
            }
        }

        (new UserModel())->delete($id);
        $this->logActivity('Deleted user: ' . ($user['username'] ?? $id), 'users', $id, 'Role: ' . ($user['role'] ?? 'unknown'));
        $this->setFlash('success', 'User deleted.');
        $this->redirect('index.php?page=users');
    }

    // =========================================================
    // RBAC Role Helpers
    // =========================================================

    /**
     * Load all roles from the database for dropdowns.
     * @return array
     */
    private function loadRoles() {
        try {
            $db = Database::getInstance();
            $tenantId = Tenant::id();

            if (Session::isSuperAdmin()) {
                return $db->query(
                    "SELECT id, name, display_name, company_id, is_super_admin
                     FROM roles
                     ORDER BY company_id IS NULL DESC, company_id ASC, display_name ASC, id ASC"
                )->fetchAll();
            }

            if ($tenantId !== null) {
                return $db->query(
                    "SELECT id, name, display_name, company_id, is_super_admin
                     FROM roles
                     WHERE (company_id IS NULL OR company_id = ?)
                       AND IFNULL(is_super_admin, 0) = 0
                     ORDER BY company_id IS NULL DESC, display_name ASC, id ASC",
                    [$tenantId]
                )->fetchAll();
            }

            return $db->query(
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

    /**
     * Resolve a role_id into the RBAC role details + legacy ENUM value.
     * Falls back to staff/role_id=5 if the role is invalid.
     *
     * @param int $roleId
     * @return array ['role_id' => int, 'role_name' => string, 'legacy_role' => string, 'is_super_admin' => bool]
     */
    private function resolveRoleById($roleId) {
        $default = $this->resolveFallbackRole();
        if ($roleId <= 0) return $default;

        try {
            $role = Database::getInstance()->query(
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

            $isSuperAdminRole = (bool)$role['is_super_admin'];

            // GUARD: Prevent Privilege Escalation
            // A non-super-admin cannot assign a super-admin role to anyone (including themselves)
            if ($isSuperAdminRole && !Session::isSuperAdmin()) {
                error_log('[SECURITY] Privilege escalation blocked. User '.Session::get('user')['username'].' attempted to assign super admin role.');
                return $default;
            }

            return [
                'role_id'        => (int)$role['id'],
                'role_name'      => $role['display_name'],
                'legacy_role'    => $this->legacyRoleFor($role),
                'is_super_admin' => $isSuperAdminRole,
            ];
        } catch (\Exception $e) {
            error_log('[RBAC] Failed to resolve role: ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * Fall back to the safest tenant-visible role instead of assuming role ID 5.
     */
    private function resolveFallbackRole(): array {
        try {
            $db = Database::getInstance();
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

            $role = $db->query($sql, $params)->fetch();
            if ($role) {
                return [
                    'role_id'        => (int)$role['id'],
                    'role_name'      => $role['display_name'],
                    'legacy_role'    => $this->legacyRoleFor($role),
                    'is_super_admin' => false,
                ];
            }
        } catch (\Throwable $e) {
            error_log('[RBAC] Failed to resolve fallback role: ' . $e->getMessage());
        }

        return [
            'role_id'        => null,
            'role_name'      => 'Staff',
            'legacy_role'    => 'staff',
            'is_super_admin' => false,
        ];
    }

    /**
     * Check whether a role is visible to the current tenant session.
     */
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

    /**
     * Convert an RBAC role into the legacy admin/staff enum value.
     */
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
}
