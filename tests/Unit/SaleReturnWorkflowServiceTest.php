<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/services/SaleReturnWorkflowService.php';

class SaleReturnWorkflowServiceTest extends BaseTestCase {
    public function testBuildCreatePayloadNormalizesItemsTotalsAndReason(): void {
        $service = new SaleReturnWorkflowService();
        $payload = $service->buildCreatePayload([
            'product_id' => [11, 12],
            'quantity' => [2, 1],
            'unit_price' => [50, 20],
            'reason' => '<b>Damaged</b>',
            'return_date' => '2026-04-11',
        ], [
            'id' => 9,
            'grand_total' => 200,
            'items' => [
                ['product_id' => 11, 'quantity' => 2, 'unit_price' => 50, 'subtotal' => 100, 'tax_rate' => 18, 'tax_amount' => 18, 'total' => 118],
                ['product_id' => 12, 'quantity' => 1, 'unit_price' => 20, 'subtotal' => 20, 'tax_rate' => 0, 'tax_amount' => 0, 'total' => 20],
            ],
        ], 150.0, 'RET-1001');

        $this->assertSame('RET-1001', $payload['return']['return_number']);
        $this->assertSame(9, $payload['return']['sale_id']);
        $this->assertSame(138.0, $payload['return']['total_amount']);
        $this->assertSame('Damaged', $payload['return']['note']);
        $this->assertCount(2, $payload['items']);
        $this->assertSame(118.0, $payload['items'][0]['total']);
        $this->assertSame(18.0, $payload['items'][0]['tax_amount']);
    }

    public function testBuildCreatePayloadRejectsOverRemainingAmount(): void {
        $service = new SaleReturnWorkflowService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds the remaining returnable amount');

        $service->buildCreatePayload([
            'product_id' => [11],
            'quantity' => [3],
            'unit_price' => [50],
            'return_date' => '2026-04-11',
        ], [
            'id' => 9,
            'grand_total' => 200,
            'items' => [
                ['product_id' => 11, 'quantity' => 3, 'unit_price' => 50, 'subtotal' => 150, 'tax_rate' => 18, 'tax_amount' => 27, 'total' => 177],
            ],
        ], 100.0, 'RET-1002');
    }

    public function testBuildCreatePayloadRejectsNegativeValues(): void {
        $service = new SaleReturnWorkflowService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid quantities or prices provided. Values must be positive.');

        $service->buildCreatePayload([
            'product_id' => [11],
            'quantity' => [-1],
            'unit_price' => [50],
        ], [
            'id' => 9,
            'grand_total' => 200,
            'items' => [
                ['product_id' => 11, 'quantity' => 1, 'unit_price' => 50, 'subtotal' => 50, 'tax_rate' => 18, 'tax_amount' => 9, 'total' => 59],
            ],
        ], 200.0, 'RET-1003');
    }

    public function testEnsureSaleIsReturnableRejectsInvalidSale(): void {
        $service = new SaleReturnWorkflowService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sale.');

        $service->ensureSaleIsReturnable(null, 50.0);
    }
}
