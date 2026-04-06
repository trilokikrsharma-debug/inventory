<?php
/**
 * HR leave requests.
 */
class HrLeaveRequest extends Model {
    protected $table = 'hr_leave_requests';

    public function summary(string $status = ''): array {
        $companyId = Tenant::require();
        $params = [$companyId];
        $where = ["company_id = ?"];

        if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        return $this->db->query(
            "SELECT
                COUNT(*) AS total_requests,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_requests,
                SUM(CASE WHEN status = 'pending' AND manager_status = 'pending' THEN 1 ELSE 0 END) AS pending_manager_requests,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_requests,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_requests,
                SUM(CASE WHEN manager_status = 'rejected' THEN 1 ELSE 0 END) AS manager_rejected_requests
             FROM {$this->table}
             WHERE " . implode(' AND ', $where),
            $params
        )->fetch() ?: [];
    }

    public function listWithEmployee(string $status = '', int $employeeId = 0, int $limit = 100): array {
        $companyId = Tenant::require();
        $params = [$companyId];
        $where = ["lr.company_id = ?"];

        if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $where[] = "lr.status = ?";
            $params[] = $status;
        }
        if ($employeeId > 0) {
            $where[] = "lr.employee_id = ?";
            $params[] = $employeeId;
        }

        return $this->db->query(
            "SELECT
                lr.*,
                e.employee_code,
                e.full_name,
                e.designation,
                approver.full_name AS approved_by_name,
                reviewer.full_name AS approver_user_name,
                manager.full_name AS manager_approved_by_name
             FROM {$this->table} lr
             JOIN hr_employees e
               ON e.id = lr.employee_id
              AND e.company_id = lr.company_id
              AND e.deleted_at IS NULL
             LEFT JOIN users approver ON approver.id = lr.approved_by
             LEFT JOIN users reviewer ON reviewer.id = lr.approver_user_id
             LEFT JOIN users manager ON manager.id = lr.manager_approved_by
             WHERE " . implode(' AND ', $where) . "
             ORDER BY
                CASE
                    WHEN lr.status = 'pending' AND lr.manager_status = 'pending' THEN 0
                    WHEN lr.status = 'pending' THEN 1
                    ELSE 2
                END,
                lr.start_date DESC,
                lr.id DESC
             LIMIT {$limit}",
            $params
        )->fetchAll();
    }

    public function createRequest(array $data): int {
        return (int)$this->create($data);
    }

    public function updateStatus(int $id, string $status, ?string $rejectionReason, int $approvedBy): void {
        $request = $this->findPendingRequest($id);
        $managerStatus = (string)($request['manager_status'] ?? 'not_required');
        if ($managerStatus === 'pending') {
            throw new \RuntimeException('This leave request is still pending manager approval.');
        }
        if ($managerStatus === 'rejected') {
            throw new \RuntimeException('This leave request has already been rejected at manager stage.');
        }

        $this->db->query(
            "UPDATE {$this->table}
             SET status = ?,
                 rejection_reason = ?,
                 approved_by = ?,
                 approved_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?
               AND company_id = ?",
            [$status, $rejectionReason, $approvedBy, $id, Tenant::require()]
        );
    }

    public function updateManagerStatus(int $id, string $status, ?string $reason, int $approvedBy): void {
        $request = $this->findPendingRequest($id);
        if (($request['manager_status'] ?? 'not_required') !== 'pending') {
            throw new \RuntimeException('This leave request is not awaiting manager approval.');
        }

        $params = [$status, $approvedBy, $reason, $id, Tenant::require()];
        $setSql = "manager_status = ?,
                   manager_approved_by = ?,
                   manager_approved_at = NOW(),
                   manager_rejection_reason = ?,
                   updated_at = NOW()";

        if ($status === 'rejected') {
            $setSql .= ",
                   status = 'rejected',
                   rejection_reason = COALESCE(?, 'Rejected at manager stage'),
                   approved_by = ?,
                   approved_at = NOW()";
            array_splice($params, 3, 0, [$reason, $approvedBy]);
        }

        $this->db->query(
            "UPDATE {$this->table}
             SET {$setSql}
             WHERE id = ?
               AND company_id = ?",
            $params
        );
    }

    private function findPendingRequest(int $id): array {
        $request = $this->find($id);
        if (!$request) {
            throw new \RuntimeException('Leave request not found.');
        }
        if (($request['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('Only pending leave requests can be updated.');
        }

        return $request;
    }

    public function approvedDaysByEmployee(string $month): array {
        $companyId = Tenant::require();
        $month = preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $rows = $this->db->query(
            "SELECT
                employee_id,
                SUM(
                    GREATEST(
                        0,
                        DATEDIFF(LEAST(end_date, ?), GREATEST(start_date, ?)) + 1
                    )
                ) AS approved_days
             FROM {$this->table}
             WHERE company_id = ?
               AND status = 'approved'
               AND start_date <= ?
               AND end_date >= ?
             GROUP BY employee_id",
            [$endDate, $startDate, $companyId, $endDate, $startDate]
        )->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['employee_id']] = (int)$row['approved_days'];
        }

        return $map;
    }
}
