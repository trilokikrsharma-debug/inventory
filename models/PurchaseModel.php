<?php
/**
 * Purchase Model — Multi-Tenant Aware
 */
class PurchaseModel extends Model {
    protected $table = 'purchases';
    /**
     * Cached products table columns for optional HSN compatibility.
     *
     * @var array<string, bool>|null
     */
    private static $productColumnMap = null;

    /**
     * Keep dashboard and report caches coherent after purchase mutations.
     */
    private function flushAnalyticCaches(): void {
        $tenantPrefix = 'c' . (Tenant::id() ?? 0) . '_';
        Cache::flushPrefix($tenantPrefix . 'dash_');
        Cache::flushPrefix($tenantPrefix . 'report_');
    }

    public function getAllWithSupplier($search = '', $fromDate = '', $toDate = '', $supplierId = '', $status = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["p.deleted_at IS NULL"];
        $supplierJoin = "LEFT JOIN suppliers s ON p.supplier_id = s.id";
        if (Tenant::id() !== null) { $where[] = "p.company_id = ?"; $params[] = Tenant::id(); }
        if ($search) { $where[] = "(p.invoice_number LIKE ? OR s.name LIKE ?)"; $t = "%{$search}%"; $params = array_merge($params, [$t, $t]); }
        if ($fromDate) { $where[] = "p.purchase_date >= ?"; $params[] = $fromDate; }
        if ($toDate) { $where[] = "p.purchase_date <= ?"; $params[] = $toDate; }
        if ($supplierId) { $where[] = "p.supplier_id = ?"; $params[] = $supplierId; }
        if ($status) { $where[] = "p.payment_status = ?"; $params[] = $status; }
        $whereClause = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM {$this->table} p";
        if ($search !== '') {
            $countSql .= " {$supplierJoin}";
        }
        $countSql .= " WHERE {$whereClause}";
        $total = $this->db->query($countSql, $params)->fetchColumn();
        $data = $this->db->query(
            "SELECT
                p.id,
                p.invoice_number,
                p.supplier_id,
                p.purchase_date,
                p.subtotal,
                p.discount_amount,
                p.tax_amount,
                p.grand_total,
                p.paid_amount,
                p.due_amount,
                p.payment_status,
                p.status,
                p.created_at,
                s.name as supplier_name
             FROM {$this->table} p
             {$supplierJoin}
             WHERE {$whereClause}
             ORDER BY p.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        )->fetchAll();
        return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'totalPages' => ceil($total / $perPage)];
    }

    public function getWithDetails($id) {
        $where = ["p.id = ?", "p.deleted_at IS NULL"];
        $params = [$id];
        if (Tenant::id() !== null) { $where[] = "p.company_id = ?"; $params[] = Tenant::id(); }
        $purchase = $this->db->query(
            "SELECT
                p.id,
                p.invoice_number,
                p.supplier_id,
                p.purchase_date,
                p.reference_number,
                p.subtotal,
                p.discount_amount,
                p.tax_amount,
                p.gst_type,
                p.shipping_cost,
                p.freight_charge,
                p.loading_charge,
                p.round_off,
                p.grand_total,
                p.paid_amount,
                p.due_amount,
                p.payment_status,
                p.status,
                p.note,
                p.created_by,
                p.created_at,
                s.name as supplier_name,
                s.phone as supplier_phone,
                s.email as supplier_email,
                s.address as supplier_address,
                u.full_name as created_by_name
             FROM {$this->table} p LEFT JOIN suppliers s ON p.supplier_id = s.id LEFT JOIN users u ON p.created_by = u.id
             WHERE " . implode(' AND ', $where), $params
        )->fetch();
        if ($purchase) {
            $hsnSelect = $this->productColumnExists('hsn_code')
                ? ", pr.hsn_code as hsn_code"
                : ", NULL as hsn_code";
            $purchase['items'] = $this->db->query(
                "SELECT
                    pi.id,
                    pi.purchase_id,
                    pi.product_id,
                    pi.quantity,
                    pi.unit_price,
                    pi.discount,
                    pi.tax_rate,
                    pi.tax_amount,
                    pi.subtotal,
                    pi.total,
                    pr.name as product_name,
                    pr.sku,
                    un.short_name as unit_name{$hsnSelect}
                 FROM purchase_items pi LEFT JOIN products pr ON pi.product_id = pr.id LEFT JOIN units un ON pr.unit_id = un.id
                 WHERE pi.purchase_id = ?" . (Tenant::id() !== null ? " AND pi.company_id = ?" : ""),
                Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
            )->fetchAll();
        }
        return $purchase;
    }

    public function createPurchase($purchaseData, $items, $userId) {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $purchaseData['created_by'] = $userId;
            $purchaseId = $this->create($purchaseData);
            $companyId = Tenant::id() ?? 1;
            $productModel = new ProductModel();
            foreach ($items as $item) {
                $db->query(
                    "INSERT INTO purchase_items (company_id, purchase_id, product_id, quantity, unit_price, discount, tax_rate, tax_amount, subtotal, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$companyId, $purchaseId, $item['product_id'], $item['quantity'], $item['unit_price'], $item['discount'] ?? 0, $item['tax_rate'] ?? 0, $item['tax_amount'] ?? 0, $item['subtotal'], $item['total']]
                );
                $productModel->updateStock($item['product_id'], +$item['quantity'], 'purchase', $purchaseId, $userId, 'Purchase #' . $purchaseData['invoice_number']);
            }
            $paymentModel = new PaymentModel();
            $paymentModel->recalculateSupplierPurchasesPublic((int)$purchaseData['supplier_id']);

            $supplierModel = new SupplierModel();
            $supplierModel->recalculateBalance((int)$purchaseData['supplier_id']);
            
            $db->commit();
            $this->flushAnalyticCaches();
            return $purchaseId;
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function updatePurchase($id, $purchaseData, $items, $userId) {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $old = $this->getWithDetails($id);
            if (!$old) throw new Exception('Purchase not found.');
            $productModel = new ProductModel();
            $supplierModel = new SupplierModel();
            $invoiceNum = $old['invoice_number'];
            $companyId = Tenant::id() ?? 1;

            foreach ($old['items'] as $item) {
                $productModel->updateStock($item['product_id'], -$item['quantity'], 'purchase_edit_reverse', $id, $userId, 'Edit Reversal: Purchase #' . $invoiceNum);
            }
            $db->query("DELETE FROM purchase_items WHERE purchase_id = ?" . (Tenant::id() !== null ? " AND company_id = ?" : ""),
                Tenant::id() !== null ? [$id, Tenant::id()] : [$id]);
            foreach ($items as $item) {
                $db->query(
                    "INSERT INTO purchase_items (company_id, purchase_id, product_id, quantity, unit_price, discount, tax_rate, tax_amount, subtotal, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$companyId, $id, $item['product_id'], $item['quantity'], $item['unit_price'], $item['discount'] ?? 0, $item['tax_rate'] ?? 0, $item['tax_amount'] ?? 0, $item['subtotal'], $item['total']]
                );
                $productModel->updateStock($item['product_id'], +$item['quantity'], 'purchase_edit', $id, $userId, 'Edited Purchase #' . $invoiceNum);
            }
            $this->update($id, $purchaseData);
            
            $paymentModel = new PaymentModel();
            $paymentModel->recalculateSupplierPurchasesPublic((int)$purchaseData['supplier_id']);
            $supplierModel->recalculateBalance((int)$purchaseData['supplier_id']);
            
            if ((int)$old['supplier_id'] !== (int)$purchaseData['supplier_id']) {
                $paymentModel->recalculateSupplierPurchasesPublic((int)$old['supplier_id']);
                $supplierModel->recalculateBalance((int)$old['supplier_id']);
            }
            $db->commit();
            $this->flushAnalyticCaches();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function deletePurchase($id, $userId) {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $purchase = $this->getWithDetails($id);
            if (!$purchase) throw new Exception('Purchase not found.');
            $productModel = new ProductModel();
            foreach ($purchase['items'] as $item) {
                $productModel->updateStock($item['product_id'], -$item['quantity'], 'purchase_cancel', $id, $userId, 'Purchase Cancelled #' . $purchase['invoice_number']);
            }
            $this->delete($id);
            $paymentModel = new PaymentModel();
            $paymentModel->recalculateSupplierPurchasesPublic((int)$purchase['supplier_id']);
            $supplierModel = new SupplierModel();
            $supplierModel->recalculateBalance((int)$purchase['supplier_id']);
            $db->commit();
            $this->flushAnalyticCaches();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Get all dashboard totals in a single query (tenant-scoped).
     * 
     * Returns today/month/all purchase totals in one round-trip,
     * matching the same contract as SalesModel::getDashboardTotals().
     * Used by DashboardController to build the KPI cards.
     */
    public function getDashboardTotals() {
        $where = ["deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }

        return $this->db->query(
            "SELECT 
                COALESCE(SUM(grand_total), 0) as all_amount,
                COALESCE(SUM(CASE WHEN purchase_date = CURDATE() THEN grand_total ELSE 0 END), 0) as today_amount,
                COALESCE(SUM(CASE WHEN purchase_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN grand_total ELSE 0 END), 0) as month_amount
             FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetch();
    }

    public function getTotals($period = 'all') {
        $where = ["deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) { $where[] = "company_id = ?"; $params[] = Tenant::id(); }
        if ($period === 'today') $where[] = "purchase_date = CURDATE()";
        elseif ($period === 'month') $where[] = "purchase_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        elseif ($period === 'year') $where[] = "purchase_date >= DATE_FORMAT(CURDATE(), '%Y-01-01')";
        return $this->db->query(
            "SELECT COUNT(*) as total_count, COALESCE(SUM(grand_total), 0) as total_amount, COALESCE(SUM(due_amount), 0) as total_due
             FROM {$this->table} WHERE " . implode(' AND ', $where), $params
        )->fetch();
    }

    public function getMonthlyData($year = null) {
        $year = (int)($year ?? date('Y'));
        $where = ["purchase_date BETWEEN ? AND ?", "deleted_at IS NULL"];
        $params = ["$year-01-01", "$year-12-31"];
        if (Tenant::id() !== null) { $where[] = "company_id = ?"; $params[] = Tenant::id(); }
        return $this->db->query(
            "SELECT MONTH(purchase_date) as month, COALESCE(SUM(grand_total), 0) as total FROM {$this->table}
             WHERE " . implode(' AND ', $where) . " GROUP BY MONTH(purchase_date) ORDER BY month", $params
        )->fetchAll();
    }

    /**
     * Check products table column existence with cached schema lookup.
     */
    private function productColumnExists(string $column): bool {
        if (self::$productColumnMap === null) {
            self::$productColumnMap = [];
            try {
                $rows = Database::getInstance()->query("SHOW COLUMNS FROM products")->fetchAll();
                foreach ($rows as $row) {
                    if (!empty($row['Field'])) {
                        self::$productColumnMap[$row['Field']] = true;
                    }
                }
            } catch (Throwable $e) {
                self::$productColumnMap = [];
            }
        }
        return !empty(self::$productColumnMap[$column]);
    }
}
