<?php
/**
 * Voucher PDF Service - Universal Document Generator
 * 
 * Generates professional A4 PDFs for all business documents:
 *   - Sales Invoice (INV), Purchase Invoice (PUR)
 *   - Payment Receipt (REC), Payment Voucher (PAY)
 *   - Sales Return Credit Note (SRN), Quotation (QTN)
 *   - Customer / Supplier Statements
 * 
 * Usage:
 *   VoucherPdfService::download('sale', $id);
 *   VoucherPdfService::stream('purchase', $id);
 *   VoucherPdfService::downloadStatement('customer', $id, $from, $to);
 */
class VoucherPdfService {

    private static array $typeConfig = [
        'sale' => [
            'title' => 'Tax Invoice', 'prefix' => 'INV',
            'numField' => 'invoice_number', 'dateField' => 'sale_date',
            'partyLabel' => 'Bill To', 'partyType' => 'customer',
        ],
        'purchase' => [
            'title' => 'Purchase Invoice', 'prefix' => 'PUR',
            'numField' => 'reference_number', 'dateField' => 'purchase_date',
            'partyLabel' => 'Supplier', 'partyType' => 'supplier',
        ],
        'receipt' => [
            'title' => 'Payment Receipt', 'prefix' => 'REC',
            'numField' => 'payment_number', 'dateField' => 'payment_date',
            'partyLabel' => 'Received From', 'partyType' => 'customer',
        ],
        'payment' => [
            'title' => 'Payment Voucher', 'prefix' => 'PAY',
            'numField' => 'payment_number', 'dateField' => 'payment_date',
            'partyLabel' => 'Paid To', 'partyType' => 'supplier',
        ],
        'return' => [
            'title' => 'Credit Note', 'prefix' => 'SRN',
            'numField' => 'return_number', 'dateField' => 'return_date',
            'partyLabel' => 'Customer', 'partyType' => 'customer',
        ],
        'quotation' => [
            'title' => 'Quotation', 'prefix' => 'QTN',
            'numField' => 'quotation_number', 'dateField' => 'quotation_date',
            'partyLabel' => 'To', 'partyType' => 'customer',
        ],
    ];

    // â”€â”€â”€ Public API â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function download(string $type, int $id): void {
        $html = self::render($type, $id);
        $config = self::$typeConfig[$type] ?? self::$typeConfig['sale'];
        $data = self::fetchData($type, $id);
        $number = $data[$config['numField']] ?? $id;
        $filename = $config['prefix'] . '_' . $number . '.pdf';

        $content = self::htmlToPdf($html);
        $isPdf = class_exists('\\Dompdf\\Dompdf');

        header('Content-Type: ' . ($isPdf ? 'application/pdf' : 'text/html'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0');
        echo $content;
        exit;
    }

    public static function stream(string $type, int $id): void {
        $html = self::render($type, $id);
        $content = self::htmlToPdf($html);
        $isPdf = class_exists('\\Dompdf\\Dompdf');

        header('Content-Type: ' . ($isPdf ? 'application/pdf' : 'text/html'));
        header('Content-Disposition: inline');
        echo $content;
        exit;
    }

    public static function downloadStatement(string $partyType, int $id, string $from = '', string $to = ''): void {
        $html = self::renderStatement($partyType, $id, $from, $to);
        $content = self::htmlToPdf($html);
        $isPdf = class_exists('\\Dompdf\\Dompdf');
        $prefix = match ($partyType) {
            'customer' => 'CUST',
            'supplier' => 'SUPP',
            'receipt'  => 'REC',
            'payment'  => 'PAY',
            default    => 'LEDGER',
        };

        header('Content-Type: ' . ($isPdf ? 'application/pdf' : 'text/html'));
        $suffix = $id > 0 ? '_' . $id : '';
        header('Content-Disposition: attachment; filename="' . $prefix . '_Statement' . $suffix . '.pdf"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    // â”€â”€â”€ Render Dispatcher â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public static function render(string $type, int $id): string {
        $config = self::$typeConfig[$type] ?? null;
        if (!$config) throw new \RuntimeException("Unknown voucher type: {$type}");

        $data = self::fetchData($type, $id);
        $company = self::fetchCompany();

        if (in_array($type, ['receipt', 'payment'])) {
            return self::renderPaymentVoucher($data, $company, $config);
        }
        return self::renderItemVoucher($data, $company, $config);
    }

    // â”€â”€â”€ Data Fetching â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function fetchData(string $type, int $id): array {
        $modelMap = [
            'sale' => 'SalesModel', 'purchase' => 'PurchaseModel',
            'receipt' => 'PaymentModel', 'payment' => 'PaymentModel',
            'return' => 'SaleReturnModel', 'quotation' => 'QuotationModel',
        ];
        $model = new ($modelMap[$type] ?? 'SalesModel')();
        $data = $model->getWithDetails($id);
        if (!$data) throw new \RuntimeException("Record not found: {$type} #{$id}");
        return $data;
    }

    private static function fetchCompany(): array {
        return (new SettingsModel())->getSettings() ?: [];
    }

    // â”€â”€â”€ Item-Based Voucher (Sale/Purchase/Return/Quotation) â”€â”€â”€â”€â”€

    private static function renderItemVoucher(array $data, array $co, array $config): string {
        $currency    = Helper::pdfCurrencySymbol($co['currency_symbol'] ?? '₹');
        $companyName = Helper::escape($co['company_name'] ?? 'My Company');
        $companyAddr = self::formatAddress($co);
        $vTitle      = $config['title'];
        $vNum        = Helper::escape($data[$config['numField']] ?? 'N/A');
        $vDate       = isset($data[$config['dateField']])
            ? date($co['date_format'] ?? 'd-m-Y', strtotime($data[$config['dateField']]))
            : date('d-m-Y');
        $partyLabel  = $config['partyLabel'];

        // Party
        $pName    = Helper::escape($data['customer_name'] ?? $data['supplier_name'] ?? 'N/A');
        $pAddr    = Helper::escape($data['customer_address'] ?? $data['supplier_address'] ?? '');
        $pCity    = Helper::escape($data['customer_city'] ?? $data['supplier_city'] ?? '');
        $pState   = Helper::escape($data['customer_state'] ?? $data['supplier_state'] ?? '');
        $pPhone   = Helper::escape($data['customer_phone'] ?? $data['supplier_phone'] ?? '');
        $pEmail   = Helper::escape($data['customer_email'] ?? $data['supplier_email'] ?? '');
        $pTax     = Helper::escape($data['customer_tax_number'] ?? $data['supplier_tax_number'] ?? '');

        // Build party block
        $partyHtml = "<strong>{$pName}</strong>";
        if ($pAddr) $partyHtml .= "<br>{$pAddr}";
        $cityLine = $pCity . ($pCity && $pState ? ', ' : '') . $pState;
        if ($cityLine) $partyHtml .= "<br>{$cityLine}";
        if ($pPhone) $partyHtml .= "<br>Phone: {$pPhone}";
        if ($pEmail) $partyHtml .= "<br>Email: {$pEmail}";
        if ($pTax) $partyHtml .= "<br>GSTIN: {$pTax}";

        // Status badge
        $statusHtml = '';
        $ps = $data['payment_status'] ?? $data['status'] ?? '';
        if ($ps) {
            $bc = match ($ps) {
                'paid', 'accepted', 'completed' => 'badge-paid',
                'partial', 'sent' => 'badge-partial',
                default => 'badge-unpaid',
            };
            $psLabel = ucfirst($ps);
            $statusHtml = "<tr><td>Status</td><td><span class=\"badge {$bc}\">{$psLabel}</span></td></tr>";
        }

        // Item rows
        $items = $data['items'] ?? [];
        $itemRows = '';
        foreach ($items as $i => $item) {
            $n = $i + 1;
            $pn = Helper::escape($item['product_name'] ?? '');
            $sk = Helper::escape($item['sku'] ?? '');
            $qty = number_format((float)($item['quantity'] ?? 0), 2);
            $un = Helper::escape($item['unit_name'] ?? 'pcs');
            $up = $currency . number_format((float)($item['unit_price'] ?? 0), 2);
            $disc = $currency . number_format((float)($item['discount'] ?? 0), 2);
            $tot = $currency . number_format((float)($item['total'] ?? 0), 2);
            $itemRows .= "<tr><td style='text-align:center'>{$n}</td><td>{$pn}<br><small style='color:#666'>{$sk}</small></td><td style='text-align:center'>{$qty} {$un}</td><td style='text-align:right'>{$up}</td><td style='text-align:right'>{$disc}</td><td style='text-align:right'><strong>{$tot}</strong></td></tr>";
        }

        // Totals
        $subtotal  = $currency . self::nf($data['subtotal'] ?? $data['total_amount'] ?? 0);
        $grandTotal = $currency . self::nf($data['grand_total'] ?? $data['total_amount'] ?? 0);
        $discountRow = '';
        if (isset($data['discount_amount']) && $data['discount_amount'] > 0) {
            $dv = $currency . self::nf($data['discount_amount']);
            $discountRow = "<tr><td>Discount</td><td style='text-align:right'>-{$dv}</td></tr>";
        }
        $taxRow = '';
        if (isset($data['tax_amount']) && $data['tax_amount'] > 0) {
            $tv = $currency . self::nf($data['tax_amount']);
            $taxRow = "<tr><td>Tax</td><td style='text-align:right'>{$tv}</td></tr>";
        }
        $shipRow = '';
        if (isset($data['shipping_cost']) && $data['shipping_cost'] > 0) {
            $sv = $currency . self::nf($data['shipping_cost']);
            $shipRow = "<tr><td>Shipping</td><td style='text-align:right'>{$sv}</td></tr>";
        }

        // Footer
        $bankHtml = '';
        if (!empty($co['invoice_bank_details'])) {
            $bd = nl2br(Helper::escape($co['invoice_bank_details']));
            $bankHtml = "<div class='footer-section'><h4>Bank Details</h4><p>{$bd}</p></div>";
        }
        $termsHtml = '';
        if (!empty($co['invoice_terms'])) {
            $td = nl2br(Helper::escape($co['invoice_terms']));
            $termsHtml = "<div class='footer-section'><h4>Terms & Conditions</h4><p>{$td}</p></div>";
        }
        $notesHtml = '';
        $noteText = $data['note'] ?? $data['reason'] ?? '';
        if ($noteText) {
            $nt = Helper::escape($noteText);
            $notesHtml = "<div style='margin-top:15px'><h4 style='font-size:11px;color:#888;text-transform:uppercase'>Notes</h4><p style='font-size:11px;color:#555'>{$nt}</p></div>";
        }
        $signLabel = Helper::escape($co['invoice_signature_label'] ?? 'Authorised Signatory');

        $body = <<<HTML
<div class="header">
    <div class="company-info">
        <h1>{$companyName}</h1>
        <p>{$companyAddr}</p>
    </div>
    <div class="invoice-meta">
        <h2>{$vTitle}</h2>
        <table>
            <tr><td>Number</td><td><strong>{$vNum}</strong></td></tr>
            <tr><td>Date</td><td>{$vDate}</td></tr>
            {$statusHtml}
        </table>
    </div>
</div>

<div class="parties">
    <div class="party">
        <h3>{$partyLabel}</h3>
        <p>{$partyHtml}</p>
    </div>
</div>

<table class="items-table">
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:35%">Product</th>
            <th style="width:15%">Qty</th>
            <th style="width:15%">Price</th>
            <th style="width:15%">Discount</th>
            <th style="width:15%">Total</th>
        </tr>
    </thead>
    <tbody>{$itemRows}</tbody>
</table>

<div class="totals">
    <table>
        <tr><td>Subtotal</td><td style="text-align:right">{$subtotal}</td></tr>
        {$discountRow}
        {$taxRow}
        {$shipRow}
        <tr><td>Grand Total</td><td style="text-align:right">{$grandTotal}</td></tr>
    </table>
</div>

<div class="footer">
    <div class="footer-grid">{$bankHtml}{$termsHtml}</div>
    {$notesHtml}
    <div style="text-align:right;margin-top:40px">
        <div style="border-top:1px solid #333;display:inline-block;padding-top:5px;min-width:200px">{$signLabel}</div>
    </div>
</div>
HTML;

        return self::wrapHtml("{$vTitle} - {$vNum}", $body);
    }

    // â”€â”€â”€ Payment/Receipt Voucher â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function renderPaymentVoucher(array $data, array $co, array $config): string {
        $currency    = Helper::pdfCurrencySymbol($co['currency_symbol'] ?? '₹');
        $companyName = Helper::escape($co['company_name'] ?? 'My Company');
        $companyAddr = self::formatAddress($co);
        $vTitle      = $config['title'];
        $vNum        = Helper::escape($data[$config['numField']] ?? 'N/A');
        $vDate       = isset($data[$config['dateField']])
            ? date($co['date_format'] ?? 'd-m-Y', strtotime($data[$config['dateField']]))
            : date('d-m-Y');
        $partyLabel  = $config['partyLabel'];
        $pName       = Helper::escape($data['customer_name'] ?? $data['supplier_name'] ?? 'N/A');
        $amount      = $currency . self::nf($data['amount'] ?? 0);
        $method      = Helper::escape(Helper::paymentMethodLabel($data['payment_method'] ?? 'cash'));
        $ref         = Helper::escape($data['reference_number'] ?? '');
        $bankName    = Helper::escape($data['bank_name'] ?? '');
        $note        = Helper::escape($data['note'] ?? '');
        $signLabel   = Helper::escape($co['invoice_signature_label'] ?? 'Authorised Signatory');

        $linkedDoc = '';
        if (!empty($data['invoice_number'])) {
            $inv = Helper::escape($data['invoice_number']);
            $linkedDoc = "Against Invoice: {$inv}";
        }

        $refRow = $ref ? "<tr><td style='padding:8px 0;color:#666'>Reference</td><td style='padding:8px 0'>{$ref}</td></tr>" : '';
        $bankRow = $bankName ? "<tr><td style='padding:8px 0;color:#666'>Bank</td><td style='padding:8px 0'>{$bankName}</td></tr>" : '';
        $linkedRow = $linkedDoc ? "<tr><td style='padding:8px 0;color:#666'>Linked Document</td><td style='padding:8px 0'>{$linkedDoc}</td></tr>" : '';
        $noteRow = $note ? "<tr><td style='padding:8px 0;color:#666'>Note</td><td style='padding:8px 0'>{$note}</td></tr>" : '';

        $body = <<<HTML
<div class="header">
    <div class="company-info">
        <h1>{$companyName}</h1>
        <p>{$companyAddr}</p>
    </div>
    <div class="invoice-meta">
        <h2>{$vTitle}</h2>
        <table>
            <tr><td>Number</td><td><strong>{$vNum}</strong></td></tr>
            <tr><td>Date</td><td>{$vDate}</td></tr>
        </table>
    </div>
</div>

<div style="margin:30px 0;padding:25px;background:#f8f9fc;border-radius:8px">
    <table style="width:100%;font-size:13px">
        <tr><td style="padding:8px 0;color:#666;width:35%">{$partyLabel}</td><td style="padding:8px 0"><strong>{$pName}</strong></td></tr>
        <tr><td style="padding:8px 0;color:#666">Amount</td><td style="padding:8px 0;font-size:24px;font-weight:bold;color:#4e73df">{$amount}</td></tr>
        <tr><td style="padding:8px 0;color:#666">Payment Method</td><td style="padding:8px 0">{$method}</td></tr>
        {$refRow}
        {$bankRow}
        {$linkedRow}
        {$noteRow}
    </table>
</div>

<div style="text-align:right;margin-top:60px">
    <div style="border-top:1px solid #333;display:inline-block;padding-top:5px;min-width:200px">{$signLabel}</div>
</div>
HTML;

        return self::wrapHtml("{$vTitle} - {$vNum}", $body);
    }

    // Statement Template

    private static function renderStatement(string $partyType, int $id, string $from, string $to): string {
        $co = self::fetchCompany();
        $currency    = Helper::pdfCurrencySymbol($co['currency_symbol'] ?? '₹');
        $companyName = Helper::escape($co['company_name'] ?? 'My Company');
        $companyAddr = self::formatAddress($co);
        $signLabel   = Helper::escape($co['invoice_signature_label'] ?? 'Authorised Signatory');
        $fromTsRaw = $from ? strtotime($from) : false;
        $toTsRaw = $to ? strtotime($to) : false;
        $fromDate = $fromTsRaw ? date('Y-m-d', $fromTsRaw) : '';
        $toDate = $toTsRaw ? date('Y-m-d', $toTsRaw) : '';
        if ($fromDate && $toDate)       $dateRange = date('d-m-Y', strtotime($fromDate)) . ' to ' . date('d-m-Y', strtotime($toDate));
        elseif ($fromDate)              $dateRange = 'From ' . date('d-m-Y', strtotime($fromDate));
        elseif ($toDate)                $dateRange = 'Up to ' . date('d-m-Y', strtotime($toDate));
        else                            $dateRange = 'All Transactions';

        $title = '';
        $partyLabel = 'Party';
        $partyNameEscaped = '';
        $openingBalance = 0.0;
        $closingBalance = 0.0;
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $rows = '';

        if (in_array($partyType, ['customer', 'supplier'], true)) {
            $partyLabel = ucfirst($partyType);
            if ($partyType === 'customer') {
                $party = (new CustomerModel())->find($id);
                $allLedger = (new CustomerModel())->getLedger($id);
                $title = 'Customer Statement';
            } else {
                $party = (new SupplierModel())->find($id);
                $allLedger = (new SupplierModel())->getLedger($id);
                $title = 'Supplier Statement';
            }

            if (!$party) {
                throw new \RuntimeException("{$partyType} not found: {$id}");
            }

            $partyNameEscaped = Helper::escape($party['name'] ?? 'N/A');
            $openingBalance = (float)($party['opening_balance'] ?? 0);

            $fromTs = $fromDate ? strtotime($fromDate . ' 00:00:00') : null;
            $toTs = $toDate ? strtotime($toDate . ' 23:59:59') : null;
            $filteredEntries = [];

            foreach ((array)$allLedger as $entry) {
                $entryTs = strtotime((string)($entry['date'] ?? $entry['created_at'] ?? 'now'));
                $debit = (float)($entry['debit'] ?? 0);
                $credit = (float)($entry['credit'] ?? 0);

                if ($fromTs !== null && $entryTs < $fromTs) {
                    $openingBalance += $debit - $credit;
                    continue;
                }
                if ($toTs !== null && $entryTs > $toTs) {
                    continue;
                }

                $filteredEntries[] = $entry;
            }

            $runningBalance = $openingBalance;
            foreach ($filteredEntries as $entry) {
                $debit = (float)($entry['debit'] ?? 0);
                $credit = (float)($entry['credit'] ?? 0);
                $runningBalance += $debit - $credit;
                $totalDebit += $debit;
                $totalCredit += $credit;

                $eDate = date('d-m-Y', strtotime((string)($entry['date'] ?? $entry['created_at'] ?? 'now')));
                $eDesc = Helper::escape($entry['description'] ?? $entry['type'] ?? '');
                $eRef  = Helper::escape($entry['reference'] ?? $entry['reference_number'] ?? '');
                $eDebit  = $debit > 0 ? ($currency . ' ' . self::nf($debit)) : '';
                $eCredit = $credit > 0 ? ($currency . ' ' . self::nf($credit)) : '';
                $eBal    = $currency . ' ' . self::nf($runningBalance);
                $rows .= "<tr><td>{$eDate}</td><td>{$eDesc}</td><td>{$eRef}</td><td style='text-align:right'>{$eDebit}</td><td style='text-align:right'>{$eCredit}</td><td style='text-align:right'><strong>{$eBal}</strong></td></tr>";
            }

            $closingBalance = $runningBalance;
        } else {
            $isReceiptRegister = $partyType === 'receipt';
            $title = $isReceiptRegister ? 'Receipt Register' : 'Payment Register';
            $partyLabel = 'Register';
            $partyNameEscaped = $isReceiptRegister ? 'All Customers' : 'All Suppliers';

            $db = Database::getInstance();
            $params = [$isReceiptRegister ? 'receipt' : 'payment'];
            $where = "p.type = ? AND p.deleted_at IS NULL";
            if (Tenant::id() !== null) {
                $where .= " AND p.company_id = ?";
                $params[] = Tenant::id();
            }

            if ($fromDate) {
                $openingParams = $params;
                $openingParams[] = $fromDate;
                $openingBalance = (float)$db->query(
                    "SELECT COALESCE(SUM(p.amount), 0)
                     FROM payments p
                     WHERE {$where} AND p.payment_date < ?",
                    $openingParams
                )->fetchColumn();
            }

            if ($fromDate) {
                $where .= " AND p.payment_date >= ?";
                $params[] = $fromDate;
            }
            if ($toDate) {
                $where .= " AND p.payment_date <= ?";
                $params[] = $toDate;
            }

            $ledger = $db->query(
                "SELECT p.payment_date, p.payment_number, p.reference_number, p.amount, p.payment_method, p.bank_name,
                        c.name AS customer_name, s.name AS supplier_name
                 FROM payments p
                 LEFT JOIN customers c ON p.customer_id = c.id
                 LEFT JOIN suppliers s ON p.supplier_id = s.id
                 WHERE {$where}
                 ORDER BY p.payment_date ASC, p.id ASC",
                $params
            )->fetchAll(\PDO::FETCH_ASSOC);

            $runningBalance = $openingBalance;
            foreach ((array)$ledger as $entry) {
                $amount = (float)($entry['amount'] ?? 0);
                $runningBalance += $amount;

                $debit = $isReceiptRegister ? 0.0 : $amount;
                $credit = $isReceiptRegister ? $amount : 0.0;
                $totalDebit += $debit;
                $totalCredit += $credit;

                $partyName = $isReceiptRegister
                    ? (string)($entry['customer_name'] ?? 'Walk-in Customer')
                    : (string)($entry['supplier_name'] ?? 'Supplier');
                $methodLabel = Helper::paymentMethodLabel($entry['payment_method'] ?? 'cash');
                $description = ($isReceiptRegister ? 'Receipt' : 'Payment') . ' - ' . $partyName . ' (' . $methodLabel . ')';

                $refParts = [(string)($entry['payment_number'] ?? '')];
                if (!empty($entry['reference_number'])) {
                    $refParts[] = 'Ref: ' . (string)$entry['reference_number'];
                }
                if (!empty($entry['bank_name'])) {
                    $refParts[] = 'Bank: ' . (string)$entry['bank_name'];
                }

                $eDate = date('d-m-Y', strtotime((string)($entry['payment_date'] ?? 'now')));
                $eDesc = Helper::escape($description);
                $eRef = Helper::escape(trim(implode(' | ', array_filter($refParts))));
                $eDebit = $debit > 0 ? ($currency . ' ' . self::nf($debit)) : '';
                $eCredit = $credit > 0 ? ($currency . ' ' . self::nf($credit)) : '';
                $eBal = $currency . ' ' . self::nf($runningBalance);
                $rows .= "<tr><td>{$eDate}</td><td>{$eDesc}</td><td>{$eRef}</td><td style='text-align:right'>{$eDebit}</td><td style='text-align:right'>{$eCredit}</td><td style='text-align:right'><strong>{$eBal}</strong></td></tr>";
            }

            $closingBalance = $runningBalance;
        }

        $genDate = date('d-m-Y H:i');
        $openBal = $currency . ' ' . self::nf($openingBalance);
        $closeBal = $currency . ' ' . self::nf($closingBalance);
        $totalDebitText = $currency . ' ' . self::nf($totalDebit);
        $totalCreditText = $currency . ' ' . self::nf($totalCredit);
        if ($rows === '') {
            $rows = "<tr><td colspan='6' style='text-align:center;color:#777;padding:14px;'>No transactions found for the selected period.</td></tr>";
        }

        $body = <<<HTML
<div class="header">
    <div class="company-info">
        <h1>{$companyName}</h1>
        <p>{$companyAddr}</p>
    </div>
    <div class="invoice-meta">
        <h2>{$title}</h2>
        <table>
            <tr><td>{$partyLabel}</td><td><strong>{$partyNameEscaped}</strong></td></tr>
            <tr><td>Period</td><td>{$dateRange}</td></tr>
            <tr><td>Generated</td><td>{$genDate}</td></tr>
        </table>
    </div>
</div>

<table class="items-table" style="margin-top:25px">
    <thead>
        <tr>
            <th style="width:15%">Date</th>
            <th style="width:25%">Description</th>
            <th style="width:15%">Reference</th>
            <th style="width:15%">Debit</th>
            <th style="width:15%">Credit</th>
            <th style="width:15%">Balance</th>
        </tr>
    </thead>
    <tbody>
        <tr style="background:#e8f4fd"><td colspan="5"><strong>Opening Balance</strong></td><td style="text-align:right"><strong>{$openBal}</strong></td></tr>
        {$rows}
        <tr style="background:#f4f6fb;font-weight:bold"><td colspan="3">Period Totals</td><td style="text-align:right">{$totalDebitText}</td><td style="text-align:right">{$totalCreditText}</td><td style="text-align:right"></td></tr>
        <tr style="background:#f8f9fc;font-weight:bold"><td colspan="5">Closing Balance</td><td style="text-align:right">{$closeBal}</td></tr>
    </tbody>
</table>

<div style="text-align:right;margin-top:50px">
    <div style="border-top:1px solid #333;display:inline-block;padding-top:5px;min-width:200px">{$signLabel}</div>
</div>
HTML;

        return self::wrapHtml("{$title} - {$partyNameEscaped}", $body);
    }

    // â”€â”€â”€ HTML/PDF Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private static function htmlToPdf(string $html): string {
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
        return $html;
    }

    private static function wrapHtml(string $title, string $body): string {
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>{$title}</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:12px; color:#333; }
.invoice { max-width:800px; margin:0 auto; padding:30px; }
.header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:30px; border-bottom:3px solid #4e73df; padding-bottom:20px; }
.company-info h1 { font-size:22px; color:#4e73df; margin-bottom:5px; }
.company-info p { color:#555; font-size:11px; line-height:1.6; }
.invoice-meta { text-align:right; }
.invoice-meta h2 { font-size:24px; color:#4e73df; text-transform:uppercase; letter-spacing:2px; }
.invoice-meta td { padding:3px 0; font-size:11px; }
.invoice-meta td:first-child { color:#888; padding-right:15px; }
.parties { display:flex; justify-content:space-between; margin-bottom:25px; }
.party { width:48%; }
.party h3 { font-size:11px; text-transform:uppercase; color:#888; margin-bottom:8px; letter-spacing:1px; }
.party p { font-size:12px; line-height:1.6; }
.items-table { width:100%; border-collapse:collapse; margin-bottom:25px; }
.items-table th { background:#4e73df; color:white; padding:10px 8px; font-size:10px; text-transform:uppercase; letter-spacing:0.5px; }
    .items-table td { padding:8px; border-bottom:1px solid #eee; font-size:11px; word-break:break-word; }
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
</style></head><body><div class="invoice">{$body}</div></body></html>
HTML;
    }

    private static function formatAddress(array $co): string {
        $parts = [];
        if (!empty($co['company_address'])) $parts[] = Helper::escape($co['company_address']);
        $cs = '';
        if (!empty($co['company_city'])) $cs .= Helper::escape($co['company_city']);
        if (!empty($co['company_state'])) $cs .= ($cs ? ', ' : '') . Helper::escape($co['company_state']);
        if (!empty($co['company_zip'])) $cs .= ' - ' . Helper::escape($co['company_zip']);
        if ($cs) $parts[] = $cs;
        if (!empty($co['company_phone'])) $parts[] = 'Phone: ' . Helper::escape($co['company_phone']);
        if (!empty($co['company_email'])) $parts[] = 'Email: ' . Helper::escape($co['company_email']);
        if (!empty($co['tax_number'])) $parts[] = 'GSTIN: ' . Helper::escape($co['tax_number']);
        return implode('<br>', $parts);
    }

    private static function nf(float|int|string|null $value): string {
        return number_format((float)($value ?? 0), 2);
    }
}



