<?php
/**
 * Sale Return Model - Multi-Tenant Aware
 */
class SaleReturnModel extends Model {
    protected $table = 'sale_returns';

    public function getAll($search = '', $fromDate = '', $toDate = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where  = ["sr.deleted_at IS NULL"];
        if (Tenant::id() !== null) { $where[] = "sr.company_id = ?"; $params[] = Tenant::id(); }
        if ($search) { $where[] = "(c.name LIKE ? OR s.invoice_number LIKE ? OR CONCAT('RET-', LPAD(sr.id, 4, '0')) LIKE ?)"; $t = "%{$search}%"; $params = array_merge($params, [$t, $t, $t]); }
        if ($fromDate) { $where[] = "sr.return_date >= ?"; $params[] = $fromDate; }
        if ($toDate)   { $where[] = "sr.return_date <= ?"; $params[] = $toDate; }
        $w = implode(' AND ', $where);
        $joinSql = "FROM {$this->table} sr LEFT JOIN sales s ON sr.sale_id = s.id LEFT JOIN customers c ON s.customer_id = c.id";
        $total = $this->db->query("SELECT COUNT(*) {$joinSql} WHERE {$w}", $params)->fetchColumn();
        $data = $this->db->query(
            "SELECT sr.*, CONCAT('RET-', LPAD(sr.id, 4, '0')) as return_number, c.name as customer_name, s.invoice_number, s.customer_id {$joinSql} WHERE {$w} ORDER BY sr.id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        )->fetchAll();
        return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'totalPages' => ceil($total / $perPage)];
    }

    public function getWithDetails($id) {
        $where = ["sr.id = ?", "sr.deleted_at IS NULL"];
        $params = [$id];
        if (Tenant::id() !== null) { $where[] = "sr.company_id = ?"; $params[] = Tenant::id(); }
        $return = $this->db->query(
            "SELECT sr.*, CONCAT('RET-', LPAD(sr.id, 4, '0')) as return_number, c.name as customer_name, c.phone as customer_phone, s.invoice_number, s.customer_id, u.full_name as created_by_name, cu.full_name as cancelled_by_name
             FROM {$this->table} sr
             LEFT JOIN sales s ON sr.sale_id = s.id
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN users u ON sr.created_by = u.id
             LEFT JOIN users cu ON sr.cancelled_by = cu.id
             WHERE " . implode(' AND ', $where),
            $params
        )->fetch();
        if ($return) {
            $return['items'] = $this->db->query(
                "SELECT sri.*, p.name as product_name, p.sku
                 FROM sale_return_items sri
                 LEFT JOIN products p ON sri.product_id = p.id
                 WHERE sri.return_id = ?" . (Tenant::id() !== null ? " AND sri.company_id = ?" : ""),
                Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
            )->fetchAll();
        }
        return $return;
    }

    public function getSaleReturnSummary($saleId) {
        $params = [(int)$saleId];
        $tenantFilter = '';
        if (Tenant::id() !== null) {
            $tenantFilter = " AND company_id = ?";
            $params[] = Tenant::id();
        }

        $returnedAmount = (float)$this->db->query(
            "SELECT COALESCE(SUM(total_amount), 0)
             FROM {$this->table}
             WHERE sale_id = ? AND deleted_at IS NULL AND status = 'posted'{$tenantFilter}",
            $params
        )->fetchColumn();

        return [
            'returned_amount' => $returnedAmount,
        ];
    }

    public function createReturn($data, $items, $userId) {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $data['created_by'] = $userId;
            $cid = Tenant::id();
            $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
            $saleParams = [(int)$data['sale_id']];
            if ($cid !== null) $saleParams[] = $cid;
            $sale = $db->query(
                "SELECT grand_total, paid_amount, due_amount, customer_id
                 FROM sales
                 WHERE id = ? AND deleted_at IS NULL{$tenantFilter}",
                $saleParams
            )->fetch();
            if (!$sale) throw new Exception('Original sale not found.');

            $refundAmount = (float)$data['total_amount'];
            $saleSummary = $this->getSaleReturnSummary((int)$data['sale_id']);
            $alreadyReturnedAmount = (float)($saleSummary['returned_amount'] ?? 0);
            $remainingAmount = max(0, (float)$sale['grand_total'] - $alreadyReturnedAmount);
            if ($refundAmount > $remainingAmount + 0.001) {
                throw new Exception(
                    'Return amount (' . number_format($refundAmount, 2) . ') exceeds the remaining returnable amount (' . number_format($remainingAmount, 2) . ').'
                );
            }

            $soldQtyParams = [(int)$data['sale_id']];
            if ($cid !== null) $soldQtyParams[] = $cid;
            $soldQtyRows = $db->query(
                "SELECT product_id, COALESCE(SUM(quantity), 0) as sold_qty
                 FROM sale_items
                 WHERE sale_id = ?" . ($cid !== null ? " AND company_id = ?" : "") . "
                 GROUP BY product_id",
                $soldQtyParams
            )->fetchAll();
            $soldQtyMap = [];
            foreach ($soldQtyRows as $row) {
                $soldQtyMap[(int)$row['product_id']] = (float)$row['sold_qty'];
            }

            $returnedQtyParams = [(int)$data['sale_id']];
            $returnedQtySql = "SELECT sri.product_id, COALESCE(SUM(sri.quantity), 0) as returned_qty
                               FROM sale_return_items sri
                               JOIN sale_returns sr ON sr.id = sri.return_id
                               WHERE sr.sale_id = ? AND sr.deleted_at IS NULL AND sr.status = 'posted'";
            if ($cid !== null) {
                $returnedQtySql .= " AND sr.company_id = ?";
                $returnedQtyParams[] = $cid;
            }
            $returnedQtySql .= " GROUP BY sri.product_id";
            $returnedQtyRows = $db->query($returnedQtySql, $returnedQtyParams)->fetchAll();
            $returnedQtyMap = [];
            foreach ($returnedQtyRows as $row) {
                $returnedQtyMap[(int)$row['product_id']] = (float)$row['returned_qty'];
            }

            $requestedQtyMap = [];
            foreach ($items as $item) {
                $pid = (int)$item['product_id'];
                $requestedQtyMap[$pid] = ($requestedQtyMap[$pid] ?? 0.0) + (float)$item['quantity'];
            }

            foreach ($requestedQtyMap as $pid => $requestedQty) {
                if (!isset($soldQtyMap[$pid])) {
                    throw new Exception('One or more selected products are not part of the original sale.');
                }
                $alreadyReturnedQty = (float)($returnedQtyMap[$pid] ?? 0);
                $maxReturnableQty = max(0, (float)$soldQtyMap[$pid] - $alreadyReturnedQty);
                if ($requestedQty > $maxReturnableQty + 0.001) {
                    throw new Exception(
                        'Return quantity exceeds available returnable quantity for product ID ' . $pid .
                        ' (available: ' . number_format($maxReturnableQty, 3) . ').'
                    );
                }
            }

            $insertData = $data;
            unset($insertData['return_number']);
            $insertData['status'] = 'posted';
            $returnId = $this->create($insertData);
            $companyId = $cid ?? 1;
            $productModel = new ProductModel();
            $returnNumber = $this->displayNumber($returnId);
            foreach ($items as $item) {
                $db->query(
                    "INSERT INTO sale_return_items (company_id, return_id, product_id, quantity, unit_price, subtotal, tax_rate, tax_amount, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $companyId,
                        $returnId,
                        $item['product_id'],
                        $item['quantity'],
                        $item['unit_price'],
                        $item['subtotal'] ?? 0,
                        $item['tax_rate'] ?? 0,
                        $item['tax_amount'] ?? 0,
                        $item['total'],
                    ]
                );
                $productModel->updateStock($item['product_id'], +$item['quantity'], 'return', $returnId, $userId, 'Sale Return #' . $returnNumber);
            }

            $customerId = (int)$sale['customer_id'];
            if ($customerId) {
                // Recompute paid/due with return impact so fully-returned invoices do not stay unpaid.
                (new PaymentModel())->recalculateCustomerSalesPublic($customerId);
                (new CustomerModel())->updateBalance($customerId, -$refundAmount);
            } else {
                // Fallback path if customer linkage is missing.
                $updateSaleParams = [$refundAmount, $refundAmount, (int)$data['sale_id']];
                if ($cid !== null) $updateSaleParams[] = $cid;
                $db->query(
                    "UPDATE sales
                     SET due_amount = GREATEST(due_amount - ?, 0),
                         payment_status = CASE
                             WHEN GREATEST(due_amount - ?, 0) <= 0.001 THEN 'paid'
                             WHEN paid_amount > 0.001 THEN 'partial'
                             ELSE 'unpaid'
                         END
                     WHERE id = ?{$tenantFilter}",
                    $updateSaleParams
                );
            }

            $db->commit();
            return $returnId;
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function getNextReturnNumber() {
        $tenantFilter = Tenant::id() !== null ? " WHERE company_id = ?" : "";
        $params = Tenant::id() !== null ? [Tenant::id()] : [];
        $lastId = (int)$this->db->query("SELECT COALESCE(MAX(id), 0) FROM {$this->table}{$tenantFilter}", $params)->fetchColumn();
        return $this->displayNumber($lastId + 1);
    }

    public function getRecentSalesForReturn() {
        $where = ["s.deleted_at IS NULL", "s.payment_status IN ('unpaid', 'partial', 'paid')"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "s.company_id = ?";
            $params[] = Tenant::id();
        }

        return $this->db->query(
            "SELECT s.id, s.invoice_number, s.grand_total, s.paid_amount, s.due_amount, s.payment_status,
                    c.name as customer_name, COALESCE(SUM(sr.total_amount), 0) as returned_amount
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN sale_returns sr ON sr.sale_id = s.id AND sr.deleted_at IS NULL AND sr.status = 'posted'
             WHERE " . implode(' AND ', $where) . "
             GROUP BY s.id, s.invoice_number, s.grand_total, s.paid_amount, s.due_amount, s.payment_status, c.name
             HAVING (s.grand_total - COALESCE(SUM(sr.total_amount), 0)) > 0.009
             ORDER BY s.id DESC
             LIMIT 200",
            $params
        )->fetchAll();
    }

    private function displayNumber(int $id): string {
        return 'RET-' . str_pad((string)max(1, $id), 4, '0', STR_PAD_LEFT);
    }

    public function markCancelled(int $id, string $reason, int $userId): void {
        $params = [$reason, $userId, $id];
        $sql = "UPDATE {$this->table}
                SET status = 'cancelled',
                    cancel_reason = ?,
                    cancelled_at = NOW(),
                    cancelled_by = ?
                WHERE id = ? AND deleted_at IS NULL";
        if (Tenant::id() !== null) {
            $sql .= " AND company_id = ?";
            $params[] = Tenant::id();
        }

        $this->db->query($sql, $params);
    }
}
