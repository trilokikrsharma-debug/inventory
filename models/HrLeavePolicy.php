<?php
/**
 * HR leave accrual policy.
 */
class HrLeavePolicy extends Model {
    protected $table = 'hr_leave_policies';
    protected $softDelete = false;

    public function allOrdered(): array {
        return $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
             ORDER BY leave_type ASC",
            [Tenant::require()]
        )->fetchAll();
    }

    public function indexedByType(): array {
        $map = [];
        foreach ($this->allOrdered() as $policy) {
            $map[(string)($policy['leave_type'] ?? '')] = $policy;
        }
        return $map;
    }

    public function activePoliciesForMonth(string $month): array {
        return $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
               AND is_active = 1
               AND effective_from <= ?
             ORDER BY leave_type ASC",
            [Tenant::require(), $month . '-01']
        )->fetchAll();
    }

    public function upsertPolicy(array $data): void {
        $this->db->query(
            "INSERT INTO {$this->table}
             (company_id, leave_type, monthly_accrual_days, max_carry_forward, effective_from, is_active, last_processed_month)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                monthly_accrual_days = VALUES(monthly_accrual_days),
                max_carry_forward = VALUES(max_carry_forward),
                effective_from = VALUES(effective_from),
                is_active = VALUES(is_active),
                updated_at = NOW()",
            [
                Tenant::require(),
                $data['leave_type'],
                round((float)$data['monthly_accrual_days'], 2),
                $data['max_carry_forward'] !== null ? round((float)$data['max_carry_forward'], 2) : null,
                $data['effective_from'],
                !empty($data['is_active']) ? 1 : 0,
                $data['last_processed_month'] ?? null,
            ]
        );
    }

    public function markProcessed(string $leaveType, string $month): void {
        $this->db->query(
            "UPDATE {$this->table}
             SET last_processed_month = ?, updated_at = NOW()
             WHERE company_id = ?
               AND leave_type = ?",
            [$month, Tenant::require(), $leaveType]
        );
    }
}
