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
     * Get all sales with customer info (tenant-scoped)
     */
    public function getAllWithCustomer($search = '', $fromDate = '', $toDate = '', $customerId = '', $status = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["s.deleted_at IS NULL"];

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

        $total = $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} s LEFT JOIN customers c ON s.customer_id = c.id WHERE {$whereClause}",
            $params
        )->fetchColumn();

        $data = $this->db->query(
            "SELECT s.*, c.name as customer_name
             FROM {$this->table} s
             LEFT JOIN customers c ON s.customer_id = c.id
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
            "SELECT s.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                    c.address as customer_address, c.city as customer_city, c.state as customer_state,
                    c.tax_number as customer_tax, u.full_name as created_by_name
             FROM {$this->table} s
             LEFT JOIN customers c ON s.customer_id = c.id
             LEFT JOIN users u ON s.created_by = u.id
             WHERE " . implode(' AND ', $where),
            $params
        )->fetch();

        if ($sale) {
            $sale['items'] = $this->db->query(
                "SELECT si.*, p.name as product_name, p.sku, un.short_name as unit_name
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

            // Update customer balance
            $customerModel = new CustomerModel();
            $customerModel->updateBalance($saleData['customer_id'], $saleData['due_amount']);

            $db->commit();
            Cache::flushPrefix('c' . (Tenant::id() ?? 0) . '_dash_');
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

            // 5. Recalculate customer balance from scratch
            $customerModel->recalculateBalance($saleData['customer_id']);
            if ((int)$old['customer_id'] !== (int)$saleData['customer_id']) {
                $customerModel->recalculateBalance($old['customer_id']);
            }

            $db->commit();
            Cache::flushPrefix('c' . (Tenant::id() ?? 0) . '_dash_');
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
            Cache::flushPrefix('c' . (Tenant::id() ?? 0) . '_dash_');
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
                COALESCE(SUM(si.total), 0) as total_sales,
                COALESCE(SUM(si.quantity * p.purchase_price), 0) as total_cost,
                COALESCE(SUM(si.total - (si.quantity * p.purchase_price)), 0) as gross_profit,
                COALESCE(SUM(s.discount_amount), 0) as total_discount,
                COALESCE(SUM(si.total - (si.quantity * p.purchase_price)) - SUM(s.discount_amount), 0) as net_profit
             FROM sale_items si
             JOIN {$this->table} s ON si.sale_id = s.id
             JOIN products p ON si.product_id = p.id
             WHERE " . implode(' AND ', $where),
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
}
