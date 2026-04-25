<?php

require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/services/ProductImportService.php';

class ProductImportServiceTest extends BaseTestCase {
    private ProductImportService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = new ProductImportService();
        Tenant::reset();
    }

    protected function tearDown(): void {
        Tenant::reset();
        $this->setDatabaseInstance(null);
        parent::tearDown();
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

    public function testBuildContextScopesLookupsToCurrentTenant(): void {
        Tenant::set(44, ['id' => 44]);
        $db = new RecordingProductImportDatabase([
            [
                ['id' => 7, 'name' => 'General'],
            ],
            [
                ['id' => 3, 'name' => 'Acme'],
            ],
            [
                ['id' => 2, 'name' => 'Pieces', 'short_name' => 'pcs'],
            ],
            [
                ['sku' => 'SKU-44'],
            ],
        ]);
        $this->setDatabaseInstance($db);

        $context = $this->service->buildContext();

        $this->assertSame(
            'SELECT id, name FROM categories WHERE deleted_at IS NULL AND company_id = ? ORDER BY name ASC',
            $db->queries[0]['sql'] ?? ''
        );
        $this->assertSame([44], $db->queries[0]['params'] ?? []);
        $this->assertSame(
            'SELECT sku FROM products WHERE deleted_at IS NULL AND sku IS NOT NULL AND sku <> \'\' AND company_id = ?',
            $db->queries[3]['sql'] ?? ''
        );
        $this->assertSame([44], $db->queries[3]['params'] ?? []);
        $this->assertSame(7, $context['categories_by_key']['general'] ?? null);
        $this->assertTrue($context['existing_skus']['sku-44'] ?? false);
    }

    private function setDatabaseInstance($instance): void {
        $ref = new ReflectionProperty(Database::class, 'instance');
        $ref->setAccessible(true);
        $ref->setValue(null, $instance);
    }
}

class RecordingProductImportDatabase {
    public array $queries = [];
    private array $resultSets;
    private int $index = 0;

    public function __construct(array $resultSets) {
        $this->resultSets = $resultSets;
    }

    public function query($sql, $params = []) {
        $this->queries[] = ['sql' => $sql, 'params' => $params];
        $rows = $this->resultSets[$this->index] ?? [];
        $this->index++;
        return new RecordingProductImportResult($rows);
    }
}

class RecordingProductImportResult {
    private array $rows;

    public function __construct(array $rows) {
        $this->rows = $rows;
    }

    public function fetchAll() {
        return $this->rows;
    }
}
