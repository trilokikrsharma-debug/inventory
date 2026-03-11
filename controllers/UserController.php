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
            return Database::getInstance()->query(
                "SELECT id, name, display_name, is_super_admin FROM roles ORDER BY id ASC"
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
        $default = ['role_id' => 5, 'role_name' => 'Staff', 'legacy_role' => 'staff', 'is_super_admin' => false];
        if ($roleId <= 0) return $default;

        try {
            $role = Database::getInstance()->query(
                "SELECT id, name, display_name, is_super_admin FROM roles WHERE id = ?",
                [$roleId]
            )->fetch();

            if (!$role) return $default;

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
                'legacy_role'    => $isSuperAdminRole ? 'admin' : 'staff',
                'is_super_admin' => $isSuperAdminRole,
            ];
        } catch (\Exception $e) {
            error_log('[RBAC] Failed to resolve role: ' . $e->getMessage());
            return $default;
        }
    }
}
