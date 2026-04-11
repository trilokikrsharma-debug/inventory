<?php
class DashboardWorkflowService {
    private SalesModel $salesModel;
    private PurchaseModel $purchaseModel;
    private ProductModel $productModel;
    private CustomerModel $customerModel;
    private SupplierModel $supplierModel;

    public function __construct(
        ?SalesModel $salesModel = null,
        ?PurchaseModel $purchaseModel = null,
        ?ProductModel $productModel = null,
        ?CustomerModel $customerModel = null,
        ?SupplierModel $supplierModel = null
    ) {
        $this->salesModel = $salesModel ?: new SalesModel();
        $this->purchaseModel = $purchaseModel ?: new PurchaseModel();
        $this->productModel = $productModel ?: new ProductModel();
        $this->customerModel = $customerModel ?: new CustomerModel();
        $this->supplierModel = $supplierModel ?: new SupplierModel();
    }

    public function buildViewData(): array {
        $snapshot = $this->loadSnapshot();

        $salesTotals = $snapshot['sales_totals'] ?? ['today_amount' => 0, 'month_amount' => 0, 'all_amount' => 0];
        $purchaseTotals = $snapshot['purchase_totals'] ?? ['today_amount' => 0, 'month_amount' => 0, 'all_amount' => 0];
        $stockValue = $snapshot['stock_value'] ?? ['total_value' => 0, 'selling_value' => 0, 'total_products' => 0];
        $lowStockProducts = $snapshot['low_stock'] ?? [];
        $customerDues = $snapshot['customer_dues'] ?? 0;
        $supplierDues = $snapshot['supplier_dues'] ?? 0;
        $monthlySales = $snapshot['monthly_sales'] ?? [];
        $monthlyPurchase = $snapshot['monthly_purchase'] ?? [];
        $recentSales = $snapshot['recent_sales'] ?? ['data' => []];
        $topProducts = $snapshot['top_products'] ?? [];

        $salesChartData = $this->buildMonthlyChartData($monthlySales);
        $purchaseChartData = $this->buildMonthlyChartData($monthlyPurchase);

        return [
            'pageTitle' => 'Dashboard',
            'salesToday' => ['total_amount' => $salesTotals['today_amount']],
            'salesMonth' => ['total_amount' => $salesTotals['month_amount']],
            'salesAll' => ['total_amount' => $salesTotals['all_amount']],
            'purchaseAll' => ['total_amount' => $purchaseTotals['all_amount']],
            'purchaseMonth' => ['total_amount' => $purchaseTotals['month_amount']],
            'stockValue' => $stockValue,
            'lowStockProducts' => $lowStockProducts,
            'customerDues' => $customerDues,
            'supplierDues' => $supplierDues,
            'salesChartData' => json_encode(array_values($salesChartData)),
            'purchaseChartData' => json_encode(array_values($purchaseChartData)),
            'recentSales' => $recentSales['data'] ?? [],
            'topProducts' => $topProducts,
        ];
    }

    private function loadSnapshot(): array {
        $prefix = 'c' . (Tenant::id() ?? 0) . '_dash_';
        $ttl = defined('CACHE_TTL_DASHBOARD') ? CACHE_TTL_DASHBOARD : 300;
        $year = date('Y');

        return Cache::remember($prefix . 'snapshot_v2_' . $year, $ttl, function () use ($year) {
            return [
                'sales_totals' => $this->salesModel->getDashboardTotals(),
                'purchase_totals' => $this->purchaseModel->getDashboardTotals(),
                'stock_value' => $this->productModel->getTotalStockValue(),
                'low_stock' => $this->productModel->getLowStock(10),
                'customer_dues' => $this->customerModel->getTotalDues(),
                'supplier_dues' => $this->supplierModel->getTotalDues(),
                'monthly_sales' => $this->salesModel->getMonthlyData($year),
                'monthly_purchase' => $this->purchaseModel->getMonthlyData($year),
                'recent_sales' => $this->salesModel->getAllWithCustomer('', '', '', '', '', 1, 5),
                'top_products' => $this->salesModel->getTopProducts(5),
            ];
        });
    }

    private function buildMonthlyChartData(array $rows): array {
        $chart = array_fill(1, 12, 0);
        foreach ($rows as $row) {
            $month = (int)($row['month'] ?? 0);
            if ($month >= 1 && $month <= 12) {
                $chart[$month] = (float)($row['total'] ?? 0);
            }
        }

        return $chart;
    }
}
