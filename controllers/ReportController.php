<?php
class ReportController extends Controller {
    protected $allowedActions = [
        'index',
        'sales',
        'purchases',
        'stock',
        'warehouse_transfers',
        'payroll_finance',
        'profit',
        'customer_dues',
        'supplier_dues',
        'queue_export',
        'export_status',
        'download_export',
    ];

    public function index() {
        $this->requirePermission('reports.view');
        $this->view('reports.index', [
            'pageTitle' => 'Reports',
            'hasWarehouseFeature' => $this->warehouseFeatureEnabled(),
        ]);
    }

    public function payroll_finance() {
        $this->requireFeature('hr');
        $this->requirePermission('reports.view');

        $fromMonth = $this->normalizeMonth((string)$this->get('from_month', date('Y-01')));
        $toMonth = $this->normalizeMonth((string)$this->get('to_month', date('Y-m')));
        if (strcmp($fromMonth, $toMonth) > 0) {
            [$fromMonth, $toMonth] = [$toMonth, $fromMonth];
        }

        $report = Cache::remember(
            $this->reportCacheKey('payroll_finance', ['from_month' => $fromMonth, 'to_month' => $toMonth]),
            $this->reportCacheTtl(),
            fn() => (new HrPayroll())->financeReport($fromMonth, $toMonth)
        );

        $this->view('reports.payroll_finance', [
            'pageTitle' => 'Payroll Finance Report',
            'report' => $report,
            'fromMonth' => $fromMonth,
            'toMonth' => $toMonth,
        ]);
    }

    public function sales() {
        $this->requirePermission('reports.view');

        $fromDate = $this->normalizeDate($this->get('from_date', ''), date('Y-m-01'));
        $toDate = $this->normalizeDate($this->get('to_date', ''), date('Y-m-d'));
        [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
        $customerId = $this->normalizeEntityId($this->get('customer_id', ''));
        $warehouseId = $this->warehouseFeatureEnabled() ? $this->normalizeEntityId($this->get('warehouse_id', '')) : 0;
        $maxRows = defined('REPORT_MAX_ROWS') ? REPORT_MAX_ROWS : 2000;

        $sales = Cache::remember(
            $this->reportCacheKey('sales', [
                'from' => $fromDate,
                'to' => $toDate,
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'max_rows' => $maxRows,
            ]),
            $this->reportCacheTtl(),
            function () use ($fromDate, $toDate, $customerId, $warehouseId, $maxRows) {
                return (new SalesModel())->getAllWithCustomer(
                    '',
                    $fromDate,
                    $toDate,
                    $customerId > 0 ? $customerId : '',
                    '',
                    1,
                    $maxRows,
                    $warehouseId > 0 ? $warehouseId : ''
                );
            }
        );

        $customers = Cache::remember(
            $this->reportCacheKey('lookup_customers'),
            $this->reportCacheTtl() * 6,
            fn() => (new CustomerModel())->allActive()
        );

        $this->view('reports.sales', [
            'pageTitle' => 'Sales Report',
            'sales' => $sales,
            'customers' => $customers,
            'warehouses' => $this->warehouseOptions(),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'customerId' => $customerId,
            'warehouseId' => $warehouseId,
        ]);
    }

    public function purchases() {
        $this->requirePermission('reports.view');

        $fromDate = $this->normalizeDate($this->get('from_date', ''), date('Y-m-01'));
        $toDate = $this->normalizeDate($this->get('to_date', ''), date('Y-m-d'));
        [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
        $supplierId = $this->normalizeEntityId($this->get('supplier_id', ''));
        $warehouseId = $this->warehouseFeatureEnabled() ? $this->normalizeEntityId($this->get('warehouse_id', '')) : 0;
        $maxRows = defined('REPORT_MAX_ROWS') ? REPORT_MAX_ROWS : 2000;

        $purchases = Cache::remember(
            $this->reportCacheKey('purchases', [
                'from' => $fromDate,
                'to' => $toDate,
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'max_rows' => $maxRows,
            ]),
            $this->reportCacheTtl(),
            function () use ($fromDate, $toDate, $supplierId, $warehouseId, $maxRows) {
                return (new PurchaseModel())->getAllWithSupplier(
                    '',
                    $fromDate,
                    $toDate,
                    $supplierId > 0 ? $supplierId : '',
                    '',
                    1,
                    $maxRows,
                    $warehouseId > 0 ? $warehouseId : ''
                );
            }
        );

        $suppliers = Cache::remember(
            $this->reportCacheKey('lookup_suppliers'),
            $this->reportCacheTtl() * 6,
            fn() => (new SupplierModel())->allActive()
        );

        $this->view('reports.purchases', [
            'pageTitle' => 'Purchase Report',
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'warehouses' => $this->warehouseOptions(),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'supplierId' => $supplierId,
            'warehouseId' => $warehouseId,
        ]);
    }

    public function stock() {
        $this->requirePermission('reports.view');

        $maxRows = defined('REPORT_MAX_ROWS') ? REPORT_MAX_ROWS : 2000;
        $search = $this->sanitize($this->get('search', ''));
        $categoryId = $this->normalizeEntityId($this->get('category_id', ''));
        $warehouseId = $this->warehouseFeatureEnabled() ? $this->normalizeEntityId($this->get('warehouse_id', '')) : 0;

        $products = Cache::remember(
            $this->reportCacheKey('stock', [
                'search' => $search,
                'category_id' => $categoryId,
                'warehouse_id' => $warehouseId,
                'max_rows' => $maxRows,
            ]),
            $this->reportCacheTtl(),
            function () use ($search, $categoryId, $warehouseId, $maxRows) {
                return (new ProductModel())->getStockReport(
                    $search,
                    $categoryId > 0 ? (string)$categoryId : '',
                    $warehouseId > 0 ? $warehouseId : null,
                    1,
                    $maxRows
                );
            }
        );

        $categories = Cache::remember(
            $this->reportCacheKey('lookup_categories'),
            $this->reportCacheTtl() * 6,
            fn() => (new CategoryModel())->allActive()
        );

        $transferSummary = ['total_transfers' => 0, 'pending_transfers' => 0, 'approved_transfers' => 0, 'total_quantity' => 0];
        $recentTransfers = [];
        if ($this->warehouseFeatureEnabled()) {
            $warehouseModel = new WarehouseModel();
            $transferSummary = Cache::remember(
                $this->reportCacheKey('stock_transfer_summary'),
                $this->reportCacheTtl(),
                fn() => $warehouseModel->transferSummary()
            );
            $recentTransfers = Cache::remember(
                $this->reportCacheKey('stock_recent_transfers'),
                $this->reportCacheTtl(),
                fn() => $warehouseModel->recentTransfers(8)
            );
        }

        $this->view('reports.stock', [
            'pageTitle' => 'Stock Report',
            'products' => $products,
            'categories' => $categories,
            'warehouses' => $this->warehouseOptions(),
            'transferSummary' => $transferSummary,
            'recentTransfers' => $recentTransfers,
            'search' => $search,
            'categoryId' => $categoryId,
            'warehouseId' => $warehouseId,
        ]);
    }

    public function warehouse_transfers() {
        $this->requirePermission('reports.view');
        if (!$this->warehouseFeatureEnabled()) {
            $this->setFlash('error', 'Warehouse transfer reporting requires the multi-warehouse feature.');
            $this->redirect('index.php?page=reports');
            return;
        }

        $fromDate = $this->normalizeDate($this->get('from_date', ''), date('Y-m-01'));
        $toDate = $this->normalizeDate($this->get('to_date', ''), date('Y-m-d'));
        [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
        $status = strtolower(trim((string)$this->get('status', '')));
        $warehouseId = $this->normalizeEntityId($this->get('warehouse_id', ''));
        $maxRows = defined('REPORT_MAX_ROWS') ? REPORT_MAX_ROWS : 2000;

        $warehouseModel = new WarehouseModel();
        $transfers = Cache::remember(
            $this->reportCacheKey('warehouse_transfers', [
                'from' => $fromDate,
                'to' => $toDate,
                'status' => $status,
                'warehouse_id' => $warehouseId,
                'max_rows' => $maxRows,
            ]),
            $this->reportCacheTtl(),
            fn() => $warehouseModel->transferReport($fromDate, $toDate, $status, $warehouseId, $maxRows)
        );

        $summary = [
            'total_transfers' => count($transfers),
            'pending_transfers' => 0,
            'approved_transfers' => 0,
            'rejected_transfers' => 0,
            'total_quantity' => 0.0,
        ];
        foreach ($transfers as $transfer) {
            $summary['total_quantity'] += (float)($transfer['total_quantity'] ?? 0);
            if (($transfer['status'] ?? '') === 'approved') {
                $summary['approved_transfers']++;
            } elseif (($transfer['status'] ?? '') === 'rejected') {
                $summary['rejected_transfers']++;
            } else {
                $summary['pending_transfers']++;
            }
        }
        $summary['total_quantity'] = round((float)$summary['total_quantity'], 3);

        $this->view('reports.warehouse_transfers', [
            'pageTitle' => 'Warehouse Transfer Report',
            'transfers' => $transfers,
            'summary' => $summary,
            'warehouses' => $this->warehouseOptions(),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'status' => $status,
            'warehouseId' => $warehouseId,
        ]);
    }

    public function profit() {
        $this->requireFeature('advanced_reports');
        $this->requirePermission('reports.view');

        $fromDate = $this->normalizeDate($this->get('from_date', ''), date('Y-m-01'));
        $toDate = $this->normalizeDate($this->get('to_date', ''), date('Y-m-d'));
        [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);

        $profitData = Cache::remember(
            $this->reportCacheKey('profit', ['from' => $fromDate, 'to' => $toDate]),
            $this->reportCacheTtl(),
            fn() => (new SalesModel())->getProfitData($fromDate, $toDate)
        );

        $this->view('reports.profit', [
            'pageTitle' => 'Profit & Loss',
            'profitData' => $profitData,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function customer_dues() {
        $this->requireFeature('advanced_reports');
        $this->requirePermission('reports.view');

        $customers = Cache::remember(
            $this->reportCacheKey('customer_dues'),
            $this->reportCacheTtl(),
            fn() => (new CustomerModel())->getWithDues()
        );

        $this->view('reports.customer_dues', [
            'pageTitle' => 'Customer Dues',
            'customers' => $customers,
        ]);
    }

    public function supplier_dues() {
        $this->requireFeature('advanced_reports');
        $this->requirePermission('reports.view');

        $suppliers = Cache::remember(
            $this->reportCacheKey('supplier_dues'),
            $this->reportCacheTtl(),
            fn() => (new SupplierModel())->getWithDues()
        );

        $this->view('reports.supplier_dues', [
            'pageTitle' => 'Supplier Dues',
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Queue heavy CSV export generation into background jobs.
     */
    public function queue_export() {
        $this->requirePermission('reports.view');

        if (!$this->isPost()) {
            $this->redirect('index.php?page=reports');
            return;
        }

        $this->validateCSRF();

        $reportType = $this->normalizeReportType((string)$this->post('report_type', 'sales'));
        if ($reportType === null) {
            $this->setFlash('error', 'Invalid report type.');
            $this->redirect('index.php?page=reports');
            return;
        }

        $companyId = Tenant::require();
        $payload = [
            'company_id' => $companyId,
            'user_id' => (int)(Session::get('user')['id'] ?? 0),
            'report_type' => $reportType,
            'filters' => $this->collectExportFilters($reportType),
            'requested_at' => date(DATETIME_FORMAT_DB),
        ];

        try {
            $jobId = JobDispatcher::dispatch('reports', 'GenerateReportExport', $payload, 4, 2);
            Cache::set($this->exportResultKey($jobId), ['status' => 'queued'], defined('CACHE_TTL_EXPORT_STATUS') ? CACHE_TTL_EXPORT_STATUS : 86400);

            if ($this->isAjax()) {
                $this->json(['success' => true, 'job_id' => $jobId, 'message' => 'Export queued successfully.']);
                return;
            }

            $this->setFlash('success', 'Report export queued. You can check status with Job ID: #' . $jobId);
            $this->redirect('index.php?page=reports&action=' . $reportType);
        } catch (\Throwable $e) {
            error_log('[REPORT_EXPORT] Failed to queue export: ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Failed to queue report export.'], 500);
                return;
            }
            $this->setFlash('error', 'Failed to queue report export. Please try again.');
            $this->redirect('index.php?page=reports&action=' . $reportType);
        }
    }

    /**
     * Poll export background job status.
     */
    public function export_status() {
        $this->requirePermission('reports.view');

        $jobId = (int)$this->get('job_id', 0);
        if ($jobId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid job id.'], 400);
            return;
        }

        $companyId = Tenant::require();
        $job = Database::getInstance()->query(
            "SELECT id, status, error, created_at, started_at, completed_at
             FROM jobs
             WHERE id = ? AND company_id = ? AND queue = 'reports'
             LIMIT 1",
            [$jobId, $companyId]
        )->fetch();

        if (!$job) {
            $this->json(['success' => false, 'message' => 'Export job not found.'], 404);
            return;
        }

        $result = Cache::get($this->exportResultKey($jobId));
        $downloadUrl = null;
        if ($job['status'] === 'completed' && is_array($result) && !empty($result['token'])) {
            $downloadUrl = APP_URL . '/index.php?page=reports&action=download_export&token=' . urlencode((string)$result['token']);
        }

        $this->json([
            'success' => true,
            'job' => $job,
            'download_url' => $downloadUrl,
        ]);
    }

    /**
     * Download generated CSV export by one-time token.
     */
    public function download_export() {
        $this->requirePermission('reports.view');

        $token = trim((string)$this->get('token', ''));
        if ($token === '') {
            $this->setFlash('error', 'Invalid export token.');
            $this->redirect('index.php?page=reports');
            return;
        }

        $tokenPayload = Cache::get($this->exportTokenKey($token));
        if (!is_array($tokenPayload) || empty($tokenPayload['path'])) {
            $this->setFlash('error', 'Export file not found or expired.');
            $this->redirect('index.php?page=reports');
            return;
        }

        $companyId = Tenant::require();
        $allowedRoot = realpath(BASE_PATH . '/uploads/exports/company_' . $companyId);
        $filePath = realpath((string)$tokenPayload['path']);

        if (
            !$allowedRoot ||
            !$filePath ||
            !is_file($filePath) ||
            !str_starts_with($filePath, $allowedRoot . DIRECTORY_SEPARATOR)
        ) {
            $this->setFlash('error', 'Export file is unavailable.');
            $this->redirect('index.php?page=reports');
            return;
        }

        $downloadName = basename((string)($tokenPayload['name'] ?? basename($filePath)));
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($filePath);
        exit;
    }

    /**
     * Invalidate report caches for current tenant.
     */
    public static function invalidateCache(): void {
        Cache::flushPrefix('c' . (Tenant::id() ?? 0) . '_report_');
    }

    private function reportCacheTtl(): int {
        return defined('CACHE_TTL_REPORTS') ? max(60, (int)CACHE_TTL_REPORTS) : 600;
    }

    private function reportCacheKey(string $name, array $filters = []): string {
        ksort($filters);
        return 'c' . (Tenant::id() ?? 0) . '_report_' . $name . '_' . md5(json_encode($filters));
    }

    private function exportResultKey(int $jobId): string {
        return 'c' . (Tenant::id() ?? 0) . '_report_export_' . $jobId;
    }

    private function exportTokenKey(string $token): string {
        return 'c' . (Tenant::id() ?? 0) . '_report_export_token_' . $token;
    }

    private function normalizeReportType(string $type): ?string {
        $type = strtolower(trim($type));
        $allowed = ['sales', 'purchases', 'stock', 'warehouse_transfers', 'profit', 'customer_dues', 'supplier_dues', 'payroll_finance'];
        return in_array($type, $allowed, true) ? $type : null;
    }

    private function collectExportFilters(string $reportType): array {
        $filters = [];
        if (in_array($reportType, ['sales', 'purchases', 'profit'], true)) {
            $fromDate = $this->normalizeDate((string)$this->post('from_date', $this->get('from_date', '')), date('Y-m-01'));
            $toDate = $this->normalizeDate((string)$this->post('to_date', $this->get('to_date', '')), date('Y-m-d'));
            [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
            $filters['from_date'] = $fromDate;
            $filters['to_date'] = $toDate;
        } elseif ($reportType === 'payroll_finance') {
            $fromMonth = $this->normalizeMonth((string)$this->post('from_month', $this->get('from_month', date('Y-01'))));
            $toMonth = $this->normalizeMonth((string)$this->post('to_month', $this->get('to_month', date('Y-m'))));
            if (strcmp($fromMonth, $toMonth) > 0) {
                [$fromMonth, $toMonth] = [$toMonth, $fromMonth];
            }
            $filters['from_month'] = $fromMonth;
            $filters['to_month'] = $toMonth;
        }

        if ($reportType === 'sales') {
            $filters['customer_id'] = $this->normalizeEntityId($this->post('customer_id', $this->get('customer_id', '')));
            $filters['warehouse_id'] = $this->warehouseFeatureEnabled() ? $this->normalizeEntityId($this->post('warehouse_id', $this->get('warehouse_id', ''))) : 0;
        } elseif ($reportType === 'purchases') {
            $filters['supplier_id'] = $this->normalizeEntityId($this->post('supplier_id', $this->get('supplier_id', '')));
            $filters['warehouse_id'] = $this->warehouseFeatureEnabled() ? $this->normalizeEntityId($this->post('warehouse_id', $this->get('warehouse_id', ''))) : 0;
        } elseif ($reportType === 'stock') {
            $filters['search'] = $this->sanitize((string)$this->post('search', $this->get('search', '')));
            $filters['category_id'] = $this->normalizeEntityId($this->post('category_id', $this->get('category_id', '')));
            $filters['warehouse_id'] = $this->warehouseFeatureEnabled() ? $this->normalizeEntityId($this->post('warehouse_id', $this->get('warehouse_id', ''))) : 0;
        } elseif ($reportType === 'warehouse_transfers') {
            $fromDate = $this->normalizeDate((string)$this->post('from_date', $this->get('from_date', '')), date('Y-m-01'));
            $toDate = $this->normalizeDate((string)$this->post('to_date', $this->get('to_date', '')), date('Y-m-d'));
            [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
            $filters['from_date'] = $fromDate;
            $filters['to_date'] = $toDate;
            $status = strtolower(trim((string)$this->post('status', $this->get('status', ''))));
            $filters['status'] = in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : '';
            $filters['warehouse_id'] = $this->warehouseFeatureEnabled() ? $this->normalizeEntityId($this->post('warehouse_id', $this->get('warehouse_id', ''))) : 0;
        }

        return $filters;
    }

    private function warehouseFeatureEnabled(): bool {
        return !Session::isSuperAdmin()
            && Tenant::id() !== null
            && Tenant::canUse('multi_warehouse');
    }

    private function warehouseOptions(): array {
        if (!$this->warehouseFeatureEnabled()) {
            return [];
        }

        return Cache::remember(
            $this->reportCacheKey('lookup_warehouses'),
            $this->reportCacheTtl() * 6,
            fn() => (new WarehouseModel())->allActiveOrdered()
        );
    }

    private function normalizeDate(string $date, string $default): string {
        $date = trim($date);
        if ($date === '') {
            return $default;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        $errors = \DateTime::getLastErrors();
        if (!$dt || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
            return $default;
        }
        return $dt->format('Y-m-d');
    }

    private function normalizeDateRange(string $fromDate, string $toDate): array {
        if (strtotime($fromDate) > strtotime($toDate)) {
            return [$toDate, $fromDate];
        }
        return [$fromDate, $toDate];
    }

    private function normalizeEntityId(mixed $value): int {
        $id = (int)$value;
        return $id > 0 ? $id : 0;
    }

    private function normalizeMonth(string $value): string {
        return preg_match('/^\d{4}-\d{2}$/', $value) ? $value : date('Y-m');
    }
}
