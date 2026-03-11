<?php
/**
 * Audit Service — Immutable Financial Audit Trail
 * 
 * Logs all changes to critical financial tables (sales, purchases,
 * payments, etc.) into the audit_trail table.
 * 
 * Designed as a repository hook — call from service/repository
 * methods instead of using database triggers (more portable).
 * 
 * Usage:
 *   AuditService::log('sales', $id, 'INSERT', null, $newData);
 *   AuditService::log('sales', $id, 'UPDATE', $oldData, $newData);
 *   AuditService::log('sales', $id, 'DELETE', $oldData, null);
 */
class AuditService {
    /** @var string[] Tables that are audited */
    private const AUDITED_TABLES = [
        'sales', 'sale_items', 'purchases', 'purchase_items',
        'payments', 'sale_returns', 'sale_return_items',
        'purchase_returns', 'purchase_return_items', 'products',
    ];

    /**
     * Log a change to the audit trail.
     * 
     * @param string     $tableName   Name of the audited table
     * @param int        $recordId    Primary key of the affected record
     * @param string     $action      'INSERT', 'UPDATE', or 'DELETE'
     * @param array|null $oldValues   Previous state (null for INSERT)
     * @param array|null $newValues   New state (null for DELETE)
     * @return void
     */
    public static function log(
        string $tableName,
        int $recordId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        // Only audit tracked tables
        if (!in_array($tableName, self::AUDITED_TABLES, true)) {
            return;
        }

        try {
            $db = Database::getInstance();

            // Get current user and company from session
            $user = Session::get('user');
            $companyId = $user['company_id'] ?? (defined('TENANT_ID') ? TENANT_ID : 0);
            $userId = $user['id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;

            // For UPDATE, compute only changed fields
            if ($action === 'UPDATE' && $oldValues && $newValues) {
                $changes = self::diffValues($oldValues, $newValues);
                if (empty($changes['old']) && empty($changes['new'])) {
                    return; // No actual changes — skip audit
                }
                $oldValues = $changes['old'];
                $newValues = $changes['new'];
            }

            $db->query(
                "INSERT INTO `audit_trail` 
                    (`company_id`, `table_name`, `record_id`, `action`, `old_values`, `new_values`, `changed_by`, `ip_address`) 
                 VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $companyId,
                    $tableName,
                    $recordId,
                    $action,
                    $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                    $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                    $userId,
                    $ip,
                ]
            );
        } catch (\Exception $e) {
            // Audit logging must NEVER break business logic
            error_log('[AuditService] Failed to log: ' . $e->getMessage());
        }
    }

    /**
     * Convenience: log an INSERT action.
     */
    public static function logInsert(string $tableName, int $recordId, array $newValues): void {
        self::log($tableName, $recordId, 'INSERT', null, $newValues);
    }

    /**
     * Convenience: log an UPDATE action.
     */
    public static function logUpdate(string $tableName, int $recordId, array $oldValues, array $newValues): void {
        self::log($tableName, $recordId, 'UPDATE', $oldValues, $newValues);
    }

    /**
     * Convenience: log a DELETE action.
     */
    public static function logDelete(string $tableName, int $recordId, array $oldValues): void {
        self::log($tableName, $recordId, 'DELETE', $oldValues, null);
    }

    /**
     * Compute only the fields that actually changed.
     * Reduces audit storage by ~80% for typical updates.
     * 
     * @return array{old: array, new: array}
     */
    private static function diffValues(array $oldValues, array $newValues): array {
        $oldDiff = [];
        $newDiff = [];

        foreach ($newValues as $key => $newVal) {
            $oldVal = $oldValues[$key] ?? null;
            if ((string)$oldVal !== (string)$newVal) {
                $oldDiff[$key] = $oldVal;
                $newDiff[$key] = $newVal;
            }
        }

        return ['old' => $oldDiff, 'new' => $newDiff];
    }

    /**
     * Query audit history for a specific record.
     * 
     * @param string $tableName  Table name
     * @param int    $recordId   Record ID
     * @param int    $limit      Max entries to return
     * @return array
     */
    public static function history(string $tableName, int $recordId, int $limit = 50): array {
        $db = Database::getInstance();
        $user = Session::get('user');
        $companyId = $user['company_id'] ?? 0;

        return $db->query(
            "SELECT a.*, u.full_name as changed_by_name 
             FROM `audit_trail` a
             LEFT JOIN `users` u ON a.changed_by = u.id
             WHERE a.company_id = ? AND a.table_name = ? AND a.record_id = ?
             ORDER BY a.changed_at DESC
             LIMIT ?",
            [$companyId, $tableName, $recordId, $limit]
        )->fetchAll(\PDO::FETCH_ASSOC);
    }
}
