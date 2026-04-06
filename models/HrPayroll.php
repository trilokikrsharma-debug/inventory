<?php
/**
 * HR payroll runs and payslip items.
 */
class HrPayroll extends Model {
    protected $table = 'hr_payroll_runs';

    public function recentRuns(int $limit = 24): array {
        return $this->db->query(
            "SELECT
                r.*,
                u.full_name AS processed_by_name,
                approver.full_name AS approved_by_name,
                locker.full_name AS locked_by_name
             FROM {$this->table} r
             LEFT JOIN users u ON u.id = r.processed_by
             LEFT JOIN users approver ON approver.id = r.approved_by
             LEFT JOIN users locker ON locker.id = r.locked_by
             WHERE r.company_id = ?
             ORDER BY r.payroll_month DESC, r.id DESC
             LIMIT {$limit}",
            [Tenant::require()]
        )->fetchAll();
    }

    public function getRunByMonth(string $month): ?array {
        $row = $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
               AND payroll_month = ?
             LIMIT 1",
            [Tenant::require(), $month]
        )->fetch();

        return $row ?: null;
    }

    public function getRunWithItems(int $runId): ?array {
        $run = $this->db->query(
            "SELECT
                r.*,
                u.full_name AS processed_by_name,
                approver.full_name AS approved_by_name,
                locker.full_name AS locked_by_name
             FROM {$this->table} r
             LEFT JOIN users u ON u.id = r.processed_by
             LEFT JOIN users approver ON approver.id = r.approved_by
             LEFT JOIN users locker ON locker.id = r.locked_by
             WHERE r.company_id = ?
               AND r.id = ?
             LIMIT 1",
            [Tenant::require(), $runId]
        )->fetch();

        if (!$run) {
            return null;
        }

        $run['items'] = $this->db->query(
            "SELECT
                i.*,
                e.employee_code,
                e.full_name,
                e.designation,
                e.department,
                p.payment_number,
                p.payment_method,
                p.payment_date
             FROM hr_payroll_items i
             JOIN hr_employees e
               ON e.id = i.employee_id
              AND e.company_id = i.company_id
             LEFT JOIN payments p
               ON p.id = i.payroll_payment_id
              AND p.company_id = i.company_id
              AND p.deleted_at IS NULL
             WHERE i.company_id = ?
               AND i.payroll_run_id = ?
             ORDER BY e.full_name ASC, i.id ASC",
            [Tenant::require(), $runId]
        )->fetchAll();

        return $run;
    }

    public function createOrRefreshRun(string $month, array $rows, int $processedBy): int {
        $companyId = Tenant::require();
        $month = preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
        $periodStart = $month . '-01';
        $periodEnd = date('Y-m-t', strtotime($periodStart));
        $employeeCount = count($rows);
        $grossAmount = 0.0;
        $deductionAmount = 0.0;
        $netAmount = 0.0;

        foreach ($rows as $row) {
            $grossAmount += (float)($row['monthly_salary'] ?? 0);
            $grossAmount += (float)($row['allowance_amount'] ?? 0);
            $grossAmount += (float)($row['bonus_amount'] ?? 0);
            $deductionAmount += (float)($row['deduction_amount'] ?? 0);
            $deductionAmount += (float)($row['statutory_deduction_amount'] ?? 0);
            $deductionAmount += (float)($row['other_deduction_amount'] ?? 0);
            $netAmount += (float)($row['net_salary'] ?? 0);
        }

        $this->db->beginTransaction();
        try {
            $existing = $this->getRunByMonth($month);
            if ($existing && ($existing['status'] ?? '') === 'paid') {
                throw new \RuntimeException('Paid payroll runs cannot be regenerated.');
            }
            if ($existing && ($existing['status'] ?? '') === 'approved') {
                throw new \RuntimeException('Approved payroll runs are locked. Unapprove before reprocessing.');
            }

            if ($existing) {
                $runId = (int)$existing['id'];
                $this->db->query(
                    "UPDATE {$this->table}
                     SET period_start = ?,
                         period_end = ?,
                         employee_count = ?,
                         gross_amount = ?,
                         deduction_amount = ?,
                         net_amount = ?,
                         status = 'processed',
                         processed_by = ?,
                         approved_by = NULL,
                         approved_at = NULL,
                         locked_by = NULL,
                         locked_at = NULL,
                         processed_at = NOW(),
                         updated_at = NOW()
                     WHERE id = ?
                       AND company_id = ?",
                    [$periodStart, $periodEnd, $employeeCount, round($grossAmount, 2), round($deductionAmount, 2), round($netAmount, 2), $processedBy, $runId, $companyId]
                );
                $this->db->query(
                    "DELETE FROM hr_payroll_items
                     WHERE company_id = ?
                       AND payroll_run_id = ?",
                    [$companyId, $runId]
                );
            } else {
                $this->db->query(
                    "INSERT INTO {$this->table}
                     (company_id, payroll_month, period_start, period_end, employee_count, gross_amount, deduction_amount, net_amount, status, processed_by, processed_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'processed', ?, NOW())",
                    [$companyId, $month, $periodStart, $periodEnd, $employeeCount, round($grossAmount, 2), round($deductionAmount, 2), round($netAmount, 2), $processedBy]
                );
                $runId = (int)$this->db->lastInsertId();
            }

            foreach ($rows as $row) {
                $this->db->query(
                    "INSERT INTO hr_payroll_items
                     (company_id, payroll_run_id, employee_id, attendance_units, approved_leave_days, gross_salary, allowance_amount, bonus_amount, pf_amount, esi_amount, tds_amount, deduction_amount, statutory_deduction_amount, other_deduction_amount, net_salary, adjustment_notes, payment_status, paid_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NULL)",
                    [
                        $companyId,
                        $runId,
                        (int)$row['employee_id'],
                        round((float)$row['attendance_units'], 2),
                        (int)$row['approved_leave_days'],
                        round((float)$row['monthly_salary'], 2),
                        round((float)($row['allowance_amount'] ?? 0), 2),
                        round((float)($row['bonus_amount'] ?? 0), 2),
                        round((float)($row['pf_amount'] ?? 0), 2),
                        round((float)($row['esi_amount'] ?? 0), 2),
                        round((float)($row['tds_amount'] ?? 0), 2),
                        round((float)$row['deduction_amount'], 2),
                        round((float)($row['statutory_deduction_amount'] ?? 0), 2),
                        round((float)($row['other_deduction_amount'] ?? 0), 2),
                        round((float)$row['net_salary'], 2),
                        $row['adjustment_notes'] ?? null,
                    ]
                );
            }

            $this->db->commit();
            return $runId;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function markItemPaid(int $itemId, int $processedBy, array $paymentData = []): int {
        $companyId = Tenant::require();
        $item = $this->db->query(
            "SELECT
                i.*,
                e.full_name,
                e.employee_code
             FROM hr_payroll_items i
             JOIN hr_employees e
               ON e.id = i.employee_id
              AND e.company_id = i.company_id
             WHERE i.company_id = ?
               AND i.id = ?
             LIMIT 1",
            [$companyId, $itemId]
        )->fetch();
        if (!$item) {
            throw new \RuntimeException('Payroll item not found.');
        }
        $run = $this->getRunWithItems((int)$item['payroll_run_id']);
        if (!$run) {
            throw new \RuntimeException('Payroll run not found.');
        }
        if (($run['status'] ?? '') !== 'approved') {
            throw new \RuntimeException('Approve and lock the payroll run before posting payouts.');
        }
        if (($item['payment_status'] ?? '') === 'paid' && !empty($item['payroll_payment_id'])) {
            return (int)$item['payroll_payment_id'];
        }

        $paymentModel = new PaymentModel();
        $this->db->beginTransaction();
        try {
            $paymentId = $paymentModel->createPayrollPayout([
                'payroll_item_id' => $itemId,
                'amount' => (float)($item['net_salary'] ?? 0),
                'payment_method' => $paymentData['payment_method'] ?? 'cash',
                'payment_date' => $paymentData['payment_date'] ?? date('Y-m-d'),
                'reference_number' => $paymentData['reference_number'] ?? null,
                'bank_name' => $paymentData['bank_name'] ?? null,
                'note' => $paymentData['note'] ?? ('Payroll payout for ' . ($item['full_name'] ?? 'Employee') . ' [' . ($item['employee_code'] ?? 'EMP') . ']'),
            ], $processedBy);

            $this->db->query(
                "UPDATE hr_payroll_items
                 SET payment_status = 'paid',
                     paid_at = NOW(),
                     payroll_payment_id = ?,
                     updated_at = NOW()
                 WHERE company_id = ?
                   AND id = ?",
                [$paymentId, $companyId, $itemId]
            );

            $this->refreshRunStatus((int)$item['payroll_run_id'], $processedBy);
            $this->db->commit();
            return $paymentId;
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function updateItemAdjustments(int $itemId, array $data, int $processedBy): void {
        $item = $this->db->query(
            "SELECT id, payroll_run_id, gross_salary, deduction_amount, statutory_deduction_amount, payment_status
             FROM hr_payroll_items
             WHERE company_id = ?
               AND id = ?
             LIMIT 1",
            [Tenant::require(), $itemId]
        )->fetch();
        if (!$item) {
            throw new \RuntimeException('Payroll item not found.');
        }
        if (($item['payment_status'] ?? '') === 'paid') {
            throw new \RuntimeException('Paid payroll items cannot be edited.');
        }
        $run = $this->getRunById((int)$item['payroll_run_id']);
        if (($run['status'] ?? '') === 'approved' || ($run['status'] ?? '') === 'paid') {
            throw new \RuntimeException('Locked payroll runs cannot be edited.');
        }

        $allowanceAmount = round((float)($data['allowance_amount'] ?? 0), 2);
        $bonusAmount = round((float)($data['bonus_amount'] ?? 0), 2);
        $otherDeductionAmount = round((float)($data['other_deduction_amount'] ?? 0), 2);
        $baseGross = round((float)($item['gross_salary'] ?? 0), 2);
        $baseDeduction = round((float)($item['deduction_amount'] ?? 0), 2);
        $statutoryDeductionAmount = round((float)($item['statutory_deduction_amount'] ?? 0), 2);
        $netSalary = max(0, round($baseGross + $allowanceAmount + $bonusAmount - $baseDeduction - $statutoryDeductionAmount - $otherDeductionAmount, 2));

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "UPDATE hr_payroll_items
                 SET allowance_amount = ?,
                     bonus_amount = ?,
                     other_deduction_amount = ?,
                     adjustment_notes = ?,
                     net_salary = ?,
                     updated_at = NOW()
                 WHERE company_id = ?
                   AND id = ?",
                [
                    $allowanceAmount,
                    $bonusAmount,
                    $otherDeductionAmount,
                    $data['adjustment_notes'] ?? null,
                    $netSalary,
                    Tenant::require(),
                    $itemId,
                ]
            );

            $totals = $this->db->query(
                "SELECT
                    COUNT(*) AS employee_count,
                    COALESCE(SUM(gross_salary + allowance_amount + bonus_amount), 0) AS gross_amount,
                    COALESCE(SUM(deduction_amount + statutory_deduction_amount + other_deduction_amount), 0) AS deduction_amount,
                    COALESCE(SUM(net_salary), 0) AS net_amount
                 FROM hr_payroll_items
                 WHERE company_id = ?
                   AND payroll_run_id = ?",
                [Tenant::require(), (int)$item['payroll_run_id']]
            )->fetch();

            $this->db->query(
                "UPDATE {$this->table}
                 SET employee_count = ?,
                     gross_amount = ?,
                     deduction_amount = ?,
                     net_amount = ?,
                     processed_by = ?,
                     updated_at = NOW()
                 WHERE company_id = ?
                   AND id = ?",
                [
                    (int)($totals['employee_count'] ?? 0),
                    round((float)($totals['gross_amount'] ?? 0), 2),
                    round((float)($totals['deduction_amount'] ?? 0), 2),
                    round((float)($totals['net_amount'] ?? 0), 2),
                    $processedBy,
                    Tenant::require(),
                    (int)$item['payroll_run_id'],
                ]
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getItemWithRun(int $itemId): ?array {
        $item = $this->db->query(
            "SELECT
                i.*,
                r.payroll_month,
                r.period_start,
                r.period_end,
                e.employee_code,
                e.full_name,
                e.designation,
                e.department,
                e.joined_on,
                p.payment_number,
                p.payment_method,
                p.payment_date
             FROM hr_payroll_items i
             JOIN {$this->table} r
               ON r.id = i.payroll_run_id
              AND r.company_id = i.company_id
             JOIN hr_employees e
               ON e.id = i.employee_id
              AND e.company_id = i.company_id
             LEFT JOIN payments p
               ON p.id = i.payroll_payment_id
              AND p.company_id = i.company_id
              AND p.deleted_at IS NULL
             WHERE i.company_id = ?
               AND i.id = ?
             LIMIT 1",
            [Tenant::require(), $itemId]
        )->fetch();

        return $item ?: null;
    }

    public function refreshRunPaymentStatus(int $runId, int $processedBy): void {
        $this->refreshRunStatus($runId, $processedBy);
    }

    public function dashboardSnapshot(string $month): array {
        $run = $this->getRunByMonth($month);
        if (!$run) {
            return [
                'has_run' => false,
                'status' => 'draft',
                'pending_items' => 0,
                'paid_items' => 0,
                'employee_count' => 0,
                'net_amount' => 0.0,
            ];
        }

        $stats = $this->db->query(
            "SELECT
                COUNT(*) AS total_items,
                SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) AS pending_items,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_items
             FROM hr_payroll_items
             WHERE company_id = ?
               AND payroll_run_id = ?",
            [Tenant::require(), (int)$run['id']]
        )->fetch() ?: [];

        return [
            'has_run' => true,
            'status' => (string)($run['status'] ?? 'processed'),
            'pending_items' => (int)($stats['pending_items'] ?? 0),
            'paid_items' => (int)($stats['paid_items'] ?? 0),
            'employee_count' => (int)($run['employee_count'] ?? 0),
            'net_amount' => round((float)($run['net_amount'] ?? 0), 2),
        ];
    }

    public function financeReport(string $fromMonth, string $toMonth): array {
        $companyId = Tenant::require();
        $fromMonth = preg_match('/^\d{4}-\d{2}$/', $fromMonth) ? $fromMonth : date('Y-m');
        $toMonth = preg_match('/^\d{4}-\d{2}$/', $toMonth) ? $toMonth : $fromMonth;
        if (strcmp($fromMonth, $toMonth) > 0) {
            [$fromMonth, $toMonth] = [$toMonth, $fromMonth];
        }

        $runs = $this->db->query(
            "SELECT
                r.*,
                processed.full_name AS processed_by_name,
                approved.full_name AS approved_by_name,
                COUNT(i.id) AS total_items,
                SUM(CASE WHEN i.payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_items,
                SUM(CASE WHEN i.payment_status = 'pending' THEN 1 ELSE 0 END) AS pending_items
             FROM {$this->table} r
             LEFT JOIN users processed ON processed.id = r.processed_by
             LEFT JOIN users approved ON approved.id = r.approved_by
             LEFT JOIN hr_payroll_items i
               ON i.payroll_run_id = r.id
              AND i.company_id = r.company_id
             WHERE r.company_id = ?
               AND r.payroll_month BETWEEN ? AND ?
             GROUP BY r.id
             ORDER BY r.payroll_month DESC, r.id DESC",
            [$companyId, $fromMonth, $toMonth]
        )->fetchAll();

        $entries = $this->db->query(
            "SELECT
                r.payroll_month,
                p.payment_number,
                p.payment_date,
                p.payment_method,
                p.amount,
                e.full_name AS employee_name,
                e.employee_code,
                j.account_code,
                j.account_name,
                j.entry_side
             FROM payroll_payment_journal_entries j
             JOIN payments p
               ON p.id = j.payment_id
              AND p.company_id = j.company_id
              AND p.deleted_at IS NULL
             JOIN hr_payroll_items i
               ON i.id = j.payroll_item_id
              AND i.company_id = j.company_id
             JOIN {$this->table} r
               ON r.id = i.payroll_run_id
              AND r.company_id = i.company_id
             JOIN hr_employees e
               ON e.id = i.employee_id
              AND e.company_id = i.company_id
             WHERE j.company_id = ?
               AND r.payroll_month BETWEEN ? AND ?
             ORDER BY p.payment_date DESC, p.id DESC, j.id ASC",
            [$companyId, $fromMonth, $toMonth]
        )->fetchAll();

        $summary = [
            'run_count' => count($runs),
            'gross_amount' => 0.0,
            'deduction_amount' => 0.0,
            'net_amount' => 0.0,
            'paid_amount' => 0.0,
            'pending_amount' => 0.0,
        ];

        foreach ($runs as $run) {
            $summary['gross_amount'] += (float)($run['gross_amount'] ?? 0);
            $summary['deduction_amount'] += (float)($run['deduction_amount'] ?? 0);
            $summary['net_amount'] += (float)($run['net_amount'] ?? 0);
            if (($run['status'] ?? '') === 'paid') {
                $summary['paid_amount'] += (float)($run['net_amount'] ?? 0);
            } else {
                $summary['pending_amount'] += (float)($run['net_amount'] ?? 0);
            }
        }

        foreach ($summary as $key => $value) {
            if ($key !== 'run_count') {
                $summary[$key] = round((float)$value, 2);
            }
        }

        return [
            'summary' => $summary,
            'runs' => $runs,
            'entries' => $entries,
        ];
    }

    public function approveRun(int $runId, int $approvedBy): void {
        $run = $this->getRunById($runId);
        if (!$run) {
            throw new \RuntimeException('Payroll run not found.');
        }
        if (($run['status'] ?? '') === 'paid') {
            throw new \RuntimeException('Paid payroll runs are already closed.');
        }
        if (($run['status'] ?? '') === 'approved') {
            return;
        }

        $this->db->query(
            "UPDATE {$this->table}
             SET status = 'approved',
                 approved_by = ?,
                 approved_at = NOW(),
                 locked_by = ?,
                 locked_at = NOW(),
                 updated_at = NOW()
             WHERE company_id = ?
               AND id = ?",
            [$approvedBy, $approvedBy, Tenant::require(), $runId]
        );
    }

    public function unapproveRun(int $runId, int $processedBy): void {
        $run = $this->getRunById($runId);
        if (!$run) {
            throw new \RuntimeException('Payroll run not found.');
        }
        if (($run['status'] ?? '') === 'paid') {
            throw new \RuntimeException('Paid payroll runs cannot be reopened.');
        }
        if (($run['status'] ?? '') !== 'approved') {
            return;
        }

        $this->db->query(
            "UPDATE {$this->table}
             SET status = 'processed',
                 approved_by = NULL,
                 approved_at = NULL,
                 locked_by = NULL,
                 locked_at = NULL,
                 processed_by = ?,
                 updated_at = NOW()
             WHERE company_id = ?
               AND id = ?",
            [$processedBy, Tenant::require(), $runId]
        );
    }

    private function refreshRunStatus(int $runId, int $processedBy): void {
        $run = $this->getRunById($runId);
        if (!$run) {
            throw new \RuntimeException('Payroll run not found.');
        }
        if (($run['status'] ?? '') === 'paid') {
            return;
        }

        $stats = $this->db->query(
            "SELECT
                COUNT(*) AS total_items,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_items
             FROM hr_payroll_items
             WHERE company_id = ?
               AND payroll_run_id = ?",
            [Tenant::require(), $runId]
        )->fetch();

        $allItemsPaid = (int)($stats['total_items'] ?? 0) > 0
            && (int)($stats['total_items'] ?? 0) === (int)($stats['paid_items'] ?? 0);

        if ($allItemsPaid) {
            $status = 'paid';
        } elseif (($run['status'] ?? '') === 'approved') {
            $status = 'approved';
        } else {
            $status = 'processed';
        }

        $this->db->query(
            "UPDATE {$this->table}
             SET status = ?,
                 processed_by = ?,
                 updated_at = NOW()
             WHERE id = ?
               AND company_id = ?",
            [$status, $processedBy, $runId, Tenant::require()]
        );
    }

    private function getRunById(int $runId): ?array {
        $row = $this->db->query(
            "SELECT *
             FROM {$this->table}
             WHERE company_id = ?
               AND id = ?
             LIMIT 1",
            [Tenant::require(), $runId]
        )->fetch();

        return $row ?: null;
    }
}
