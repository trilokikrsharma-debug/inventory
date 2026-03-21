<?php
/**
 * Dashboard Controller
 * 
 * Displays dashboard summary with stats, charts, and alerts.
 */
class DashboardController extends Controller {

    protected $allowedActions = ['index'];

    public function index() {
        $this->requireAuth();

        // SECURITY: Platform super-admins have no tenant context.
        // Redirect them to the platform dashboard where they belong.
        if (Session::isSuperAdmin()) {
            $this->redirect('platform/dashboard');
            return;
        }

        $salesModel = new SalesModel();
        $purchaseModel = new PurchaseModel();
        $productModel = new ProductModel();
        $customerModel = new CustomerModel();
        $supplierModel = new SupplierModel();

        // Tenant-scoped cache key prefix
        $p = 'c' . (Tenant::id() ?? 0) . '_dash_';
        $ttl = defined('CACHE_TTL_DASHBOARD') ? CACHE_TTL_DASHBOARD : 300;

        // Single snapshot cache drastically reduces repeated cache file reads
        // on every dashboard request while still keeping a short TTL.
        $snapshot = Cache::remember($p . 'snapshot_v2_' . date('Y'), $ttl, function () use (
            $salesModel,
            $purchaseModel,
            $productModel,
            $customerModel,
            $supplierModel
        ) {
            return [
                'sales_totals' => $salesModel->getDashboardTotals(),
                'purchase_totals' => $purchaseModel->getDashboardTotals(),
                'stock_value' => $productModel->getTotalStockValue(),
                'low_stock' => $productModel->getLowStock(10),
                'customer_dues' => $customerModel->getTotalDues(),
                'supplier_dues' => $supplierModel->getTotalDues(),
                'monthly_sales' => $salesModel->getMonthlyData(date('Y')),
                'monthly_purchase' => $purchaseModel->getMonthlyData(date('Y')),
                'recent_sales' => $salesModel->getAllWithCustomer('', '', '', '', '', 1, 5),
                'top_products' => $salesModel->getTopProducts(5),
            ];
        });

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

        // Map to view format
        $salesChartData = array_fill(1, 12, 0);
        $purchaseChartData = array_fill(1, 12, 0);
        foreach ($monthlySales as $row) { $salesChartData[$row['month']] = (float)$row['total']; }
        foreach ($monthlyPurchase as $row) { $purchaseChartData[$row['month']] = (float)$row['total']; }

        $this->view('dashboard.index', [
            'pageTitle'        => 'Dashboard',
            'salesToday'       => ['total_amount' => $salesTotals['today_amount']],
            'salesMonth'       => ['total_amount' => $salesTotals['month_amount']],
            'salesAll'         => ['total_amount' => $salesTotals['all_amount']],
            'purchaseAll'      => ['total_amount' => $purchaseTotals['all_amount']],
            'purchaseMonth'    => ['total_amount' => $purchaseTotals['month_amount']],
            'stockValue'       => $stockValue,
            'lowStockProducts' => $lowStockProducts,
            'customerDues'     => $customerDues ?? 0,
            'supplierDues'     => $supplierDues ?? 0,
            'salesChartData'   => json_encode(array_values($salesChartData)),
            'purchaseChartData'=> json_encode(array_values($purchaseChartData)),
            'recentSales'      => $recentSales['data'],
            'topProducts'      => $topProducts,
        ]);
    }

    /**
     * Invalidate all dashboard caches for the current tenant.
     * Call from SalesController::create(), PurchaseController::create(), etc.
     */
    public static function invalidateCache(): void {
        $prefix = 'c' . (Tenant::id() ?? 0) . '_dash_';
        Cache::flushPrefix($prefix);
    }
}
