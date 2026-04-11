<?php
/**
 * Background job handler for exporting reports to CSV.
 */
class GenerateReportExport {
    public static function handle(array $payload, array $job = []): void {
        $companyId = (int)($payload['company_id'] ?? $job['company_id'] ?? 0);
        if ($companyId <= 0) {
            throw new \RuntimeException('Invalid company for report export.');
        }

        Tenant::set($companyId);
        $reportType = strtolower(trim((string)($payload['report_type'] ?? '')));
        $filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
        $maxRows = defined('REPORT_MAX_ROWS') ? max(200, (int)REPORT_MAX_ROWS) : 2000;
        $maxRows = min($maxRows, 5000);

        [$headers, $rows] = self::buildRows($reportType, $filters, $maxRows);

        $exportsDir = UPLOAD_PATH . '/exports/company_' . $companyId;
        if (!is_dir($exportsDir) && !mkdir($exportsDir, 0755, true) && !is_dir($exportsDir)) {
            throw new \RuntimeException('Could not create export directory.');
        }

        $filename = 'report_' . $reportType . '_' . date('Ymd_His') . '.csv';
        $filePath = $exportsDir . '/' . $filename;

        $fp = fopen($filePath, 'wb');
        if ($fp === false) {
            throw new \RuntimeException('Could not create report export file.');
        }

        // UTF-8 BOM for Excel compatibility.
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);

        $jobId = (int)($job['id'] ?? 0);
        if ($jobId > 0) {
            $ttl = defined('CACHE_TTL_EXPORT_STATUS') ? (int)CACHE_TTL_EXPORT_STATUS : 86400;
            $token = bin2hex(random_bytes(20));
            $exportService = new ReportExportService();

            Cache::set(
                $exportService->resultCacheKey($companyId, $jobId),
                [
                    'status' => 'ready',
                    'token' => $token,
                    'name' => $filename,
                    'path' => $filePath,
                    'generated_at' => date(DATETIME_FORMAT_DB),
                ],
                $ttl
            );

            Cache::set(
                $exportService->tokenCacheKey($companyId, $token),
                [
                    'name' => $filename,
                    'path' => $filePath,
                    'report_type' => $reportType,
                ],
                $ttl
            );
        }

        self::logExportActivity(
            $companyId,
            (int)($payload['user_id'] ?? 0),
            $reportType,
            $filename,
            count($rows)
        );
    }

    /**
     * Build header + row matrix for CSV output.
     *
     * @return array{0: array<int, string>, 1: array<int, array<int, mixed>>}
     */
    private static function buildRows(string $reportType, array $filters, int $maxRows): array {
        return match ($reportType) {
            'sales' => self::salesRows($filters, $maxRows),
            'purchases' => self::purchaseRows($filters, $maxRows),
            'tax_summary' => self::taxSummaryRows($filters),
            'stock' => self::stockRows($filters, $maxRows),
            'warehouse_transfers' => self::warehouseTransferRows($filters, $maxRows),
            'payroll_finance' => self::payrollFinanceRows($filters),
            'profit' => self::profitRows($filters),
            'customer_dues' => self::customerDuesRows(),
            'supplier_dues' => self::supplierDuesRows(),
            default => throw new \RuntimeException('Unsupported report export type: ' . $reportType),
        };
    }

    private static function salesRows(array $filters, int $maxRows): array {
        $fromDate = self::normalizeDate((string)($filters['from_date'] ?? ''), date('Y-m-01'));
        $toDate = self::normalizeDate((string)($filters['to_date'] ?? ''), date('Y-m-d'));
        [$fromDate, $toDate] = self::normalizeRange($fromDate, $toDate);
        $customerId = (int)($filters['customer_id'] ?? 0);

        $result = (new SalesModel())->getAllWithCustomer(
            '',
            $fromDate,
            $toDate,
            $customerId > 0 ? $customerId : '',
            '',
            1,
            $maxRows
        );

        $rows = [];
        foreach (($result['data'] ?? []) as $sale) {
            $rows[] = [
                $sale['invoice_number'] ?? '',
                $sale['sale_date'] ?? '',
                $sale['customer_name'] ?? '',
                (float)($sale['grand_total'] ?? 0),
                (float)($sale['paid_amount'] ?? 0),
                (float)($sale['due_amount'] ?? 0),
                $sale['payment_status'] ?? '',
            ];
        }

        return [[
            'Invoice',
            'Sale Date',
            'Customer',
            'Grand Total',
            'Paid Amount',
            'Due Amount',
            'Payment Status',
        ], $rows];
    }

    private static function purchaseRows(array $filters, int $maxRows): array {
        $fromDate = self::normalizeDate((string)($filters['from_date'] ?? ''), date('Y-m-01'));
        $toDate = self::normalizeDate((string)($filters['to_date'] ?? ''), date('Y-m-d'));
        [$fromDate, $toDate] = self::normalizeRange($fromDate, $toDate);
        $supplierId = (int)($filters['supplier_id'] ?? 0);

        $result = (new PurchaseModel())->getAllWithSupplier(
            '',
            $fromDate,
            $toDate,
            $supplierId > 0 ? $supplierId : '',
            '',
            1,
            $maxRows
        );

        $rows = [];
        foreach (($result['data'] ?? []) as $purchase) {
            $rows[] = [
                $purchase['invoice_number'] ?? '',
                $purchase['purchase_date'] ?? '',
                $purchase['supplier_name'] ?? '',
                (float)($purchase['grand_total'] ?? 0),
                (float)($purchase['paid_amount'] ?? 0),
                (float)($purchase['due_amount'] ?? 0),
                $purchase['payment_status'] ?? '',
            ];
        }

        return [[
            'Invoice',
            'Purchase Date',
            'Supplier',
            'Grand Total',
            'Paid Amount',
            'Due Amount',
            'Payment Status',
        ], $rows];
    }

    private static function taxSummaryRows(array $filters): array {
        $fromDate = self::normalizeDate((string)($filters['from_date'] ?? ''), date('Y-m-01'));
        $toDate = self::normalizeDate((string)($filters['to_date'] ?? ''), date('Y-m-d'));
        [$fromDate, $toDate] = self::normalizeRange($fromDate, $toDate);
        $settings = (new SettingsModel())->getSettings();
        $report = (new TaxReportService())->buildReport($fromDate, $toDate, $settings);
        $summary = $report['summary'] ?? [];

        $rows = [
            ['Period', $fromDate . ' to ' . $toDate],
            ['GST Enabled In Settings', !empty($report['gst_enabled']) ? 'Yes' : 'No'],
            ['Sales Taxable Turnover', (float)($summary['sales_taxable'] ?? 0)],
            ['Posted Return Taxable Adjustment', (float)($summary['sales_return_taxable'] ?? 0)],
            ['Non-GST / Zero-Tax Sales', (float)($summary['sales_non_gst'] ?? 0)],
            ['Output CGST', (float)($summary['output_cgst'] ?? 0)],
            ['Output SGST', (float)($summary['output_sgst'] ?? 0)],
            ['Output IGST', (float)($summary['output_igst'] ?? 0)],
            ['Total Output Tax', (float)($summary['output_tax'] ?? 0)],
            ['Purchase Taxable Value', (float)($summary['purchase_taxable'] ?? 0)],
            ['Purchase Return Taxable Adjustment', (float)($summary['purchase_return_taxable'] ?? 0)],
            ['Input Tax', (float)($summary['input_tax'] ?? 0)],
            ['Net Tax Payable', (float)($summary['net_tax_payable'] ?? 0)],
            [],
            ['Sales Breakdown'],
            ['Rate %', 'GST Type', 'Voucher Count', 'Taxable Amount', 'Tax Amount'],
        ];

        foreach (($report['sales_breakdown'] ?? []) as $row) {
            $rows[] = [
                (float)($row['tax_rate'] ?? 0),
                $row['gst_type'] ?? '',
                (int)($row['voucher_count'] ?? 0),
                (float)($row['taxable_amount'] ?? 0),
                (float)($row['tax_amount'] ?? 0),
            ];
        }

        $rows[] = [];
        $rows[] = ['Purchase Breakdown'];
        $rows[] = ['Rate %', 'Voucher Count', 'Taxable Amount', 'Tax Amount'];
        foreach (($report['purchase_breakdown'] ?? []) as $row) {
            $rows[] = [
                (float)($row['tax_rate'] ?? 0),
                (int)($row['voucher_count'] ?? 0),
                (float)($row['taxable_amount'] ?? 0),
                (float)($row['tax_amount'] ?? 0),
            ];
        }

        return [['Metric', 'Value', 'Voucher Count', 'Taxable Amount', 'Tax Amount'], $rows];
    }

    private static function stockRows(array $filters, int $maxRows): array {
        $search = trim((string)($filters['search'] ?? ''));
        $categoryId = (int)($filters['category_id'] ?? 0);

        $result = (new ProductModel())->getAllWithRelations(
            $search,
            $categoryId > 0 ? (string)$categoryId : '',
            1,
            $maxRows
        );

        $rows = [];
        foreach (($result['data'] ?? []) as $product) {
            $rows[] = [
                $product['name'] ?? '',
                $product['sku'] ?? '',
                $product['category_name'] ?? '',
                (float)($product['current_stock'] ?? 0),
                (float)($product['purchase_price'] ?? 0),
                (float)($product['selling_price'] ?? 0),
                (float)($product['current_stock'] ?? 0) * (float)($product['purchase_price'] ?? 0),
            ];
        }

        return [[
            'Product',
            'SKU',
            'Category',
            'Current Stock',
            'Purchase Price',
            'Selling Price',
            'Stock Value',
        ], $rows];
    }

    private static function warehouseTransferRows(array $filters, int $maxRows): array {
        $fromDate = self::normalizeDate((string)($filters['from_date'] ?? ''), date('Y-m-01'));
        $toDate = self::normalizeDate((string)($filters['to_date'] ?? ''), date('Y-m-d'));
        [$fromDate, $toDate] = self::normalizeRange($fromDate, $toDate);
        $status = strtolower(trim((string)($filters['status'] ?? '')));
        $warehouseId = (int)($filters['warehouse_id'] ?? 0);

        $transfers = (new WarehouseModel())->transferReport($fromDate, $toDate, $status, $warehouseId, $maxRows);
        $rows = [];
        foreach ($transfers as $transfer) {
            $rows[] = [
                $transfer['transfer_number'] ?? '',
                $transfer['transfer_date'] ?? '',
                $transfer['status'] ?? '',
                $transfer['source_warehouse_name'] ?? '',
                $transfer['destination_warehouse_name'] ?? '',
                (int)($transfer['item_count'] ?? 0),
                (float)($transfer['total_quantity'] ?? 0),
                $transfer['created_by_name'] ?? '',
                $transfer['approved_by_name'] ?? '',
                $transfer['reference_number'] ?? '',
                $transfer['note'] ?? '',
            ];
        }

        return [[
            'Transfer Number',
            'Transfer Date',
            'Status',
            'Source Warehouse',
            'Destination Warehouse',
            'Item Count',
            'Total Quantity',
            'Created By',
            'Approved By',
            'Reference Number',
            'Note',
        ], $rows];
    }

    private static function profitRows(array $filters): array {
        $fromDate = self::normalizeDate((string)($filters['from_date'] ?? ''), date('Y-m-01'));
        $toDate = self::normalizeDate((string)($filters['to_date'] ?? ''), date('Y-m-d'));
        [$fromDate, $toDate] = self::normalizeRange($fromDate, $toDate);

        $profit = (new SalesModel())->getProfitData($fromDate, $toDate);
        $rows = [[
            $fromDate,
            $toDate,
            (float)($profit['total_sales'] ?? 0),
            (float)($profit['total_cost'] ?? 0),
            (float)($profit['gross_profit'] ?? 0),
            (float)($profit['total_discount'] ?? 0),
            (float)($profit['net_profit'] ?? 0),
        ]];

        return [[
            'From Date',
            'To Date',
            'Total Sales',
            'Total Cost',
            'Gross Profit',
            'Total Discount',
            'Net Profit',
        ], $rows];
    }

    private static function payrollFinanceRows(array $filters): array {
        $fromMonth = self::normalizeMonth((string)($filters['from_month'] ?? date('Y-01')), date('Y-01'));
        $toMonth = self::normalizeMonth((string)($filters['to_month'] ?? date('Y-m')), date('Y-m'));
        if (strcmp($fromMonth, $toMonth) > 0) {
            [$fromMonth, $toMonth] = [$toMonth, $fromMonth];
        }

        $report = (new HrPayroll())->financeReport($fromMonth, $toMonth);
        $rows = [];
        foreach (($report['entries'] ?? []) as $entry) {
            $rows[] = [
                $entry['payroll_month'] ?? '',
                $entry['payment_date'] ?? '',
                $entry['payment_number'] ?? '',
                $entry['employee_name'] ?? '',
                $entry['employee_code'] ?? '',
                $entry['account_code'] ?? '',
                $entry['account_name'] ?? '',
                $entry['entry_side'] ?? '',
                $entry['payment_method'] ?? '',
                (float)($entry['amount'] ?? 0),
            ];
        }

        return [[
            'Payroll Month',
            'Payment Date',
            'Payment Number',
            'Employee',
            'Employee Code',
            'Account Code',
            'Account Name',
            'Entry Side',
            'Payment Method',
            'Amount',
        ], $rows];
    }

    private static function customerDuesRows(): array {
        $customers = (new CustomerModel())->getWithDues();
        $rows = [];
        foreach ($customers as $customer) {
            $rows[] = [
                $customer['name'] ?? '',
                $customer['phone'] ?? '',
                $customer['city'] ?? '',
                (float)($customer['current_balance'] ?? 0),
            ];
        }

        return [[
            'Customer',
            'Phone',
            'City',
            'Balance Due',
        ], $rows];
    }

    private static function supplierDuesRows(): array {
        $suppliers = (new SupplierModel())->getWithDues();
        $rows = [];
        foreach ($suppliers as $supplier) {
            $rows[] = [
                $supplier['name'] ?? '',
                $supplier['phone'] ?? '',
                $supplier['city'] ?? '',
                (float)($supplier['current_balance'] ?? 0),
            ];
        }

        return [[
            'Supplier',
            'Phone',
            'City',
            'Balance Due',
        ], $rows];
    }

    private static function normalizeDate(string $date, string $default): string {
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

    private static function normalizeRange(string $fromDate, string $toDate): array {
        if (strtotime($fromDate) > strtotime($toDate)) {
            return [$toDate, $fromDate];
        }
        return [$fromDate, $toDate];
    }

    private static function normalizeMonth(string $month, string $default): string {
        $month = trim($month);
        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : $default;
    }

    private static function logExportActivity(
        int $companyId,
        int $userId,
        string $reportType,
        string $filename,
        int $rowsCount
    ): void {
        try {
            Database::getInstance()->query(
                "INSERT INTO activity_log (company_id, user_id, action, module, details, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $companyId,
                    $userId > 0 ? $userId : null,
                    'Generated report export',
                    'reports',
                    json_encode([
                        'report_type' => $reportType,
                        'file' => $filename,
                        'rows' => $rowsCount,
                    ]),
                    'queue-worker',
                ]
            );
        } catch (\Throwable $e) {
            error_log('[REPORT_EXPORT] Could not write activity log: ' . $e->getMessage());
        }
    }
}
