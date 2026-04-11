<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/models/CustomerModel.php';
require_once dirname(__DIR__, 2) . '/services/LineItemProcessor.php';
require_once dirname(__DIR__, 2) . '/services/QuotationWorkflowService.php';

if (!class_exists('QuotationWorkflowFakeCustomerModel')) {
    class QuotationWorkflowFakeCustomerModel extends CustomerModel {
        public array $customersById = [];
        public function __construct() {}
        public function find($id) {
            return $this->customersById[(int)$id] ?? null;
        }
    }
}

class QuotationWorkflowServiceTest extends BaseTestCase {
    public function testBuildCreatePayloadNormalizesTotalsAndDates(): void {
        $customerModel = new QuotationWorkflowFakeCustomerModel();
        $customerModel->customersById[7] = ['id' => 7, 'name' => 'Retail Customer'];

        $service = new QuotationWorkflowService($customerModel, new LineItemProcessor());
        $payload = $service->buildCreatePayload([
            'customer_id' => 7,
            'quotation_date' => '2026-04-11',
            'valid_until' => '2026-04-20',
            'product_id' => [11],
            'quantity' => [2],
            'unit_price' => [50],
            'discount' => [10],
            'tax_rate' => [18],
            'discount_amount' => 5,
            'shipping_cost' => 20,
            'note' => '<b>Handle with care</b>',
            'terms' => '<i>Net 15</i>',
        ], [
            'enable_tax' => 1,
            'enable_gst' => 1,
        ], 'QUO-1001');

        $this->assertSame('QUO-1001', $payload['quotation']['quotation_number']);
        $this->assertSame(90.0, $payload['quotation']['subtotal']);
        $this->assertSame(16.2, $payload['quotation']['tax_amount']);
        $this->assertSame(121.2, $payload['quotation']['grand_total']);
        $this->assertSame('2026-04-20', $payload['quotation']['valid_until']);
        $this->assertSame('Handle with care', $payload['quotation']['note']);
        $this->assertSame('Net 15', $payload['quotation']['terms']);
        $this->assertSame(10.0, $payload['items'][0]['discount']);
    }

    public function testBuildCreatePayloadDisablesTaxAndRejectsInvalidCustomer(): void {
        $service = new QuotationWorkflowService(new QuotationWorkflowFakeCustomerModel(), new LineItemProcessor());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please select a valid customer.');

        $service->buildCreatePayload([
            'customer_id' => 2,
            'quotation_date' => '2026-04-11',
            'product_id' => [9],
            'quantity' => [1],
            'unit_price' => [25],
            'discount' => [0],
            'tax_rate' => [18],
        ], [
            'enable_tax' => 0,
            'enable_gst' => 1,
        ], 'QUO-1002');
    }

    public function testBuildSaleConversionPayloadMapsQuotationIntoSaleShape(): void {
        $service = new QuotationWorkflowService(new QuotationWorkflowFakeCustomerModel(), new LineItemProcessor());
        $payload = $service->buildSaleConversionPayload([
            'id' => 14,
            'customer_id' => 7,
            'subtotal' => 90.0,
            'tax_amount' => 16.2,
            'discount_amount' => 5.0,
            'shipping_cost' => 20.0,
            'grand_total' => 121.2,
            'note' => 'Converted note',
            'items' => [[
                'product_id' => 11,
                'quantity' => 2,
                'unit_price' => 50,
                'discount' => 10,
                'tax_rate' => 18,
                'tax_amount' => 16.2,
                'subtotal' => 90,
                'total' => 106.2,
            ]],
        ], 'INV-1001', '2026-04-11');

        $this->assertSame('INV-1001', $payload['sale']['invoice_number']);
        $this->assertSame(7, $payload['sale']['customer_id']);
        $this->assertSame(121.2, $payload['sale']['due_amount']);
        $this->assertSame('unpaid', $payload['sale']['payment_status']);
        $this->assertSame(14, $payload['sale']['quotation_id']);
        $this->assertSame(11, $payload['items'][0]['product_id']);
    }

    public function testBuildSaleConversionPayloadRejectsMissingItems(): void {
        $service = new QuotationWorkflowService(new QuotationWorkflowFakeCustomerModel(), new LineItemProcessor());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quotation has no items to convert.');

        $service->buildSaleConversionPayload([
            'id' => 14,
            'customer_id' => 7,
            'items' => [],
        ], 'INV-1001', '2026-04-11');
    }
}
