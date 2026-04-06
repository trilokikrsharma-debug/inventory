<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/services/ProductImportService.php';

class ProductImportServiceTest extends BaseTestCase {
    private ProductImportService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = new ProductImportService();
    }

    public function testTemplateCsvIncludesExpectedHeaders(): void {
        $csv = $this->service->templateCsv();

        $this->assertStringContainsString('name,sku,barcode,hsn_code,category,brand,unit,purchase_price,selling_price,mrp,tax_rate,opening_stock,low_stock_alert,description,is_active', $csv);
        $this->assertStringContainsString('Sample Product', $csv);
    }

    public function testAnalyzeCsvStringFlagsMissingLookupAndDuplicateSku(): void {
        $analysis = $this->service->analyzeCsvString(
            "name,sku,purchase_price,selling_price,category\n" .
            "Alpha,SKU-1,10,12,Missing Category\n" .
            "Beta,SKU-1,11,13,Missing Category\n",
            [
                'categories_by_key' => [],
                'brands_by_key' => [],
                'units_by_key' => [],
                'existing_skus' => [],
            ]
        );

        $this->assertSame(2, $analysis['summary']['total_rows']);
        $this->assertSame(0, $analysis['summary']['valid_rows']);
        $this->assertSame(2, $analysis['summary']['invalid_rows']);
        $this->assertStringContainsString('Category "Missing Category" was not found.', implode(' ', $analysis['rows'][0]['errors']));
        $this->assertStringContainsString('Duplicate SKU in file.', implode(' ', $analysis['rows'][1]['errors']));
    }

    public function testAnalyzeCsvStringNormalizesValidRows(): void {
        $analysis = $this->service->analyzeCsvString(
            "name,sku,purchase_price,selling_price,category,brand,unit,opening_stock,is_active,tax_rate\n" .
            "Alpha,SKU-1,10,12,General,Acme,pcs,5,yes,18\n",
            [
                'categories_by_key' => ['general' => 7],
                'brands_by_key' => ['acme' => 3],
                'units_by_key' => ['pcs' => 2],
                'existing_skus' => [],
            ]
        );

        $this->assertSame(1, $analysis['summary']['valid_rows']);
        $this->assertSame(0, $analysis['summary']['invalid_rows']);
        $row = $analysis['rows'][0]['normalized'];
        $this->assertSame('Alpha', $row['name']);
        $this->assertSame('SKU-1', $row['sku']);
        $this->assertSame(7, $row['category_id']);
        $this->assertSame(3, $row['brand_id']);
        $this->assertSame(2, $row['unit_id']);
        $this->assertSame(5.0, $row['opening_stock']);
        $this->assertSame(5.0, $row['current_stock']);
        $this->assertSame(1, $row['is_active']);
        $this->assertSame(18.0, $row['tax_rate']);
    }
}
