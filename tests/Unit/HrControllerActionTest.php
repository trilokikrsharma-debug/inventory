<?php

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Session.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/core/Controller.php';
require_once dirname(__DIR__, 2) . '/services/HrWorkflowService.php';

require_once dirname(__DIR__, 2) . '/controllers/HrController.php';

class HrControllerActionTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->bootstrapHrControllerStubs();
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        HrPayroll::$lastApprovedRunId = null;
        HrPayroll::$lastApprovedBy = null;
        HrLeaveRequest::$request = [];
        HrLeaveRequest::$updatedManagerId = null;
        HrLeaveRequest::$updatedManagerStatus = null;
        HrLeaveRequest::$updatedManagerReason = null;
    }

    protected function tearDown(): void {
        Tenant::reset();
        $_SESSION = [];
        parent::tearDown();
    }

    private function bootstrapHrControllerStubs(): void {
        if (!class_exists('HrPayroll', false)) {
            eval(<<<'PHP'
class HrPayroll {
    public static ?int $lastApprovedRunId = null;
    public static ?int $lastApprovedBy = null;

    public function approveRun(int $runId, int $approvedBy): void {
        self::$lastApprovedRunId = $runId;
        self::$lastApprovedBy = $approvedBy;
    }
}
PHP);
        }

        if (!class_exists('HrLeaveRequest', false)) {
            eval(<<<'PHP'
class HrLeaveRequest {
    public static array $request = [];
    public static ?int $updatedManagerId = null;
    public static ?string $updatedManagerStatus = null;
    public static ?string $updatedManagerReason = null;

    public function find($id) {
        return self::$request;
    }

    public function updateManagerStatus(int $id, string $status, ?string $reason, int $approvedBy): void {
        self::$updatedManagerId = $id;
        self::$updatedManagerStatus = $status;
        self::$updatedManagerReason = $reason;
    }
}
PHP);
        }
    }

    public function testApprovePayrollSetsSuccessFlashOnSuccessfulApproval(): void {
        $_SESSION['user'] = [
            'id' => 5,
            'role' => 'admin',
            'company_id' => 44,
            'is_super_admin' => 0,
        ];
        Tenant::set(44, ['id' => 44]);

        $controller = new TestHrController();
        $controller->setRequestMethod('POST');
        $controller->setPostData([
            'id' => '18',
            'month' => '2026-04',
        ]);

        try {
            $controller->approve_payroll();
            $this->fail('Expected redirect exception was not thrown.');
        } catch (TestHrRedirectException $e) {
            $this->assertSame('index.php?page=hr&action=payroll&month=2026-04', $e->target);
            $this->assertSame('Payroll approved and locked for payout posting.', $_SESSION['flash']['success'] ?? null);
            $this->assertSame(18, HrPayroll::$lastApprovedRunId);
            $this->assertSame(5, HrPayroll::$lastApprovedBy);
        }
    }

    public function testManagerApproveLeaveSetsSuccessFlashOnSuccessfulApproval(): void {
        $_SESSION['user'] = [
            'id' => 5,
            'role' => 'admin',
            'company_id' => 44,
            'is_super_admin' => 0,
        ];
        Tenant::set(44, ['id' => 44]);
        HrLeaveRequest::$request = [
            'id' => 27,
            'company_id' => 44,
            'status' => 'pending',
            'manager_status' => 'pending',
            'created_by' => 5,
        ];

        $controller = new TestHrController();
        $controller->setRequestMethod('POST');
        $controller->setPostData([
            'id' => '27',
        ]);

        try {
            $controller->manager_approve_leave();
            $this->fail('Expected redirect exception was not thrown.');
        } catch (TestHrRedirectException $e) {
            $this->assertSame('index.php?page=hr&action=leaves', $e->target);
            $this->assertSame('Manager review updated.', $_SESSION['flash']['success'] ?? null);
            $this->assertSame(27, HrLeaveRequest::$updatedManagerId);
            $this->assertSame('approved', HrLeaveRequest::$updatedManagerStatus);
            $this->assertNull(HrLeaveRequest::$updatedManagerReason);
        }
    }
}

class TestHrController extends HrController {
    private array $postData = [];

    public function setPostData(array $data): void {
        $this->postData = $data;
    }

    public function setRequestMethod(string $method): void {
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    }

    protected function validateCSRF() {
        return;
    }

    protected function requirePermission($permission) {
        return true;
    }

    protected function requireFeature($feature) {
        return true;
    }

    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $this->postData;
        }
        return $this->postData[$key] ?? $default;
    }

    protected function redirect($url) {
        throw new TestHrRedirectException($url);
    }

    protected function logActivity($message, $module = null, $recordId = null, $meta = null) {
        return;
    }
}

class TestHrRedirectException extends RuntimeException {
    public string $target;

    public function __construct(string $target) {
        parent::__construct('Redirected to ' . $target);
        $this->target = $target;
    }
}
