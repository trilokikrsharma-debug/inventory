<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/services/WarehouseStockService.php';
if (!class_exists('WarehouseModel', false)) {
    require_once dirname(__DIR__, 2) . '/models/WarehouseModel.php';
}
require_once dirname(__DIR__, 2) . '/services/WarehouseWorkflowService.php';

if (!class_exists('WarehouseWorkflowFakeModel')) {
    class WarehouseWorkflowFakeModel extends WarehouseModel {
        public array $workflowActiveWarehouses = [];
        public array $createTransferResult = [
            'id' => 15,
            'transfer_number' => 'TRF-15',
            'source_warehouse_name' => 'Main',
            'destination_warehouse_name' => 'Branch',
        ];
        public array $approveResult = [
            'id' => 15,
            'transfer_number' => 'TRF-15',
            'source_warehouse_name' => 'Main',
            'destination_warehouse_name' => 'Branch',
        ];
        public array $rejectResult = [
            'id' => 15,
            'transfer_number' => 'TRF-15',
            'source_warehouse_name' => 'Main',
            'destination_warehouse_name' => 'Branch',
        ];
        public array $createTransferCalls = [];
        public array $rejectCalls = [];
        public function __construct() {}
        public function allActiveOrdered(): array { return $this->workflowActiveWarehouses; }
        public function createTransfer(array $payload, array $items, int $userId): array {
            $this->createTransferCalls[] = ['payload' => $payload, 'items' => $items, 'user_id' => $userId];
            return $this->createTransferResult;
        }
        public function approveTransfer(int $transferId, int $userId): array {
            return $this->approveResult;
        }
        public function rejectTransfer(int $transferId, int $userId, ?string $reason = null): array {
            $this->rejectCalls[] = ['transfer_id' => $transferId, 'user_id' => $userId, 'reason' => $reason];
            return $this->rejectResult;
        }
    }
}

class WarehouseWorkflowServiceTest extends BaseTestCase {
    public function testValidateWarehousePayloadSanitizesFields(): void {
        $service = new WarehouseWorkflowService(new WarehouseWorkflowFakeModel());
        $payload = $service->validateWarehousePayload([
            'name' => ' <b>Main</b> ',
            'code' => ' wh-1 ',
            'location' => '<script>x</script>Floor 1',
            'description' => '  Primary ',
            'is_default' => '1',
        ]);

        $this->assertSame('Main', $payload['name']);
        $this->assertSame('WH-1', $payload['code']);
        $this->assertSame('xFloor 1', $payload['location']);
        $this->assertSame('Primary', $payload['description']);
        $this->assertSame(1, $payload['is_default']);
    }

    public function testCreateTransferRequestBuildsNormalizedTransferPayload(): void {
        $model = new WarehouseWorkflowFakeModel();
        $model->workflowActiveWarehouses = [
            ['id' => 1, 'name' => 'Main'],
            ['id' => 2, 'name' => 'Branch'],
        ];
        $service = new WarehouseWorkflowService($model);

        $result = $service->createTransferRequest([
            'source_warehouse_id' => '1',
            'destination_warehouse_id' => '2',
            'transfer_date' => '2026-04-10',
            'reference_number' => ' REF-1 ',
            'note' => ' Move stock ',
            'product_id' => ['5', '5', '7'],
            'quantity' => ['1', '2', '3'],
        ], 9);

        $this->assertSame('TRF-15', $result['transfer_number']);
        $this->assertCount(1, $model->createTransferCalls);
        $this->assertSame('REF-1', $model->createTransferCalls[0]['payload']['reference_number']);
        $this->assertCount(2, $model->createTransferCalls[0]['items']);
    }

    public function testCreateTransferRequestRejectsInvalidDate(): void {
        $model = new WarehouseWorkflowFakeModel();
        $model->workflowActiveWarehouses = [
            ['id' => 1, 'name' => 'Main'],
            ['id' => 2, 'name' => 'Branch'],
        ];
        $service = new WarehouseWorkflowService($model);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid transfer date. Use YYYY-MM-DD.');

        $service->createTransferRequest([
            'source_warehouse_id' => '1',
            'destination_warehouse_id' => '2',
            'transfer_date' => '2026-02-31',
            'product_id' => ['5'],
            'quantity' => ['1'],
        ], 9);
    }

    public function testApproveTransferDelegatesToWarehouseModel(): void {
        $model = new WarehouseWorkflowFakeModel();
        $service = new WarehouseWorkflowService($model);

        $result = $service->approveTransfer(15, 9);

        $this->assertSame('TRF-15', $result['transfer_number']);
    }

    public function testRejectTransferSanitizesReasonBeforeDelegating(): void {
        $model = new WarehouseWorkflowFakeModel();
        $service = new WarehouseWorkflowService($model);

        $result = $service->rejectTransfer(15, 9, ' <b>Duplicate</b> request ');

        $this->assertSame('TRF-15', $result['transfer_number']);
        $this->assertCount(1, $model->rejectCalls);
        $this->assertSame('Duplicate request', $model->rejectCalls[0]['reason']);
    }
}
