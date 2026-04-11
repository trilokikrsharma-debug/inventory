<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/SaleReturnLifecycleService.php';

if (!class_exists('SaleReturnLifecycleFakeDb')) {
    class SaleReturnLifecycleFakeDb {
        public int $beginCount = 0;
        public int $commitCount = 0;
        public int $rollbackCount = 0;
        public function beginTransaction(): void { $this->beginCount++; }
        public function commit(): void { $this->commitCount++; }
        public function rollback(): void { $this->rollbackCount++; }
        public function query($sql, $params = []) { return null; }
    }
}

if (!class_exists('SaleReturnLifecycleFakeModel')) {
    class SaleReturnLifecycleFakeModel {
        public array $records = [];
        public array $markCancelledCalls = [];
        public function getWithDetails($id) { return $this->records[$id] ?? null; }
        public function markCancelled(int $id, string $reason, int $userId): void {
            $this->markCancelledCalls[] = ['id' => $id, 'reason' => $reason, 'user_id' => $userId];
            if (isset($this->records[$id])) {
                $this->records[$id]['status'] = 'cancelled';
                $this->records[$id]['cancel_reason'] = $reason;
            }
        }
    }
}

if (!class_exists('SaleReturnLifecycleFakeProductModel')) {
    class SaleReturnLifecycleFakeProductModel {
        public array $stockCalls = [];
        public function updateStock($productId, $quantity, $type, $referenceId = null, $userId = null, $note = '', ?int $warehouseId = null) {
            $this->stockCalls[] = compact('productId', 'quantity', 'type', 'referenceId', 'userId', 'note');
            return true;
        }
    }
}

if (!class_exists('SaleReturnLifecycleFakePaymentModel')) {
    class SaleReturnLifecycleFakePaymentModel {
        public array $calls = [];
        public function recalculateCustomerSalesPublic($customerId) { $this->calls[] = $customerId; }
    }
}

if (!class_exists('SaleReturnLifecycleFakeCustomerModel')) {
    class SaleReturnLifecycleFakeCustomerModel {
        public array $calls = [];
        public function recalculateBalance($customerId) { $this->calls[] = $customerId; }
    }
}

class SaleReturnLifecycleServiceTest extends BaseTestCase {
    public function testNormalizeCancelReasonRequiresMeaningfulInput(): void {
        $service = new SaleReturnLifecycleService(
            new SaleReturnLifecycleFakeDb(),
            new SaleReturnLifecycleFakeModel(),
            new SaleReturnLifecycleFakeProductModel(),
            new SaleReturnLifecycleFakePaymentModel(),
            new SaleReturnLifecycleFakeCustomerModel()
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cancel reason is required.');
        $service->normalizeCancelReason('   ');
    }

    public function testCancelReturnMarksRecordReversesStockAndRecalculatesCustomer(): void {
        $db = new SaleReturnLifecycleFakeDb();
        $returnModel = new SaleReturnLifecycleFakeModel();
        $returnModel->records[7] = [
            'id' => 7,
            'return_number' => 'RET-0007',
            'status' => 'posted',
            'customer_id' => 5,
            'items' => [
                ['product_id' => 11, 'quantity' => 2],
                ['product_id' => 12, 'quantity' => 1.5],
            ],
        ];
        $productModel = new SaleReturnLifecycleFakeProductModel();
        $paymentModel = new SaleReturnLifecycleFakePaymentModel();
        $customerModel = new SaleReturnLifecycleFakeCustomerModel();
        $service = new SaleReturnLifecycleService($db, $returnModel, $productModel, $paymentModel, $customerModel);

        $result = $service->cancelReturn(7, 'Pricing corrected', 99);

        $this->assertSame('cancelled', $result['status']);
        $this->assertSame(1, $db->beginCount);
        $this->assertSame(1, $db->commitCount);
        $this->assertSame(0, $db->rollbackCount);
        $this->assertCount(1, $returnModel->markCancelledCalls);
        $this->assertCount(2, $productModel->stockCalls);
        $this->assertSame(-2.0, $productModel->stockCalls[0]['quantity']);
        $this->assertSame([5], $paymentModel->calls);
        $this->assertSame([5], $customerModel->calls);
    }
}
