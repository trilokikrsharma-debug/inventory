<?php
/**
 * Customer Model — Multi-Tenant Aware
 */
class CustomerModel extends Model {
    protected $table = 'customers';

    /**
     * Get all customers with pagination and search (tenant-scoped)
     */
    public function getAllPaginated($search = '', $page = 1, $perPage = RECORDS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = ["deleted_at IS NULL"];

        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }

        if ($search) {
            $where[] = "(name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $s = "%{$search}%";
            $params = array_merge($params, [$s, $s, $s]);
        }
        $whereClause = implode(' AND ', $where);

        $total = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}", $params)->fetchColumn();
        $data = $this->db->query(
            "SELECT * FROM {$this->table} WHERE {$whereClause} ORDER BY name ASC LIMIT {$perPage} OFFSET {$offset}",
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
     * Update customer balance (tenant-scoped)
     */
    public function updateBalance($customerId, $amount) {
        $sql = "UPDATE {$this->table} SET current_balance = current_balance + ? WHERE id = ?";
        $params = [$amount, $customerId];
        if (Tenant::id() !== null) {
            $sql .= " AND company_id = ?";
            $params[] = Tenant::id();
        }
        $res = $this->db->query($sql, $params);
        Cache::flushPrefix('c' . (Tenant::id() ?? 0) . '_dash_');
        return $res;
    }

    /**
     * Get customer ledger (tenant-scoped)
     */
    public function getLedger($customerId, $fromDate = null, $toDate = null) {
        $cid = Tenant::id();
        $dateFilter = '';
        $params = [];

        // Build params for each UNION segment
        if ($fromDate && $toDate) {
            // Sales
            $params[] = $customerId;
            if ($cid !== null) $params[] = $cid;
            $params[] = $fromDate; $params[] = $toDate;
            // Receipts
            $params[] = $customerId;
            if ($cid !== null) $params[] = $cid;
            $params[] = $fromDate; $params[] = $toDate;
            // Returns
            $params[] = $customerId;
            if ($cid !== null) $params[] = $cid;
            $params[] = $fromDate; $params[] = $toDate;
            $dateFilter = " AND date >= ? AND date <= ?";
        } else {
            $params = [$customerId];
            if ($cid !== null) $params[] = $cid;
            $params[] = $customerId;
            if ($cid !== null) $params[] = $cid;
            $params[] = $customerId;
            if ($cid !== null) $params[] = $cid;
        }

        $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
        $tenantFilterS = $cid !== null ? " AND s.company_id = ?" : "";

        return $this->db->query(
            "SELECT * FROM (
                SELECT sale_date as date, invoice_number as reference, 'Sale' as type,
                       grand_total as debit, 0 as credit, id
                FROM sales WHERE customer_id = ? AND deleted_at IS NULL {$tenantFilter} {$dateFilter}
                UNION ALL
                SELECT payment_date as date, payment_number as reference, 'Receipt' as type,
                       0 as debit, amount as credit, id
                FROM payments WHERE customer_id = ? AND type = 'receipt' AND deleted_at IS NULL {$tenantFilter} {$dateFilter}
                UNION ALL
                SELECT sr.return_date as date, CONCAT('RET-', sr.id) as reference, 'Return' as type,
                       0 as debit, sr.total_amount as credit, sr.id
                FROM sale_returns sr
                JOIN sales s ON sr.sale_id = s.id
                WHERE s.customer_id = ? AND sr.deleted_at IS NULL {$tenantFilterS} {$dateFilter}
            ) as ledger ORDER BY date ASC, id ASC",
            $params
        )->fetchAll();
    }

    /**
     * Recalculate customer balance from actual transactions (tenant-scoped)
     */
    public function recalculateBalance($customerId) {
        $customer = $this->find($customerId);
        if (!$customer) return;

        $opening = (float)($customer['opening_balance'] ?? 0);
        $cid = Tenant::id();
        $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
        $tenantFilterS = $cid !== null ? " AND s.company_id = ?" : "";

        $saleParams = [$customerId];
        if ($cid !== null) $saleParams[] = $cid;

        $saleDue = (float)$this->db->query(
            "SELECT COALESCE(SUM(due_amount), 0) FROM sales WHERE customer_id = ? AND deleted_at IS NULL" . $tenantFilter,
            $saleParams
        )->fetchColumn();

        $returnParams = [$customerId];
        if ($cid !== null) $returnParams[] = $cid;

        $returnAmt = (float)$this->db->query(
            "SELECT COALESCE(SUM(sr.total_amount), 0)
             FROM sale_returns sr
             JOIN sales s ON sr.sale_id = s.id
             WHERE s.customer_id = ? AND sr.deleted_at IS NULL" . $tenantFilterS,
            $returnParams
        )->fetchColumn();

        $correctBalance = $opening + $saleDue - $returnAmt;

        $updateSql = "UPDATE {$this->table} SET current_balance = ? WHERE id = ?";
        $updateParams = [$correctBalance, $customerId];
        if ($cid !== null) {
            $updateSql .= " AND company_id = ?";
            $updateParams[] = $cid;
        }
        $this->db->query($updateSql, $updateParams);

        Cache::flushPrefix('c' . (Tenant::id() ?? 0) . '_dash_');
        return $correctBalance;
    }

    /**
     * Recalculate ALL customer balances (tenant-scoped)
     */
    public function recalculateAllBalances() {
        $where = ["deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        $customers = $this->db->query(
            "SELECT id FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetchAll();

        foreach ($customers as $c) {
            $this->recalculateBalance($c['id']);
        }
    }

    /**
     * Get customers with dues (tenant-scoped)
     */
    public function getWithDues() {
        $where = ["current_balance > 0", "deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " ORDER BY current_balance DESC",
            $params
        )->fetchAll();
    }

    /**
     * Get total dues (tenant-scoped)
     */
    public function getTotalDues() {
        $where = ["current_balance > 0", "deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        return $this->db->query(
            "SELECT SUM(current_balance) as total_due FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetchColumn();
    }
}
