<?php
/**
 * Payment Model — Multi-Tenant Aware
 */
class PaymentModel extends Model {
    protected $table = 'payments';

    /**
     * Keep dashboard and report caches coherent after payment mutations.
     */
    private function flushAnalyticCaches(): void {
        $tenantPrefix = 'c' . (Tenant::id() ?? 0) . '_';
        Cache::flushPrefix($tenantPrefix . 'dash_');
        Cache::flushPrefix($tenantPrefix . 'report_');
    }

    public function getAllPaginated($type = '', $search = '', $fromDate = '', $toDate = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["p.deleted_at IS NULL"];
        $partyJoin = "LEFT JOIN customers c ON p.customer_id = c.id LEFT JOIN suppliers s ON p.supplier_id = s.id";
        if (Tenant::id() !== null) { $where[] = "p.company_id = ?"; $params[] = Tenant::id(); }
        if ($type) { $where[] = "p.type = ?"; $params[] = $type; }
        if ($search) {
            $where[] = "(p.payment_number LIKE ? OR c.name LIKE ? OR s.name LIKE ?)";
            $t = "%{$search}%";
            $params = array_merge($params, [$t, $t, $t]);
        }
        if ($fromDate) { $where[] = "p.payment_date >= ?"; $params[] = $fromDate; }
        if ($toDate) { $where[] = "p.payment_date <= ?"; $params[] = $toDate; }
        $whereClause = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM {$this->table} p";
        if ($search !== '') {
            $countSql .= " {$partyJoin}";
        }
        $countSql .= " WHERE {$whereClause}";
        $total = $this->db->query($countSql, $params)->fetchColumn();
        $data = $this->db->query(
            "SELECT
                p.id,
                p.payment_number,
                p.type,
                p.customer_id,
                p.supplier_id,
                p.sale_id,
                p.purchase_id,
                p.amount,
                p.payment_method,
                p.payment_date,
                p.reference_number,
                p.bank_name,
                p.note,
                p.created_by,
                p.created_at,
                c.name as customer_name,
                s.name as supplier_name
             FROM {$this->table} p
             {$partyJoin}
             WHERE {$whereClause}
             ORDER BY p.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        )->fetchAll();
        return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'totalPages' => ceil($total / $perPage)];
    }

    public function createPayment($data, $userId) {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $data['created_by'] = $userId;
            $paymentId = $this->create($data);
            $remaining = (float)$data['amount'];

            if ($data['type'] === 'receipt' && !empty($data['customer_id'])) {
                $customerModel = new CustomerModel();
                $customerModel->updateBalance($data['customer_id'], -$data['amount']);
                if (!empty($data['sale_id'])) {
                    $remaining = $this->applyPaymentToSale($db, (int)$data['sale_id'], $remaining);
                } else {
                    $cid = Tenant::id();
                    $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
                    $saleParams = [$data['customer_id']];
                    if ($cid !== null) $saleParams[] = $cid;
                    $unpaidSales = $db->query(
                        "SELECT id, due_amount FROM sales WHERE customer_id = ? AND payment_status IN ('unpaid','partial') AND deleted_at IS NULL {$tenantFilter} ORDER BY id ASC",
                        $saleParams
                    )->fetchAll();
                    foreach ($unpaidSales as $sale) {
                        if ($remaining <= 0) break;
                        $remaining = $this->applyPaymentToSale($db, $sale['id'], $remaining);
                    }
                }
            } elseif ($data['type'] === 'payment' && !empty($data['supplier_id'])) {
                $supplierModel = new SupplierModel();
                $supplierModel->updateBalance($data['supplier_id'], -$data['amount']);
                if (!empty($data['purchase_id'])) {
                    $remaining = $this->applyPaymentToPurchase($db, (int)$data['purchase_id'], $remaining);
                } else {
                    $cid = Tenant::id();
                    $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
                    $purchaseParams = [$data['supplier_id']];
                    if ($cid !== null) $purchaseParams[] = $cid;
                    $unpaidPurchases = $db->query(
                        "SELECT id, due_amount FROM purchases WHERE supplier_id = ? AND payment_status IN ('unpaid','partial') AND deleted_at IS NULL {$tenantFilter} ORDER BY id ASC",
                        $purchaseParams
                    )->fetchAll();
                    foreach ($unpaidPurchases as $purchase) {
                        if ($remaining <= 0) break;
                        $remaining = $this->applyPaymentToPurchase($db, $purchase['id'], $remaining);
                    }
                }
            }
            $db->commit();
            $this->flushAnalyticCaches();
            return $paymentId;
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    private function applyPaymentToSale($db, $saleId, $amount) {
        $cid = Tenant::id();
        $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
        $params = [$saleId];
        if ($cid !== null) $params[] = $cid;
        $sale = $db->query("SELECT paid_amount, due_amount, grand_total FROM sales WHERE id = ?{$tenantFilter}", $params)->fetch();
        if (!$sale) return $amount;
        $apply = min((float)$amount, (float)$sale['due_amount']);
        $newPaid = (float)$sale['paid_amount'] + $apply;
        $newDue = (float)$sale['due_amount'] - $apply;
        $status = 'unpaid';
        if ($newDue <= 0.001) { $status = 'paid'; $newDue = 0; } elseif ($newPaid > 0) { $status = 'partial'; }
        $updateParams = [$newPaid, $newDue, $status, $saleId];
        if ($cid !== null) $updateParams[] = $cid;
        $db->query("UPDATE sales SET paid_amount = ?, due_amount = ?, payment_status = ? WHERE id = ?{$tenantFilter}", $updateParams);
        return $amount - $apply;
    }

    private function applyPaymentToPurchase($db, $purchaseId, $amount) {
        $cid = Tenant::id();
        $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
        $params = [$purchaseId];
        if ($cid !== null) $params[] = $cid;
        $purchase = $db->query("SELECT paid_amount, due_amount, grand_total FROM purchases WHERE id = ?{$tenantFilter}", $params)->fetch();
        if (!$purchase) return $amount;
        $apply = min((float)$amount, (float)$purchase['due_amount']);
        $newPaid = (float)$purchase['paid_amount'] + $apply;
        $newDue = (float)$purchase['due_amount'] - $apply;
        $status = 'unpaid';
        if ($newDue <= 0.001) { $status = 'paid'; $newDue = 0; } elseif ($newPaid > 0) { $status = 'partial'; }
        $updateParams = [$newPaid, $newDue, $status, $purchaseId];
        if ($cid !== null) $updateParams[] = $cid;
        $db->query("UPDATE purchases SET paid_amount = ?, due_amount = ?, payment_status = ? WHERE id = ?{$tenantFilter}", $updateParams);
        return $amount - $apply;
    }

    public function getWithDetails($id) {
        $where = ["p.id = ?", "p.deleted_at IS NULL"];
        $params = [$id];
        if (Tenant::id() !== null) { $where[] = "p.company_id = ?"; $params[] = Tenant::id(); }
        return $this->db->query(
            "SELECT
                p.id,
                p.payment_number,
                p.type,
                p.customer_id,
                p.supplier_id,
                p.sale_id,
                p.purchase_id,
                p.amount,
                p.payment_method,
                p.payment_date,
                p.reference_number,
                p.bank_name,
                p.note,
                p.created_by,
                p.created_at,
                c.name as customer_name,
                c.phone as customer_phone,
                s.name as supplier_name,
                s.phone as supplier_phone,
                u.full_name as created_by_name
             FROM {$this->table} p LEFT JOIN customers c ON p.customer_id = c.id LEFT JOIN suppliers s ON p.supplier_id = s.id LEFT JOIN users u ON p.created_by = u.id
             WHERE " . implode(' AND ', $where), $params
        )->fetch();
    }

    public function getSummary($type = '', $period = 'all') {
        $where = ["deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) { $where[] = "company_id = ?"; $params[] = Tenant::id(); }
        if ($type) { $where[] = "type = ?"; $params[] = $type; }
        if ($period === 'today') $where[] = "payment_date = CURDATE()";
        elseif ($period === 'month') $where[] = "MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())";
        return $this->db->query(
            "SELECT COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END), 0) as cash_total,
                    COALESCE(SUM(CASE WHEN payment_method = 'bank' THEN amount ELSE 0 END), 0) as bank_total
             FROM {$this->table} WHERE " . implode(' AND ', $where), $params
        )->fetch();
    }

    public function deletePayment($id) {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $payment = $this->find($id);
            if (!$payment) throw new Exception('Payment not found.');
            $this->delete($id);
            if ($payment['type'] === 'receipt' && !empty($payment['customer_id'])) {
                $this->recalculateCustomerSalesPublic((int)$payment['customer_id']);
                (new CustomerModel())->recalculateBalance((int)$payment['customer_id']);
            } elseif ($payment['type'] === 'payment' && !empty($payment['supplier_id'])) {
                $this->recalculateSupplierPurchasesPublic((int)$payment['supplier_id']);
                (new SupplierModel())->recalculateBalance((int)$payment['supplier_id']);
            }
            $db->commit();
            $this->flushAnalyticCaches();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function recalculateCustomerSalesPublic($customerId) {
        $db = $this->db;
        $cid = Tenant::id();
        $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
        $salesSql = "SELECT s.id, s.grand_total, COALESCE(SUM(sr.total_amount), 0) as returned_total
                     FROM sales s
                     LEFT JOIN sale_returns sr ON sr.sale_id = s.id AND sr.deleted_at IS NULL";
        $salesParams = [];
        if ($cid !== null) {
            $salesSql .= " AND sr.company_id = ?";
            $salesParams[] = $cid;
        }
        $salesSql .= " WHERE s.customer_id = ? AND s.deleted_at IS NULL";
        $salesParams[] = $customerId;
        if ($cid !== null) {
            $salesSql .= " AND s.company_id = ?";
            $salesParams[] = $cid;
        }
        $salesSql .= " GROUP BY s.id, s.grand_total ORDER BY s.id ASC";
        $sales = $db->query($salesSql, $salesParams)->fetchAll();

        $receiptParams = [$customerId]; if ($cid !== null) $receiptParams[] = $cid;
        $totalReceipts = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM {$this->table} WHERE customer_id = ? AND type = 'receipt' AND deleted_at IS NULL {$tenantFilter}", $receiptParams)->fetchColumn();
        $remaining = $totalReceipts;
        foreach ($sales as $sale) {
            $effectiveTotal = max(0, (float)$sale['grand_total'] - (float)($sale['returned_total'] ?? 0));
            $apply = min($remaining, $effectiveTotal);
            $newPaid = $apply; $newDue = $effectiveTotal - $apply;
            if ($newDue <= 0.009) { $status = 'paid'; $newDue = 0; $newPaid = $effectiveTotal; }
            elseif ($newPaid > 0.009) { $status = 'partial'; } else { $status = 'unpaid'; }
            $updateParams = [$newPaid, $newDue, $status, $sale['id']];
            if ($cid !== null) $updateParams[] = $cid;
            $db->query("UPDATE sales SET paid_amount = ?, due_amount = ?, payment_status = ? WHERE id = ?{$tenantFilter}", $updateParams);
            $remaining -= $apply;
            if ($remaining < 0) $remaining = 0;
        }
    }

    public function recalculateSupplierPurchasesPublic($supplierId) {
        $db = $this->db;
        $cid = Tenant::id();
        $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
        $purchParams = [$supplierId]; if ($cid !== null) $purchParams[] = $cid;
        $purchases = $db->query("SELECT id, grand_total FROM purchases WHERE supplier_id = ? AND deleted_at IS NULL {$tenantFilter} ORDER BY id ASC", $purchParams)->fetchAll();
        $payParams = [$supplierId]; if ($cid !== null) $payParams[] = $cid;
        $totalPayments = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM {$this->table} WHERE supplier_id = ? AND type = 'payment' AND deleted_at IS NULL {$tenantFilter}", $payParams)->fetchColumn();
        $remaining = $totalPayments;
        foreach ($purchases as $purchase) {
            $grandTotal = (float)$purchase['grand_total'];
            $apply = min($remaining, $grandTotal);
            $newPaid = $apply; $newDue = $grandTotal - $apply;
            if ($newDue <= 0.009) { $status = 'paid'; $newDue = 0; $newPaid = $grandTotal; }
            elseif ($newPaid > 0.009) { $status = 'partial'; } else { $status = 'unpaid'; }
            $updateParams = [$newPaid, $newDue, $status, $purchase['id']];
            if ($cid !== null) $updateParams[] = $cid;
            $db->query("UPDATE purchases SET paid_amount = ?, due_amount = ?, payment_status = ? WHERE id = ?{$tenantFilter}", $updateParams);
            $remaining -= $apply;
            if ($remaining < 0) $remaining = 0;
        }
    }
}
