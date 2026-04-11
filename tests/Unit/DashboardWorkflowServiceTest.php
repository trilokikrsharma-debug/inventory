<?php
require_once __DIR__ . '/../BaseTestCase.php';
require_once dirname(__DIR__, 2) . '/core/Tenant.php';
require_once dirname(__DIR__, 2) . '/core/Cache.php';
require_once dirname(__DIR__, 2) . '/models/SalesModel.php';
require_once dirname(__DIR__, 2) . '/models/PurchaseModel.php';
require_once dirname(__DIR__, 2) . '/models/ProductModel.php';
require_once dirname(__DIR__, 2) . '/models/CustomerModel.php';
require_once dirname(__DIR__, 2) . '/models/SupplierModel.php';
require_once dirname(__DIR__, 2) . '/services/DashboardWorkflowService.php';

if (!class_exists('DashboardWorkflowFakeSalesModel')) {
    class DashboardWorkflowFakeSalesModel extends SalesModel {
        public function __construct() {}
        public function getDashboardTotals() { return ['today_amount' => 10, 'month_amount' => 20, 'all_amount' => 30]; }
        public function getMonthlyData($year = null) { return [['month' => 1, 'total' => 5.5], ['month' => 3, 'total' => 8]]; }
        public function getAllWithCustomer($search = '', $fromDate = '', $toDate = '', $customerId = '', $status = '', $page = 1, $perPage = RECORDS_PER_PAGE, $warehouseId = '') { return ['data' => [['id' => 7]]]; }
        public function getTopProducts($limit = 10) { return [['name' => 'Widget']]; }
    }
}

if (!class_exists('DashboardWorkflowFakePurchaseModel')) {
    class DashboardWorkflowFakePurchaseModel extends PurchaseModel {
        public function __construct() {}
        public function getDashboardTotals() { return ['today_amount' => 11, 'month_amount' => 21, 'all_amount' => 31]; }
        public function getMonthlyData($year = null) { return [['month' => 2, 'total' => 6.25]]; }
    }
}

if (!class_exists('DashboardWorkflowFakeProductModel')) {
    class DashboardWorkflowFakeProductModel extends ProductModel {
        public function __construct() {}
        public function getTotalStockValue() { return ['total_value' => 100, 'selling_value' => 120, 'total_products' => 4]; }
        public function getLowStock($limit = 10, $threshold = null) { return [['id' => 3, 'name' => 'Low Stock']]; }
    }
}

if (!class_exists('DashboardWorkflowFakeCustomerModel')) {
    class DashboardWorkflowFakeCustomerModel extends CustomerModel {
        public function __construct() {}
        public function getTotalDues() { return 42.5; }
    }
}

if (!class_exists('DashboardWorkflowFakeSupplierModel')) {
    class DashboardWorkflowFakeSupplierModel extends SupplierModel {
        public function __construct() {}
        public function getTotalDues() { return 17.75; }
    }
}

class DashboardWorkflowServiceTest extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        Cache::flush();
        Tenant::set(77, ['id' => 77]);
    }

    protected function tearDown(): void {
        Cache::flush();
        Tenant::reset();
        parent::tearDown();
    }

    public function testBuildViewDataShapesSnapshotIntoDashboardPayload(): void {
        $service = new DashboardWorkflowService(
            new DashboardWorkflowFakeSalesModel(),
            new DashboardWorkflowFakePurchaseModel(),
            new DashboardWorkflowFakeProductModel(),
            new DashboardWorkflowFakeCustomerModel(),
            new DashboardWorkflowFakeSupplierModel()
        );

        $payload = $service->buildViewData();

        $this->assertSame('Dashboard', $payload['pageTitle']);
        $this->assertSame(10, $payload['salesToday']['total_amount']);
        $this->assertSame(31, $payload['purchaseAll']['total_amount']);
        $this->assertSame(100, $payload['stockValue']['total_value']);
        $this->assertSame(42.5, $payload['customerDues']);
        $this->assertSame([['id' => 7]], $payload['recentSales']);
        $this->assertSame([['name' => 'Widget']], $payload['topProducts']);
        $this->assertSame('[5.5,0,8,0,0,0,0,0,0,0,0,0]', $payload['salesChartData']);
        $this->assertSame('[0,6.25,0,0,0,0,0,0,0,0,0,0]', $payload['purchaseChartData']);
    }
}
