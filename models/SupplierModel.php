<?php
/**
 * Supplier Model — Multi-Tenant Aware
 */
class SupplierModel extends Model {
    protected $table = 'suppliers';

    /**
     * Keep dashboard and report caches coherent after supplier/payments mutations.
     */
    private function flushAnalyticCaches(): void {
        $tenantPrefix = 'c' . (Tenant::id() ?? 0) . '_';
        Cache::flushPrefix($tenantPrefix . 'dash_');
        Cache::flushPrefix($tenantPrefix . 'report_');
    }

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
        return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'totalPages' => ceil($total / $perPage)];
    }

    public function updateBalance($supplierId, $amount) {
        $sql = "UPDATE {$this->table} SET current_balance = current_balance + ? WHERE id = ?";
        $params = [$amount, $supplierId];
        if (Tenant::id() !== null) { $sql .= " AND company_id = ?"; $params[] = Tenant::id(); }
        $res = $this->db->query($sql, $params);
        $this->flushAnalyticCaches();
        return $res;
    }

    public function getLedger($supplierId, $fromDate = null, $toDate = null) {
        $cid = Tenant::id();
        $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
        $params = [];
        $dateFilter = '';

        if ($fromDate && $toDate) {
            $params[] = $supplierId; if ($cid !== null) $params[] = $cid; $params[] = $fromDate; $params[] = $toDate;
            $params[] = $supplierId; if ($cid !== null) $params[] = $cid; $params[] = $fromDate; $params[] = $toDate;
            $dateFilter = " AND date >= ? AND date <= ?";
        } else {
            $params[] = $supplierId; if ($cid !== null) $params[] = $cid;
            $params[] = $supplierId; if ($cid !== null) $params[] = $cid;
        }

        return $this->db->query(
            "SELECT date, reference, type, debit, credit, id FROM (
                SELECT purchase_date as date, invoice_number as reference, 'Purchase' as type, grand_total as debit, 0 as credit, id
                FROM purchases WHERE supplier_id = ? AND deleted_at IS NULL {$tenantFilter} {$dateFilter}
                UNION ALL
                SELECT payment_date as date, payment_number as reference, 'Payment' as type, 0 as debit, amount as credit, id
                FROM payments WHERE supplier_id = ? AND type = 'payment' AND deleted_at IS NULL {$tenantFilter} {$dateFilter}
            ) as ledger ORDER BY date ASC, id ASC",
            $params
        )->fetchAll();
    }

    public function getWithDues() {
        $where = ["current_balance > 0", "deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) { $where[] = "company_id = ?"; $params[] = Tenant::id(); }
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

    public function getTotalDues() {
        $where = ["current_balance > 0", "deleted_at IS NULL"];
        $params = [];
        if (Tenant::id() !== null) { $where[] = "company_id = ?"; $params[] = Tenant::id(); }
        return $this->db->query(
            "SELECT SUM(current_balance) as total_due FROM {$this->table} WHERE " . implode(' AND ', $where), $params
        )->fetchColumn();
    }

    public function recalculateBalance($supplierId) {
        $supplier = $this->find($supplierId);
        if (!$supplier) return;
        $opening = (float)($supplier['opening_balance'] ?? 0);
        $cid = Tenant::id();
        $tenantFilter = $cid !== null ? " AND company_id = ?" : "";
        $params = [$supplierId];
        if ($cid !== null) $params[] = $cid;
        $purchaseTotal = (float)$this->db->query(
            "SELECT COALESCE(SUM(grand_total), 0) FROM purchases WHERE supplier_id = ? AND deleted_at IS NULL" . $tenantFilter, $params
        )->fetchColumn();

        $paymentTotal = (float)$this->db->query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE supplier_id = ? AND type = 'payment' AND deleted_at IS NULL" . $tenantFilter, $params
        )->fetchColumn();

        $correctBalance = $opening + $purchaseTotal - $paymentTotal;
        $updateParams = [$correctBalance, $supplierId];
        $updateSql = "UPDATE {$this->table} SET current_balance = ? WHERE id = ?";
        if ($cid !== null) { $updateSql .= " AND company_id = ?"; $updateParams[] = $cid; }
        $this->db->query($updateSql, $updateParams);
        $this->flushAnalyticCaches();
        return $correctBalance;
    }
}
