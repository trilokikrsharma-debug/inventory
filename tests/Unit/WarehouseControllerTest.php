<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Session.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/core/Controller.php';
require_once dirname(__DIR__, 2) . '/services/WarehouseStockService.php';

if (!class_exists('WarehouseModel', false)) {
    class WarehouseModel {
        public static array $activeWarehouses = [];
        public static ?RuntimeException $approveException = null;
        public static ?RuntimeException $rejectException = null;

        public function allActiveOrdered(): array {
            return self::$activeWarehouses;
        }

        public function approveTransfer(int $transferId, int $userId): array {
            if (self::$approveException) {
                throw self::$approveException;
            }

            return [
                'id' => $transferId,
                'transfer_number' => 'TRF-000001',
                'source_warehouse_name' => 'Main',
                'destination_warehouse_name' => 'Branch',
            ];
        }

        public function rejectTransfer(int $transferId, int $userId, ?string $reason = null): array {
            if (self::$rejectException) {
                throw self::$rejectException;
            }

            return [
                'id' => $transferId,
                'transfer_number' => 'TRF-000001',
                'source_warehouse_name' => 'Main',
                'destination_warehouse_name' => 'Branch',
            ];
        }
    }
}

require_once dirname(__DIR__, 2) . '/controllers/WarehouseController.php';

class WarehouseControllerTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        WarehouseModel::$activeWarehouses = [];
        WarehouseModel::$approveException = null;
        WarehouseModel::$rejectException = null;
    }

    protected function tearDown(): void {
        Tenant::reset();
        $_SESSION = [];
        WarehouseModel::$activeWarehouses = [];
        WarehouseModel::$approveException = null;
        WarehouseModel::$rejectException = null;
        parent::tearDown();
    }

    public function testGuardWarehouseAccessRejectsNonTenantSession(): void {
        $_SESSION['user'] = [
            'id' => 5,
            'role' => 'admin',
            'company_id' => 0,
            'is_super_admin' => 0,
        ];

        $controller = new TestWarehouseController();

        try {
            $this->invokePrivate($controller, 'guardWarehouseAccess');
            $this->fail('Expected redirect exception was not thrown.');
        } catch (TestWarehouseRedirectException $e) {
            $this->assertSame('index.php?page=dashboard', $e->target);
            $this->assertSame('Warehouses can only be managed inside a tenant account.', $_SESSION['flash']['error'] ?? null);
        }
    }

    public function testTransferRejectsSameSourceAndDestinationWarehouse(): void {
        $_SESSION['user'] = [
            'id' => 5,
            'role' => 'admin',
            'company_id' => 44,
            'is_super_admin' => 0,
        ];
        Tenant::set(44, ['id' => 44]);
        WarehouseModel::$activeWarehouses = [
            ['id' => 10, 'name' => 'Main'],
            ['id' => 11, 'name' => 'Branch'],
        ];

        $controller = new TestWarehouseController();
        $controller->setRequestMethod('POST');
        $controller->setPostData([
            'source_warehouse_id' => '10',
            'destination_warehouse_id' => '10',
            'transfer_date' => '2026-04-06',
        ]);

        try {
            $controller->transfer();
            $this->fail('Expected redirect exception was not thrown.');
        } catch (TestWarehouseRedirectException $e) {
            $this->assertSame('index.php?page=warehouses', $e->target);
            $this->assertSame('Source and destination warehouses must be different.', $_SESSION['flash']['error'] ?? null);
        }
    }

    public function testApproveTransferShowsRuntimeExceptionMessage(): void {
        $_SESSION['user'] = [
            'id' => 5,
            'role' => 'admin',
            'company_id' => 44,
            'is_super_admin' => 0,
        ];
        Tenant::set(44, ['id' => 44]);
        WarehouseModel::$approveException = new RuntimeException('Transfer is already approved.');

        $controller = new TestWarehouseController();
        $controller->setRequestMethod('POST');
        $controller->setPostData(['id' => '15']);

        try {
            $controller->approve_transfer();
            $this->fail('Expected redirect exception was not thrown.');
        } catch (TestWarehouseRedirectException $e) {
            $this->assertSame('index.php?page=warehouses', $e->target);
            $this->assertSame('Transfer is already approved.', $_SESSION['flash']['error'] ?? null);
        }
    }

    public function testApproveTransferSetsSuccessFlashOnSuccessfulApproval(): void {
        $_SESSION['user'] = [
            'id' => 5,
            'role' => 'admin',
            'company_id' => 44,
            'is_super_admin' => 0,
        ];
        Tenant::set(44, ['id' => 44]);

        $controller = new TestWarehouseController();
        $controller->setRequestMethod('POST');
        $controller->setPostData(['id' => '15']);

        try {
            $controller->approve_transfer();
            $this->fail('Expected redirect exception was not thrown.');
        } catch (TestWarehouseRedirectException $e) {
            $this->assertSame('index.php?page=warehouses', $e->target);
            $this->assertSame('Transfer approved and stock moved: TRF-000001', $_SESSION['flash']['success'] ?? null);
        }
    }

    public function testRejectTransferShowsRuntimeExceptionMessage(): void {
        $_SESSION['user'] = [
            'id' => 5,
            'role' => 'admin',
            'company_id' => 44,
            'is_super_admin' => 0,
        ];
        Tenant::set(44, ['id' => 44]);
        WarehouseModel::$rejectException = new RuntimeException('Transfer is already rejected.');

        $controller = new TestWarehouseController();
        $controller->setRequestMethod('POST');
        $controller->setPostData([
            'id' => '15',
            'rejection_reason' => 'Duplicate request',
        ]);

        try {
            $controller->reject_transfer();
            $this->fail('Expected redirect exception was not thrown.');
        } catch (TestWarehouseRedirectException $e) {
            $this->assertSame('index.php?page=warehouses', $e->target);
            $this->assertSame('Transfer is already rejected.', $_SESSION['flash']['error'] ?? null);
        }
    }

    public function testRejectTransferSetsSuccessFlashOnSuccessfulRejection(): void {
        $_SESSION['user'] = [
            'id' => 5,
            'role' => 'admin',
            'company_id' => 44,
            'is_super_admin' => 0,
        ];
        Tenant::set(44, ['id' => 44]);

        $controller = new TestWarehouseController();
        $controller->setRequestMethod('POST');
        $controller->setPostData([
            'id' => '15',
            'rejection_reason' => 'Duplicate request',
        ]);

        try {
            $controller->reject_transfer();
            $this->fail('Expected redirect exception was not thrown.');
        } catch (TestWarehouseRedirectException $e) {
            $this->assertSame('index.php?page=warehouses', $e->target);
            $this->assertSame('Transfer rejected: TRF-000001', $_SESSION['flash']['success'] ?? null);
        }
    }

    private function invokePrivate(object $controller, string $method, array $args = []) {
        $ref = new ReflectionMethod(WarehouseController::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($controller, $args);
    }
}

class TestWarehouseController extends WarehouseController {
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
        throw new TestWarehouseRedirectException($url);
    }

    protected function logActivity($message, $module = null, $recordId = null, $meta = null) {
        return;
    }
}

class TestWarehouseRedirectException extends RuntimeException {
    public string $target;

    public function __construct(string $target) {
        parent::__construct('Redirected to ' . $target);
        $this->target = $target;
    }
}
