<?php

class HrWorkflowService {
    private array $deps;

    public function __construct(array $deps = []) {
        $this->deps = $deps;
    }

    public function buildIndexViewData(string $search, string $status, int $page, string $month): array {
        $perPage = defined('RECORDS_PER_PAGE') ? RECORDS_PER_PAGE : 15;
        $employeeModel = $this->deps['employee_model'] ?? new HrEmployee();
        $attendanceModel = $this->deps['attendance_model'] ?? new HrAttendance();
        $leaveModel = $this->deps['leave_model'] ?? new HrLeaveRequest();
        $holidayModel = $this->deps['holiday_model'] ?? new HrHoliday();
        $shiftModel = $this->deps['shift_model'] ?? new HrShift();
        $payrollModel = $this->deps['payroll_model'] ?? new HrPayroll();

        $employees = ['data' => [], 'total' => 0, 'page' => 1, 'perPage' => $perPage, 'totalPages' => 1];
        $stats = ['total_employees' => 0, 'active_employees' => 0, 'on_leave_employees' => 0, 'inactive_employees' => 0];
        $attendanceSummary = [];
        $leaveSummary = [];
        $upcomingHolidays = [];
        $shiftCount = 0;
        $payrollSnapshot = ['has_run' => false, 'status' => 'draft', 'pending_items' => 0, 'paid_items' => 0, 'employee_count' => 0, 'net_amount' => 0.0];

        try {
            $employees = $employeeModel->searchPaginate($search, $status, $page);
        } catch (\Throwable $e) {
            error_log('[HR_INDEX_EMPLOYEES] ' . $e->getMessage());
        }

        try {
            $stats = $employeeModel->stats();
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
            $payrollSnapshot = $payrollModel->dashboardSnapshot($month);
        } catch (\Throwable $e) {
            error_log('[HR_INDEX_PAYROLL] ' . $e->getMessage());
        }

        return [
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
        ];
    }
}
