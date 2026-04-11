<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/models/SupplierModel.php';
require_once dirname(__DIR__, 2) . '/services/LineItemProcessor.php';
require_once dirname(__DIR__, 2) . '/services/PurchaseWorkflowService.php';

if (!class_exists('PurchaseWorkflowFakeSupplierModel')) {
    class PurchaseWorkflowFakeSupplierModel extends SupplierModel {
        public array $suppliersById = [];
        public function __construct() {}
        public function find($id) {
            return $this->suppliersById[(int)$id] ?? null;
        }
    }
}

class PurchaseWorkflowServiceTest extends BaseTestCase {
    public function testBuildCreatePayloadNormalizesTotalsAndStatus(): void {
        $supplierModel = new PurchaseWorkflowFakeSupplierModel();
        $supplierModel->suppliersById[5] = ['id' => 5, 'name' => 'Acme Supplies'];

        $service = new PurchaseWorkflowService($supplierModel, new LineItemProcessor());
        $payload = $service->buildCreatePayload([
            'supplier_id' => 5,
            'purchase_date' => '2026-04-11',
            'reference_number' => ' <b>PO-10</b> ',
            'product_id' => [11],
            'quantity' => [2],
            'unit_price' => [40],
            'item_discount' => [5],
            'item_tax_rate' => [18],
            'discount_amount' => 3,
            'shipping_cost' => 10,
            'paid_amount' => 20,
            'status' => ' received ',
            'note' => '<i>Urgent</i>',
        ], [
            'enable_tax' => 1,
            'enable_gst' => 1,
        ], 'PUR-1001', false);

        $this->assertSame('PUR-1001', $payload['purchase']['invoice_number']);
        $this->assertSame('received', $payload['purchase']['status']);
        $this->assertSame('PO-10', $payload['purchase']['reference_number']);
        $this->assertSame(75.0, $payload['purchase']['subtotal']);
        $this->assertSame(13.5, $payload['purchase']['tax_amount']);
        $this->assertSame(95.5, $payload['purchase']['grand_total']);
        $this->assertSame(75.5, $payload['purchase']['due_amount']);
        $this->assertSame('partial', $payload['purchase']['payment_status']);
        $this->assertSame('Urgent', $payload['purchase']['note']);
        $this->assertSame(5.0, $payload['items'][0]['discount']);
    }

    public function testBuildUpdatePayloadDisablesTaxAndRejectsOverpayment(): void {
        $supplierModel = new PurchaseWorkflowFakeSupplierModel();
        $supplierModel->suppliersById[2] = ['id' => 2, 'name' => 'North Traders'];

        $service = new PurchaseWorkflowService($supplierModel, new LineItemProcessor());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Paid amount cannot exceed grand total.');

        $service->buildUpdatePayload([
            'supplier_id' => 2,
            'purchase_date' => '2026-04-11',
            'product_id' => [9],
            'quantity' => [1],
            'unit_price' => [25],
            'item_discount' => [0],
            'item_tax_rate' => [18],
            'shipping_cost' => 0,
            'paid_amount' => 30,
        ], [
            'enable_tax' => 0,
            'enable_gst' => 1,
        ], false);
    }

    public function testBuildUpdatePayloadRejectsInvalidWarehouseSelection(): void {
        $supplierModel = new PurchaseWorkflowFakeSupplierModel();
        $supplierModel->suppliersById[4] = ['id' => 4, 'name' => 'West Supply'];

        $service = new PurchaseWorkflowService($supplierModel, new LineItemProcessor());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please select a valid warehouse.');

        $service->buildUpdatePayload([
            'supplier_id' => 4,
            'warehouse_id' => 99,
            'purchase_date' => '2026-04-11',
            'product_id' => [1],
            'quantity' => [1],
            'unit_price' => [10],
            'item_discount' => [0],
            'item_tax_rate' => [0],
        ], [
            'enable_tax' => 1,
            'enable_gst' => 1,
        ], true, [['id' => 3]]);
    }

    public function testBuildPaymentPayloadNormalizesLegacyUpiMethod(): void {
        $service = new PurchaseWorkflowService(new PurchaseWorkflowFakeSupplierModel(), new LineItemProcessor());

        $payload = $service->buildPaymentPayload([
            'payment_method' => 'upi',
            'purchase_date' => '2026-04-11',
        ], [
            'supplier_id' => 7,
            'paid_amount' => 35.5,
            'invoice_number' => 'PUR-1001',
        ], 'PAY-1001', 44, 12);

        $this->assertSame('PAY-1001', $payload['payment_number']);
        $this->assertSame('online', $payload['payment_method']);
        $this->assertSame('Payment for PUR-1001', $payload['note']);
        $this->assertSame(44, $payload['purchase_id']);
        $this->assertSame(12, $payload['created_by']);
    }
}
