<?php
/**
 * Customer Model — Multi-Tenant Aware
 */
class CustomerModel extends Model {
    protected $table = 'customers';

    /**
     * Keep dashboard and report caches coherent after customer/payments mutations.
     */
    private function flushAnalyticCaches(): void {
        $tenantPrefix = 'c' . (Tenant::id() ?? 0) . '_';
        Cache::flushPrefix($tenantPrefix . 'dash_');
        Cache::flushPrefix($tenantPrefix . 'report_');
    }

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
            "SELECT
                id,
                name,
                phone,
                email,
                city,
                current_balance,
                is_active,
                created_at
             FROM {$this->table}
             WHERE {$whereClause}
             ORDER BY name ASC
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
        $this->flushAnalyticCaches();
        return $res;
    }

    /**
     * Get customer ledger (tenant-scoped)
     */
    public function getLedger($customerId, $fromDate = null, $toDate = null) {
        $cid = Tenant::id();
        $params = [];
        $salesDateFilter = '';
        $paymentDateFilter = '';
        $returnDateFilter = '';

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
            $salesDateFilter = " AND sale_date >= ? AND sale_date <= ?";
            $paymentDateFilter = " AND payment_date >= ? AND payment_date <= ?";
            $returnDateFilter = " AND sr.return_date >= ? AND sr.return_date <= ?";
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
            "SELECT txn_at, date, reference, type, debit, credit, id FROM (
                SELECT COALESCE(created_at, CONCAT(sale_date, ' 00:00:00')) as txn_at,
                       sale_date as date, invoice_number as reference, 'Sale' as type,
                       grand_total as debit, 0 as credit, id
                FROM sales WHERE customer_id = ? AND deleted_at IS NULL {$tenantFilter} {$salesDateFilter}
                UNION ALL
                SELECT COALESCE(created_at, CONCAT(payment_date, ' 00:00:00')) as txn_at,
                       payment_date as date, payment_number as reference, 'Receipt' as type,
                       0 as debit, amount as credit, id
                FROM payments WHERE customer_id = ? AND type = 'receipt' AND deleted_at IS NULL {$tenantFilter} {$paymentDateFilter}
                UNION ALL
                SELECT COALESCE(sr.created_at, CONCAT(sr.return_date, ' 00:00:00')) as txn_at,
                       sr.return_date as date,
                       COALESCE(NULLIF(sr.return_number, ''), CONCAT('RET-', LPAD(sr.id, 4, '0'))) as reference,
                       'Return' as type,
                       0 as debit, sr.total_amount as credit, sr.id
                FROM sale_returns sr
                JOIN sales s ON sr.sale_id = s.id
                WHERE s.customer_id = ? AND sr.deleted_at IS NULL {$tenantFilterS} {$returnDateFilter}
            ) as ledger ORDER BY txn_at ASC, id ASC",
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

        $saleTotal = (float)$this->db->query(
            "SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE customer_id = ? AND deleted_at IS NULL" . $tenantFilter,
            $saleParams
        )->fetchColumn();

        $paymentParams = [$customerId];
        if ($cid !== null) $paymentParams[] = $cid;

        $paymentTotal = (float)$this->db->query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE customer_id = ? AND type = 'receipt' AND deleted_at IS NULL" . $tenantFilter,
            $paymentParams
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

        $correctBalance = $opening + $saleTotal - $paymentTotal - $returnAmt;

        $updateSql = "UPDATE {$this->table} SET current_balance = ? WHERE id = ?";
        $updateParams = [$correctBalance, $customerId];
        if ($cid !== null) {
            $updateSql .= " AND company_id = ?";
            $updateParams[] = $cid;
        }
        $this->db->query($updateSql, $updateParams);

        $this->flushAnalyticCaches();
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
     * Check whether an email already exists for another active customer (tenant-scoped).
     */
    public function emailExists($email, $excludeId = null) {
        if ($email === null || $email === '') return false;

        $where = ["email = ?", "deleted_at IS NULL"];
        $params = [$email];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        if ($excludeId) {
            $where[] = "id != ?";
            $params[] = (int)$excludeId;
        }
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetchColumn() > 0;
    }

    /**
     * Check whether a phone already exists for another active customer (tenant-scoped).
     */
    public function phoneExists($phone, $excludeId = null) {
        if ($phone === null || $phone === '') return false;

        $where = ["phone = ?", "deleted_at IS NULL"];
        $params = [$phone];
        if (Tenant::id() !== null) {
            $where[] = "company_id = ?";
            $params[] = Tenant::id();
        }
        if ($excludeId) {
            $where[] = "id != ?";
            $params[] = (int)$excludeId;
        }
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE " . implode(' AND ', $where),
            $params
        )->fetchColumn() > 0;
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
            "SELECT
                id,
                name,
                phone,
                city,
                current_balance
             FROM {$this->table}
             WHERE " . implode(' AND ', $where) . "
             ORDER BY current_balance DESC",
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
