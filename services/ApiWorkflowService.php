<?php

class ApiWorkflowService {
    private $db;

    public function __construct($db = null) {
        $this->db = $db;
    }

    private function db() {
        if ($this->db === null) {
            $this->db = Database::getInstance();
        }

        return $this->db;
    }

    public function listTokens(int $companyId): array {
        return $this->db()->query(
            "SELECT id, name, scopes, is_active, expires_at, last_used_at, created_at
             FROM api_tokens
             WHERE company_id = ?
             ORDER BY id DESC",
            [$companyId]
        )->fetchAll();
    }

    public function normalizeTokenRequest(array $input): array {
        $name = trim((string)($input['name'] ?? ''));
        if ($name === '') {
            $name = 'Default Integration';
        }

        $fullAccess = (string)($input['full_access'] ?? '0') === '1';
        $scopes = $fullAccess ? ['*'] : ApiAuth::normalizeScopes((array)($input['scopes'] ?? []));

        return [
            'name' => mb_substr($name, 0, 100),
            'full_access' => $fullAccess,
            'scopes' => $scopes,
            'expires_at' => $this->resolveExpiryTimestamp($input['expiry_days'] ?? ''),
        ];
    }

    public function buildSummaryPayload(int $companyId): array {
        $salesToday = (float)$this->db()->query(
            "SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE company_id = ? AND deleted_at IS NULL AND sale_date = CURDATE()",
            [$companyId]
        )->fetchColumn();
        $salesMonth = (float)$this->db()->query(
            "SELECT COALESCE(SUM(grand_total), 0) FROM sales WHERE company_id = ? AND deleted_at IS NULL AND sale_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')",
            [$companyId]
        )->fetchColumn();
        $outstandingReceivables = (float)$this->db()->query(
            "SELECT COALESCE(SUM(current_balance), 0) FROM customers WHERE company_id = ? AND deleted_at IS NULL AND current_balance > 0",
            [$companyId]
        )->fetchColumn();
        $lowStockCount = (int)$this->db()->query(
            "SELECT COUNT(*) FROM products WHERE company_id = ? AND deleted_at IS NULL AND is_active = 1 AND current_stock <= COALESCE(low_stock_alert, 10)",
            [$companyId]
        )->fetchColumn();

        return [
            'success' => true,
            'data' => [
                'sales_today' => SaaSBillingHelper::money($salesToday),
                'sales_month' => SaaSBillingHelper::money($salesMonth),
                'outstanding_receivables' => SaaSBillingHelper::money($outstandingReceivables),
                'low_stock_count' => $lowStockCount,
            ],
            'meta' => [
                'company_id' => $companyId,
                'generated_at' => date(DATE_ATOM),
            ],
        ];
    }

    public function resolveExpiryTimestamp($input): ?string {
        $value = trim((string)$input);
        if ($value === '' || $value === 'never') {
            return null;
        }

        $days = (int)$value;
        $allowed = [1, 7, 30, 90, 365];
        if (!in_array($days, $allowed, true)) {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));
    }
}
