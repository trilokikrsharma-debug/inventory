<?php
/**
 * Brand Model — Multi-Tenant Aware
 */
class BrandModel extends Model {
    protected $table = 'brands';

    public function allWithCount() {
        $where = ["b.deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "b.company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT b.*, COUNT(p.id) as product_count
             FROM {$this->table} b
             LEFT JOIN products p ON b.id = p.brand_id AND p.deleted_at IS NULL
             WHERE " . implode(' AND ', $where) . "
             GROUP BY b.id
             ORDER BY b.name ASC",
            $params
        )->fetchAll();
    }
}
