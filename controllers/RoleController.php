<?php
/**
 * Role & Permission Management Controller
 *
 * Super admin only — manages RBAC roles and their permission assignments.
 */
class RoleController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'delete'];

    /**
     * List all roles with user count
     */
    public function index() {
        $this->requirePermission('roles.manage');
        $db = Database::getInstance();

        // SECURITY FIX (RBAC-1): Tenant-scoped role listing.
        // Show only: (a) system-level roles (company_id IS NULL) + (b) this tenant's roles.
        $cid = Tenant::id();
        if ($cid !== null) {
            $roles = $db->query(
                "SELECT r.* FROM roles r WHERE r.company_id IS NULL OR r.company_id = ? ORDER BY r.id ASC",
                [$cid]
            )->fetchAll();
        } else {
            // Super-admin sees all roles
            $roles = $db->query("SELECT r.* FROM roles r ORDER BY r.id ASC")->fetchAll();
        }

        // Attach tenant-scoped user counts safely via prepared statement
        $cid = Tenant::id();
        foreach ($roles as &$role) {
            $sql = "SELECT COUNT(*) FROM users WHERE role_id = ? AND deleted_at IS NULL";
            $params = [$role['id']];
            if ($cid !== null) { $sql .= " AND company_id = ?"; $params[] = $cid; }
            $role['user_count'] = $db->query($sql, $params)->fetchColumn();
        }
        unset($role);

        $this->view('roles.index', [
            'pageTitle' => 'Roles & Permissions',
            'roles'     => $roles,
        ]);
    }

    /**
     * Create a new role
     */
    public function create() {
        $this->requirePermission('roles.manage');
        $db = Database::getInstance();

        if ($this->isPost()) {
            $this->validateCSRF();

            $name        = $this->sanitize($this->post('name'));
            $displayName = $this->sanitize($this->post('display_name'));
            $description = $this->sanitize($this->post('description'));

            // Validate required fields
            if (empty($name) || empty($displayName)) {
                $this->setFlash('error', 'Role name and display name are required.');
                $this->redirect('index.php?page=roles&action=create');
                return;
            }

            // Sanitize slug — lowercase, alphanumeric + underscore only
            $name = preg_replace('/[^a-z0-9_]/', '', strtolower($name));
            if (empty($name)) {
                $this->setFlash('error', 'Role name must contain valid characters (a-z, 0-9, _).');
                $this->redirect('index.php?page=roles&action=create');
                return;
            }

            // Check duplicate (tenant-scoped)
            $cid = Tenant::id();
            if ($cid !== null) {
                $exists = $db->query("SELECT id FROM roles WHERE name = ? AND (company_id = ? OR company_id IS NULL)", [$name, $cid])->fetch();
            } else {
                $exists = $db->query("SELECT id FROM roles WHERE name = ?", [$name])->fetch();
            }
            if ($exists) {
                $this->setFlash('error', 'A role with this name already exists.');
                $this->redirect('index.php?page=roles&action=create');
                return;
            }

            $db->beginTransaction();
            try {
                // Tenant-scoped role creation: attach company_id so the role belongs to this tenant
                $cid = Tenant::id();
                $db->query(
                    "INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system) VALUES (?, ?, ?, ?, 0, 0)",
                    [$cid, $name, $displayName, $description]
                );
                $roleId = $db->getConnection()->lastInsertId();

                // Save permissions
                $this->savePermissions($db, (int)$roleId);

                $db->commit();

                $this->logActivity('Created role: ' . $displayName, 'roles', $roleId);
                $this->setFlash('success', 'Role created successfully.');
                $this->redirect('index.php?page=roles');
            } catch (Exception $e) {
                $db->rollback();
                error_log('[RBAC] Create role failed: ' . $e->getMessage());
                $this->setFlash('error', 'Failed to create role. Please try again.');
                $this->redirect('index.php?page=roles&action=create');
            }
            return;
        }

        // GET: show create form
        $permissions = $this->getGroupedPermissions($db);
        $this->view('roles.create', [
            'pageTitle'   => 'Create Role',
            'permissions' => $permissions,
        ]);
    }

    /**
     * Edit an existing role
     */
    public function edit() {
        $this->requirePermission('roles.manage');
        $db = Database::getInstance();
        $id = (int)$this->get('id');

        // SECURITY: Tenant-scoped role lookup — prevent cross-tenant editing
        $cid = Tenant::id();
        if ($cid !== null) {
            $role = $db->query("SELECT * FROM roles WHERE id = ? AND (company_id = ? OR company_id IS NULL)", [$id, $cid])->fetch();
        } else {
            $role = $db->query("SELECT * FROM roles WHERE id = ?", [$id])->fetch();
        }
        if (!$role) {
            $this->setFlash('error', 'Role not found.');
            $this->redirect('index.php?page=roles');
            return;
        }

        // SECURITY FIX (RBAC-2): Prevent tenants from editing system-wide global roles.
        // A tenant (cid !== null) should only be able to edit roles that belong to them (company_id = cid).
        if ($cid !== null && $role['company_id'] === null) {
            $this->setFlash('error', 'Global system roles cannot be modified. Please create a custom role instead.');
            $this->redirect('index.php?page=roles');
            return;
        }

        if ($this->isPost()) {
            $this->validateCSRF();

            $displayName = $this->sanitize($this->post('display_name'));
            $description = $this->sanitize($this->post('description'));

            if (empty($displayName)) {
                $this->setFlash('error', 'Display name is required.');
                $this->redirect('index.php?page=roles&action=edit&id=' . $id);
                return;
            }

            $db->beginTransaction();
            try {
                // Update role info (name slug is immutable after creation for safety)
                $db->query(
                    "UPDATE roles SET display_name = ?, description = ?, updated_at = NOW() WHERE id = ?",
                    [$displayName, $description, $id]
                );

                // Don't touch permissions for super admin roles — they bypass checks anyway
                if (!$role['is_super_admin']) {
                    $this->savePermissions($db, $id);
                }

                $db->commit();

                // Clear permission cache for the current logged-in user
                // (other users' caches clear on their next login or page refresh)
                Session::clearPermissionCache();

                $this->logActivity('Updated role: ' . $displayName, 'roles', $id);
                $this->setFlash('success', 'Role updated successfully.');
                $this->redirect('index.php?page=roles');
            } catch (Exception $e) {
                $db->rollback();
                error_log('[RBAC] Update role failed: ' . $e->getMessage());
                $this->setFlash('error', 'Failed to update role. Please try again.');
                $this->redirect('index.php?page=roles&action=edit&id=' . $id);
            }
            return;
        }

        // GET: show edit form
        $permissions    = $this->getGroupedPermissions($db);
        $rolePermRaw = $db->query(
            "SELECT permission_id FROM role_permissions WHERE role_id = ?", [$id]
        )->fetchAll(\PDO::FETCH_COLUMN);
        
        // Normalize: if the DB wrapper ignored FETCH_COLUMN and returned associative arrays, extract the column
        $rolePermIds = [];
        if (!empty($rolePermRaw) && is_array($rolePermRaw)) {
            $first = reset($rolePermRaw);
            if (is_array($first)) {
                $rolePermIds = array_column($rolePermRaw, 'permission_id');
            } else {
                $rolePermIds = $rolePermRaw;
            }
        }
        
        // Sanitize: Array keys must be int or string for array_flip
        $rolePermIds = array_filter($rolePermIds, 'is_scalar');
        $rolePermIdsMap = array_flip($rolePermIds);

        $this->view('roles.edit', [
            'pageTitle'      => 'Edit Role: ' . $role['display_name'],
            'role'           => $role,
            'permissions'    => $permissions,
            'rolePermIdsMap' => $rolePermIdsMap,
        ]);
    }

    /**
     * Delete a role (POST only)
     */
    public function delete() {
        $this->requirePermission('roles.manage');
        if (!$this->isPost()) { $this->redirect('index.php?page=roles'); return; }
        $this->validateCSRF();

        $id = (int)$this->post('id');
        $db = Database::getInstance();

        // SECURITY: Tenant-scoped role lookup — prevent cross-tenant deletion
        $cid = Tenant::id();
        if ($cid !== null) {
            $role = $db->query("SELECT * FROM roles WHERE id = ? AND (company_id = ? OR company_id IS NULL)", [$id, $cid])->fetch();
        } else {
            $role = $db->query("SELECT * FROM roles WHERE id = ?", [$id])->fetch();
        }
        if (!$role) {
            $this->setFlash('error', 'Role not found.');
            $this->redirect('index.php?page=roles');
            return;
        }

        // SECURITY FIX: Prevent tenants from deleting global system roles
        if ($cid !== null && $role['company_id'] === null) {
            $this->setFlash('error', 'Global system roles cannot be deleted.');
            $this->redirect('index.php?page=roles');
            return;
        }

        // Safety: prevent deleting super admin role
        if ($role['is_super_admin']) {
            $this->setFlash('error', 'Cannot delete a super admin role.');
            $this->redirect('index.php?page=roles');
            return;
        }

        // Safety: prevent deleting system roles
        if ($role['is_system']) {
            $this->setFlash('error', 'Cannot delete a system role.');
            $this->redirect('index.php?page=roles');
            return;
        }

        // Safety: prevent deleting role with active users
        $userCount = $db->query(
            "SELECT COUNT(*) FROM users WHERE role_id = ? AND deleted_at IS NULL" . (Tenant::id() !== null ? " AND company_id = ?" : ""),
            Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
        )->fetchColumn();

        if ($userCount > 0) {
            $this->setFlash('error', 'Cannot delete role: ' . $userCount . ' user(s) are assigned to it. Reassign them first.');
            $this->redirect('index.php?page=roles');
            return;
        }

        $db->beginTransaction();
        try {
            // Delete permission assignments first (FK cascade handles this, but explicit is safer)
            $db->query("DELETE FROM role_permissions WHERE role_id = ?", [$id]);
            $db->query("DELETE FROM roles WHERE id = ?", [$id]);
            $db->commit();

            $this->logActivity('Deleted role: ' . $role['display_name'], 'roles', $id);
            $this->setFlash('success', 'Role deleted successfully.');
        } catch (Exception $e) {
            $db->rollback();
            error_log('[RBAC] Delete role failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to delete role.');
        }

        $this->redirect('index.php?page=roles');
    }

    // =========================================================
    // Helpers
    // =========================================================

    /**
     * Get all permissions grouped by module for the checkbox grid.
     *
     * @return array ['sales' => [['id'=>1, 'name'=>'sales.view', ...], ...], ...]
     */
    private function getGroupedPermissions($db) {
        $all = $db->query("SELECT * FROM permissions ORDER BY module ASC, id ASC")->fetchAll();
        $grouped = [];
        foreach ($all as $p) {
            $grouped[$p['module']][] = $p;
        }
        return $grouped;
    }

    /**
     * Save permission assignments from form submission.
     * Replaces ALL permissions for the role (delete + re-insert in transaction).
     *
     * @param Database $db Active database instance (must be in a transaction)
     * @param int $roleId
     */
    private function savePermissions($db, $roleId) {
        // Delete existing
        $db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);

        // Insert selected
        $permIds = $this->post('permissions', []);
        if (!is_array($permIds)) return;

        // Validate that all submitted IDs actually exist
        if (!empty($permIds)) {
            $placeholders = implode(',', array_fill(0, count($permIds), '?'));
            $validIds = $db->query(
                "SELECT id FROM permissions WHERE id IN ($placeholders)",
                array_map('intval', $permIds)
            )->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($validIds as $pid) {
                $db->query(
                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                    [$roleId, $pid]
                );
            }
        }
    }
}
