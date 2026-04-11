<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/models/CustomerModel.php';
require_once dirname(__DIR__, 2) . '/services/LineItemProcessor.php';
require_once dirname(__DIR__, 2) . '/services/SalesWorkflowService.php';

if (!class_exists('SalesWorkflowFakeCustomerModel')) {
    class SalesWorkflowFakeCustomerModel extends CustomerModel {
        public array $customersById = [];
        public function __construct() {}
        public function find($id) {
            return $this->customersById[(int)$id] ?? null;
        }
    }
}

class SalesWorkflowServiceTest extends BaseTestCase {
    public function testBuildCreatePayloadNormalizesTotalsGstAndStatus(): void {
        $customerModel = new SalesWorkflowFakeCustomerModel();
        $customerModel->customersById[7] = ['id' => 7, 'state' => 'Delhi'];

        $service = new SalesWorkflowService($customerModel, new LineItemProcessor());
        $payload = $service->buildCreatePayload([
            'customer_id' => 7,
            'sale_date' => '2026-04-10',
            'reference_number' => ' <b>SO-1</b> ',
            'product_id' => [11],
            'quantity' => [2],
            'unit_price' => [50],
            'item_discount' => [10],
            'item_tax_rate' => [18],
            'discount_amount' => 5,
            'freight_charge' => 20,
            'loading_charge' => 5,
            'paid_amount' => 40,
            'gst_type' => 'auto',
            'status' => ' completed ',
            'note' => '<i>Fragile</i>',
        ], [
            'enable_tax' => 1,
            'enable_gst' => 1,
            'company_state' => 'Maharashtra',
        ], 'INV-1001', false);

        $this->assertSame('INV-1001', $payload['sale']['invoice_number']);
        $this->assertSame('completed', $payload['sale']['status']);
        $this->assertSame('SO-1', $payload['sale']['reference_number']);
        $this->assertSame(90.0, $payload['sale']['subtotal']);
        $this->assertSame(16.2, $payload['sale']['tax_amount']);
        $this->assertSame(25.0, $payload['sale']['shipping_cost']);
        $this->assertSame(126.2, $payload['sale']['grand_total']);
        $this->assertSame(86.2, $payload['sale']['due_amount']);
        $this->assertSame('partial', $payload['sale']['payment_status']);
        $this->assertSame('igst', $payload['sale']['gst_type']);
        $this->assertSame('Fragile', $payload['sale']['note']);
        $this->assertSame(10.0, $payload['items'][0]['discount']);
    }

    public function testBuildUpdatePayloadDisablesTaxAndFallsBackShippingField(): void {
        $customerModel = new SalesWorkflowFakeCustomerModel();
        $customerModel->customersById[9] = ['id' => 9, 'state' => 'Maharashtra'];

        $service = new SalesWorkflowService($customerModel, new LineItemProcessor());
        $payload = $service->buildUpdatePayload([
            'customer_id' => 9,
            'sale_date' => '2026-04-10',
            'product_id' => [15],
            'quantity' => [1],
            'unit_price' => [99.99],
            'item_discount' => [0],
            'item_tax_rate' => [18],
            'shipping_cost' => 12,
            'paid_amount' => 20,
        ], [
            'enable_tax' => 0,
            'enable_gst' => 1,
            'company_state' => 'Maharashtra',
        ], false);

        $this->assertSame(0.0, $payload['sale']['tax_amount']);
        $this->assertSame(12.0, $payload['sale']['shipping_cost']);
        $this->assertSame(12.0, $payload['sale']['freight_charge']);
        $this->assertEquals(0.0, $payload['sale']['loading_charge']);
        $this->assertSame('none', $payload['sale']['gst_type']);
        $this->assertSame('partial', $payload['sale']['payment_status']);
        $this->assertEquals(0.0, $payload['items'][0]['tax_rate']);
    }

    public function testBuildUpdatePayloadRejectsInvalidWarehouseSelection(): void {
        $customerModel = new SalesWorkflowFakeCustomerModel();
        $customerModel->customersById[4] = ['id' => 4, 'state' => 'Maharashtra'];

        $service = new SalesWorkflowService($customerModel, new LineItemProcessor());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Please select a valid warehouse.');

        $service->buildUpdatePayload([
            'customer_id' => 4,
            'warehouse_id' => 99,
            'sale_date' => '2026-04-10',
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

    public function testBuildReceiptPayloadNormalizesLegacyUpiMethod(): void {
        $customerModel = new SalesWorkflowFakeCustomerModel();
        $service = new SalesWorkflowService($customerModel, new LineItemProcessor());

        $payload = $service->buildReceiptPayload([
            'payment_method' => 'upi',
            'sale_date' => '2026-04-10',
        ], [
            'customer_id' => 7,
            'paid_amount' => 35.5,
            'invoice_number' => 'INV-1001',
        ], 'RCPT-1001', 44, 12);

        $this->assertSame('RCPT-1001', $payload['payment_number']);
        $this->assertSame('online', $payload['payment_method']);
        $this->assertSame('Payment for INV-1001', $payload['note']);
        $this->assertSame(44, $payload['sale_id']);
        $this->assertSame(12, $payload['created_by']);
    }
}
