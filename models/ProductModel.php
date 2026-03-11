<?php
/**
 * Product Model — Multi-Tenant Aware
 * 
 * Manages products and inventory operations.
 * All queries scoped by company_id via Tenant::id().
 */
class ProductModel extends Model {
    protected $table = 'products';

    /**
     * Keep dashboard + sidebar stock indicators in sync after any product/stock mutation.
     */
    private function flushStockCaches(): void {
        $tenantPrefix = 'c' . (Tenant::id() ?? 0) . '_';
        Cache::flushPrefix($tenantPrefix . 'dash_');
        Cache::delete($tenantPrefix . 'sidebar_lowstock');
    }

    /**
     * Get all products with related data (tenant-scoped)
     */
    public function getAllWithRelations($search = '', $categoryId = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["p.deleted_at IS NULL"];

        // Tenant scoping
        if (Tenant::id() !== null) {
            $where[] = "p.company_id = ?";
            $params[] = Tenant::id();
        }

        if ($search) {
            $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }

        if ($categoryId) {
            $where[] = "p.category_id = ?";
            $params[] = $categoryId;
        }

        $whereClause = implode(' AND ', $where);

        // Count
        $total = $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} p WHERE {$whereClause}",
            $params
        )->fetchColumn();

        // Data
        $data = $this->db->query(
            "SELECT p.*, c.name as category_name, b.name as brand_name, u.short_name as unit_name
             FROM {$this->table} p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN units u ON p.unit_id = u.id
             WHERE {$whereClause}
             ORDER BY p.id DESC
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
     * Get product with full details (tenant-scoped)
     */
    public function getWithDetails($id) {
        $where = ["p.id = ?", "p.deleted_at IS NULL"];
        $params = [$id];
        if (Tenant::id() !== null) {
            $where[] = "p.company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT p.*, c.name as category_name, b.name as brand_name, u.name as unit_name, u.short_name as unit_short
             FROM {$this->table} p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN units u ON p.unit_id = u.id
             WHERE " . implode(' AND ', $where),
            $params
        )->fetch();
    }

    /**
     * Update stock (tenant-scoped via product lookup)
     */
    public function updateStock($productId, $quantity, $type, $referenceId = null, $userId = null, $note = '') {
        $where = ["id = ?"];
        $params = [$productId];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        $product = $this->db->query(
            "SELECT current_stock FROM {$this->table} WHERE " . implode(' AND ', $where) . " FOR UPDATE",
            $params
        )->fetch();
        if (!$product) return false;

        $stockBefore = $product['current_stock'];
        $stockAfter = $stockBefore + $quantity;
        
        // Update product stock
        $this->db->query(
            "UPDATE {$this->table} SET current_stock = current_stock + ? WHERE id = ?" . (Tenant::id() !== null ? " AND company_id = ?" : ""),
            Tenant::id() !== null ? [$quantity, $productId, Tenant::id()] : [$quantity, $productId]
        );

        // Log stock history (with company_id)
        $this->db->query(
            "INSERT INTO stock_history (company_id, product_id, type, reference_id, quantity, stock_before, stock_after, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [Tenant::id() ?? 1, $productId, $type, $referenceId, $quantity, $stockBefore, $stockAfter, $note, $userId]
        );

        $this->flushStockCaches();
        return $stockAfter;
    }

    /**
     * Create product and refresh stock-related badges/counters.
     */
    public function create($data) {
        $id = parent::create($data);
        $this->flushStockCaches();
        return $id;
    }

    /**
     * Update product and refresh stock-related badges/counters.
     */
    public function update($id, $data) {
        $affected = parent::update($id, $data);
        if ($affected > 0) {
            $this->flushStockCaches();
        }
        return $affected;
    }

    /**
     * Delete product and refresh stock-related badges/counters.
     */
    public function delete($id) {
        $affected = parent::delete($id);
        if ($affected > 0) {
            $this->flushStockCaches();
        }
        return $affected;
    }

    /**
     * Get low stock products (tenant-scoped)
     */
    public function getLowStock($limit = 10, $threshold = null) {
        if ($threshold === null) {
            $settings = (new SettingsModel())->getSettings();
            $threshold = $settings['low_stock_threshold'] ?? 10;
        }

        $where = ["p.deleted_at IS NULL", "p.is_active = 1", "(p.current_stock <= COALESCE(p.low_stock_alert, ?))"];
        $params = [$threshold];
        if (Tenant::id() !== null) {
            $where[] = "p.company_id = ?";
            $params[] = Tenant::id();
        }
        $params[] = $limit;

        return $this->db->query(
            "SELECT p.id, p.name, p.sku, p.current_stock, p.low_stock_alert, p.purchase_price, p.selling_price,
                    c.name as category_name, u.short_name as unit_name
             FROM {$this->table} p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN units u ON p.unit_id = u.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.current_stock ASC
             LIMIT ?",
            $params
        )->fetchAll();
    }

    /**
     * Get stock history for a product (tenant-scoped)
     */
    public function getStockHistory($productId, $limit = 200) {
        $where = ["sh.product_id = ?"];
        $params = [$productId];
        if (Tenant::id() !== null) {
            $where[] = "sh.company_id = ?";
            $params[] = Tenant::id();
        }
        $params[] = $limit;

        return $this->db->query(
            "SELECT sh.*, u.full_name as user_name
             FROM stock_history sh
             LEFT JOIN users u ON sh.created_by = u.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY sh.created_at DESC
             LIMIT ?",
            $params
        )->fetchAll();
    }

    /**
     * Search products for AJAX autocomplete (tenant-scoped)
     */
    public function search($term) {
        $where = ["p.deleted_at IS NULL", "p.is_active = 1", "(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)"];
        $params = ["%{$term}%", "%{$term}%", "%{$term}%"];
        if (Tenant::id() !== null) {
            $where[] = "p.company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT p.id, p.name, p.sku, p.mrp, p.selling_price, p.purchase_price, p.current_stock, p.tax_rate, u.short_name as unit_name
             FROM {$this->table} p
             LEFT JOIN units u ON p.unit_id = u.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.name ASC
             LIMIT 20",
            $params
        )->fetchAll();
    }

    /**
     * Get total stock value (tenant-scoped)
     */
    public function getTotalStockValue() {
        $where = ["deleted_at IS NULL", "is_active = 1"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT SUM(current_stock * purchase_price) as total_value, SUM(current_stock * selling_price) as selling_value, COUNT(*) as total_products
             FROM {$this->table}
             WHERE " . implode(' AND ', $where),
            $params
        )->fetch();
    }
}
