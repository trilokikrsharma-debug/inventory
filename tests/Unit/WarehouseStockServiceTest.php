<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/WarehouseStockService.php';

class WarehouseStockServiceTest extends BaseTestCase {
    public function testNormalizeAllocationsFiltersUnknownAndZeroRows(): void {
        $rows = WarehouseStockService::normalizeAllocations([
            '2' => '5.250',
            '8' => '0',
            '99' => '7',
            '3' => '1.5',
        ], [2, 3, 8]);

        $this->assertSame([
            ['warehouse_id' => 2, 'quantity' => 5.25],
            ['warehouse_id' => 3, 'quantity' => 1.5],
        ], $rows);
    }

    public function testNormalizeAllocationsRejectsNegativeQuantity(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot be negative');

        WarehouseStockService::normalizeAllocations(['2' => '-1'], [2]);
    }

    public function testTotalQuantityReturnsRoundedAggregate(): void {
        $total = WarehouseStockService::totalQuantity([
            ['warehouse_id' => 2, 'quantity' => 1.111],
            ['warehouse_id' => 3, 'quantity' => 2.222],
        ]);

        $this->assertSame(3.333, $total);
    }

    public function testNormalizeTransferItemsCombinesDuplicateProducts(): void {
        $items = WarehouseStockService::normalizeTransferItems(
            ['5', '8', '5', ''],
            ['1.250', '2', '0.750', '']
        );

        $this->assertSame([
            ['product_id' => 5, 'quantity' => 2.0],
            ['product_id' => 8, 'quantity' => 2.0],
        ], $items);
    }

    public function testNormalizeTransferItemsRejectsInvalidQuantity(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('greater than zero');

        WarehouseStockService::normalizeTransferItems(['5'], ['0']);
    }
}
