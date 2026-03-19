<?php
/**
 * Sales Model — Multi-Tenant Aware
 * 
 * Manages sales transactions, items, and related operations.
 * All queries scoped by company_id via Tenant::id().
 */
class SalesModel extends Model {
    protected $table = 'sales';
    /**
     * Cached products table columns for optional HSN compatibility.
     *
     * @var array<string, bool>|null
     */
    private static $productColumnMap = null;

    /**
     * Keep dashboard and report caches coherent after sales mutations.
     */
    private function flushAnalyticCaches(): void {
        $tenantPrefix = 'c' . (Tenant::id() ?? 0) . '_';
        Cache::flushPrefix($tenantPrefix . 'dash_');
        Cache::flushPrefix($tenantPrefix . 'report_');
    }

    /**
     * Get all sales with customer info (tenant-scoped)
     */
    public function getAllWithCustomer($search = '', $fromDate = '', $toDate = '', $customerId = '', $status = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["s.deleted_at IS NULL"];
        $customerJoin = "LEFT JOIN customers c ON s.customer_id = c.id";

        if (Tenant::id() !== null) {
            $where[] = "s.company_id = ?";
            $params[] = Tenant::id();
        }

        if ($search) {
            $where[] = "(s.invoice_number LIKE ? OR c.name LIKE ?)";
            $t = "%{$search}%";
            $params = array_merge($params, [$t, $t]);
        }
        if ($fromDate) { $where[] = "s.sale_date >= ?"; $params[] = $fromDate; }
        if ($toDate) { $where[] = "s.sale_date <= ?"; $params[] = $toDate; }
        if ($customerId) { $where[] = "s.customer_id = ?"; $params[] = $customerId; }
        if ($status) { $where[] = "s.payment_status = ?"; $params[] = $status; }

        $whereClause = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM {$this->table} s";
        if ($search !== '') {
            $countSql .= " {$customerJoin}";
        }
        $countSql .= " WHERE {$whereClause}";
        $total = $this->db->query($countSql, $params)->fetchColumn();

        $data = $this->db->query(
            "SELECT
                s.id,
                s.invoice_number,
                s.customer_id,
                s.sale_date,
                s.subtotal,
                s.discount_amount,
                s.tax_amount,
                s.grand_total,
                s.paid_amount,
                s.due_amount,
                s.payment_status,
                s.status,
                s.created_at,
                c.name as customer_name
             FROM {$this->table} s
             {$customerJoin}
             WHERE {$whereClause}
             ORDER BY s.id DESC
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
     * Get sale with all details (tenant-scoped)
     */
    public function getWithDetails($id) {
        $where = ["s.id = ?", "s.deleted_at IS NULL"];
        $params = [$id];
        if (Tenant::id() !== null) {
            $where[] = "s.company_id = ?";
            $params[] = Tenant::id();
        }

        $sale = $this->db->query(
            "SELECT
                s.id,
                s.invoice_number,
                s.customer_id,
                s.sale_date,
                s.reference_number,
                s.subtotal,
                s.discount_amount,
                s.tax_amount,
                s.gst_type,
                s.shipping_cost,
                s.freight_charge,
                s.loading_charge,
                s.round_off,
                s.grand_total,
                s.paid_amount,
                s.due_amount,
                s.payment_status,
                s.status,
                s.note,
                s.created_by,
                s.created_at,
                c.name as customer_name,
                c.phone as customer_phone,
                c.email as customer_email,
                c.address as customer_address,
                c.city as customer_city,
                c.state as customer_state,
                c.tax_number as customer_tax,
                c.tax_number as customer_tax_number,
                u.full_name as created_by_name
             FROM {$this->table} s
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN users u ON s.created_by = u.id
             WHERE " . implode(' AND ', $where),
            $params
        )->fetch();

        if ($sale) {
            $hsnSelect = $this->productColumnExists('hsn_code')
                ? ", p.hsn_code as hsn_code"
                : ", NULL as hsn_code";
            $sale['items'] = $this->db->query(
                "SELECT
                    si.id,
                    si.sale_id,
                    si.product_id,
                    si.quantity,
                    si.unit_price,
                    si.discount,
                    si.tax_rate,
                    si.tax_amount,
                    si.subtotal,
                    si.total,
                    p.name as product_name,
                    p.sku,
                    un.short_name as unit_name{$hsnSelect}
                 FROM sale_items si
                 LEFT JOIN products p ON si.product_id = p.id
                 LEFT JOIN units un ON p.unit_id = un.id
                 WHERE si.sale_id = ?" . (Tenant::id() !== null ? " AND si.company_id = ?" : ""),
                Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
            )->fetchAll();
        }

        return $sale;
    }

    /**
     * Create a complete sale with items (auto-injects company_id)
     */
    public function createSale($saleData, $items, $userId) {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $saleData['created_by'] = $userId;
            $saleId = $this->create($saleData);
            $companyId = Tenant::id() ?? 1;

            $productModel = new ProductModel();
            foreach ($items as $item) {
                $db->query(
                    "INSERT INTO sale_items (company_id, sale_id, product_id, quantity, unit_price, discount, tax_rate, tax_amount, subtotal, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$companyId, $saleId, $item['product_id'], $item['quantity'], $item['unit_price'], $item['discount'], $item['tax_rate'], $item['tax_amount'], $item['subtotal'], $item['total']]
                );

                // Decrease stock (negative quantity)
                $productModel->updateStock($item['product_id'], -$item['quantity'], 'sale', $saleId, $userId, 'Sale #' . $saleData['invoice_number']);
            }

            // Distribute any unapplied advance payments to this new sale
            $paymentModel = new PaymentModel();
            $paymentModel->recalculateCustomerSalesPublic((int)$saleData['customer_id']);

            // Update customer balance
            $customerModel = new CustomerModel();
            $customerModel->recalculateBalance((int)$saleData['customer_id']);

            $db->commit();
            $this->flushAnalyticCaches();
            return $saleId;
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Update an existing sale with full stock and balance reconciliation.
     */
    public function updateSale($id, $saleData, $items, $userId) {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $old = $this->getWithDetails($id);
            if (!$old) throw new Exception('Sale not found.');

            $productModel  = new ProductModel();
            $customerModel = new CustomerModel();
            $invoiceNum    = $old['invoice_number'];
            $companyId     = Tenant::id() ?? 1;

            // 1. Restore stock from old items
            foreach ($old['items'] as $item) {
                $productModel->updateStock(
                    $item['product_id'], +$item['quantity'],
                    'sale_edit_reverse', $id, $userId,
                    'Edit Reversal: Sale #' . $invoiceNum
                );
            }

            // 2. Delete old items
            $db->query("DELETE FROM sale_items WHERE sale_id = ?" . (Tenant::id() !== null ? " AND company_id = ?" : ""),
                Tenant::id() !== null ? [$id, Tenant::id()] : [$id]);

            // 3. Insert new items + deduct new stock
            foreach ($items as $item) {
                $db->query(
                    "INSERT INTO sale_items (company_id, sale_id, product_id, quantity, unit_price, discount, tax_rate, tax_amount, subtotal, total)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$companyId, $id, $item['product_id'], $item['quantity'], $item['unit_price'],
                     $item['discount'], $item['tax_rate'], $item['tax_amount'], $item['subtotal'], $item['total']]
                );
                $productModel->updateStock(
                    $item['product_id'], -$item['quantity'],
                    'sale_edit', $id, $userId,
                    'Edited Sale #' . $invoiceNum
                );
            }

            // 4. Update sale header
            $this->update($id, $saleData);

            // 5. Recalculate payments and customer balance from scratch
            $paymentModel = new PaymentModel();
            $paymentModel->recalculateCustomerSalesPublic((int)$saleData['customer_id']);
            $customerModel->recalculateBalance((int)$saleData['customer_id']);

            if ((int)$old['customer_id'] !== (int)$saleData['customer_id']) {
                $paymentModel->recalculateCustomerSalesPublic((int)$old['customer_id']);
                $customerModel->recalculateBalance((int)$old['customer_id']);
            }

            $db->commit();
            $this->flushAnalyticCaches();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Delete sale and reverse all its effects
     */
    public function deleteSale($id, $userId) {
        $db = $this->db;
        $db->beginTransaction();

        try {
            $sale = $this->getWithDetails($id);
            if (!$sale) throw new Exception('Sale not found.');

            $productModel = new ProductModel();

            foreach ($sale['items'] as $item) {
                $productModel->updateStock(
                    $item['product_id'], +$item['quantity'],
                    'sale_cancel', $id, $userId,
                    'Sale Cancelled #' . $sale['invoice_number']
                );
            }

            $this->delete($id);

            $paymentModel = new PaymentModel();
            $paymentModel->recalculateCustomerSalesPublic((int)$sale['customer_id']);

            $customerModel = new CustomerModel();
            $customerModel->recalculateBalance((int)$sale['customer_id']);

            $db->commit();
            $this->flushAnalyticCaches();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    /**
     * Get sale totals (tenant-scoped)
     */
    public function getTotals($period = 'all') {
        $where = ["deleted_at IS NULL"];
        $params = [];

        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }

        if ($period === 'today') {
            $where[] = "sale_date = CURDATE()";
        } elseif ($period === 'month') {
            $where[] = "sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
        } elseif ($period === 'year') {
            $where[] = "sale_date >= DATE_FORMAT(CURDATE(), '%Y-01-01')";
        }

        return $this->db->query(
            "SELECT COUNT(*) as total_count, COALESCE(SUM(grand_total), 0) as total_amount, COALESCE(SUM(due_amount), 0) as total_due, COALESCE(SUM(paid_amount), 0) as total_paid
             FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetch();
    }

    /**
     * Get all dashboard totals in a single query (tenant-scoped)
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
                COALESCE(SUM(CASE WHEN sale_date = CURDATE() THEN grand_total ELSE 0 END), 0) as today_amount,
                COALESCE(SUM(CASE WHEN sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN grand_total ELSE 0 END), 0) as month_amount
             FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetch();
    }

    /**
     * Get monthly sales data for chart (tenant-scoped)
     */
    public function getMonthlyData($year = null) {
        $year = (int)($year ?? date('Y'));
        $startDate = "$year-01-01";
        $endDate = "$year-12-31";
        $where = ["sale_date BETWEEN ? AND ?", "deleted_at IS NULL"];
        $params = [$startDate, $endDate];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT MONTH(sale_date) as month, COALESCE(SUM(grand_total), 0) as total
             FROM {$this->table}
             WHERE " . implode(' AND ', $where) . "
             GROUP BY MONTH(sale_date)
             ORDER BY month",
            $params
        )->fetchAll();
    }

    /**
     * Get profit data (tenant-scoped)
     */
    public function getProfitData($fromDate = null, $toDate = null) {
        $where = ["s.deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "s.company_id = ?";
            $params[] = Tenant::id();
        }
        if ($fromDate) { $where[] = "s.sale_date >= ?"; $params[] = $fromDate; }
        if ($toDate) { $where[] = "s.sale_date <= ?"; $params[] = $toDate; }

        return $this->db->query(
            "SELECT
                COALESCE(SUM(t.total_sales), 0) as total_sales,
                COALESCE(SUM(t.total_cost), 0) as total_cost,
                COALESCE(SUM(t.gross_profit), 0) as gross_profit,
                COALESCE(SUM(t.discount_amount), 0) as total_discount,
                COALESCE(SUM(t.gross_profit - t.discount_amount), 0) as net_profit
             FROM (
                SELECT
                    s.id,
                    COALESCE(SUM(si.total), 0) as total_sales,
                    COALESCE(SUM(si.quantity * p.purchase_price), 0) as total_cost,
                    COALESCE(SUM(si.total - (si.quantity * p.purchase_price)), 0) as gross_profit,
                    COALESCE(MAX(s.discount_amount), 0) as discount_amount
                FROM {$this->table} s
                JOIN sale_items si ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY s.id
             ) t",
            $params
        )->fetch();
    }

    /**
     * Get top selling products (tenant-scoped)
     */
    public function getTopProducts($limit = 10) {
        $where = ["s.deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "s.company_id = ?";
            $params[] = Tenant::id();
        }
        $params[] = $limit;
        return $this->db->query(
            "SELECT p.name, p.sku, SUM(si.quantity) as total_qty, SUM(si.total) as total_amount
             FROM sale_items si
             JOIN {$this->table} s ON si.sale_id = s.id
             JOIN products p ON si.product_id = p.id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY si.product_id
             ORDER BY total_qty DESC
             LIMIT ?",
            $params
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
