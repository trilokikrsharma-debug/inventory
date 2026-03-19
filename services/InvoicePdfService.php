<?php
/**
 * PDF Invoice Generation Service
 * 
 * Generates professional PDF invoices using Dompdf.
 * Falls back to an HTML download if Dompdf is not installed.
 * 
 * Usage:
 *   $pdf = InvoicePdfService::generate($saleId);
 *   InvoicePdfService::download($saleId);   // Force browser download
 *   InvoicePdfService::stream($saleId);     // Inline PDF in browser
 */
class InvoicePdfService {
    /**
     * Generate a PDF for a sale invoice.
     * 
     * @param int $saleId  Sale ID
     * @return string      Raw PDF binary data
     */
    public static function generate(int $saleId): string {
        $data = self::fetchInvoiceData($saleId);
        $html = self::renderTemplate($data);

        // Use Dompdf if available
        if (class_exists('\\Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return $dompdf->output();
        }

        // Fallback: return HTML (caller can force download as .html)
        return $html;
    }

    /**
     * Force-download PDF to browser.
     */
    public static function download(int $saleId): void {
        $data = self::fetchInvoiceData($saleId);
        $filename = 'Invoice_' . ($data['invoice_number'] ?? $saleId) . '.pdf';
        $content = self::generate($saleId);

        $isPdf = class_exists('\\Dompdf\\Dompdf');

        header('Content-Type: ' . ($isPdf ? 'application/pdf' : 'text/html'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0');
        echo $content;
        exit;
    }

    /**
     * Stream PDF inline in browser.
     */
    public static function stream(int $saleId): void {
        $data = self::fetchInvoiceData($saleId);
        $filename = 'Invoice_' . ($data['invoice_number'] ?? $saleId) . '.pdf';
        $content = self::generate($saleId);

        $isPdf = class_exists('\\Dompdf\\Dompdf');

        header('Content-Type: ' . ($isPdf ? 'application/pdf' : 'text/html'));
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    /**
     * Fetch all data needed for the invoice.
     */
    private static function fetchInvoiceData(int $saleId): array {
        $db = Database::getInstance();
        $user = Session::get('user');
        $companyId = $user['company_id'] ?? 0;

        // Sale header
        $sale = $db->query(
            "SELECT s.*, c.name AS customer_name, c.email AS customer_email,
                    c.phone AS customer_phone, c.address AS customer_address,
                    c.city AS customer_city, c.state AS customer_state,
                    c.zip AS customer_zip, c.tax_number AS customer_tax_number
             FROM sales s
             LEFT JOIN customers c ON s.customer_id = c.id
             WHERE s.id = ? AND s.company_id = ?",
            [$saleId, $companyId]
        )->fetch(\PDO::FETCH_ASSOC);

        if (!$sale) {
            throw new \RuntimeException("Invoice not found: {$saleId}");
        }

        // Sale items
        $items = $db->query(
            "SELECT si.*, p.name AS product_name, p.sku, u.short_name AS unit_name
             FROM sale_items si
             LEFT JOIN products p ON si.product_id = p.id
             LEFT JOIN units u ON p.unit_id = u.id
             WHERE si.sale_id = ?",
            [$saleId]
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Company settings
        $settings = $db->query(
            "SELECT * FROM company_settings WHERE id = 1 LIMIT 1"
        )->fetch(\PDO::FETCH_ASSOC);

        return array_merge($sale, [
            'items' => $items,
            'settings' => $settings ?? [],
        ]);
    }

    /**
     * Render the HTML invoice template.
     */
    private static function renderTemplate(array $data): string {
        $s = $data['settings'];
        $currency = Helper::pdfCurrencySymbol($s['currency_symbol'] ?? '₹');
        $companyName = Helper::escape($s['company_name'] ?? 'My Company');
        $invoiceTitle = Helper::escape($s['invoice_title'] ?? 'Tax Invoice');
        $invoiceNumber = Helper::escape($data['invoice_number'] ?? '');
        $customerName = Helper::escape($data['customer_name'] ?? '');

        // Format date
        $dateFormat = $s['date_format'] ?? 'd-m-Y';
        $saleDate = date($dateFormat, strtotime($data['sale_date']));

        // Build items table rows
        $itemRows = '';
        $sn = 0;
        foreach ($data['items'] as $item) {
            $sn++;
            $itemRows .= sprintf(
                '<tr>
                    <td style="text-align:center">%d</td>
                    <td>%s<br><small style="color:#666">%s</small></td>
                    <td style="text-align:center">%s %s</td>
                    <td style="text-align:right">%s%s</td>
                    <td style="text-align:right">%s%s</td>
                    <td style="text-align:center">%s%%</td>
                    <td style="text-align:right">%s%s</td>
                    <td style="text-align:right"><strong>%s%s</strong></td>
                </tr>',
                $sn,
                Helper::escape($item['product_name'] ?? ''),
                Helper::escape($item['sku'] ?? ''),
                number_format((float)($item['quantity'] ?? 0), 2),
                Helper::escape($item['unit_name'] ?? 'pcs'),
                $currency, number_format((float)($item['unit_price'] ?? 0), 2),
                $currency, number_format((float)($item['discount'] ?? 0), 2),
                number_format((float)($item['tax_rate'] ?? 0), 2),
                $currency, number_format((float)($item['tax_amount'] ?? 0), 2),
                $currency, number_format((float)($item['total'] ?? 0), 2)
            );
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{$invoiceTitle} - {$invoiceNumber}</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size:12px; color:#333; }
    .invoice { max-width:800px; margin:0 auto; padding:30px; }
    .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:30px; border-bottom:3px solid #4e73df; padding-bottom:20px; }
    .company-info h1 { font-size:22px; color:#4e73df; margin-bottom:5px; }
    .company-info p { color:#555; font-size:11px; line-height:1.6; }
    .invoice-meta { text-align:right; }
    .invoice-meta h2 { font-size:28px; color:#4e73df; text-transform:uppercase; letter-spacing:2px; }
    .invoice-meta table { margin-top:10px; }
    .invoice-meta td { padding:3px 0; font-size:11px; }
    .invoice-meta td:first-child { color:#888; padding-right:15px; }
    .parties { display:flex; justify-content:space-between; margin-bottom:25px; }
    .party { width:48%; }
    .party h3 { font-size:11px; text-transform:uppercase; color:#888; margin-bottom:8px; letter-spacing:1px; }
    .party p { font-size:12px; line-height:1.6; }
    .items-table { width:100%; border-collapse:collapse; margin-bottom:25px; }
    .items-table th { background:#4e73df; color:white; padding:10px 8px; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; }
    .items-table td { padding:8px; border-bottom:1px solid #eee; font-size:11px; }
    .items-table tr:nth-child(even) { background:#f8f9fc; }
    .totals { float:right; width:300px; margin-bottom:30px; }
    .totals table { width:100%; }
    .totals td { padding:6px 10px; font-size:12px; }
    .totals tr:last-child { border-top:2px solid #333; font-size:14px; font-weight:bold; }
    .totals tr:last-child td { padding-top:10px; }
    .footer { clear:both; border-top:1px solid #ddd; padding-top:20px; margin-top:40px; }
    .footer-grid { display:flex; justify-content:space-between; }
    .footer-section { width:48%; }
    .footer-section h4 { font-size:11px; color:#888; margin-bottom:8px; text-transform:uppercase; }
    .footer-section p { font-size:11px; line-height:1.6; color:#555; }
    .badge { display:inline-block; padding:4px 12px; border-radius:12px; font-size:10px; font-weight:bold; text-transform:uppercase; }
    .badge-paid { background:#d4edda; color:#155724; }
    .badge-unpaid { background:#f8d7da; color:#721c24; }
    .badge-partial { background:#fff3cd; color:#856404; }
    @media print { body { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; } }
</style>
</head>
<body>
<div class="invoice">
    <div class="header">
        <div class="company-info">
            <h1>{$companyName}</h1>
            <p>
HTML;

        if (!empty($s['company_address'])) $html .= Helper::escape($s['company_address']) . '<br>';
        if (!empty($s['company_city'])) $html .= Helper::escape($s['company_city']);
        if (!empty($s['company_state'])) $html .= ', ' . Helper::escape($s['company_state']);
        if (!empty($s['company_zip'])) $html .= ' - ' . Helper::escape($s['company_zip']);
        $html .= '<br>';
        if (!empty($s['company_phone'])) $html .= 'Phone: ' . Helper::escape($s['company_phone']) . '<br>';
        if (!empty($s['company_email'])) $html .= 'Email: ' . Helper::escape($s['company_email']) . '<br>';
        if (!empty($s['tax_number'])) $html .= 'GSTIN: ' . Helper::escape($s['tax_number']);

        $paymentBadge = match($data['payment_status'] ?? 'unpaid') {
            'paid' => '<span class="badge badge-paid">Paid</span>',
            'partial' => '<span class="badge badge-partial">Partial</span>',
            default => '<span class="badge badge-unpaid">Unpaid</span>',
        };

        $html .= <<<HTML
            </p>
        </div>
        <div class="invoice-meta">
            <h2>{$invoiceTitle}</h2>
            <table>
                <tr><td>Invoice #</td><td><strong>{$invoiceNumber}</strong></td></tr>
                <tr><td>Date</td><td>{$saleDate}</td></tr>
                <tr><td>Status</td><td>{$paymentBadge}</td></tr>
            </table>
        </div>
    </div>

    <div class="parties">
        <div class="party">
            <h3>Bill To</h3>
            <p>
                <strong>{$customerName}</strong><br>
HTML;

        if (!empty($data['customer_address'])) $html .= Helper::escape($data['customer_address']) . '<br>';
        if (!empty($data['customer_city'])) $html .= Helper::escape($data['customer_city']);
        if (!empty($data['customer_state'])) $html .= ', ' . Helper::escape($data['customer_state']);
        $html .= '<br>';
        if (!empty($data['customer_phone'])) $html .= 'Phone: ' . Helper::escape($data['customer_phone']) . '<br>';
        if (!empty($data['customer_email'])) $html .= 'Email: ' . Helper::escape($data['customer_email']) . '<br>';
        if (!empty($data['customer_tax_number'])) $html .= 'GSTIN: ' . Helper::escape($data['customer_tax_number']);

        $html .= <<<HTML
            </p>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:28%">Product</th>
                <th style="width:10%">Qty</th>
                <th style="width:12%">Price</th>
                <th style="width:10%">Discount</th>
                <th style="width:8%">Tax%</th>
                <th style="width:12%">Tax Amt</th>
                <th style="width:15%">Total</th>
            </tr>
        </thead>
        <tbody>
            {$itemRows}
        </tbody>
    </table>
HTML;

        $subtotalStr = number_format_safe($data['subtotal'] ?? 0);
        $discountStr = number_format_safe($data['discount_amount'] ?? 0);
        $taxStr = number_format_safe($data['tax_amount'] ?? 0);
        $grandTotalStr = number_format_safe($data['grand_total'] ?? 0);

        $html .= <<<HTML
    <div class="totals">
        <table>
            <tr><td>Subtotal</td><td style="text-align:right">{$currency}{$subtotalStr}</td></tr>
            <tr><td>Discount</td><td style="text-align:right">-{$currency}{$discountStr}</td></tr>
            <tr><td>Tax</td><td style="text-align:right">{$currency}{$taxStr}</td></tr>
HTML;
        if (!empty($data['shipping_cost']) && $data['shipping_cost'] > 0) {
            $html .= "<tr><td>Shipping</td><td style=\"text-align:right\">{$currency}" . number_format((float)$data['shipping_cost'], 2) . "</td></tr>";
        }
        if (!empty($data['round_off']) && $data['round_off'] != 0) {
            $html .= "<tr><td>Round Off</td><td style=\"text-align:right\">{$currency}" . number_format((float)$data['round_off'], 2) . "</td></tr>";
        }

        $html .= <<<HTML
            <tr><td>Grand Total</td><td style="text-align:right">{$currency}{$grandTotalStr}</td></tr>
        </table>
    </div>

    <div class="footer">
        <div class="footer-grid">
HTML;

        if (!empty($s['invoice_bank_details'])) {
            $html .= '<div class="footer-section"><h4>Bank Details</h4><p>' . nl2br(Helper::escape($s['invoice_bank_details'])) . '</p></div>';
        }
        if (!empty($s['invoice_terms'])) {
            $html .= '<div class="footer-section"><h4>Terms & Conditions</h4><p>' . nl2br(Helper::escape($s['invoice_terms'])) . '</p></div>';
        }

        $signLabel = $s['invoice_signature_label'] ?? 'Authorised Signatory';
        if (!empty($s['invoice_footer_text'])) {
            $html .= '<div style="width:100%; text-align:center; margin-top:20px; font-size:11px; color:#888;">' . Helper::escape($s['invoice_footer_text']) . '</div>';
        }

        $html .= <<<HTML
        </div>
        <div style="text-align:right; margin-top:40px;">
            <div style="border-top:1px solid #333; display:inline-block; padding-top:5px; min-width:200px;">
                {$signLabel}
            </div>
        </div>
    </div>
</div>
</body>
</html>
HTML;

        return $html;
    }
}

/**
 * Helper: safe number format that handles null/empty values.
 */
function number_format_safe($value): string {
    return number_format((float)($value ?? 0), 2);
}



