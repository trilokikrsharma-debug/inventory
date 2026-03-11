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
        if (Session::isSuperAdmin() && Tenant::id() === null) {
            $this->redirect('index.php?page=platform&action=dashboard');
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

        // All 8 queries cached at 5-minute TTL
        $salesTotals = Cache::remember($p . 'sales_totals', $ttl, fn() => $salesModel->getDashboardTotals());
        $purchaseTotals = Cache::remember($p . 'purchase_totals', $ttl, fn() => $purchaseModel->getDashboardTotals());
        $stockValue = Cache::remember($p . 'stock_value', $ttl, fn() => $productModel->getTotalStockValue());
        $lowStockProducts = Cache::remember($p . 'low_stock', $ttl, fn() => $productModel->getLowStock(10));
        $customerDues = Cache::remember($p . 'customer_dues', $ttl, fn() => $customerModel->getTotalDues());
        $supplierDues = Cache::remember($p . 'supplier_dues', $ttl, fn() => $supplierModel->getTotalDues());
        $monthlySales = Cache::remember($p . 'monthly_sales_' . date('Y'), $ttl, fn() => $salesModel->getMonthlyData(date('Y')));
        $monthlyPurchase = Cache::remember($p . 'monthly_purchase_' . date('Y'), $ttl, fn() => $purchaseModel->getMonthlyData(date('Y')));
        $recentSales = Cache::remember($p . 'recent_sales', $ttl, fn() => $salesModel->getAllWithCustomer('', '', '', '', '', 1, 5));
        $topProducts = Cache::remember($p . 'top_products', $ttl, fn() => $salesModel->getTopProducts(5));

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
