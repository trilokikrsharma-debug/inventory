<?php
/**
 * Builds GST/non-GST tax summaries from immutable billing line snapshots.
 */
class TaxReportService {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?: Database::getInstance();
    }

    public function buildReport(string $fromDate, string $toDate, array $settings = []): array {
        [$fromDate, $toDate] = $this->normalizeDateRange($fromDate, $toDate);
        $salesRows = $this->fetchSalesTaxRows($fromDate, $toDate);
        $saleReturnRows = $this->fetchSaleReturnTaxRows($fromDate, $toDate);
        $purchaseRows = $this->fetchPurchaseTaxRows($fromDate, $toDate);
        $purchaseReturnRows = $this->fetchPurchaseReturnTaxRows($fromDate, $toDate);

        return $this->summarizeRows($salesRows, $purchaseRows, $fromDate, $toDate, $settings, $saleReturnRows, $purchaseReturnRows);
    }

    public function summarizeRows(array $salesRows, array $purchaseRows, string $fromDate, string $toDate, array $settings = [], array $saleReturnRows = [], array $purchaseReturnRows = []): array {
        $summary = [
            'sales_taxable' => 0.0,
            'sales_return_taxable' => 0.0,
            'sales_non_gst' => 0.0,
            'output_cgst' => 0.0,
            'output_sgst' => 0.0,
            'output_igst' => 0.0,
            'output_tax' => 0.0,
            'purchase_taxable' => 0.0,
            'purchase_return_taxable' => 0.0,
            'input_tax' => 0.0,
            'net_tax_payable' => 0.0,
        ];

        $salesBreakdown = [];
        foreach ($salesRows as $row) {
            $rate = $this->normalizeRate($row['tax_rate'] ?? 0);
            $gstType = $this->normalizeGstType((string)($row['gst_type'] ?? 'none'), (float)($row['tax_amount'] ?? 0));
            $taxable = round((float)($row['taxable_amount'] ?? 0), 2);
            $tax = round((float)($row['tax_amount'] ?? 0), 2);
            $voucherCount = (int)($row['voucher_count'] ?? 0);

            $summary['sales_taxable'] += $taxable;
            if ($tax <= 0.0 || $gstType === 'none') {
                $summary['sales_non_gst'] += $taxable;
            } elseif ($gstType === 'igst') {
                $summary['output_igst'] += $tax;
            } else {
                $summary['output_cgst'] += $tax / 2;
                $summary['output_sgst'] += $tax / 2;
            }

            $key = $rate . '|' . $gstType;
            if (!isset($salesBreakdown[$key])) {
                $salesBreakdown[$key] = [
                    'tax_rate' => (float)$rate,
                    'gst_type' => $gstType,
                    'voucher_count' => 0,
                    'taxable_amount' => 0.0,
                    'tax_amount' => 0.0,
                ];
            }
            $salesBreakdown[$key]['voucher_count'] += $voucherCount;
            $salesBreakdown[$key]['taxable_amount'] += $taxable;
            $salesBreakdown[$key]['tax_amount'] += $tax;
        }

        foreach ($saleReturnRows as $row) {
            $rate = $this->normalizeRate($row['tax_rate'] ?? 0);
            $gstType = $this->normalizeGstType((string)($row['gst_type'] ?? 'none'), (float)($row['tax_amount'] ?? 0));
            $taxable = round((float)($row['taxable_amount'] ?? 0), 2);
            $tax = round((float)($row['tax_amount'] ?? 0), 2);

            $summary['sales_return_taxable'] += $taxable;
            $summary['sales_taxable'] -= $taxable;
            if ($tax <= 0.0 || $gstType === 'none') {
                $summary['sales_non_gst'] -= $taxable;
            } elseif ($gstType === 'igst') {
                $summary['output_igst'] -= $tax;
            } else {
                $summary['output_cgst'] -= $tax / 2;
                $summary['output_sgst'] -= $tax / 2;
            }

            $key = $rate . '|' . $gstType;
            if (!isset($salesBreakdown[$key])) {
                $salesBreakdown[$key] = [
                    'tax_rate' => (float)$rate,
                    'gst_type' => $gstType,
                    'voucher_count' => 0,
                    'taxable_amount' => 0.0,
                    'tax_amount' => 0.0,
                ];
            }
            $salesBreakdown[$key]['taxable_amount'] -= $taxable;
            $salesBreakdown[$key]['tax_amount'] -= $tax;
        }

        $purchaseBreakdown = [];
        foreach ($purchaseRows as $row) {
            $rate = $this->normalizeRate($row['tax_rate'] ?? 0);
            $taxable = round((float)($row['taxable_amount'] ?? 0), 2);
            $tax = round((float)($row['tax_amount'] ?? 0), 2);
            $voucherCount = (int)($row['voucher_count'] ?? 0);

            $summary['purchase_taxable'] += $taxable;
            $summary['input_tax'] += $tax;

            if (!isset($purchaseBreakdown[$rate])) {
                $purchaseBreakdown[$rate] = [
                    'tax_rate' => (float)$rate,
                    'voucher_count' => 0,
                    'taxable_amount' => 0.0,
                    'tax_amount' => 0.0,
                ];
            }
            $purchaseBreakdown[$rate]['voucher_count'] += $voucherCount;
            $purchaseBreakdown[$rate]['taxable_amount'] += $taxable;
            $purchaseBreakdown[$rate]['tax_amount'] += $tax;
        }

        foreach ($purchaseReturnRows as $row) {
            $rate = $this->normalizeRate($row['tax_rate'] ?? 0);
            $taxable = round((float)($row['taxable_amount'] ?? 0), 2);
            $tax = round((float)($row['tax_amount'] ?? 0), 2);

            $summary['purchase_return_taxable'] += $taxable;
            $summary['purchase_taxable'] -= $taxable;
            $summary['input_tax'] -= $tax;

            if (!isset($purchaseBreakdown[$rate])) {
                $purchaseBreakdown[$rate] = [
                    'tax_rate' => (float)$rate,
                    'voucher_count' => 0,
                    'taxable_amount' => 0.0,
                    'tax_amount' => 0.0,
                ];
            }
            $purchaseBreakdown[$rate]['taxable_amount'] -= $taxable;
            $purchaseBreakdown[$rate]['tax_amount'] -= $tax;
        }

        $summary['output_tax'] = $summary['output_cgst'] + $summary['output_sgst'] + $summary['output_igst'];
        $summary['net_tax_payable'] = $summary['output_tax'] - $summary['input_tax'];
        $summary = array_map(fn($value) => round((float)$value, 2), $summary);

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'gst_enabled' => (!isset($settings['enable_tax']) || !empty($settings['enable_tax']))
                && (!isset($settings['enable_gst']) || !empty($settings['enable_gst'])),
            'summary' => $summary,
            'sales_breakdown' => array_values(array_map([$this, 'roundBreakdownRow'], $salesBreakdown)),
            'purchase_breakdown' => array_values(array_map([$this, 'roundBreakdownRow'], $purchaseBreakdown)),
        ];
    }

    private function fetchSalesTaxRows(string $fromDate, string $toDate): array {
        $params = [$fromDate, $toDate];
        $tenantSql = '';
        if (Tenant::id() !== null) {
            $tenantSql = ' AND s.company_id = ? AND si.company_id = ?';
            $params[] = Tenant::id();
            $params[] = Tenant::id();
        }

        return $this->db->query(
            "SELECT
                COALESCE(si.tax_rate, 0) AS tax_rate,
                COALESCE(s.gst_type, 'none') AS gst_type,
                COUNT(DISTINCT s.id) AS voucher_count,
                COALESCE(SUM(si.subtotal), 0) AS taxable_amount,
                COALESCE(SUM(si.tax_amount), 0) AS tax_amount
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             WHERE s.deleted_at IS NULL
               AND s.status <> 'cancelled'
               AND s.sale_date BETWEEN ? AND ?
               {$tenantSql}
             GROUP BY COALESCE(si.tax_rate, 0), COALESCE(s.gst_type, 'none')
             ORDER BY COALESCE(si.tax_rate, 0), COALESCE(s.gst_type, 'none')",
            $params
        )->fetchAll();
    }

    private function fetchPurchaseTaxRows(string $fromDate, string $toDate): array {
        $params = [$fromDate, $toDate];
        $tenantSql = '';
        if (Tenant::id() !== null) {
            $tenantSql = ' AND p.company_id = ? AND pi.company_id = ?';
            $params[] = Tenant::id();
            $params[] = Tenant::id();
        }

        return $this->db->query(
            "SELECT
                COALESCE(pi.tax_rate, 0) AS tax_rate,
                COUNT(DISTINCT p.id) AS voucher_count,
                COALESCE(SUM(pi.subtotal), 0) AS taxable_amount,
                COALESCE(SUM(pi.tax_amount), 0) AS tax_amount
             FROM purchase_items pi
             JOIN purchases p ON p.id = pi.purchase_id
             WHERE p.deleted_at IS NULL
               AND p.status <> 'cancelled'
               AND p.purchase_date BETWEEN ? AND ?
               {$tenantSql}
             GROUP BY COALESCE(pi.tax_rate, 0)
             ORDER BY COALESCE(pi.tax_rate, 0)",
            $params
        )->fetchAll();
    }

    private function fetchSaleReturnTaxRows(string $fromDate, string $toDate): array {
        $params = [$fromDate, $toDate];
        $tenantSql = '';
        if (Tenant::id() !== null) {
            $tenantSql = ' AND sr.company_id = ? AND sri.company_id = ? AND s.company_id = ?';
            $params[] = Tenant::id();
            $params[] = Tenant::id();
            $params[] = Tenant::id();
        }

        return $this->db->query(
            "SELECT
                COALESCE(sri.tax_rate, 0) AS tax_rate,
                COALESCE(s.gst_type, 'none') AS gst_type,
                COUNT(DISTINCT sr.id) AS voucher_count,
                COALESCE(SUM(sri.subtotal), 0) AS taxable_amount,
                COALESCE(SUM(sri.tax_amount), 0) AS tax_amount
             FROM sale_return_items sri
             JOIN sale_returns sr ON sr.id = sri.return_id
             JOIN sales s ON s.id = sr.sale_id
             WHERE sr.deleted_at IS NULL
               AND sr.status = 'posted'
               AND sr.return_date BETWEEN ? AND ?
               {$tenantSql}
             GROUP BY COALESCE(sri.tax_rate, 0), COALESCE(s.gst_type, 'none')
             ORDER BY COALESCE(sri.tax_rate, 0), COALESCE(s.gst_type, 'none')",
            $params
        )->fetchAll();
    }

    private function fetchPurchaseReturnTaxRows(string $fromDate, string $toDate): array {
        $params = [$fromDate, $toDate];
        $tenantSql = '';
        if (Tenant::id() !== null) {
            $tenantSql = ' AND pr.company_id = ? AND pri.company_id = ?';
            $params[] = Tenant::id();
            $params[] = Tenant::id();
        }

        return $this->db->query(
            "SELECT
                COALESCE(pri.tax_rate, 0) AS tax_rate,
                COUNT(DISTINCT pr.id) AS voucher_count,
                COALESCE(SUM(pri.subtotal), 0) AS taxable_amount,
                COALESCE(SUM(pri.tax_amount), 0) AS tax_amount
             FROM purchase_return_items pri
             JOIN purchase_returns pr ON pr.id = pri.return_id
             WHERE pr.deleted_at IS NULL
               AND pr.return_date BETWEEN ? AND ?
               {$tenantSql}
             GROUP BY COALESCE(pri.tax_rate, 0)
             ORDER BY COALESCE(pri.tax_rate, 0)",
            $params
        )->fetchAll();
    }

    private function normalizeDateRange(string $fromDate, string $toDate): array {
        $fromDate = $this->normalizeDate($fromDate, date('Y-m-01'));
        $toDate = $this->normalizeDate($toDate, date('Y-m-d'));
        if (strtotime($fromDate) > strtotime($toDate)) {
            return [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }

    private function normalizeDate(string $date, string $default): string {
        $date = trim($date);
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        $errors = DateTime::getLastErrors();
        if (!$dt || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
            return $default;
        }

        return $dt->format('Y-m-d');
    }

    private function normalizeRate($rate): string {
        return number_format(round((float)$rate, 2), 2, '.', '');
    }

    private function normalizeGstType(string $gstType, float $taxAmount): string {
        $gstType = strtolower(trim($gstType));
        if ($taxAmount <= 0.0) {
            return 'none';
        }
        if ($gstType === 'igst') {
            return 'igst';
        }
        if ($gstType === 'cgst_sgst') {
            return 'cgst_sgst';
        }

        return 'cgst_sgst';
    }

    private function roundBreakdownRow(array $row): array {
        $row['taxable_amount'] = round((float)$row['taxable_amount'], 2);
        $row['tax_amount'] = round((float)$row['tax_amount'], 2);
        return $row;
    }
}
