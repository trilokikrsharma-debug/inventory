<?php
/**
 * HR employee master records.
 */
class HrEmployee extends Model {
    protected $table = 'hr_employees';

    public function searchPaginate(string $search = '', string $status = '', int $page = 1, int $perPage = RECORDS_PER_PAGE): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = ['e.deleted_at IS NULL'];
        $params = [];

        if (Tenant::id() !== null) {
            $where[] = 'e.company_id = ?';
            $params[] = Tenant::id();
        }

        $search = trim($search);
        if ($search !== '') {
            $where[] = '(e.employee_code LIKE ? OR e.full_name LIKE ? OR e.designation LIKE ? OR e.department LIKE ? OR e.phone LIKE ? OR e.email LIKE ? OR s.shift_name LIKE ?)';
            $term = '%' . $search . '%';
            array_push($params, $term, $term, $term, $term, $term, $term, $term);
        }

        $status = strtolower(trim($status));
        if ($status !== '' && in_array($status, ['active', 'inactive', 'on_leave'], true)) {
            $where[] = 'e.status = ?';
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);
        $total = (int)$this->db->query(
            "SELECT COUNT(*)
             FROM {$this->table} e
             LEFT JOIN hr_shifts s
               ON s.id = e.shift_id
              AND s.company_id = e.company_id
             WHERE {$whereSql}",
            $params
        )->fetchColumn();

        $rows = $this->db->query(
            "SELECT
                e.*,
                s.shift_name,
                s.start_time AS shift_start_time,
                s.end_time AS shift_end_time,
                s.grace_period_minutes,
                s.weekly_off_day,
                u.full_name AS reporting_manager_name
             FROM {$this->table} e
             LEFT JOIN hr_shifts s
               ON s.id = e.shift_id
              AND s.company_id = e.company_id
             LEFT JOIN users u
               ON u.id = e.reporting_manager_user_id
              AND u.company_id = e.company_id
             WHERE {$whereSql}
             ORDER BY e.joined_on DESC, e.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        )->fetchAll();

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    public function stats(): array {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if (Tenant::id() !== null) {
            $where[] = 'company_id = ?';
            $params[] = Tenant::id();
        }

        $whereSql = implode(' AND ', $where);

        $row = $this->db->query(
            "SELECT
                COUNT(*) AS total_employees,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_employees,
                SUM(CASE WHEN status = 'on_leave' THEN 1 ELSE 0 END) AS on_leave_employees,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_employees
             FROM {$this->table}
             WHERE {$whereSql}",
            $params
        )->fetch();

        return [
            'total_employees' => (int)($row['total_employees'] ?? 0),
            'active_employees' => (int)($row['active_employees'] ?? 0),
            'on_leave_employees' => (int)($row['on_leave_employees'] ?? 0),
            'inactive_employees' => (int)($row['inactive_employees'] ?? 0),
        ];
    }

    public function nextEmployeeCode(): string {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if (Tenant::id() !== null) {
            $where[] = 'company_id = ?';
            $params[] = Tenant::id();
        }

        $lastCode = (string)$this->db->query(
            "SELECT employee_code FROM {$this->table}
             WHERE " . implode(' AND ', $where) . "
             ORDER BY id DESC LIMIT 1",
            $params
        )->fetchColumn();

        if (preg_match('/(\d+)$/', $lastCode, $matches)) {
            $next = (int)$matches[1] + 1;
        } else {
            $next = 1;
        }

        return 'EMP-' . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public function findWithShift(int $id): ?array {
        $row = $this->db->query(
            "SELECT
                e.*,
                s.shift_name,
                s.start_time AS shift_start_time,
                s.end_time AS shift_end_time,
                s.grace_period_minutes,
                s.weekly_off_day,
                u.full_name AS reporting_manager_name
             FROM {$this->table} e
             LEFT JOIN hr_shifts s
               ON s.id = e.shift_id
              AND s.company_id = e.company_id
             LEFT JOIN users u
               ON u.id = e.reporting_manager_user_id
              AND u.company_id = e.company_id
             WHERE e.id = ?
               AND e.company_id = ?
               AND e.deleted_at IS NULL
             LIMIT 1",
            [$id, Tenant::require()]
        )->fetch();

        return $row ?: null;
    }

    public function eligibleForLeaveAccrual(string $month): array {
        $monthStart = preg_match('/^\d{4}-\d{2}$/', $month) ? $month . '-01' : date('Y-m-01');

        return $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
               AND deleted_at IS NULL
               AND status IN ('active', 'on_leave')
               AND joined_on <= ?
             ORDER BY full_name ASC",
            [Tenant::require(), $monthStart]
        )->fetchAll();
    }
}
