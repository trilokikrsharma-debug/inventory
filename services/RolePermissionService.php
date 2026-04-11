<?php
class RolePermissionService {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: Database::getInstance();
    }

    public function groupedPermissions(): array {
        $all = $this->db->query("SELECT * FROM permissions ORDER BY module ASC, id ASC")->fetchAll();
        $grouped = [];
        foreach ($all as $permission) {
            if (!$this->isPermissionAvailableForTenant($permission)) {
                continue;
            }
            $grouped[(string)$permission['module']][] = $permission;
        }

        return $grouped;
    }

    public function replaceRolePermissions(int $roleId, array $permissionIds): void {
        $this->db->query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
        if ($permissionIds === []) {
            return;
        }

        $normalizedIds = array_values(array_map('intval', $permissionIds));
        $placeholders = implode(',', array_fill(0, count($normalizedIds), '?'));
        $validIds = $this->db->query(
            "SELECT id FROM permissions WHERE id IN ($placeholders)",
            $normalizedIds
        )->fetchAll(\PDO::FETCH_COLUMN);

        $allowedPermissionIds = $this->allowedPermissionIds();
        foreach ($validIds as $permissionId) {
            $permissionId = (int)$permissionId;
            if (!isset($allowedPermissionIds[$permissionId])) {
                continue;
            }

            $this->db->query(
                "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                [$roleId, $permissionId]
            );
        }
    }

    private function allowedPermissionIds(): array {
        $allowed = [];
        $rows = $this->db->query("SELECT id, name FROM permissions ORDER BY id ASC")->fetchAll();
        foreach ($rows as $row) {
            if ($this->isPermissionAvailableForTenant($row)) {
                $allowed[(int)$row['id']] = true;
            }
        }

        return $allowed;
    }

    private function isPermissionAvailableForTenant(array $permission): bool {
        if (Session::isSuperAdmin() || Tenant::id() === null) {
            return true;
        }

        $name = (string)($permission['name'] ?? '');
        if (str_starts_with($name, 'quotations.')) {
            return Tenant::canUse('quotations');
        }
        if (str_starts_with($name, 'returns.')) {
            return Tenant::canUse('sale_returns');
        }
        if ($name === 'backup.manage') {
            return Tenant::canUse('backup_restore');
        }
        if ($name === 'reports.view') {
            return Tenant::canUse('basic_reports') || Tenant::canUse('advanced_reports');
        }

        return true;
    }
}
