<?php
/**
 * User Controller - Admin user management
 */
class UserController extends Controller {
    protected $allowedActions = ['index', 'create', 'edit', 'resetPassword', 'toggleActive', 'delete'];
    private UserManagementService $userManagementService;

    public function __construct() {
        $this->userManagementService = new UserManagementService();
    }

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
            $role = $this->userManagementService->resolveAssignableRole($roleId);
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

        $roles = $this->userManagementService->loadAssignableRoles();
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
            $role = $this->userManagementService->resolveAssignableRole($roleId);

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
                $currentUser = $this->userManagementService->applyRoleSessionState($currentUser, $role);
                Session::set('user', $currentUser);
            }

            $this->setFlash('success', 'User updated successfully.');
            $this->redirect('index.php?page=users');
        }

        $roles = $this->userManagementService->loadAssignableRoles();
        $this->view('users.edit', ['pageTitle' => 'Edit User', 'user' => $user, 'roles' => $roles]);
    }

    public function resetPassword() {
        $this->requirePermission('users.edit');
        if (!$this->isPost()) { $this->redirect('index.php?page=users'); }
        $this->validateCSRF();

        $id       = (int)$this->post('id');
        $password = (string)$this->post('new_password');
        $minLen = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8;

        if (strlen($password) < $minLen) {
            $this->setFlash('error', "Password must be at least {$minLen} characters.");
            $this->redirect('index.php?page=users');
            return;
        }

        if (PASSWORD_COMPLEXITY) {
            if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
                $this->setFlash('error', 'Password must contain at least 1 uppercase letter and 1 number.');
                $this->redirect('index.php?page=users');
                return;
            }
        }
        // SECURITY FIX (IDOR-1): Verify target user belongs to current tenant.
        // Model::find() is tenant-scoped — returns null if user belongs to another company.
        $guard = $this->userManagementService->guardManagedUserTarget($id, 'reset_password');
        if (!$guard['allowed']) {
            $this->setFlash('error', $guard['message']);
            $this->redirect('index.php?page=users');
            return;
        }

        $model = new UserModel();
        $result = $model->resetPassword($id, $password);
        if (empty($result['success'])) {
            $this->setFlash('error', (string)($result['message'] ?? 'Password reset failed.'));
            $this->redirect('index.php?page=users');
            return;
        }

        RememberMeService::revokeAllForUser($id);

        $this->logActivity('Reset password for user ID: ' . $id, 'users', $id);
        Helper::securityLog('PASSWORD_RESET', 'Admin reset password for user ID: ' . $id);
        $this->setFlash('success', (string)($result['message'] ?? 'Password reset successfully.'));
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

        $guard = $this->userManagementService->guardManagedUserTarget($id, 'delete');
        if (!$guard['allowed']) {
            $this->setFlash('error', $guard['message']);
            $this->redirect('index.php?page=users');
            return;
        }

        $user = $guard['user'];
        (new UserModel())->delete($id);
        $this->logActivity('Deleted user: ' . ($user['username'] ?? $id), 'users', $id, 'Role: ' . ($user['role'] ?? 'unknown'));
        $this->setFlash('success', 'User deleted.');
        $this->redirect('index.php?page=users');
    }
}
