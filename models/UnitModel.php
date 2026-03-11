<?php
/**
 * Unit Model — Multi-Tenant Aware
 */
class UnitModel extends Model {
    protected $table = 'units';

    public function allWithCount() {
        $where = ["u.deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "u.company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT u.*, COUNT(p.id) as product_count
             FROM {$this->table} u
             LEFT JOIN products p ON u.id = p.unit_id AND p.deleted_at IS NULL
             WHERE " . implode(' AND ', $where) . "
             GROUP BY u.id
             ORDER BY u.name ASC",
            $params
        )->fetchAll();
    }
}
