<?php
/**
 * HR leave balances.
 */
class HrLeaveBalance extends Model {
    protected $table = 'hr_leave_balances';

    public function balanceMap(): array {
        $rows = $this->db->query(
            "SELECT
                employee_id,
                leave_type,
                opening_days,
                accrued_days,
                used_days,
                available_days
             FROM {$this->table}
             WHERE company_id = ?",
            [Tenant::require()]
        )->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $employeeId = (int)$row['employee_id'];
            $leaveType = (string)$row['leave_type'];
            if (!isset($map[$employeeId])) {
                $map[$employeeId] = [];
            }
            $map[$employeeId][$leaveType] = $row;
        }
        return $map;
    }

    public function summaryByEmployee(int $employeeId): array {
        return $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
               AND employee_id = ?
             ORDER BY leave_type ASC",
            [Tenant::require(), $employeeId]
        )->fetchAll();
    }

    public function upsertBalance(array $data): void {
        $opening = round((float)($data['opening_days'] ?? 0), 2);
        $accrued = round((float)($data['accrued_days'] ?? 0), 2);
        $used = round((float)($data['used_days'] ?? 0), 2);
        $available = round($opening + $accrued - $used, 2);

        $this->db->query(
            "INSERT INTO {$this->table}
             (company_id, employee_id, leave_type, opening_days, accrued_days, used_days, available_days)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                opening_days = VALUES(opening_days),
                accrued_days = VALUES(accrued_days),
                used_days = VALUES(used_days),
                available_days = VALUES(available_days),
                updated_at = NOW()",
            [Tenant::require(), (int)$data['employee_id'], $data['leave_type'], $opening, $accrued, $used, $available]
        );
    }

    public function processMonthlyAccruals(string $month, array $policies, array $employees): int {
        $processed = 0;
        $balanceMap = $this->balanceMap();
        $companyId = Tenant::require();

        $this->db->beginTransaction();
        try {
            foreach ($policies as $policy) {
                $leaveType = (string)($policy['leave_type'] ?? '');
                if ($leaveType === '' || !HrLeaveAccrualService::shouldProcessMonth((string)($policy['last_processed_month'] ?? ''), $month)) {
                    continue;
                }

                $accrualDays = (float)($policy['monthly_accrual_days'] ?? 0);
                $carryForward = array_key_exists('max_carry_forward', $policy) && $policy['max_carry_forward'] !== null
                    ? (float)$policy['max_carry_forward']
                    : null;

                foreach ($employees as $employee) {
                    $employeeId = (int)($employee['id'] ?? 0);
                    if ($employeeId <= 0) {
                        continue;
                    }

                    $existing = $balanceMap[$employeeId][$leaveType] ?? [
                        'opening_days' => 0,
                        'accrued_days' => 0,
                        'used_days' => 0,
                    ];
                    $next = HrLeaveAccrualService::applyMonthlyAccrual($existing, $accrualDays, $carryForward);

                    $this->db->query(
                        "INSERT INTO {$this->table}
                         (company_id, employee_id, leave_type, opening_days, accrued_days, used_days, available_days)
                         VALUES (?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                            opening_days = VALUES(opening_days),
                            accrued_days = VALUES(accrued_days),
                            used_days = VALUES(used_days),
                            available_days = VALUES(available_days),
                            updated_at = NOW()",
                        [
                            $companyId,
                            $employeeId,
                            $leaveType,
                            $next['opening_days'],
                            $next['accrued_days'],
                            $next['used_days'],
                            $next['available_days'],
                        ]
                    );

                    $balanceMap[$employeeId][$leaveType] = $next;
                    $processed++;
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return $processed;
    }
}
