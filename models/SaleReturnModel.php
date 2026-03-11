<?php
/**
 * Sale Return Model — Multi-Tenant Aware
 */
class SaleReturnModel extends Model {
    protected $table = 'sale_returns';

    public function getAll($search = '', $fromDate = '', $toDate = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where  = ["sr.deleted_at IS NULL"];
        if (Tenant::id() !== null) { $where[] = "sr.company_id = ?"; $params[] = Tenant::id(); }
        if ($search) { $where[] = "(sr.return_number LIKE ? OR c.name LIKE ? OR s.invoice_number LIKE ?)"; $t = "%{$search}%"; $params = array_merge($params, [$t, $t, $t]); }
        if ($fromDate) { $where[] = "sr.return_date >= ?"; $params[] = $fromDate; }
        if ($toDate)   { $where[] = "sr.return_date <= ?"; $params[] = $toDate; }
        $w = implode(' AND ', $where);
        $joinSql = "FROM {$this->table} sr LEFT JOIN sales s ON sr.sale_id = s.id LEFT JOIN customers c ON s.customer_id = c.id";
        $total = $this->db->query("SELECT COUNT(*) {$joinSql} WHERE {$w}", $params)->fetchColumn();
        $data = $this->db->query(
            "SELECT sr.*, c.name as customer_name, s.invoice_number, s.customer_id {$joinSql} WHERE {$w} ORDER BY sr.id DESC LIMIT {$perPage} OFFSET {$offset}", $params
        )->fetchAll();
        return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'totalPages' => ceil($total / $perPage)];
    }

    public function getWithDetails($id) {
        $where = ["sr.id = ?", "sr.deleted_at IS NULL"];
        $params = [$id];
        if (Tenant::id() !== null) { $where[] = "sr.company_id = ?"; $params[] = Tenant::id(); }
        $return = $this->db->query(
            "SELECT sr.*, c.name as customer_name, c.phone as customer_phone, s.invoice_number, s.customer_id, u.full_name as created_by_name
             FROM {$this->table} sr LEFT JOIN sales s ON sr.sale_id = s.id LEFT JOIN customers c ON s.customer_id = c.id LEFT JOIN users u ON sr.created_by = u.id
             WHERE " . implode(' AND ', $where), $params
        )->fetch();
        if ($return) {
            $return['items'] = $this->db->query(
                "SELECT sri.*, p.name as product_name, p.sku FROM sale_return_items sri LEFT JOIN products p ON sri.product_id = p.id WHERE sri.return_id = ?" . (Tenant::id() !== null ? " AND sri.company_id = ?" : ""),
                Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
            )->fetchAll();
        }
        return $return;
    }

    public function createReturn($data, $items, $userId) {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $data['created_by'] = $userId;
            $cid = Tenant::id();
            $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
            $saleParams = [$data['sale_id']];
            if ($cid !== null) $saleParams[] = $cid;
            $sale = $db->query("SELECT grand_total, paid_amount, due_amount, customer_id FROM sales WHERE id = ? AND deleted_at IS NULL{$tenantFilter}", $saleParams)->fetch();
            if (!$sale) throw new Exception('Original sale not found.');

            $refundAmount = (float)$data['total_amount'];
            $paidSoFar = (float)$sale['grand_total'] - (float)$sale['due_amount'];
            if ($refundAmount > $paidSoFar + 0.001) {
                throw new Exception('Return amount (₹' . number_format($refundAmount, 2) . ') exceeds the amount paid (₹' . number_format($paidSoFar, 2) . ') on this sale.');
            }

            $returnId = $this->create($data);
            $companyId = $cid ?? 1;
            $productModel = new ProductModel();
            foreach ($items as $item) {
                $db->query(
                    "INSERT INTO sale_return_items (company_id, return_id, product_id, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?, ?)",
                    [$companyId, $returnId, $item['product_id'], $item['quantity'], $item['unit_price'], $item['total']]
                );
                $productModel->updateStock($item['product_id'], +$item['quantity'], 'return', $returnId, $userId, 'Sale Return #' . $data['return_number']);
            }

            $updateSaleParams = [$refundAmount, $refundAmount, $refundAmount, $refundAmount, $data['sale_id']];
            if ($cid !== null) $updateSaleParams[] = $cid;
            $db->query(
                "UPDATE sales SET paid_amount = GREATEST(paid_amount - ?, 0), due_amount = due_amount + ?,
                 payment_status = CASE WHEN (due_amount + ?) >= grand_total THEN 'unpaid' WHEN GREATEST(paid_amount - ?, 0) > 0 THEN 'partial' ELSE 'unpaid' END WHERE id = ?{$tenantFilter}",
                $updateSaleParams
            );

            $customerId = (int)$sale['customer_id'];
            if ($customerId) {
                (new CustomerModel())->updateBalance($customerId, -$refundAmount);
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
        $last = $this->db->query("SELECT return_number FROM {$this->table}{$tenantFilter} ORDER BY id DESC LIMIT 1", $params)->fetchColumn();
        if (!$last) return 'RET-0001';
        preg_match('/(\d+)$/', $last, $m);
        return 'RET-' . str_pad((int)($m[1] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
    }

    public function getRecentSalesForReturn() {
        $where = ["s.deleted_at IS NULL", "s.payment_status IN ('unpaid', 'partial', 'paid')"];
        $params = [];
        if (Tenant::id() !== null) { $where[] = "s.company_id = ?"; $params[] = Tenant::id(); }
        return $this->db->query(
            "SELECT s.id, s.invoice_number, s.grand_total, s.paid_amount, s.due_amount, s.payment_status, c.name as customer_name
             FROM sales s LEFT JOIN customers c ON s.customer_id = c.id
             WHERE " . implode(' AND ', $where) . " ORDER BY s.id DESC LIMIT 200", $params
        )->fetchAll();
    }
}
