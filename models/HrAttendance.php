<?php
/**
 * HR attendance records.
 */
class HrAttendance extends Model {
    protected $table = 'hr_attendance';

    public function monthlySummary(string $month): array {
        $companyId = Tenant::require();
        $month = preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        return $this->db->query(
            "SELECT
                COUNT(*) AS total_entries,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_days,
                SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) AS half_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_days
             FROM {$this->table}
             WHERE company_id = ?
               AND attendance_date BETWEEN ? AND ?",
            [$companyId, $startDate, $endDate]
        )->fetch() ?: [];
    }

    public function recentEntries(string $month, string $status = '', int $employeeId = 0, int $limit = 100): array {
        $companyId = Tenant::require();
        $month = preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $params = [$companyId, $startDate, $endDate];
        $where = ["a.company_id = ?", "a.attendance_date BETWEEN ? AND ?"];

        if ($status !== '' && in_array($status, ['present', 'absent', 'half_day', 'late'], true)) {
            $where[] = "a.status = ?";
            $params[] = $status;
        }

        if ($employeeId > 0) {
            $where[] = "a.employee_id = ?";
            $params[] = $employeeId;
        }

        return $this->db->query(
            "SELECT
                a.*,
                e.employee_code,
                e.full_name,
                e.designation,
                e.department,
                s.shift_name,
                s.start_time AS shift_start_time,
                s.end_time AS shift_end_time,
                s.grace_period_minutes,
                s.weekly_off_day,
                h.holiday_name,
                h.holiday_type
             FROM {$this->table} a
             JOIN hr_employees e
               ON e.id = a.employee_id
              AND e.company_id = a.company_id
              AND e.deleted_at IS NULL
             LEFT JOIN hr_shifts s
               ON s.id = e.shift_id
              AND s.company_id = e.company_id
             LEFT JOIN hr_holidays h
               ON h.company_id = a.company_id
              AND h.holiday_date = a.attendance_date
             WHERE " . implode(' AND ', $where) . "
             ORDER BY a.attendance_date DESC, e.full_name ASC
             LIMIT {$limit}",
            $params
        )->fetchAll();
    }

    public function upsertEntry(array $data): void {
        $this->db->query(
            "INSERT INTO {$this->table}
             (company_id, employee_id, attendance_date, status, check_in_time, check_out_time, note, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                check_in_time = VALUES(check_in_time),
                check_out_time = VALUES(check_out_time),
                note = VALUES(note),
                created_by = VALUES(created_by),
                updated_at = NOW()",
            [
                Tenant::require(),
                (int)$data['employee_id'],
                $data['attendance_date'],
                $data['status'],
                $data['check_in_time'] ?? null,
                $data['check_out_time'] ?? null,
                $data['note'] ?? null,
                $data['created_by'] ?? null,
            ]
        );
    }

    public function employeeMonthlyUnits(string $month): array {
        $companyId = Tenant::require();
        $month = preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $rows = $this->db->query(
            "SELECT
                employee_id,
                ROUND(SUM(
                    CASE
                        WHEN status IN ('present', 'late') THEN 1
                        WHEN status = 'half_day' THEN 0.5
                        ELSE 0
                    END
                ), 2) AS attendance_units
             FROM {$this->table}
             WHERE company_id = ?
               AND attendance_date BETWEEN ? AND ?
             GROUP BY employee_id",
            [$companyId, $startDate, $endDate]
        )->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['employee_id']] = (float)$row['attendance_units'];
        }

        return $map;
    }
}
