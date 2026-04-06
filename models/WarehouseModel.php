<?php

class WarehouseModel extends Model {
    protected $table = 'warehouses';

    private function flushWarehouseCaches(): void {
        $tenantPrefix = 'c' . (Tenant::id() ?? 0) . '_';
        Cache::flushPrefix($tenantPrefix . 'report_');
        Cache::flushPrefix($tenantPrefix . 'products_');
    }

    public function allActiveOrdered(): array {
        return $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
               AND deleted_at IS NULL
               AND is_active = 1
             ORDER BY is_default DESC, name ASC, id ASC",
            [Tenant::require()]
        )->fetchAll();
    }

    public function listWithStats(): array {
        return $this->db->query(
            "SELECT
                w.*,
                COUNT(DISTINCT CASE WHEN COALESCE(pws.quantity, 0) <> 0 THEN pws.product_id END) AS assigned_products,
                COALESCE(SUM(pws.quantity), 0) AS stock_units
             FROM {$this->table} w
             LEFT JOIN product_warehouse_stock pws
               ON pws.company_id = w.company_id
              AND pws.warehouse_id = w.id
             WHERE w.company_id = ?
               AND w.deleted_at IS NULL
             GROUP BY w.id
             ORDER BY w.is_default DESC, w.name ASC, w.id ASC",
            [Tenant::require()]
        )->fetchAll();
    }

    public function defaultWarehouseId(): ?int {
        $row = $this->db->query(
            "SELECT id
             FROM {$this->table}
             WHERE company_id = ?
               AND deleted_at IS NULL
               AND is_default = 1
             ORDER BY id ASC
             LIMIT 1",
            [Tenant::require()]
        )->fetch();

        if ($row) {
            return (int)$row['id'];
        }

        $fallback = $this->db->query(
            "SELECT id
             FROM {$this->table}
             WHERE company_id = ?
               AND deleted_at IS NULL
             ORDER BY id ASC
             LIMIT 1",
            [Tenant::require()]
        )->fetch();

        return $fallback ? (int)$fallback['id'] : null;
    }

    public function codeExists(string $code, ?int $excludeId = null): bool {
        $sql = "SELECT id FROM {$this->table} WHERE company_id = ? AND deleted_at IS NULL AND UPPER(code) = ?";
        $params = [Tenant::require(), strtoupper($code)];
        if ($excludeId !== null) {
            $sql .= " AND id <> ?";
            $params[] = $excludeId;
        }
        return (bool)$this->db->query($sql . " LIMIT 1", $params)->fetch();
    }

    public function setDefaultWarehouse(int $warehouseId): void {
        $companyId = Tenant::require();
        $this->db->beginTransaction();
        try {
            $this->db->query(
                "UPDATE {$this->table}
                 SET is_default = 0, updated_at = NOW()
                 WHERE company_id = ?
                   AND deleted_at IS NULL",
                [$companyId]
            );

            $this->db->query(
                "UPDATE {$this->table}
                 SET is_default = 1, updated_at = NOW()
                 WHERE id = ?
                   AND company_id = ?
                   AND deleted_at IS NULL",
                [$warehouseId, $companyId]
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function hasBlockingStock(int $warehouseId): bool {
        return (bool)$this->db->query(
            "SELECT 1
             FROM product_warehouse_stock
             WHERE company_id = ?
               AND warehouse_id = ?
               AND quantity <> 0
             LIMIT 1",
            [Tenant::require(), $warehouseId]
        )->fetch();
    }

    public function warehouseCount(): int {
        return (int)$this->db->query(
            "SELECT COUNT(*)
             FROM {$this->table}
             WHERE company_id = ?
               AND deleted_at IS NULL",
            [Tenant::require()]
        )->fetchColumn();
    }

    public function searchableProducts(string $term, int $warehouseId): array {
        return $this->db->query(
            "SELECT
                p.id,
                p.name,
                p.sku,
                p.purchase_price,
                p.selling_price,
                p.tax_rate,
                u.short_name AS unit_name,
                COALESCE(pws.quantity, 0) AS available_stock
             FROM products p
             LEFT JOIN units u ON p.unit_id = u.id
             LEFT JOIN product_warehouse_stock pws
               ON pws.company_id = p.company_id
              AND pws.product_id = p.id
              AND pws.warehouse_id = ?
             WHERE p.company_id = ?
               AND p.deleted_at IS NULL
               AND p.is_active = 1
               AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)
             ORDER BY
                CASE WHEN COALESCE(pws.quantity, 0) > 0 THEN 0 ELSE 1 END,
                p.name ASC
             LIMIT 20",
            [$warehouseId, Tenant::require(), '%' . $term . '%', '%' . $term . '%', '%' . $term . '%']
        )->fetchAll();
    }

    public function recentTransfers(int $limit = 12): array {
        return $this->db->query(
            "SELECT
                st.id,
                st.transfer_number,
                st.transfer_date,
                st.status,
                st.source_warehouse_id,
                st.destination_warehouse_id,
                st.total_quantity,
                st.item_count,
                st.note,
                sw.name AS source_warehouse_name,
                dw.name AS destination_warehouse_name,
                u.full_name AS created_by_name,
                approver.full_name AS approved_by_name,
                rejector.full_name AS rejected_by_name
             FROM stock_transfers st
             JOIN warehouses sw
               ON sw.id = st.source_warehouse_id
              AND sw.company_id = st.company_id
             JOIN warehouses dw
               ON dw.id = st.destination_warehouse_id
              AND dw.company_id = st.company_id
             LEFT JOIN users u ON u.id = st.created_by
             LEFT JOIN users approver ON approver.id = st.approved_by
             LEFT JOIN users rejector ON rejector.id = st.rejected_by
             WHERE st.company_id = ?
             ORDER BY st.transfer_date DESC, st.id DESC
             LIMIT {$limit}",
            [Tenant::require()]
        )->fetchAll();
    }

    public function transferSummary(): array {
        $row = $this->db->query(
            "SELECT
                COUNT(*) AS total_transfers,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_transfers,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_transfers,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_transfers,
                COALESCE(SUM(total_quantity), 0) AS total_quantity
             FROM stock_transfers
             WHERE company_id = ?",
            [Tenant::require()]
        )->fetch();

        return [
            'total_transfers' => (int)($row['total_transfers'] ?? 0),
            'pending_transfers' => (int)($row['pending_transfers'] ?? 0),
            'approved_transfers' => (int)($row['approved_transfers'] ?? 0),
            'rejected_transfers' => (int)($row['rejected_transfers'] ?? 0),
            'total_quantity' => round((float)($row['total_quantity'] ?? 0), 3),
        ];
    }

    public function transferReport(string $fromDate, string $toDate, string $status = '', int $warehouseId = 0, int $limit = 2000): array {
        $params = [Tenant::require(), $fromDate, $toDate];
        $where = [
            'st.company_id = ?',
            'st.transfer_date >= ?',
            'st.transfer_date <= ?',
        ];

        if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $where[] = 'st.status = ?';
            $params[] = $status;
        }

        if ($warehouseId > 0) {
            $where[] = '(st.source_warehouse_id = ? OR st.destination_warehouse_id = ?)';
            $params[] = $warehouseId;
            $params[] = $warehouseId;
        }

        return $this->db->query(
            "SELECT
                st.*,
                sw.name AS source_warehouse_name,
                dw.name AS destination_warehouse_name,
                u.full_name AS created_by_name,
                approver.full_name AS approved_by_name,
                rejector.full_name AS rejected_by_name
             FROM stock_transfers st
             JOIN warehouses sw
               ON sw.id = st.source_warehouse_id
              AND sw.company_id = st.company_id
             JOIN warehouses dw
               ON dw.id = st.destination_warehouse_id
              AND dw.company_id = st.company_id
             LEFT JOIN users u ON u.id = st.created_by
             LEFT JOIN users approver ON approver.id = st.approved_by
             LEFT JOIN users rejector ON rejector.id = st.rejected_by
             WHERE " . implode(' AND ', $where) . "
             ORDER BY st.transfer_date DESC, st.id DESC
             LIMIT {$limit}",
            $params
        )->fetchAll();
    }

    public function createTransfer(array $transferData, array $items, int $userId): array {
        $companyId = Tenant::require();
        $sourceWarehouseId = (int)$transferData['source_warehouse_id'];
        $destinationWarehouseId = (int)$transferData['destination_warehouse_id'];

        if ($sourceWarehouseId === $destinationWarehouseId) {
            throw new \RuntimeException('Source and destination warehouses must be different.');
        }

        $db = $this->db;
        $db->beginTransaction();

        try {
            $sourceWarehouse = $this->db->query(
                "SELECT id, name
                 FROM {$this->table}
                 WHERE company_id = ?
                   AND id = ?
                   AND deleted_at IS NULL
                   AND is_active = 1
                 LIMIT 1",
                [$companyId, $sourceWarehouseId]
            )->fetch();
            $destinationWarehouse = $this->db->query(
                "SELECT id, name
                 FROM {$this->table}
                 WHERE company_id = ?
                   AND id = ?
                   AND deleted_at IS NULL
                   AND is_active = 1
                 LIMIT 1",
                [$companyId, $destinationWarehouseId]
            )->fetch();

            if (!$sourceWarehouse || !$destinationWarehouse) {
                throw new \RuntimeException('Please select valid active warehouses.');
            }

            $this->db->query(
                "INSERT INTO stock_transfers
                 (company_id, transfer_number, source_warehouse_id, destination_warehouse_id, transfer_date, reference_number, note, status, item_count, total_quantity, created_by)
                 VALUES (?, '', ?, ?, ?, ?, ?, 'pending', ?, ?, ?)",
                [
                    $companyId,
                    $sourceWarehouseId,
                    $destinationWarehouseId,
                    $transferData['transfer_date'],
                    $transferData['reference_number'] ?? null,
                    $transferData['note'] ?? null,
                    count($items),
                    WarehouseStockService::totalQuantity($items),
                    $userId,
                ]
            );
            $transferId = (int)$this->db->lastInsertId();

            $transferNumber = 'TRF-' . str_pad((string)$transferId, 6, '0', STR_PAD_LEFT);
            $this->db->query(
                "UPDATE stock_transfers
                 SET transfer_number = ?, updated_at = NOW()
                 WHERE id = ?
                   AND company_id = ?",
                [$transferNumber, $transferId, $companyId]
            );

            foreach ($items as $item) {
                $product = $this->db->query(
                    "SELECT id, name, purchase_price
                     FROM products
                     WHERE company_id = ?
                       AND id = ?
                       AND deleted_at IS NULL
                     LIMIT 1",
                    [$companyId, (int)$item['product_id']]
                )->fetch();
                if (!$product) {
                    throw new \RuntimeException('One or more selected products are invalid.');
                }

                $quantity = round((float)$item['quantity'], 3);
                $unitCost = round((float)($product['purchase_price'] ?? 0), 2);
                $this->db->query(
                    "INSERT INTO stock_transfer_items
                     (company_id, transfer_id, product_id, quantity, unit_cost, total_cost)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$companyId, $transferId, (int)$item['product_id'], $quantity, $unitCost, round($quantity * $unitCost, 2)]
                );
            }

            $db->commit();
            $this->flushWarehouseCaches();

            return [
                'id' => $transferId,
                'transfer_number' => $transferNumber,
                'status' => 'pending',
                'source_warehouse_name' => $sourceWarehouse['name'],
                'destination_warehouse_name' => $destinationWarehouse['name'],
            ];
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function approveTransfer(int $transferId, int $userId): array {
        $companyId = Tenant::require();
        $db = $this->db;
        $db->beginTransaction();

        try {
            $transfer = $db->query(
                "SELECT st.*,
                        sw.name AS source_warehouse_name,
                        sw.is_active AS source_warehouse_active,
                        sw.deleted_at AS source_warehouse_deleted_at,
                        dw.name AS destination_warehouse_name,
                        dw.is_active AS destination_warehouse_active,
                        dw.deleted_at AS destination_warehouse_deleted_at
                 FROM stock_transfers st
                 JOIN warehouses sw ON sw.id = st.source_warehouse_id AND sw.company_id = st.company_id
                 JOIN warehouses dw ON dw.id = st.destination_warehouse_id AND dw.company_id = st.company_id
                 WHERE st.company_id = ?
                   AND st.id = ?
                 LIMIT 1",
                [$companyId, $transferId]
            )->fetch();
            if (!$transfer) {
                throw new \RuntimeException('Transfer not found.');
            }
            if (($transfer['status'] ?? '') === 'approved') {
                throw new \RuntimeException('Transfer is already approved.');
            }
            if (($transfer['status'] ?? '') === 'rejected') {
                throw new \RuntimeException('Rejected transfers cannot be approved.');
            }
            if (!empty($transfer['source_warehouse_deleted_at']) || (int)($transfer['source_warehouse_active'] ?? 0) !== 1) {
                throw new \RuntimeException('Source warehouse is no longer active. Review the transfer before approving.');
            }
            if (!empty($transfer['destination_warehouse_deleted_at']) || (int)($transfer['destination_warehouse_active'] ?? 0) !== 1) {
                throw new \RuntimeException('Destination warehouse is no longer active. Review the transfer before approving.');
            }

            $items = $db->query(
                "SELECT sti.*, p.name, p.purchase_price
                 FROM stock_transfer_items sti
                 JOIN products p
                   ON p.id = sti.product_id
                  AND p.company_id = sti.company_id
                  AND p.deleted_at IS NULL
                 WHERE sti.company_id = ?
                   AND sti.transfer_id = ?
                 ORDER BY sti.id ASC",
                [$companyId, $transferId]
            )->fetchAll();
            if (empty($items)) {
                throw new \RuntimeException('Transfer has no items to approve.');
            }

            $productModel = new ProductModel();
            foreach ($items as $item) {
                $quantity = round((float)($item['quantity'] ?? 0), 3);
                try {
                    $productModel->transferWarehouseStock((int)$item['product_id'], (int)$transfer['source_warehouse_id'], (int)$transfer['destination_warehouse_id'], $quantity);
                } catch (\RuntimeException $e) {
                    throw new \RuntimeException('Transfer failed for product "' . ($item['name'] ?? 'Unknown') . '": ' . $e->getMessage());
                }
            }

            $db->query(
                "UPDATE stock_transfers
                 SET status = 'approved',
                     approved_by = ?,
                     approved_at = NOW(),
                     updated_at = NOW()
                 WHERE company_id = ?
                   AND id = ?",
                [$userId, $companyId, $transferId]
            );

            $db->commit();
            $this->flushWarehouseCaches();

            return [
                'id' => $transferId,
                'transfer_number' => $transfer['transfer_number'],
                'source_warehouse_name' => $transfer['source_warehouse_name'],
                'destination_warehouse_name' => $transfer['destination_warehouse_name'],
            ];
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function rejectTransfer(int $transferId, int $userId, ?string $reason = null): array {
        $companyId = Tenant::require();
        $transfer = $this->db->query(
            "SELECT st.*, sw.name AS source_warehouse_name, dw.name AS destination_warehouse_name
             FROM stock_transfers st
             JOIN warehouses sw ON sw.id = st.source_warehouse_id AND sw.company_id = st.company_id
             JOIN warehouses dw ON dw.id = st.destination_warehouse_id AND dw.company_id = st.company_id
             WHERE st.company_id = ?
               AND st.id = ?
             LIMIT 1",
            [$companyId, $transferId]
        )->fetch();

        if (!$transfer) {
            throw new \RuntimeException('Transfer not found.');
        }
        if (($transfer['status'] ?? '') === 'approved') {
            throw new \RuntimeException('Approved transfers cannot be rejected.');
        }
        if (($transfer['status'] ?? '') === 'rejected') {
            throw new \RuntimeException('Transfer is already rejected.');
        }

        $this->db->query(
            "UPDATE stock_transfers
             SET status = 'rejected',
                 rejected_by = ?,
                 rejected_at = NOW(),
                 rejection_reason = ?,
                 updated_at = NOW()
             WHERE company_id = ?
               AND id = ?",
            [$userId, $reason, $companyId, $transferId]
        );

        $this->flushWarehouseCaches();

        return [
            'id' => $transferId,
            'transfer_number' => $transfer['transfer_number'],
            'source_warehouse_name' => $transfer['source_warehouse_name'],
            'destination_warehouse_name' => $transfer['destination_warehouse_name'],
        ];
    }
}
