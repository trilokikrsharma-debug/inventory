<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Helper.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/models/ProductModel.php';
require_once dirname(__DIR__, 2) . '/services/ProductWorkflowService.php';

if (!class_exists('ProductWorkflowFakeDb')) {
    class ProductWorkflowFakeDb {
        public array $queries = [];
        public int $beginCount = 0;
        public int $commitCount = 0;
        public int $rollbackCount = 0;
        public function beginTransaction(): void { $this->beginCount++; }
        public function commit(): void { $this->commitCount++; }
        public function rollback(): void { $this->rollbackCount++; }
        public function query($sql, $params = []) {
            $this->queries[] = ['sql' => $sql, 'params' => $params];
            return new class {
                public function fetch() { return null; }
            };
        }
    }
}

if (!class_exists('ProductWorkflowFakeProductModel')) {
    class ProductWorkflowFakeProductModel extends ProductModel {
        public array $createPayloads = [];
        public array $allocateCalls = [];
        public int $nextId = 10;
        public function __construct() {}
        public function create($data) {
            $this->createPayloads[] = $data;
            return $this->nextId++;
        }
        public function allocateOpeningStock(int $productId, ?int $warehouseId, float $quantity): void {
            $this->allocateCalls[] = ['product_id' => $productId, 'warehouse_id' => $warehouseId, 'quantity' => $quantity];
        }
    }
}

class ProductWorkflowServiceTest extends BaseTestCase {
    protected function tearDown(): void {
        Tenant::reset();
        parent::tearDown();
    }

    public function testBuildPayloadNormalizesSanitizedFields(): void {
        $service = new ProductWorkflowService(new ProductWorkflowFakeProductModel(), new ProductWorkflowFakeDb());
        $payload = $service->buildPayload([
            'name' => ' <b>Widget</b> ',
            'sku' => ' SKU-1 ',
            'barcode' => ' 12345 ',
            'purchase_price' => '10.5',
            'selling_price' => '20.75',
            'opening_stock' => '5',
            'hsn_code' => ' ab-12 ',
            'description' => '<script>x</script>Useful',
            'is_active' => '1',
        ]);
        $this->assertSame('Widget', $payload['name']);
        $this->assertSame('SKU-1', $payload['sku']);
        $this->assertSame('AB-12', $payload['hsn_code']);
        $this->assertSame('xUseful', $payload['description']);
        $this->assertSame(5.0, $payload['current_stock']);
    }

    public function testPersistImportedProductsCreatesStockHistoryAndWarehouseAllocations(): void {
        Tenant::set(44, ['id' => 44]);
        $productModel = new ProductWorkflowFakeProductModel();
        $db = new ProductWorkflowFakeDb();
        $service = new ProductWorkflowService($productModel, $db);
        $count = $service->persistImportedProducts([[
            'normalized' => [
                'name' => 'Imported Widget',
                'sku' => 'IW-1',
                'purchase_price' => 15,
                'selling_price' => 25,
                'opening_stock' => 7,
                'current_stock' => 7,
                'is_active' => 1,
            ],
        ]], 9, true);
        $this->assertSame(1, $count);
        $this->assertCount(1, $productModel->createPayloads);
        $this->assertCount(1, $db->queries);
        $this->assertCount(1, $productModel->allocateCalls);
        $this->assertSame(1, $db->beginCount);
        $this->assertSame(1, $db->commitCount);
    }
}
