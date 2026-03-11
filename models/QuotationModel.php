<?php
/**
 * Quotation Model — Multi-Tenant Aware
 */
class QuotationModel extends Model {
    protected $table = 'quotations';

    public function getAllWithCustomer($search = '', $fromDate = '', $toDate = '', $status = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where  = ["q.deleted_at IS NULL"];
        if (Tenant::id() !== null) { $where[] = "q.company_id = ?"; $params[] = Tenant::id(); }
        if ($search) { $where[] = "(q.quotation_number LIKE ? OR c.name LIKE ?)"; $t = "%{$search}%"; $params = array_merge($params, [$t, $t]); }
        if ($fromDate) { $where[] = "q.quotation_date >= ?"; $params[] = $fromDate; }
        if ($toDate)   { $where[] = "q.quotation_date <= ?"; $params[] = $toDate; }
        if ($status)   { $where[] = "q.status = ?";          $params[] = $status; }
        $w = implode(' AND ', $where);
        $total = $this->db->query("SELECT COUNT(*) FROM {$this->table} q LEFT JOIN customers c ON q.customer_id = c.id WHERE {$w}", $params)->fetchColumn();
        $data = $this->db->query(
            "SELECT q.*, c.name as customer_name FROM {$this->table} q LEFT JOIN customers c ON q.customer_id = c.id WHERE {$w} ORDER BY q.id DESC LIMIT {$perPage} OFFSET {$offset}", $params
        )->fetchAll();
        return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'totalPages' => ceil($total / $perPage)];
    }

    public function getWithDetails($id) {
        $where = ["q.id = ?", "q.deleted_at IS NULL"];
        $params = [$id];
        if (Tenant::id() !== null) { $where[] = "q.company_id = ?"; $params[] = Tenant::id(); }
        $quote = $this->db->query(
            "SELECT q.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, c.email as customer_email, u.full_name as created_by_name
             FROM {$this->table} q LEFT JOIN customers c ON q.customer_id = c.id LEFT JOIN users u ON q.created_by = u.id
             WHERE " . implode(' AND ', $where), $params
        )->fetch();
        if ($quote) {
            $quote['items'] = $this->db->query(
                "SELECT qi.*, p.name as product_name, p.sku FROM quotation_items qi LEFT JOIN products p ON qi.product_id = p.id WHERE qi.quotation_id = ?" . (Tenant::id() !== null ? " AND qi.company_id = ?" : ""),
                Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
            )->fetchAll();
        }
        return $quote;
    }

    public function createQuotation($data, $items, $userId) {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $data['created_by'] = $userId;
            $qId = $this->create($data);
            $companyId = Tenant::id() ?? 1;
            foreach ($items as $item) {
                $db->query(
                    "INSERT INTO quotation_items (company_id, quotation_id, product_id, quantity, unit_price, discount, tax_rate, tax_amount, subtotal, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$companyId, $qId, $item['product_id'], $item['quantity'], $item['unit_price'], $item['discount'] ?? 0, $item['tax_rate'] ?? 0, $item['tax_amount'] ?? 0, $item['subtotal'], $item['total']]
                );
            }
            $db->commit();
            return $qId;
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function convertToSale($id, $saleData, $saleItems, $userId) {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $locked = $db->query(
                "SELECT id, status, quotation_number FROM {$this->table} WHERE id = ? AND deleted_at IS NULL" . (Tenant::id() !== null ? " AND company_id = ?" : "") . " FOR UPDATE",
                Tenant::id() !== null ? [$id, Tenant::id()] : [$id]
            )->fetch();
            if (!$locked) throw new Exception('Quotation not found.');
            if ($locked['status'] === 'converted') { $db->commit(); return ['already_converted' => true]; }
            if ($locked['status'] === 'cancelled') { $db->commit(); throw new Exception('Cannot convert a cancelled quotation.'); }

            $quote = $this->getWithDetails($id);
            if (!$quote || empty($quote['items'])) throw new Exception('Quotation has no items to convert.');

            $saleData['created_by'] = $userId;
            $salesModel = new SalesModel();
            $saleId = $salesModel->create($saleData);
            $companyId = Tenant::id() ?? 1;

            foreach ($saleItems as $item) {
                $db->query(
                    "INSERT INTO sale_items (company_id, sale_id, product_id, quantity, unit_price, discount, tax_rate, tax_amount, subtotal, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$companyId, $saleId, $item['product_id'], $item['quantity'], $item['unit_price'], $item['discount'], $item['tax_rate'], $item['tax_amount'], $item['subtotal'], $item['total']]
                );
            }

            $productModel = new ProductModel();
            foreach ($saleItems as $item) {
                $productModel->updateStock($item['product_id'], -$item['quantity'], 'sale', $saleId, $userId, 'Sale #' . $saleData['invoice_number']);
            }

            $customerModel = new CustomerModel();
            $customerModel->updateBalance($saleData['customer_id'], $saleData['due_amount']);

            $this->update($id, ['status' => 'converted']);
            $db->commit();
            return ['sale_id' => $saleId, 'invoice_number' => $saleData['invoice_number'], 'quote' => $quote];
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    public function getNextNumber() {
        $maxRetries = 2;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->getNextNumberLocked();
            } catch (\PDOException $e) {
                if ($attempt < $maxRetries && $e->getCode() == '40001') { usleep(50000); continue; }
                throw $e;
            }
        }
    }

    private function getNextNumberLocked() {
        $db = $this->db;
        $ownTransaction = !$db->getConnection()->inTransaction();
        if ($ownTransaction) $db->beginTransaction();
        try {
            $tenantFilter = Tenant::id() !== null ? " WHERE company_id = ?" : "";
            $params = Tenant::id() !== null ? [Tenant::id()] : [];
            $last = $db->query("SELECT quotation_number FROM {$this->table}{$tenantFilter} ORDER BY id DESC LIMIT 1 FOR UPDATE", $params)->fetchColumn();
            if ($ownTransaction) $db->commit();
            if (!$last) return 'QUO-0001';
            preg_match('/(\d+)$/', $last, $m);
            return 'QUO-' . str_pad((int)($m[1] ?? 0) + 1, 4, '0', STR_PAD_LEFT);
        } catch (\Exception $e) {
            if ($ownTransaction && $db->getConnection()->inTransaction()) $db->rollback();
            throw $e;
        }
    }

    public function getTotals() {
        $where = ["deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) { $where[] = "company_id = ?"; $params[] = Tenant::id(); }
        return $this->db->query(
            "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft, SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent, SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted, SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled, COALESCE(SUM(grand_total), 0) as total_value
             FROM {$this->table} WHERE " . implode(' AND ', $where), $params
        )->fetch();
    }
}
