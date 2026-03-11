<?php
/**
 * Unit Tests — LineItemProcessor
 */

require_once __DIR__ . '/BaseTestCase.php';

class LineItemProcessorTest extends BaseTestCase {
    private LineItemProcessor $processor;

    protected function setUp(): void {
        parent::setUp();
        $this->processor = new LineItemProcessor();
    }

    // ── parseFromPost Tests ──────────────────────────────

    public function testParseValidItems(): void {
        $post = [
            'product_id' => [1, 2, 3],
            'quantity' => [10, 5, 2],
            'unit_price' => [100.00, 200.00, 50.00],
            'item_discount' => [0, 10.00, 0],
            'item_tax_rate' => [18, 18, 0],
        ];

        $items = $this->processor->parseFromPost($post);

        $this->assertCount(3, $items);
        $this->assertEquals(1, $items[0]['product_id']);
        $this->assertEquals(10, $items[0]['quantity']);
        $this->assertEquals(100.00, $items[0]['unit_price']);
        $this->assertEquals(180.00, $items[0]['tax_amount']); // (10*100)*18% = 180
        $this->assertEquals(1000.00, $items[0]['subtotal']);
        $this->assertEquals(1180.00, $items[0]['total']);
    }

    public function testParseSkipsEmptyProductIds(): void {
        $post = [
            'product_id' => [1, '', 3],
            'quantity' => [10, 5, 2],
            'unit_price' => [100.00, 200.00, 50.00],
            'item_discount' => [0, 0, 0],
            'item_tax_rate' => [0, 0, 0],
        ];

        $items = $this->processor->parseFromPost($post);
        $this->assertCount(2, $items);
        $this->assertEquals(1, $items[0]['product_id']);
        $this->assertEquals(3, $items[1]['product_id']);
    }

    public function testParseThrowsOnEmptyItems(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one valid line item');

        $this->processor->parseFromPost(['product_id' => ['', '']]);
    }

    public function testParseThrowsOnNegativeQuantity(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('quantity must be greater than zero');

        $post = [
            'product_id' => [1],
            'quantity' => [-5],
            'unit_price' => [100.00],
            'item_discount' => [0],
            'item_tax_rate' => [0],
        ];
        $this->processor->parseFromPost($post);
    }

    public function testParseThrowsOnDiscountExceedingSubtotal(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('discount cannot exceed subtotal');

        $post = [
            'product_id' => [1],
            'quantity' => [1],
            'unit_price' => [100.00],
            'item_discount' => [200.00],
            'item_tax_rate' => [0],
        ];
        $this->processor->parseFromPost($post);
    }

    public function testParseHandlesDiscountCorrectly(): void {
        $post = [
            'product_id' => [1],
            'quantity' => [2],
            'unit_price' => [100.00],
            'item_discount' => [50.00],
            'item_tax_rate' => [10],
        ];

        $items = $this->processor->parseFromPost($post);
        $this->assertEquals(50.00, $items[0]['discount_amount']);
        $this->assertEquals(150.00, $items[0]['subtotal']); // (2*100)-50 = 150
        $this->assertEquals(15.00, $items[0]['tax_amount']); // 150*10% = 15
        $this->assertEquals(165.00, $items[0]['total']);
    }

    // ── calculateTotals Tests ────────────────────────────

    public function testCalculateTotals(): void {
        $items = [
            ['subtotal' => 1000.00, 'tax_amount' => 180.00],
            ['subtotal' => 500.00, 'tax_amount' => 90.00],
            ['subtotal' => 200.00, 'tax_amount' => 0.00],
        ];

        $totals = $this->processor->calculateTotals($items);

        $this->assertEquals(1700.00, $totals['subtotal']);
        $this->assertEquals(270.00, $totals['total_tax']);
        $this->assertEquals(1970.00, $totals['grand_total']);
        $this->assertEquals(3, $totals['item_count']);
    }

    public function testCalculateTotalsEmpty(): void {
        $totals = $this->processor->calculateTotals([]);
        $this->assertEquals(0.00, $totals['subtotal']);
        $this->assertEquals(0.00, $totals['grand_total']);
        $this->assertEquals(0, $totals['item_count']);
    }

    // ── reconcile Tests ──────────────────────────────────

    public function testReconcileIdentifiesAddUpdateRemove(): void {
        $existing = [
            ['id' => 1, 'product_id' => 10, 'quantity' => 5],
            ['id' => 2, 'product_id' => 20, 'quantity' => 3],
            ['id' => 3, 'product_id' => 30, 'quantity' => 1],
        ];

        $new = [
            ['id' => 1, 'product_id' => 10, 'quantity' => 8],  // update
            ['id' => 2, 'product_id' => 20, 'quantity' => 3],  // update (no change)
            ['product_id' => 40, 'quantity' => 2],              // add
        ];

        $result = $this->processor->reconcile($new, $existing);

        $this->assertCount(1, $result['add']);
        $this->assertCount(2, $result['update']);
        $this->assertCount(1, $result['remove']);
        $this->assertEquals(3, $result['remove'][0]['id']); // id=3 removed
    }
}
