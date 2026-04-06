<?php
/**
 * Product Model — Multi-Tenant Aware
 * 
 * Manages products and inventory operations.
 * All queries scoped by company_id via Tenant::id().
 */
class ProductModel extends Model {
    protected $table = 'products';
    private static ?bool $warehouseTablesAvailable = null;

    /**
     * Keep dashboard + sidebar stock indicators in sync after any product/stock mutation.
     */
    private function flushStockCaches(): void {
        $tenantPrefix = 'c' . (Tenant::id() ?? 0) . '_';
        Cache::flushPrefix($tenantPrefix . 'dash_');
        Cache::flushPrefix($tenantPrefix . 'report_');
        Cache::flushPrefix($tenantPrefix . 'products_');
        Cache::delete($tenantPrefix . 'sidebar_lowstock');
    }

    /**
     * Build a deterministic cache key for product list pages.
     */
    private function productsCacheKey(string $search, string $categoryId, int $page, int $perPage): string {
        $tenantId = Tenant::id() ?? 0;
        return 'c' . $tenantId . '_products_list_' . md5(json_encode([
            'search' => trim($search),
            'category' => trim((string)$categoryId),
            'page' => $page,
            'per_page' => $perPage,
        ]));
    }

    private function warehouseTablesAvailable(): bool {
        if (self::$warehouseTablesAvailable !== null) {
            return self::$warehouseTablesAvailable;
        }

        try {
            $tables = $this->db->query("SHOW TABLES LIKE 'product_warehouse_stock'")->fetchAll();
            self::$warehouseTablesAvailable = !empty($tables);
        } catch (\Throwable $e) {
            self::$warehouseTablesAvailable = false;
        }

        return self::$warehouseTablesAvailable;
    }

    /**
     * Query-only implementation for paginated product listing.
     */
    private function fetchAllWithRelations(string $search, string $categoryId, int $page, int $perPage): array {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["p.deleted_at IS NULL"];

        if (Tenant::id() !== null) {
            $where[] = "p.company_id = ?";
            $params[] = Tenant::id();
        }

        if ($search !== '') {
            $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }

        if ($categoryId !== '') {
            $where[] = "p.category_id = ?";
            $params[] = $categoryId;
        }

        $whereClause = implode(' AND ', $where);

        $total = (int)$this->db->query(
            "SELECT COUNT(*) FROM {$this->table} p WHERE {$whereClause}",
            $params
        )->fetchColumn();

        $data = $this->db->query(
            "SELECT
                p.id,
                p.name,
                p.sku,
                p.barcode,
                p.category_id,
                p.brand_id,
                p.unit_id,
                p.purchase_price,
                p.selling_price,
                p.mrp,
                p.tax_rate,
                p.opening_stock,
                p.current_stock,
                p.low_stock_alert,
                p.is_active,
                p.created_at,
                c.name as category_name,
                b.name as brand_name,
                u.short_name as unit_name
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
            'totalPages' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    /**
     * Get all products with related data (tenant-scoped)
     */
    public function getAllWithRelations($search = '', $categoryId = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $search = trim((string)$search);
        $categoryId = trim((string)$categoryId);
        $page = max(1, (int)$page);
        $perPage = max(1, min(5000, (int)$perPage));

        $canCache = $search === '' && $page <= 5;
        if (!$canCache) {
            return $this->fetchAllWithRelations($search, $categoryId, $page, $perPage);
        }

        $ttl = defined('CACHE_TTL_PRODUCTS') ? CACHE_TTL_PRODUCTS : 120;
        $cacheKey = $this->productsCacheKey($search, $categoryId, $page, $perPage);

        return Cache::remember($cacheKey, $ttl, function () use ($search, $categoryId, $page, $perPage) {
            return $this->fetchAllWithRelations($search, $categoryId, $page, $perPage);
        });
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
    public function updateStock($productId, $quantity, $type, $referenceId = null, $userId = null, $note = '', ?int $warehouseId = null) {
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

        $this->applyWarehouseDelta((int)$productId, (float)$quantity, $warehouseId);

        // Log stock history (with company_id)
        $this->db->query(
            "INSERT INTO stock_history (company_id, product_id, type, reference_id, quantity, stock_before, stock_after, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [Tenant::id() ?? 1, $productId, $type, $referenceId, $quantity, $stockBefore, $stockAfter, $note, $userId]
        );

        $this->flushStockCaches();
        return $stockAfter;
    }

    public function allocateOpeningStock(int $productId, ?int $warehouseId, float $quantity): void {
        if ($quantity <= 0 || Tenant::id() === null || !$this->warehouseTablesAvailable()) {
            return;
        }

        $targetWarehouseId = $warehouseId ?: (new WarehouseModel())->defaultWarehouseId();
        if (!$targetWarehouseId) {
            return;
        }

        $this->db->query(
            "INSERT INTO product_warehouse_stock (company_id, product_id, warehouse_id, quantity)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = NOW()",
            [Tenant::id(), $productId, $targetWarehouseId, round($quantity, 3)]
        );
    }

    public function getWarehouseBreakdown(int $productId): array {
        if (Tenant::id() === null || !$this->warehouseTablesAvailable()) {
            return [];
        }

        return $this->db->query(
            "SELECT
                w.id,
                w.name,
                w.code,
                w.location,
                w.is_default,
                COALESCE(pws.quantity, 0) AS quantity
             FROM warehouses w
             LEFT JOIN product_warehouse_stock pws
               ON pws.company_id = w.company_id
              AND pws.warehouse_id = w.id
              AND pws.product_id = ?
             WHERE w.company_id = ?
               AND w.deleted_at IS NULL
               AND w.is_active = 1
             ORDER BY w.is_default DESC, w.name ASC, w.id ASC",
            [$productId, Tenant::id()]
        )->fetchAll();
    }

    /**
     * @param array<int, array{warehouse_id:int, quantity:float}> $allocations
     */
    public function syncWarehouseAllocations(int $productId, array $allocations, ?int $userId = null, string $note = 'Warehouse stock rebalanced'): float {
        if (Tenant::id() === null || !$this->warehouseTablesAvailable()) {
            return 0.0;
        }

        $this->db->beginTransaction();
        try {
            $product = $this->db->query(
                "SELECT current_stock
                 FROM {$this->table}
                 WHERE id = ?
                   AND company_id = ?
                   AND deleted_at IS NULL
                 FOR UPDATE",
                [$productId, Tenant::id()]
            )->fetch();
            if (!$product) {
                throw new \RuntimeException('Product not found.');
            }

            $stockBefore = (float)$product['current_stock'];
            $stockAfter = WarehouseStockService::totalQuantity($allocations);

            $this->db->query(
                "DELETE FROM product_warehouse_stock
                 WHERE company_id = ?
                   AND product_id = ?",
                [Tenant::id(), $productId]
            );

            foreach ($allocations as $allocation) {
                $this->db->query(
                    "INSERT INTO product_warehouse_stock (company_id, product_id, warehouse_id, quantity)
                     VALUES (?, ?, ?, ?)",
                    [Tenant::id(), $productId, (int)$allocation['warehouse_id'], (float)$allocation['quantity']]
                );
            }

            $this->db->query(
                "UPDATE {$this->table}
                 SET current_stock = ?
                 WHERE id = ?
                   AND company_id = ?",
                [$stockAfter, $productId, Tenant::id()]
            );

            $delta = round($stockAfter - $stockBefore, 3);
            if ($delta != 0.0) {
                $this->db->query(
                    "INSERT INTO stock_history (company_id, product_id, type, quantity, stock_before, stock_after, note, created_by)
                     VALUES (?, ?, 'adjustment', ?, ?, ?, ?, ?)",
                    [Tenant::id(), $productId, $delta, $stockBefore, $stockAfter, $note, $userId]
                );
            }

            $this->db->commit();
            $this->flushStockCaches();
            return $stockAfter;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Create product and refresh stock-related badges/counters.
     */
    public function create($data) {
        if (Tenant::id() !== null) {
            $currentProducts = (int)Tenant::usageCount('max_products');
            if (!Tenant::canUse('max_products', $currentProducts, 1)) {
                $limit = (int)(Tenant::usageLimit('max_products') ?? 0);
                $message = $limit > 0
                    ? 'Product limit reached (' . $limit . '). Please upgrade your plan.'
                    : 'Product limit reached for your current plan. Please upgrade to add more products.';
                throw new \RuntimeException($message);
            }
        }

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

    public function getStockReport(string $search = '', string $categoryId = '', ?int $warehouseId = null, int $page = 1, int $perPage = RECORDS_PER_PAGE): array {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["p.deleted_at IS NULL"];
        $stockSelect = "p.current_stock";
        $warehouseJoin = '';

        if (Tenant::id() !== null) {
            $where[] = "p.company_id = ?";
            $params[] = Tenant::id();
        }

        if ($search !== '') {
            $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term);
        }

        if ($categoryId !== '') {
            $where[] = "p.category_id = ?";
            $params[] = $categoryId;
        }

        if ($warehouseId !== null && $warehouseId > 0 && Tenant::id() !== null && $this->warehouseTablesAvailable()) {
            $warehouseJoin = " LEFT JOIN product_warehouse_stock pws
                                 ON pws.company_id = p.company_id
                                AND pws.product_id = p.id
                                AND pws.warehouse_id = " . (int)$warehouseId . "
                               LEFT JOIN warehouses rw
                                 ON rw.id = " . (int)$warehouseId . "
                                AND rw.company_id = p.company_id ";
            $stockSelect = "COALESCE(pws.quantity, 0)";
        } else {
            $warehouseJoin = " LEFT JOIN warehouses rw ON 1 = 0 ";
        }

        $whereClause = implode(' AND ', $where);

        $total = (int)$this->db->query(
            "SELECT COUNT(*)
             FROM {$this->table} p
             {$warehouseJoin}
             WHERE {$whereClause}",
            $params
        )->fetchColumn();

        $rows = $this->db->query(
            "SELECT
                p.id,
                p.name,
                p.sku,
                p.purchase_price,
                p.selling_price,
                p.low_stock_alert,
                {$stockSelect} AS stock_quantity,
                c.name AS category_name,
                u.short_name AS unit_name,
                rw.name AS report_warehouse_name
             FROM {$this->table} p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN units u ON p.unit_id = u.id
             {$warehouseJoin}
             WHERE {$whereClause}
             ORDER BY p.name ASC, p.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        )->fetchAll();

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int)ceil($total / max(1, $perPage)),
        ];
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

    public function warehouseQuantity(int $productId, int $warehouseId): float {
        if (Tenant::id() === null || !$this->warehouseTablesAvailable()) {
            return 0.0;
        }

        $value = $this->db->query(
            "SELECT COALESCE(quantity, 0)
             FROM product_warehouse_stock
             WHERE company_id = ?
               AND product_id = ?
               AND warehouse_id = ?
             LIMIT 1",
            [Tenant::id(), $productId, $warehouseId]
        )->fetchColumn();

        return round((float)$value, 3);
    }

    public function transferWarehouseStock(int $productId, int $sourceWarehouseId, int $destinationWarehouseId, float $quantity): void {
        if (Tenant::id() === null || !$this->warehouseTablesAvailable()) {
            throw new \RuntimeException('Warehouse stock tracking is not available.');
        }
        if ($sourceWarehouseId === $destinationWarehouseId) {
            throw new \RuntimeException('Source and destination warehouses must be different.');
        }

        $quantity = round($quantity, 3);
        if ($quantity <= 0) {
            throw new \RuntimeException('Transfer quantity must be greater than zero.');
        }

        $this->db->query(
            "INSERT INTO product_warehouse_stock (company_id, product_id, warehouse_id, quantity)
             VALUES (?, ?, ?, 0)
             ON DUPLICATE KEY UPDATE updated_at = NOW()",
            [Tenant::id(), $productId, $sourceWarehouseId]
        );
        $this->db->query(
            "INSERT INTO product_warehouse_stock (company_id, product_id, warehouse_id, quantity)
             VALUES (?, ?, ?, 0)
             ON DUPLICATE KEY UPDATE updated_at = NOW()",
            [Tenant::id(), $productId, $destinationWarehouseId]
        );

        $sourceQuantity = $this->db->query(
            "SELECT quantity
             FROM product_warehouse_stock
             WHERE company_id = ?
               AND product_id = ?
               AND warehouse_id = ?
             LIMIT 1
             FOR UPDATE",
            [Tenant::id(), $productId, $sourceWarehouseId]
        )->fetchColumn();

        if (round((float)$sourceQuantity, 3) + 0.0001 < $quantity) {
            throw new \RuntimeException('Insufficient stock in the selected source warehouse.');
        }

        $this->db->query(
            "UPDATE product_warehouse_stock
             SET quantity = quantity - ?, updated_at = NOW()
             WHERE company_id = ?
               AND product_id = ?
               AND warehouse_id = ?",
            [$quantity, Tenant::id(), $productId, $sourceWarehouseId]
        );
        $this->db->query(
            "UPDATE product_warehouse_stock
             SET quantity = quantity + ?, updated_at = NOW()
             WHERE company_id = ?
               AND product_id = ?
               AND warehouse_id = ?",
            [$quantity, Tenant::id(), $productId, $destinationWarehouseId]
        );
    }

    private function applyWarehouseDelta(int $productId, float $quantity, ?int $warehouseId = null): void {
        if (Tenant::id() === null || !$this->warehouseTablesAvailable() || $quantity == 0.0) {
            return;
        }

        $warehouseModel = new WarehouseModel();
        $targetWarehouseId = $warehouseId ?: $warehouseModel->defaultWarehouseId();
        if (!$targetWarehouseId) {
            return;
        }

        if ($quantity > 0) {
            $this->db->query(
                "INSERT INTO product_warehouse_stock (company_id, product_id, warehouse_id, quantity)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at = NOW()",
                [Tenant::id(), $productId, $targetWarehouseId, round($quantity, 3)]
            );
            return;
        }

        $remaining = round(abs($quantity), 3);
        if ($warehouseId !== null) {
            $available = $this->warehouseQuantity($productId, $targetWarehouseId);
            if ($available + 0.0001 < $remaining) {
                throw new \RuntimeException('Insufficient stock in the selected warehouse.');
            }

            $this->db->query(
                "INSERT INTO product_warehouse_stock (company_id, product_id, warehouse_id, quantity)
                 VALUES (?, ?, ?, 0)
                 ON DUPLICATE KEY UPDATE updated_at = NOW()",
                [Tenant::id(), $productId, $targetWarehouseId]
            );
            $this->db->query(
                "UPDATE product_warehouse_stock
                 SET quantity = quantity - ?, updated_at = NOW()
                 WHERE company_id = ?
                   AND product_id = ?
                   AND warehouse_id = ?",
                [$remaining, Tenant::id(), $productId, $targetWarehouseId]
            );
            return;
        }

        $rows = $this->db->query(
            "SELECT pws.id, pws.warehouse_id, pws.quantity
             FROM product_warehouse_stock pws
             JOIN warehouses w
               ON w.id = pws.warehouse_id
              AND w.company_id = pws.company_id
              AND w.deleted_at IS NULL
             WHERE pws.company_id = ?
               AND pws.product_id = ?
             ORDER BY pws.quantity DESC, w.is_default DESC, pws.id ASC",
            [Tenant::id(), $productId]
        )->fetchAll();

        foreach ($rows as $row) {
            $available = max(0, (float)$row['quantity']);
            if ($available <= 0 || $remaining <= 0) {
                continue;
            }

            $consume = min($available, $remaining);
            $this->db->query(
                "UPDATE product_warehouse_stock
                 SET quantity = quantity - ?, updated_at = NOW()
                 WHERE id = ?",
                [$consume, (int)$row['id']]
            );
            $remaining = round($remaining - $consume, 3);
        }

        if ($remaining > 0) {
            $this->db->query(
                "INSERT INTO product_warehouse_stock (company_id, product_id, warehouse_id, quantity)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE quantity = quantity - VALUES(quantity), updated_at = NOW()",
                [Tenant::id(), $productId, $targetWarehouseId, $remaining]
            );
        }
    }
}
