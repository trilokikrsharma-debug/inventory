<?php
/**
 * HR Controller
 */
class HrController extends Controller {

    protected $allowedActions = ['index', 'create', 'edit', 'view_employee', 'delete', 'attendance', 'mark_attendance', 'leaves', 'create_leave', 'approve_leave', 'reject_leave', 'manager_approve_leave', 'manager_reject_leave', 'payroll', 'process_payroll', 'approve_payroll', 'unlock_payroll', 'mark_payroll_paid', 'update_payroll_item', 'payslip', 'holidays', 'create_holiday', 'shifts', 'create_shift', 'leave_balances', 'update_leave_balance', 'save_leave_policy', 'process_leave_accruals', 'save_payroll_policy'];

    public function index() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');

        $search = trim((string)$this->get('search', ''));
        $status = trim((string)$this->get('status', ''));
        $page = max(1, (int)$this->get('pg', 1));
        $model = new HrEmployee();
        $month = $this->normalizeMonth((string)$this->get('month', date('Y-m')));
        $attendanceModel = new HrAttendance();
        $leaveModel = new HrLeaveRequest();
        $holidayModel = new HrHoliday();
        $shiftModel = new HrShift();

        $employees = ['data' => [], 'total' => 0, 'page' => 1, 'perPage' => RECORDS_PER_PAGE, 'totalPages' => 1];
        $stats = ['total_employees' => 0, 'active_employees' => 0, 'on_leave_employees' => 0, 'inactive_employees' => 0];
        $attendanceSummary = [];
        $leaveSummary = [];
        $upcomingHolidays = [];
        $shiftCount = 0;
        $payrollSnapshot = ['has_run' => false, 'status' => 'draft', 'pending_items' => 0, 'paid_items' => 0, 'employee_count' => 0, 'net_amount' => 0.0];

        try {
            $employees = $model->searchPaginate($search, $status, $page);
        } catch (\Throwable $e) {
            error_log('[HR_INDEX_EMPLOYEES] ' . $e->getMessage());
        }

        try {
            $stats = $model->stats();
        } catch (\Throwable $e) {
            error_log('[HR_INDEX_STATS] ' . $e->getMessage());
        }

        try {
            $attendanceSummary = $attendanceModel->monthlySummary($month);
        } catch (\Throwable $e) {
            error_log('[HR_INDEX_ATTENDANCE] ' . $e->getMessage());
        }

        try {
            $leaveSummary = $leaveModel->summary();
        } catch (\Throwable $e) {
            error_log('[HR_INDEX_LEAVES] ' . $e->getMessage());
        }

        try {
            $upcomingHolidays = $holidayModel->upcoming();
        } catch (\Throwable $e) {
            error_log('[HR_INDEX_HOLIDAYS] ' . $e->getMessage());
        }

        try {
            $shiftCount = count($shiftModel->allOrdered());
        } catch (\Throwable $e) {
            error_log('[HR_INDEX_SHIFTS] ' . $e->getMessage());
        }

        try {
            $payrollSnapshot = (new HrPayroll())->dashboardSnapshot($month);
        } catch (\Throwable $e) {
            error_log('[HR_INDEX_PAYROLL] ' . $e->getMessage());
        }

        $this->view('hr.index', [
            'pageTitle' => 'HR Tools',
            'employees' => $employees,
            'stats' => $stats,
            'attendanceSummary' => $attendanceSummary,
            'leaveSummary' => $leaveSummary,
            'upcomingHolidays' => $upcomingHolidays,
            'shiftCount' => $shiftCount,
            'payrollSnapshot' => $payrollSnapshot,
            'month' => $month,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        $model = new HrEmployee();
        $employee = [
            'employee_code' => $model->nextEmployeeCode(),
            'status' => 'active',
            'joined_on' => date('Y-m-d'),
            'shift_id' => (new HrShift())->defaultShiftId(),
        ];

        if ($this->isPost()) {
            $this->validateCSRF();
            $employee = $this->employeePayload();

            try {
                $id = $model->create($employee);
                $this->logActivity('Created employee: ' . $employee['full_name'], 'hr_employees', $id);
                $this->setFlash('success', 'Employee created successfully.');
                $this->redirect('index.php?page=hr');
                return;
            } catch (\Throwable $e) {
                $this->setFlash('error', 'Unable to create employee right now. Please try again.');
            }
        }

        $this->view('hr.form', [
            'pageTitle' => 'Add Employee',
            'employee' => $employee,
            'formAction' => 'create',
            'shifts' => (new HrShift())->allOrdered(),
            'managers' => $this->managerOptions(),
        ]);
    }

    public function edit() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        $id = (int)$this->get('id');
        $model = new HrEmployee();
        $employee = $model->findWithShift($id);
        $this->authorizeRecordAccess($employee, 'index.php?page=hr', false);

        if ($this->isPost()) {
            $this->validateCSRF();
            $payload = $this->employeePayload();

            try {
                $model->update($id, $payload);
                $this->logActivity('Updated employee: ' . $payload['full_name'], 'hr_employees', $id);
                $this->setFlash('success', 'Employee updated successfully.');
                $this->redirect('index.php?page=hr');
                return;
            } catch (\Throwable $e) {
                $employee = array_merge($employee ?: [], $payload);
                $this->setFlash('error', 'Unable to update employee right now. Please try again.');
            }
        }

        $this->view('hr.form', [
            'pageTitle' => 'Edit Employee',
            'employee' => $employee,
            'formAction' => 'edit&id=' . $id,
            'shifts' => (new HrShift())->allOrdered(),
            'managers' => $this->managerOptions(),
        ]);
    }

    public function view_employee() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        $id = (int)$this->get('id');
        $employee = (new HrEmployee())->findWithShift($id);
        $this->authorizeRecordAccess($employee, 'index.php?page=hr', true);

        $this->view('hr.view', [
            'pageTitle' => 'Employee Details',
            'employee' => $employee,
            'attendanceEntries' => (new HrAttendance())->recentEntries(date('Y-m'), '', (int)$id, 8),
            'leaveRequests' => (new HrLeaveRequest())->listWithEmployee('', (int)$id, 8),
            'leaveBalances' => (new HrLeaveBalance())->summaryByEmployee((int)$id),
        ]);
    }

    public function holidays() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');

        $year = max(2020, min(2100, (int)$this->get('year', date('Y'))));
        $this->view('hr.holidays', [
            'pageTitle' => 'Holiday Calendar',
            'year' => $year,
            'holidays' => (new HrHoliday())->listByYear($year),
        ]);
    }

    public function create_holiday() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=holidays');
            return;
        }
        $this->validateCSRF();

        $holidayDate = trim((string)$this->post('holiday_date', ''));
        $holidayName = trim((string)$this->post('holiday_name', ''));
        $holidayType = strtolower(trim((string)$this->post('holiday_type', 'public')));
        if (!$this->isValidDateYmd($holidayDate) || $holidayName === '') {
            $this->setFlash('error', 'Valid holiday date and name are required.');
            $this->redirect('index.php?page=hr&action=holidays');
            return;
        }
        if (!in_array($holidayType, ['public', 'optional', 'company'], true)) {
            $holidayType = 'public';
        }

        try {
            (new HrHoliday())->create([
                'holiday_date' => $holidayDate,
                'holiday_name' => $this->sanitize($holidayName),
                'holiday_type' => $holidayType,
                'notes' => trim((string)$this->post('notes', '')) ?: null,
            ]);
            $this->setFlash('success', 'Holiday added.');
        } catch (\Throwable $e) {
            error_log('[HR_HOLIDAY_CREATE] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to add holiday.');
        }
        $this->redirect('index.php?page=hr&action=holidays&year=' . date('Y', strtotime($holidayDate)));
    }

    public function shifts() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');

        $this->view('hr.shifts', [
            'pageTitle' => 'Shift Scheduling',
            'shifts' => (new HrShift())->allOrdered(),
        ]);
    }

    public function create_shift() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=shifts');
            return;
        }
        $this->validateCSRF();

        $shiftName = trim((string)$this->post('shift_name', ''));
        $startTime = trim((string)$this->post('start_time', ''));
        $endTime = trim((string)$this->post('end_time', ''));
        $gracePeriodMinutes = max(0, min(180, (int)$this->post('grace_period_minutes', 15)));
        if ($shiftName === '' || !preg_match('/^\d{2}:\d{2}$/', $startTime) || !preg_match('/^\d{2}:\d{2}$/', $endTime)) {
            $this->setFlash('error', 'Shift name, start time, and end time are required.');
            $this->redirect('index.php?page=hr&action=shifts');
            return;
        }

        try {
            $shiftModel = new HrShift();
            $isDefault = $this->post('is_default', 0) ? 1 : 0;
            $shiftId = $shiftModel->create([
                'shift_name' => $this->sanitize($shiftName),
                'start_time' => $startTime . ':00',
                'end_time' => $endTime . ':00',
                'grace_period_minutes' => $gracePeriodMinutes,
                'weekly_off_day' => trim((string)$this->post('weekly_off_day', 'Sunday')) ?: 'Sunday',
                'is_default' => $isDefault,
                'notes' => trim((string)$this->post('notes', '')) ?: null,
            ]);
            if ($isDefault === 1) {
                $shiftModel->setDefault((int)$shiftId);
            }
            $this->setFlash('success', 'Shift saved.');
        } catch (\Throwable $e) {
            error_log('[HR_SHIFT_CREATE] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to save shift.');
        }
        $this->redirect('index.php?page=hr&action=shifts');
    }

    public function leave_balances() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');

        $employees = (new HrEmployee())->searchPaginate('', '', 1, 500);
        $this->view('hr.leave_balances', [
            'pageTitle' => 'Leave Balances',
            'employees' => $employees['data'] ?? [],
            'balanceMap' => (new HrLeaveBalance())->balanceMap(),
            'policyMap' => (new HrLeavePolicy())->indexedByType(),
            'accrualMonth' => $this->normalizeMonth((string)$this->get('month', date('Y-m'))),
        ]);
    }

    public function update_leave_balance() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=leave_balances');
            return;
        }
        $this->validateCSRF();

        $employeeId = (int)$this->post('employee_id', 0);
        $leaveType = strtolower(trim((string)$this->post('leave_type', 'casual')));
        if ($employeeId <= 0 || !(new HrEmployee())->find($employeeId)) {
            $this->setFlash('error', 'Please select a valid employee.');
            $this->redirect('index.php?page=hr&action=leave_balances');
            return;
        }
        if (!in_array($leaveType, ['casual', 'sick', 'earned', 'unpaid', 'other'], true)) {
            $this->setFlash('error', 'Invalid leave type.');
            $this->redirect('index.php?page=hr&action=leave_balances');
            return;
        }

        try {
            (new HrLeaveBalance())->upsertBalance([
                'employee_id' => $employeeId,
                'leave_type' => $leaveType,
                'opening_days' => max(0, (float)$this->post('opening_days', 0)),
                'accrued_days' => max(0, (float)$this->post('accrued_days', 0)),
                'used_days' => max(0, (float)$this->post('used_days', 0)),
            ]);
            $this->setFlash('success', 'Leave balance updated.');
        } catch (\Throwable $e) {
            error_log('[HR_LEAVE_BALANCE] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to update leave balance.');
        }
        $this->redirect('index.php?page=hr&action=leave_balances');
    }

    public function save_leave_policy() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=leave_balances');
            return;
        }
        $this->validateCSRF();

        try {
            $policyModel = new HrLeavePolicy();
            foreach ($this->leavePolicyPayloads() as $policy) {
                $policyModel->upsertPolicy($policy);
            }
            $this->setFlash('success', 'Leave accrual policies updated.');
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[HR_LEAVE_POLICY] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to update leave accrual policies.');
        }

        $this->redirect('index.php?page=hr&action=leave_balances');
    }

    public function process_leave_accruals() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=leave_balances');
            return;
        }
        $this->validateCSRF();

        $month = $this->normalizeMonth((string)$this->post('month', date('Y-m')));
        $policyModel = new HrLeavePolicy();
        $policies = $policyModel->activePoliciesForMonth($month);
        $employees = (new HrEmployee())->eligibleForLeaveAccrual($month);

        if (empty($policies)) {
            $this->setFlash('error', 'No active leave accrual policies are configured.');
            $this->redirect('index.php?page=hr&action=leave_balances&month=' . urlencode($month));
            return;
        }

        if (empty($employees)) {
            $this->setFlash('error', 'No eligible employees found for the selected accrual month.');
            $this->redirect('index.php?page=hr&action=leave_balances&month=' . urlencode($month));
            return;
        }

        try {
            $processed = (new HrLeaveBalance())->processMonthlyAccruals($month, $policies, $employees);
            foreach ($policies as $policy) {
                if (HrLeaveAccrualService::shouldProcessMonth((string)($policy['last_processed_month'] ?? ''), $month)) {
                    $policyModel->markProcessed((string)$policy['leave_type'], $month);
                }
            }
            $this->logActivity('Processed leave accruals for ' . $month, 'hr_leave_policies', 0);
            $this->setFlash('success', 'Leave accrual processed for ' . date('F Y', strtotime($month . '-01')) . ' across ' . $processed . ' balance records.');
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[HR_LEAVE_ACCRUAL] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to process leave accrual right now.');
        }

        $this->redirect('index.php?page=hr&action=leave_balances&month=' . urlencode($month));
    }

    public function attendance() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');

        $month = $this->normalizeMonth((string)$this->get('month', date('Y-m')));
        $status = strtolower(trim((string)$this->get('status', '')));
        $employeeId = (int)$this->get('employee_id', 0);
        $selectedDate = trim((string)$this->get('attendance_date', date('Y-m-d')));
        $employees = (new HrEmployee())->searchPaginate('', 'active', 1, 500);
        $attendanceModel = new HrAttendance();

        $this->view('hr.attendance', [
            'pageTitle' => 'Attendance',
            'month' => $month,
            'status' => $status,
            'employeeId' => $employeeId,
            'employees' => $employees['data'] ?? [],
            'summary' => $attendanceModel->monthlySummary($month),
            'entries' => $attendanceModel->recentEntries($month, $status, $employeeId),
            'selectedDate' => $selectedDate,
            'selectedContext' => $this->attendanceDateContext($employeeId, $selectedDate),
        ]);
    }

    public function mark_attendance() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=attendance');
            return;
        }

        $this->validateCSRF();
        try {
            $payload = $this->attendancePayload();
            $context = $this->attendanceDateContext((int)$payload['employee_id'], (string)$payload['attendance_date']);
            $attendancePolicy = HrAttendancePolicyService::resolveStatus(
                (string)$payload['status'],
                $payload['check_in_time'] ? substr((string)$payload['check_in_time'], 0, 5) : null,
                !empty($context['shift_start_time']) ? (string)$context['shift_start_time'] : null,
                isset($context['grace_period_minutes']) ? (int)$context['grace_period_minutes'] : null
            );
            $payload['status'] = $attendancePolicy['status'];
            $noteParts = [];
            if (!empty($attendancePolicy['label'])) {
                $noteParts[] = $attendancePolicy['label'];
            }
            if (!empty($context['label'])) {
                $noteParts[] = $context['label'];
            }
            if (!empty($payload['note'])) {
                $noteParts[] = $payload['note'];
            }
            $payload['note'] = !empty($noteParts) ? implode(' ', $noteParts) : null;
            (new HrAttendance())->upsertEntry($payload + ['created_by' => (int)(Session::get('user')['id'] ?? 0)]);
            $this->logActivity('Marked attendance for employee #' . $payload['employee_id'], 'hr_attendance', $payload['employee_id']);
            $this->setFlash('success', 'Attendance updated.');
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[HR_ATTENDANCE] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to update attendance right now.');
        }

        $this->redirect('index.php?page=hr&action=attendance&month=' . urlencode($this->normalizeMonth((string)$this->post('attendance_month', date('Y-m')))));
    }

    public function leaves() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');

        $status = strtolower(trim((string)$this->get('status', '')));
        $employeeId = (int)$this->get('employee_id', 0);
        $employeeModel = new HrEmployee();
        $employees = $employeeModel->searchPaginate('', '', 1, 500);
        $leaveModel = new HrLeaveRequest();

        $this->view('hr.leaves', [
            'pageTitle' => 'Leave Management',
            'status' => $status,
            'employeeId' => $employeeId,
            'employees' => $employees['data'] ?? [],
            'summary' => $leaveModel->summary($status),
            'requests' => $leaveModel->listWithEmployee($status, $employeeId),
        ]);
    }

    public function payroll() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');

        $month = $this->normalizeMonth((string)$this->get('month', date('Y-m')));
        $employees = (new HrEmployee())->searchPaginate('', '', 1, 500);
        $attendanceUnits = (new HrAttendance())->employeeMonthlyUnits($month);
        $approvedLeaveDays = (new HrLeaveRequest())->approvedDaysByEmployee($month);
        $payrollModel = new HrPayroll();
        $existingRun = $payrollModel->getRunByMonth($month);
        $policy = (new HrPayrollPolicy())->current();

        $rows = $this->buildPayrollRows($month, $policy);
        $totalPayroll = 0.0;
        foreach ($rows as $row) {
            $totalPayroll += (float)($row['net_salary'] ?? 0);
        }

        $run = $existingRun ? $payrollModel->getRunWithItems((int)$existingRun['id']) : null;

        $this->view('hr.payroll', [
            'pageTitle' => 'Payroll Register',
            'month' => $month,
            'rows' => $rows,
            'totalPayroll' => round($totalPayroll, 2),
            'run' => $run,
            'recentRuns' => $payrollModel->recentRuns(),
            'policy' => $policy,
        ]);
    }

    public function save_payroll_policy() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=payroll');
            return;
        }

        $this->validateCSRF();
        try {
            (new HrPayrollPolicy())->savePolicy($this->payrollPolicyPayload());
            $this->setFlash('success', 'Statutory payroll policy updated.');
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[HR_PAYROLL_POLICY] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to update payroll policy.');
        }

        $this->redirect('index.php?page=hr&action=payroll&month=' . urlencode($this->normalizeMonth((string)$this->post('month', date('Y-m')))));
    }

    public function process_payroll() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=payroll');
            return;
        }

        $this->validateCSRF();
        $month = $this->normalizeMonth((string)$this->post('month', date('Y-m')));
        $rows = $this->buildPayrollRows($month);
        if (empty($rows)) {
            $this->setFlash('error', 'No employees available for payroll processing.');
            $this->redirect('index.php?page=hr&action=payroll&month=' . urlencode($month));
            return;
        }

        try {
            $runId = (new HrPayroll())->createOrRefreshRun($month, $rows, (int)(Session::get('user')['id'] ?? 0));
            $this->logActivity('Processed payroll for ' . $month, 'hr_payroll_runs', $runId);
            $this->setFlash('success', 'Payroll processed for ' . date('F Y', strtotime($month . '-01')) . '.');
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[HR_PAYROLL_PROCESS] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to process payroll right now.');
        }

        $this->redirect('index.php?page=hr&action=payroll&month=' . urlencode($month));
    }

    public function approve_payroll() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=payroll');
            return;
        }

        $this->validateCSRF();
        $runId = (int)$this->post('id', 0);
        $month = $this->normalizeMonth((string)$this->post('month', date('Y-m')));

        try {
            (new HrPayroll())->approveRun($runId, (int)(Session::get('user')['id'] ?? 0));
            $this->logActivity('Approved payroll run #' . $runId, 'hr_payroll_runs', $runId);
            $this->setFlash('success', 'Payroll approved and locked for payout posting.');
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[HR_PAYROLL_APPROVE] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to approve payroll.');
        }

        $this->redirect('index.php?page=hr&action=payroll&month=' . urlencode($month));
    }

    public function unlock_payroll() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=payroll');
            return;
        }

        $this->validateCSRF();
        $runId = (int)$this->post('id', 0);
        $month = $this->normalizeMonth((string)$this->post('month', date('Y-m')));

        try {
            (new HrPayroll())->unapproveRun($runId, (int)(Session::get('user')['id'] ?? 0));
            $this->logActivity('Unlocked payroll run #' . $runId, 'hr_payroll_runs', $runId);
            $this->setFlash('success', 'Payroll unlocked for adjustment and reprocessing.');
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[HR_PAYROLL_UNLOCK] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to unlock payroll.');
        }

        $this->redirect('index.php?page=hr&action=payroll&month=' . urlencode($month));
    }

    public function mark_payroll_paid() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=payroll');
            return;
        }

        $this->validateCSRF();
        $itemId = (int)$this->post('id', 0);
        $month = $this->normalizeMonth((string)$this->post('month', date('Y-m')));

        try {
            $paymentId = (new HrPayroll())->markItemPaid($itemId, (int)(Session::get('user')['id'] ?? 0), [
                'payment_method' => $this->normalizePaymentMethod((string)$this->post('payment_method', 'cash')),
                'payment_date' => trim((string)$this->post('payment_date', date('Y-m-d'))),
                'reference_number' => trim((string)$this->post('reference_number', '')) ?: null,
                'bank_name' => trim((string)$this->post('bank_name', '')) ?: null,
                'note' => trim((string)$this->post('payment_note', '')) ?: null,
            ]);
            $this->logActivity('Marked payroll item paid #' . $itemId, 'hr_payroll_items', $itemId);
            $this->setFlash('success', 'Payroll item marked as paid and posted to finance register #' . $paymentId . '.');
        } catch (\Throwable $e) {
            error_log('[HR_PAYROLL_PAID] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to update payroll payment status.');
        }

        $this->redirect('index.php?page=hr&action=payroll&month=' . urlencode($month));
    }

    public function update_payroll_item() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=payroll');
            return;
        }

        $this->validateCSRF();
        $itemId = (int)$this->post('id', 0);
        $month = $this->normalizeMonth((string)$this->post('month', date('Y-m')));

        try {
            (new HrPayroll())->updateItemAdjustments($itemId, [
                'allowance_amount' => max(0, (float)$this->post('allowance_amount', 0)),
                'bonus_amount' => max(0, (float)$this->post('bonus_amount', 0)),
                'other_deduction_amount' => max(0, (float)$this->post('other_deduction_amount', 0)),
                'adjustment_notes' => trim((string)$this->post('adjustment_notes', '')) ?: null,
            ], (int)(Session::get('user')['id'] ?? 0));
            $this->logActivity('Updated payroll adjustments for item #' . $itemId, 'hr_payroll_items', $itemId);
            $this->setFlash('success', 'Payroll adjustments updated.');
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[HR_PAYROLL_UPDATE] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to update payroll adjustments.');
        }

        $this->redirect('index.php?page=hr&action=payroll&month=' . urlencode($month));
    }

    public function payslip() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');

        $itemId = (int)$this->get('id', 0);
        $item = (new HrPayroll())->getItemWithRun($itemId);
        $this->authorizeRecordAccess($item, 'index.php?page=hr&action=payroll', true);
        if (!$item) {
            $this->setFlash('error', 'Payslip not found.');
            $this->redirect('index.php?page=hr&action=payroll');
            return;
        }

        $company = (new SettingsModel())->getSettings();
        $html = $this->renderTemplateToString('hr.payslip', [
            'item' => $item,
            'company' => $company,
        ]);

        if ($this->get('download', '0') === '1' && class_exists('\\Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $content = $dompdf->output();
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)($item['employee_code'] ?? 'payslip'));
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="PAYSLIP_' . $safeName . '_' . ($item['payroll_month'] ?? date('Y-m')) . '.pdf"');
            header('Content-Length: ' . strlen($content));
            echo $content;
            exit;
        }

        echo $html;
        exit;
    }

    public function create_leave() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=leaves');
            return;
        }

        $this->validateCSRF();
        try {
            $payload = $this->leavePayload();
            $id = (new HrLeaveRequest())->createRequest($payload);
            $this->logActivity('Created leave request for employee #' . $payload['employee_id'], 'hr_leave_requests', $id);
            $this->setFlash('success', 'Leave request created.');
        } catch (\RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[HR_LEAVE_CREATE] ' . $e->getMessage());
            $this->setFlash('error', 'Unable to create leave request right now.');
        }

        $this->redirect('index.php?page=hr&action=leaves');
    }

    public function approve_leave() {
        $this->updateLeaveStatus('approved', null);
    }

    public function reject_leave() {
        $reason = trim((string)$this->post('rejection_reason', ''));
        $this->updateLeaveStatus('rejected', $reason !== '' ? $this->sanitize($reason) : 'Rejected');
    }

    public function manager_approve_leave() {
        $this->updateManagerLeaveStatus('approved', null);
    }

    public function manager_reject_leave() {
        $reason = trim((string)$this->post('rejection_reason', ''));
        $this->updateManagerLeaveStatus('rejected', $reason !== '' ? $this->sanitize($reason) : 'Rejected by manager');
    }

    public function delete() {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr');
            return;
        }

        $this->validateCSRF();
        $id = (int)$this->post('id');
        $model = new HrEmployee();
        $employee = $model->find($id);
        $this->authorizeRecordAccess($employee, 'index.php?page=hr', false);

        $model->delete($id);
        $this->logActivity('Deleted employee: ' . ($employee['full_name'] ?? $id), 'hr_employees', $id);
        $this->setFlash('success', 'Employee deleted.');
        $this->redirect('index.php?page=hr');
    }

    private function employeePayload(): array {
        $fullName = trim((string)$this->post('full_name', ''));
        $employeeCode = strtoupper(trim((string)$this->post('employee_code', '')));
        $designation = trim((string)$this->post('designation', ''));
        $department = trim((string)$this->post('department', ''));
        $email = strtolower(trim((string)$this->post('email', '')));
        $phone = trim((string)$this->post('phone', ''));
        $status = strtolower(trim((string)$this->post('status', 'active')));
        $joinedOn = trim((string)$this->post('joined_on', date('Y-m-d')));
        $salary = trim((string)$this->post('salary', ''));
        $notes = trim((string)$this->post('notes', ''));

        if ($fullName === '' || mb_strlen($fullName) < 2) {
            throw new \RuntimeException('Employee name is required.');
        }
        if ($employeeCode === '') {
            throw new \RuntimeException('Employee code is required.');
        }
        if ($designation === '') {
            throw new \RuntimeException('Designation is required.');
        }
        if ($joinedOn === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $joinedOn)) {
            throw new \RuntimeException('Valid joining date is required.');
        }
        if (!in_array($status, ['active', 'inactive', 'on_leave'], true)) {
            $status = 'active';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Valid email is required.');
        }
        if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) {
            throw new \RuntimeException('Phone must be 7 to 20 valid characters.');
        }
        if ($salary !== '' && !is_numeric($salary)) {
            throw new \RuntimeException('Salary must be numeric.');
        }

        return [
            'employee_code' => $this->sanitize($employeeCode),
            'full_name' => $this->sanitize($fullName),
            'designation' => $this->sanitize($designation),
            'department' => $this->sanitize($department) ?: null,
            'email' => $email !== '' ? $this->sanitize($email) : null,
            'phone' => $phone !== '' ? $this->sanitize($phone) : null,
            'shift_id' => $this->validatedShiftId(),
            'reporting_manager_user_id' => $this->validatedReportingManagerUserId(),
            'status' => $status,
            'joined_on' => $joinedOn,
            'salary' => $salary !== '' ? (float)$salary : null,
            'notes' => $notes !== '' ? $this->sanitize($notes) : null,
        ];
    }

    private function attendancePayload(): array {
        $employeeId = (int)$this->post('employee_id', 0);
        $attendanceDate = trim((string)$this->post('attendance_date', date('Y-m-d')));
        $status = strtolower(trim((string)$this->post('status', 'present')));
        $checkInTime = trim((string)$this->post('check_in_time', ''));
        $checkOutTime = trim((string)$this->post('check_out_time', ''));
        $note = trim((string)$this->post('note', ''));

        $employee = (new HrEmployee())->findWithShift($employeeId);
        if ($employeeId <= 0 || !$employee) {
            throw new \RuntimeException('Please select a valid employee.');
        }
        if (!$this->isValidDateYmd($attendanceDate)) {
            throw new \RuntimeException('Valid attendance date is required.');
        }
        if (!in_array($status, ['present', 'absent', 'half_day', 'late'], true)) {
            throw new \RuntimeException('Please select a valid attendance status.');
        }
        if ($checkInTime !== '' && !preg_match('/^\d{2}:\d{2}$/', $checkInTime)) {
            throw new \RuntimeException('Check-in time must be in HH:MM format.');
        }
        if ($checkOutTime !== '' && !preg_match('/^\d{2}:\d{2}$/', $checkOutTime)) {
            throw new \RuntimeException('Check-out time must be in HH:MM format.');
        }

        return [
            'employee_id' => $employeeId,
            'attendance_date' => $attendanceDate,
            'status' => $status,
            'check_in_time' => $checkInTime !== '' ? $checkInTime . ':00' : null,
            'check_out_time' => $checkOutTime !== '' ? $checkOutTime . ':00' : null,
            'note' => $note !== '' ? $this->sanitize($note) : null,
        ];
    }

    private function leavePayload(): array {
        $employeeId = (int)$this->post('employee_id', 0);
        $leaveType = strtolower(trim((string)$this->post('leave_type', 'casual')));
        $startDate = trim((string)$this->post('start_date', ''));
        $endDate = trim((string)$this->post('end_date', ''));
        $reason = trim((string)$this->post('reason', ''));

        if ($employeeId <= 0 || !(new HrEmployee())->find($employeeId)) {
            throw new \RuntimeException('Please select a valid employee.');
        }
        if (!in_array($leaveType, ['casual', 'sick', 'earned', 'unpaid', 'other'], true)) {
            throw new \RuntimeException('Please select a valid leave type.');
        }
        if (!$this->isValidDateYmd($startDate) || !$this->isValidDateYmd($endDate)) {
            throw new \RuntimeException('Valid leave dates are required.');
        }
        if (strtotime($startDate) > strtotime($endDate)) {
            throw new \RuntimeException('Leave start date must be before end date.');
        }
        if ($reason === '') {
            throw new \RuntimeException('Leave reason is required.');
        }

        $days = (int)((strtotime($endDate) - strtotime($startDate)) / 86400) + 1;

        return [
            'employee_id' => $employeeId,
            'leave_type' => $leaveType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_count' => max(1, $days),
            'reason' => $this->sanitize($reason),
            'status' => 'pending',
            'requested_by' => (int)(Session::get('user')['id'] ?? 0),
            'approver_user_id' => !empty($employee['reporting_manager_user_id']) ? (int)$employee['reporting_manager_user_id'] : null,
            'manager_status' => !empty($employee['reporting_manager_user_id']) ? 'pending' : 'not_required',
        ];
    }

    private function leavePolicyPayloads(): array {
        $leaveTypes = ['casual', 'earned', 'sick', 'unpaid', 'other'];
        $accrualDays = (array)$this->post('monthly_accrual_days', []);
        $carryForward = (array)$this->post('max_carry_forward', []);
        $effectiveFrom = (array)$this->post('effective_from', []);
        $activeTypes = array_map('strval', (array)$this->post('is_active', []));
        $payloads = [];

        foreach ($leaveTypes as $leaveType) {
            $daysValue = trim((string)($accrualDays[$leaveType] ?? '0'));
            $carryValue = trim((string)($carryForward[$leaveType] ?? ''));
            $effectiveValue = trim((string)($effectiveFrom[$leaveType] ?? date('Y-m-01')));

            if ($daysValue !== '' && !is_numeric($daysValue)) {
                throw new \RuntimeException('Monthly accrual days must be numeric.');
            }
            if ($carryValue !== '' && !is_numeric($carryValue)) {
                throw new \RuntimeException('Carry forward limit must be numeric.');
            }
            if (!$this->isValidDateYmd($effectiveValue)) {
                throw new \RuntimeException('Effective from date is required for each leave policy.');
            }

            $payloads[] = [
                'leave_type' => $leaveType,
                'monthly_accrual_days' => max(0, (float)$daysValue),
                'max_carry_forward' => $carryValue !== '' ? max(0, (float)$carryValue) : null,
                'effective_from' => $effectiveValue,
                'is_active' => in_array($leaveType, $activeTypes, true) ? 1 : 0,
            ];
        }

        return $payloads;
    }

    private function payrollPolicyPayload(): array {
        $fields = [
            'pf_rate' => (string)$this->post('pf_rate', '12'),
            'pf_salary_cap' => (string)$this->post('pf_salary_cap', '15000'),
            'esi_rate' => (string)$this->post('esi_rate', '0.75'),
            'esi_salary_threshold' => (string)$this->post('esi_salary_threshold', '21000'),
            'tds_rate' => (string)$this->post('tds_rate', '10'),
            'tds_annual_threshold' => (string)$this->post('tds_annual_threshold', '700000'),
        ];

        foreach ($fields as $label => $value) {
            if ($value !== '' && !is_numeric($value)) {
                throw new \RuntimeException('Payroll policy values must be numeric.');
            }
        }

        return [
            'enable_pf' => (int)$this->post('enable_pf', 0) === 1,
            'pf_rate' => max(0, (float)$fields['pf_rate']),
            'pf_salary_cap' => $fields['pf_salary_cap'] !== '' ? max(0, (float)$fields['pf_salary_cap']) : null,
            'enable_esi' => (int)$this->post('enable_esi', 0) === 1,
            'esi_rate' => max(0, (float)$fields['esi_rate']),
            'esi_salary_threshold' => $fields['esi_salary_threshold'] !== '' ? max(0, (float)$fields['esi_salary_threshold']) : null,
            'enable_tds' => (int)$this->post('enable_tds', 0) === 1,
            'tds_rate' => max(0, (float)$fields['tds_rate']),
            'tds_annual_threshold' => $fields['tds_annual_threshold'] !== '' ? max(0, (float)$fields['tds_annual_threshold']) : null,
        ];
    }

    private function updateLeaveStatus(string $status, ?string $rejectionReason): void {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=leaves');
            return;
        }

        $this->validateCSRF();
        $id = (int)$this->post('id', 0);
        $leaveModel = new HrLeaveRequest();
        $request = $leaveModel->find($id);
        $this->authorizeRecordAccess($request, 'index.php?page=hr&action=leaves', false);

        if (!$request) {
            $this->setFlash('error', 'Leave request not found.');
            $this->redirect('index.php?page=hr&action=leaves');
            return;
        }

        if (($request['status'] ?? '') !== 'pending') {
            $this->setFlash('error', 'Only pending leave requests can be updated.');
            $this->redirect('index.php?page=hr&action=leaves');
            return;
        }

        $managerStatus = (string)($request['manager_status'] ?? 'not_required');
        if ($managerStatus === 'pending') {
            $this->setFlash('error', 'This leave request is still pending manager approval.');
            $this->redirect('index.php?page=hr&action=leaves');
            return;
        }
        if ($managerStatus === 'rejected') {
            $this->setFlash('error', 'This leave request has already been rejected at manager stage.');
            $this->redirect('index.php?page=hr&action=leaves');
            return;
        }

        $leaveModel->updateStatus($id, $status, $rejectionReason, (int)(Session::get('user')['id'] ?? 0));
        $this->logActivity(ucfirst($status) . ' leave request #' . $id, 'hr_leave_requests', $id);
        $this->setFlash('success', 'Leave request ' . $status . '.');
        $this->redirect('index.php?page=hr&action=leaves');
    }

    private function normalizeMonth(string $value): string {
        return preg_match('/^\d{4}-\d{2}$/', $value) ? $value : date('Y-m');
    }

    private function validatedShiftId(): ?int {
        $shiftId = (int)$this->post('shift_id', 0);
        if ($shiftId <= 0) {
            return null;
        }

        foreach ((new HrShift())->allOrdered() as $shift) {
            if ((int)($shift['id'] ?? 0) === $shiftId) {
                return $shiftId;
            }
        }

        throw new \RuntimeException('Please select a valid shift.');
    }

    private function validatedReportingManagerUserId(): ?int {
        $managerUserId = (int)$this->post('reporting_manager_user_id', 0);
        if ($managerUserId <= 0) {
            return null;
        }

        foreach ($this->managerOptions() as $manager) {
            if ((int)($manager['id'] ?? 0) === $managerUserId) {
                return $managerUserId;
            }
        }

        throw new \RuntimeException('Please select a valid reporting manager.');
    }

    private function isValidDateYmd(string $value): bool {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false;
    }

    private function attendanceDateContext(int $employeeId, string $attendanceDate): array {
        if ($employeeId <= 0 || !$this->isValidDateYmd($attendanceDate)) {
            return [];
        }

        $employee = (new HrEmployee())->findWithShift($employeeId);
        if (!$employee) {
            return [];
        }

        $context = [];
        $labels = [];
        $holiday = (new HrHoliday())->findByDate($attendanceDate);
        if ($holiday) {
            $holidayLabel = 'Holiday: ' . ($holiday['holiday_name'] ?? 'Scheduled holiday');
            $context['holiday_name'] = $holiday['holiday_name'] ?? null;
            $context['holiday_type'] = $holiday['holiday_type'] ?? null;
            $context['holiday_label'] = $holidayLabel;
            $labels[] = '[' . $holidayLabel . ']';
        }

        if (!empty($employee['shift_name'])) {
            $context['shift_name'] = $employee['shift_name'];
            $context['shift_start_time'] = $employee['shift_start_time'] ?? null;
            $context['shift_end_time'] = $employee['shift_end_time'] ?? null;
            $context['grace_period_minutes'] = isset($employee['grace_period_minutes']) ? (int)$employee['grace_period_minutes'] : 0;
            $context['weekly_off_day'] = $employee['weekly_off_day'] ?? null;
        }

        $weeklyOffDay = trim((string)($employee['weekly_off_day'] ?? ''));
        if ($weeklyOffDay !== '') {
            $dayName = date('l', strtotime($attendanceDate));
            if (strcasecmp($weeklyOffDay, $dayName) === 0) {
                $context['weekly_off_label'] = 'Weekly Off: ' . $weeklyOffDay;
                $labels[] = '[' . $context['weekly_off_label'] . ']';
            }
        }

        if (!empty($labels)) {
            $context['label'] = implode(' ', $labels);
        }

        return $context;
    }

    private function managerOptions(): array {
        $users = (new UserModel())->getAllUsers('', 1, 500);
        $rows = is_array($users['data'] ?? null) ? $users['data'] : [];

        return array_values(array_filter($rows, static function ($user): bool {
            return (int)($user['is_active'] ?? 0) === 1 && (int)($user['is_super_admin'] ?? 0) !== 1;
        }));
    }

    private function updateManagerLeaveStatus(string $status, ?string $rejectionReason): void {
        $this->requireFeature('hr');
        $this->requirePermission('settings.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=hr&action=leaves');
            return;
        }

        $this->validateCSRF();
        $id = (int)$this->post('id', 0);
        $leaveModel = new HrLeaveRequest();
        $request = $leaveModel->find($id);
        $this->authorizeRecordAccess($request, 'index.php?page=hr&action=leaves', false);

        if (!$request) {
            $this->setFlash('error', 'Leave request not found.');
            $this->redirect('index.php?page=hr&action=leaves');
            return;
        }

        if (($request['status'] ?? '') !== 'pending') {
            $this->setFlash('error', 'Only pending leave requests can be updated.');
            $this->redirect('index.php?page=hr&action=leaves');
            return;
        }

        if (($request['manager_status'] ?? 'not_required') !== 'pending') {
            $this->setFlash('error', 'This leave request is not awaiting manager approval.');
            $this->redirect('index.php?page=hr&action=leaves');
            return;
        }

        $leaveModel->updateManagerStatus($id, $status, $rejectionReason, (int)(Session::get('user')['id'] ?? 0));
        $this->logActivity('Manager ' . $status . ' leave request #' . $id, 'hr_leave_requests', $id);
        $this->setFlash('success', 'Manager review updated.');
        $this->redirect('index.php?page=hr&action=leaves');
    }

    private function buildPayrollRows(string $month, ?array $policy = null): array {
        $employees = (new HrEmployee())->searchPaginate('', '', 1, 500);
        $attendanceUnits = (new HrAttendance())->employeeMonthlyUnits($month);
        $approvedLeaveDays = (new HrLeaveRequest())->approvedDaysByEmployee($month);
        $policy = $policy ?? (new HrPayrollPolicy())->current();
        $daysInMonth = (int)date('t', strtotime($month . '-01'));
        $rows = [];

        foreach (($employees['data'] ?? []) as $employee) {
            $salary = round((float)($employee['salary'] ?? 0), 2);
            $leaveDays = (int)($approvedLeaveDays[(int)$employee['id']] ?? 0);
            $attendance = round((float)($attendanceUnits[(int)$employee['id']] ?? 0), 2);
            $attendanceGap = max(0.0, $daysInMonth - ($attendance + $leaveDays));
            $deductionAmount = $salary > 0 && $daysInMonth > 0 ? round(($salary / $daysInMonth) * $attendanceGap, 2) : 0.0;
            $statutory = HrStatutoryPayrollService::calculate($salary, $policy);
            $netSalary = max(0, round($salary - $deductionAmount - (float)$statutory['statutory_deduction_amount'], 2));

            $rows[] = [
                'employee_id' => (int)$employee['id'],
                'employee' => $employee,
                'attendance_units' => $attendance,
                'approved_leave_days' => $leaveDays,
                'monthly_salary' => $salary,
                'pf_amount' => $statutory['pf_amount'],
                'esi_amount' => $statutory['esi_amount'],
                'tds_amount' => $statutory['tds_amount'],
                'allowance_amount' => 0.0,
                'bonus_amount' => 0.0,
                'deduction_amount' => $deductionAmount,
                'statutory_deduction_amount' => $statutory['statutory_deduction_amount'],
                'other_deduction_amount' => 0.0,
                'adjustment_notes' => null,
                'net_salary' => $netSalary,
            ];
        }

        return $rows;
    }

    private function renderTemplateToString(string $viewPath, array $viewData = []): string {
        extract($viewData, EXTR_SKIP);
        $viewFile = VIEW_PATH . '/' . str_replace('.', '/', $viewPath) . '.php';
        if (!is_file($viewFile) || !is_readable($viewFile)) {
            throw new RuntimeException('View not found: ' . $viewPath);
        }

        ob_start();
        require $viewFile;
        return (string)ob_get_clean();
    }
}
