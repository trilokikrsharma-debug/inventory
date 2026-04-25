<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/HrWorkflowService.php';

class HrWorkflowServiceTest extends BaseTestCase {
    public function testBuildIndexViewDataAggregatesDependencies(): void {
        $service = new HrWorkflowService([
            'employee_model' => new HrWorkflowStub([
                'searchPaginate' => ['data' => [['id' => 1]], 'total' => 1, 'page' => 2, 'perPage' => 15, 'totalPages' => 1],
                'stats' => ['total_employees' => 8, 'active_employees' => 7, 'on_leave_employees' => 1, 'inactive_employees' => 0],
            ]),
            'attendance_model' => new HrWorkflowStub(['monthlySummary' => ['present' => 5]]),
            'leave_model' => new HrWorkflowStub(['summary' => ['pending' => 2]]),
            'holiday_model' => new HrWorkflowStub(['upcoming' => [['holiday_name' => 'Founders Day']]]),
            'shift_model' => new HrWorkflowStub(['allOrdered' => [['id' => 1], ['id' => 2]]]),
            'payroll_model' => new HrWorkflowStub(['dashboardSnapshot' => ['has_run' => true, 'status' => 'approved']]),
        ]);

        $payload = $service->buildIndexViewData('ann', 'active', 2, '2026-04');

        $this->assertSame('HR Tools', $payload['pageTitle']);
        $this->assertSame('ann', $payload['search']);
        $this->assertSame('active', $payload['status']);
        $this->assertSame('2026-04', $payload['month']);
        $this->assertSame(8, $payload['stats']['total_employees']);
        $this->assertSame(['present' => 5], $payload['attendanceSummary']);
        $this->assertSame(2, $payload['shiftCount']);
        $this->assertTrue($payload['payrollSnapshot']['has_run']);
    }

    public function testBuildIndexViewDataFallsBackSafelyWhenDependencyFails(): void {
        $service = new HrWorkflowService([
            'employee_model' => new HrWorkflowStub([
                'searchPaginate' => new RuntimeException('employee lookup failed'),
                'stats' => ['total_employees' => 0, 'active_employees' => 0, 'on_leave_employees' => 0, 'inactive_employees' => 0],
            ]),
            'attendance_model' => new HrWorkflowStub(['monthlySummary' => new RuntimeException('attendance failed')]),
            'leave_model' => new HrWorkflowStub(['summary' => []]),
            'holiday_model' => new HrWorkflowStub(['upcoming' => []]),
            'shift_model' => new HrWorkflowStub(['allOrdered' => []]),
            'payroll_model' => new HrWorkflowStub(['dashboardSnapshot' => new RuntimeException('payroll failed')]),
        ]);

        $payload = $service->buildIndexViewData('', '', 1, '2026-04');

        $this->assertSame([], $payload['employees']['data']);
        $this->assertSame([], $payload['attendanceSummary']);
        $this->assertSame(0, $payload['shiftCount']);
        $this->assertFalse($payload['payrollSnapshot']['has_run']);
    }
}

class HrWorkflowStub {
    private array $map;

    public function __construct(array $map) {
        $this->map = $map;
    }

    public function __call(string $name, array $arguments) {
        $value = $this->map[$name] ?? null;
        if ($value instanceof Throwable) {
            throw $value;
        }
        return $value;
    }
}
