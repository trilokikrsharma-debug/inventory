<?php
/**
 * Category Model — Multi-Tenant Aware
 */
class CategoryModel extends Model {
    protected $table = 'categories';

    public function allWithCount() {
        $where = ["c.deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "c.company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT c.*, COUNT(p.id) as product_count
             FROM {$this->table} c
             LEFT JOIN products p ON c.id = p.category_id AND p.deleted_at IS NULL
             WHERE " . implode(' AND ', $where) . "
             GROUP BY c.id
             ORDER BY c.name ASC",
            $params
        )->fetchAll();
    }
}
