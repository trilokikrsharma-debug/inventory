<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/models/HrLeaveRequest.php';

class HrLeaveRequestTransitionTest extends BaseTestCase {
    protected function tearDown(): void {
        Tenant::reset();
        parent::tearDown();
    }

    public function testUpdateStatusRejectsWhenManagerApprovalIsStillPending(): void {
        Tenant::set(44, ['id' => 44]);
        $model = $this->makeModelWithRows([
            ['id' => 7, 'company_id' => 44, 'status' => 'pending', 'manager_status' => 'pending', 'deleted_at' => null],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('still pending manager approval');

        $model->updateStatus(7, 'approved', null, 9);
    }

    public function testUpdateStatusRejectsWhenManagerAlreadyRejected(): void {
        Tenant::set(44, ['id' => 44]);
        $model = $this->makeModelWithRows([
            ['id' => 7, 'company_id' => 44, 'status' => 'pending', 'manager_status' => 'rejected', 'deleted_at' => null],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already been rejected at manager stage');

        $model->updateStatus(7, 'approved', null, 9);
    }

    public function testUpdateManagerStatusRejectsWhenRequestIsNotAwaitingManagerApproval(): void {
        Tenant::set(44, ['id' => 44]);
        $model = $this->makeModelWithRows([
            ['id' => 7, 'company_id' => 44, 'status' => 'pending', 'manager_status' => 'approved', 'deleted_at' => null],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not awaiting manager approval');

        $model->updateManagerStatus(7, 'approved', null, 9);
    }

    public function testUpdateStatusAllowsFinalApprovalAfterManagerApproval(): void {
        Tenant::set(44, ['id' => 44]);
        $db = new FakeHrLeaveRequestDb([
            ['id' => 7, 'company_id' => 44, 'status' => 'pending', 'manager_status' => 'approved', 'deleted_at' => null],
            null,
        ]);
        $model = $this->makeModel($db);

        $model->updateStatus(7, 'approved', null, 9);

        $this->assertCount(2, $db->queries);
        $this->assertStringContainsString('UPDATE hr_leave_requests', $db->queries[1]['sql']);
        $this->assertSame(['approved', null, 9, 7, 44], $db->queries[1]['params']);
    }

    public function testUpdateManagerStatusAllowsManagerRejectionOnPendingRequest(): void {
        Tenant::set(44, ['id' => 44]);
        $db = new FakeHrLeaveRequestDb([
            ['id' => 7, 'company_id' => 44, 'status' => 'pending', 'manager_status' => 'pending', 'deleted_at' => null],
            null,
        ]);
        $model = $this->makeModel($db);

        $model->updateManagerStatus(7, 'rejected', 'Manager unavailable', 9);

        $this->assertCount(2, $db->queries);
        $this->assertStringContainsString('manager_status = ?', $db->queries[1]['sql']);
        $this->assertStringContainsString("status = 'rejected'", $db->queries[1]['sql']);
        $this->assertSame(['rejected', 9, 'Manager unavailable', 'Manager unavailable', 9, 7, 44], $db->queries[1]['params']);
    }

    private function makeModelWithRows(array $rows): HrLeaveRequest {
        return $this->makeModel(new FakeHrLeaveRequestDb(array_merge($rows, [null])));
    }

    private function makeModel(FakeHrLeaveRequestDb $db): HrLeaveRequest {
        return new class($db) extends HrLeaveRequest {
            public function __construct($db) {
                $this->db = $db;
            }
        };
    }
}

class FakeHrLeaveRequestDb {
    public array $queries = [];
    private array $rows;

    public function __construct(array $rows) {
        $this->rows = array_values($rows);
    }

    public function query(string $sql, array $params = []) {
        $this->queries[] = ['sql' => $sql, 'params' => $params];
        $row = array_shift($this->rows);
        return new FakeHrLeaveRequestResult($row);
    }
}

class FakeHrLeaveRequestResult {
    private $row;

    public function __construct($row) {
        $this->row = $row;
    }

    public function fetch() {
        return $this->row;
    }
}
