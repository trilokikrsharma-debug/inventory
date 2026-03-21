<?php
class ReportController extends Controller {
    protected $allowedActions = [
        'index',
        'sales',
        'purchases',
        'stock',
        'profit',
        'customer_dues',
        'supplier_dues',
        'queue_export',
        'export_status',
        'download_export',
    ];

    public function index() {
        $this->requirePermission('reports.view');
        $this->view('reports.index', ['pageTitle' => 'Reports']);
    }

    public function sales() {
        $this->requirePermission('reports.view');

        $fromDate = $this->normalizeDate($this->get('from_date', ''), date('Y-m-01'));
        $toDate = $this->normalizeDate($this->get('to_date', ''), date('Y-m-d'));
        [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
        $customerId = $this->normalizeEntityId($this->get('customer_id', ''));
        $maxRows = defined('REPORT_MAX_ROWS') ? REPORT_MAX_ROWS : 2000;

        $sales = Cache::remember(
            $this->reportCacheKey('sales', [
                'from' => $fromDate,
                'to' => $toDate,
                'customer_id' => $customerId,
                'max_rows' => $maxRows,
            ]),
            $this->reportCacheTtl(),
            function () use ($fromDate, $toDate, $customerId, $maxRows) {
                return (new SalesModel())->getAllWithCustomer(
                    '',
                    $fromDate,
                    $toDate,
                    $customerId > 0 ? $customerId : '',
                    '',
                    1,
                    $maxRows
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
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'customerId' => $customerId,
        ]);
    }

    public function purchases() {
        $this->requirePermission('reports.view');

        $fromDate = $this->normalizeDate($this->get('from_date', ''), date('Y-m-01'));
        $toDate = $this->normalizeDate($this->get('to_date', ''), date('Y-m-d'));
        [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
        $supplierId = $this->normalizeEntityId($this->get('supplier_id', ''));
        $maxRows = defined('REPORT_MAX_ROWS') ? REPORT_MAX_ROWS : 2000;

        $purchases = Cache::remember(
            $this->reportCacheKey('purchases', [
                'from' => $fromDate,
                'to' => $toDate,
                'supplier_id' => $supplierId,
                'max_rows' => $maxRows,
            ]),
            $this->reportCacheTtl(),
            function () use ($fromDate, $toDate, $supplierId, $maxRows) {
                return (new PurchaseModel())->getAllWithSupplier(
                    '',
                    $fromDate,
                    $toDate,
                    $supplierId > 0 ? $supplierId : '',
                    '',
                    1,
                    $maxRows
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
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'supplierId' => $supplierId,
        ]);
    }

    public function stock() {
        $this->requirePermission('reports.view');

        $maxRows = defined('REPORT_MAX_ROWS') ? REPORT_MAX_ROWS : 2000;
        $search = $this->sanitize($this->get('search', ''));
        $categoryId = $this->normalizeEntityId($this->get('category_id', ''));

        $products = Cache::remember(
            $this->reportCacheKey('stock', [
                'search' => $search,
                'category_id' => $categoryId,
                'max_rows' => $maxRows,
            ]),
            $this->reportCacheTtl(),
            function () use ($search, $categoryId, $maxRows) {
                return (new ProductModel())->getAllWithRelations(
                    $search,
                    $categoryId > 0 ? (string)$categoryId : '',
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

        $this->view('reports.stock', [
            'pageTitle' => 'Stock Report',
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'categoryId' => $categoryId,
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
        $allowed = ['sales', 'purchases', 'stock', 'profit', 'customer_dues', 'supplier_dues'];
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
        }

        if ($reportType === 'sales') {
            $filters['customer_id'] = $this->normalizeEntityId($this->post('customer_id', $this->get('customer_id', '')));
        } elseif ($reportType === 'purchases') {
            $filters['supplier_id'] = $this->normalizeEntityId($this->post('supplier_id', $this->get('supplier_id', '')));
        } elseif ($reportType === 'stock') {
            $filters['search'] = $this->sanitize((string)$this->post('search', $this->get('search', '')));
            $filters['category_id'] = $this->normalizeEntityId($this->post('category_id', $this->get('category_id', '')));
        }

        return $filters;
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
}
