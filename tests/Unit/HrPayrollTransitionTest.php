<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/models/HrPayroll.php';

class HrPayrollTransitionTest extends BaseTestCase {
    protected function tearDown(): void {
        Tenant::reset();
        parent::tearDown();
    }

    public function testRefreshRunStatusPromotesApprovedRunToPaidWhenAllItemsArePaid(): void {
        Tenant::set(71, ['id' => 71]);
        $db = new FakeHrPayrollDb([
            ['id' => 12, 'company_id' => 71, 'status' => 'approved'],
            ['total_items' => 3, 'paid_items' => 3],
            null,
        ]);
        $model = $this->makeModel($db);

        $this->invokeRefreshRunStatus($model, 12, 9);

        $this->assertCount(3, $db->queries);
        $this->assertStringContainsString('UPDATE hr_payroll_runs', $db->queries[2]['sql']);
        $this->assertSame(['paid', 9, 12, 71], $db->queries[2]['params']);
    }

    public function testRefreshRunStatusKeepsApprovedRunLockedWhenNotFullyPaid(): void {
        Tenant::set(71, ['id' => 71]);
        $db = new FakeHrPayrollDb([
            ['id' => 12, 'company_id' => 71, 'status' => 'approved'],
            ['total_items' => 3, 'paid_items' => 2],
            null,
        ]);
        $model = $this->makeModel($db);

        $this->invokeRefreshRunStatus($model, 12, 9);

        $this->assertCount(3, $db->queries);
        $this->assertSame(['approved', 9, 12, 71], $db->queries[2]['params']);
    }

    public function testRefreshRunStatusReturnsImmediatelyForPaidRun(): void {
        Tenant::set(71, ['id' => 71]);
        $db = new FakeHrPayrollDb([
            ['id' => 12, 'company_id' => 71, 'status' => 'paid'],
        ]);
        $model = $this->makeModel($db);

        $this->invokeRefreshRunStatus($model, 12, 9);

        $this->assertCount(1, $db->queries);
    }

    public function testUpdateItemAdjustmentsRejectsLockedApprovedRun(): void {
        Tenant::set(71, ['id' => 71]);
        $db = new FakeHrPayrollDb([
            ['id' => 22, 'payroll_run_id' => 12, 'gross_salary' => 10000, 'deduction_amount' => 500, 'statutory_deduction_amount' => 0, 'payment_status' => 'pending'],
            ['id' => 12, 'company_id' => 71, 'status' => 'approved'],
        ]);
        $model = $this->makeModel($db);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Locked payroll runs cannot be edited');

        $model->updateItemAdjustments(22, [
            'allowance_amount' => 100,
            'bonus_amount' => 50,
            'other_deduction_amount' => 0,
        ], 9);
    }

    private function makeModel(FakeHrPayrollDb $db): HrPayroll {
        return new class($db) extends HrPayroll {
            public function __construct($db) {
                $this->db = $db;
            }
        };
    }

    private function invokeRefreshRunStatus(HrPayroll $model, int $runId, int $processedBy): void {
        $method = new ReflectionMethod(HrPayroll::class, 'refreshRunStatus');
        $method->setAccessible(true);
        $method->invoke($model, $runId, $processedBy);
    }
}

class FakeHrPayrollDb {
    public array $queries = [];
    private array $rows;

    public function __construct(array $rows) {
        $this->rows = array_values($rows);
    }

    public function query(string $sql, array $params = []) {
        $this->queries[] = ['sql' => $sql, 'params' => $params];
        $row = array_shift($this->rows);
        return new FakeHrPayrollResult($row);
    }
}

class FakeHrPayrollResult {
    private $row;

    public function __construct($row) {
        $this->row = $row;
    }

    public function fetch() {
        return $this->row;
    }
}
