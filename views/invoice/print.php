<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php
    // Determine GST mode and invoice title from settings
    $isGst = ($company['enable_gst'] ?? 1) ? true : false;
    $isTaxEnabled = ($company['enable_tax'] ?? 1) ? true : false;
    $invoiceTitle = '';
    if ($type === 'sale') {
        $invoiceTitle = $company['invoice_title'] ?? ($isGst ? 'Tax Invoice' : 'Bill of Supply');
    } else {
        $invoiceTitle = $company['purchase_invoice_title'] ?? 'Purchase Bill';
    }
    $signatureLabel = $company['invoice_signature_label'] ?? 'Authorised Signatory';
    $footerText = $company['invoice_footer_text'] ?? '';
    $invoiceTerms = $company['invoice_terms'] ?? '';
    $bankDetails = $company['invoice_bank_details'] ?? '';
    $currencySymbol = $company['currency_symbol'] ?? '₹';
    $showPaidDue = ($company['show_paid_due_on_invoice'] ?? 1) ? true : false;
    $showUnit = ($company['show_unit_on_invoice'] ?? 0) ? true : false;
    $showDiscount = (!isset($company['show_discount_on_invoice']) || !empty($company['show_discount_on_invoice'])) ? true : false;
    $showHsnOnInvoice = (!isset($company['show_hsn_on_invoice']) || !empty($company['show_hsn_on_invoice'])) ? true : false;
    $returnedAmount = 0.0;
    if ($type === 'sale') {
        $returnedAmount = (float)($returnSummary['returned_amount'] ?? $data['returned_amount'] ?? 0);
    }
    $grandTotal = (float)($data['grand_total'] ?? 0);
    $effectiveTotal = max(0, $grandTotal - $returnedAmount);
    $displayPaid = ($type === 'sale')
        ? min((float)($data['paid_amount'] ?? 0), $effectiveTotal)
        : (float)($data['paid_amount'] ?? 0);
    $displayDue = ($type === 'sale')
        ? max(0, $effectiveTotal - $displayPaid)
        : max(0, (float)($data['due_amount'] ?? 0));
    $isFullyReturned = ($type === 'sale') && $returnedAmount > 0.009 && $effectiveTotal <= 0.009;
    $displayStatus = strtolower((string)($data['payment_status'] ?? 'unpaid'));
    if ($type === 'sale') {
        if ($isFullyReturned) {
            $displayStatus = 'returned';
        } elseif ($displayDue <= 0.009) {
            $displayStatus = 'paid';
        } elseif ($displayPaid > 0.009) {
            $displayStatus = 'partial';
        } else {
            $displayStatus = 'unpaid';
        }
    }
    $freightCharge = (float)($data['freight_charge'] ?? $data['shipping_cost'] ?? 0);
    $loadingCharge = (float)($data['loading_charge'] ?? 0);
    $shippingCharge = (float)($data['shipping_cost'] ?? ($freightCharge + $loadingCharge));
    $hasFreightColumn = array_key_exists('freight_charge', $data);
    $hasLoadingColumn = array_key_exists('loading_charge', $data);
    $showChargeBreakup = $hasFreightColumn || $hasLoadingColumn;
    $normalizeState = static function ($value): string {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$value)));
    };
    $gstType = strtolower((string)($data['gst_type'] ?? 'auto'));
    if (!in_array($gstType, ['cgst_sgst', 'igst', 'none'], true)) {
        $counterpartyState = $type === 'sale'
            ? ($data['customer_state'] ?? '')
            : ($data['supplier_state'] ?? '');
        $companyState = $normalizeState($company['company_state'] ?? '');
        $partyState = $normalizeState($counterpartyState);
        $gstType = ($companyState !== '' && $partyState !== '' && $companyState !== $partyState)
            ? 'igst'
            : 'cgst_sgst';
    }
    if (!$isTaxEnabled || !$isGst) {
        $gstType = 'none';
    }
    $showHsnColumn = $isTaxEnabled && $isGst && $showHsnOnInvoice;
    $cgstAmount = ($gstType === 'cgst_sgst') ? ((float)($data['tax_amount'] ?? 0) / 2) : 0;
    $sgstAmount = ($gstType === 'cgst_sgst') ? ((float)($data['tax_amount'] ?? 0) / 2) : 0;
    $igstAmount = ($gstType === 'igst') ? (float)($data['tax_amount'] ?? 0) : 0;
    $forPdf = !empty($forPdf);
    $formatMoney = static function ($amount) use ($forPdf, $currencySymbol): string {
        return $forPdf
            ? Helper::formatCurrencyPdf($amount, $currencySymbol)
            : Helper::formatCurrency($amount, $currencySymbol);
    };
    $docId = (int)($data['id'] ?? 0);
    $closeUrl = APP_URL . '/index.php?page=dashboard';
    if ($docId > 0) {
        if ($type === 'sale') {
            $closeUrl = APP_URL . '/index.php?page=sales&action=view_sale&id=' . $docId;
        } elseif ($type === 'purchase') {
            $closeUrl = APP_URL . '/index.php?page=purchases&action=view_purchase&id=' . $docId;
        } elseif ($type === 'quotation') {
            $closeUrl = APP_URL . '/index.php?page=quotations&action=detail&id=' . $docId;
        } elseif ($type === 'return') {
            $closeUrl = APP_URL . '/index.php?page=sale_returns&action=detail&id=' . $docId;
        } elseif ($type === 'receipt' || $type === 'payment') {
            $closeUrl = APP_URL . '/index.php?page=payments&action=view_payment&id=' . $docId;
        }
    }
    ?>
    <title><?= Helper::escape($invoiceTitle) ?> - <?= Helper::escape($data['invoice_number'] ?? $data['reference_number'] ?? '') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #333; background: #fff; }
        .invoice { max-width: 800px; margin: 0 auto; padding: 30px; }
        <?php if ($forPdf): ?>
        /* PDF-specific tuning: keep original layout width, only reduce top gap slightly */
        @page { margin: 12mm 10mm 12mm 10mm; }
        body { margin: 0; padding: 0; }
        .invoice { max-width: 800px; margin: 0 auto; padding: 14px 24px 20px; }
        <?php endif; ?>

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 25px; padding-bottom: 18px; border-bottom: 3px solid #4e73df; }
        .header .company-block, .header .invoice-block { display: table-cell; vertical-align: top; }
        .header .company-logo { display: block; max-height: 64px; max-width: 190px; width: auto; height: auto; object-fit: contain; margin-bottom: 8px; }
        .header .company-block { width: 68%; padding-right: 18px; }
        .header .invoice-block { width: 32%; text-align: right; }
        .header .company-block h1 { font-size: 22px; color: #4e73df; margin-bottom: 3px; }
        .header .company-block .company-details { font-size: 11.5px; color: #666; line-height: 1.6; }
        .header .invoice-title { font-size: 14px; font-weight: 700; color: #4e73df; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .header .invoice-number { font-size: 18px; font-weight: bold; color: #333; }
        .header .invoice-date { color: #666; font-size: 12px; margin-top: 2px; }

        /* Party Info */
        .party-info { display: flex; justify-content: space-between; margin-bottom: 22px; padding: 14px 16px; background: #f8f9fc; border-radius: 8px; }
        .party-info .label { font-weight: bold; margin-bottom: 4px; color: #4e73df; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .party-info .party-name { font-weight: 600; font-size: 14px; margin-bottom: 2px; }
        .party-info .party-detail { font-size: 11.5px; color: #666; line-height: 1.5; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
        table th { background: #4e73df; color: #fff; padding: 9px 10px; text-align: left; font-size: 11.5px; font-weight: 600; }
        table th:first-child { border-radius: 4px 0 0 0; }
        table th:last-child { border-radius: 0 4px 0 0; }
        table td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 12.5px; word-break: break-word; }
        table tbody tr:nth-child(even) { background: #fcfcfd; }

        /* Summary */
        .summary-section { display: flex; justify-content: space-between; gap: 30px; margin-bottom: 25px; }
        .summary-left { flex: 1; }
        .summary { width: 280px; flex-shrink: 0; }
        .summary-row { display: table; width: 100%; table-layout: fixed; padding: 5px 0; font-size: 12.5px; }
        .summary-row > span { display: table-cell; vertical-align: middle; }
        .summary-row > span:last-child { text-align: right; white-space: nowrap; padding-left: 14px; }
        .summary-row.total { font-size: 15px; font-weight: bold; border-top: 2px solid #333; padding-top: 10px; margin-top: 5px; }

        /* Bank Details */
        .bank-section { background: #f8f9fc; border-radius: 8px; padding: 12px 14px; font-size: 11.5px; }
        .bank-section .bank-title { font-weight: 700; color: #4e73df; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .bank-section .bank-info { color: #555; white-space: pre-line; line-height: 1.6; }

        /* Terms */
        .terms-section { margin-top: 20px; padding: 12px 14px; border: 1px solid #eee; border-radius: 8px; font-size: 11px; }
        .terms-section .terms-title { font-weight: 700; color: #333; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .terms-section .terms-text { color: #666; white-space: pre-line; line-height: 1.5; }

        /* Signature */
        .signature-section { text-align: right; margin-top: 40px; }
        .signature-section .sig-line { width: 200px; border-top: 1px solid #333; margin-left: auto; margin-bottom: 5px; }
        .signature-section .sig-label { font-size: 11px; color: #555; font-weight: 600; }
        .signature-section .sig-company { font-size: 10px; color: #888; }

        /* Footer */
        .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #eee; text-align: center; font-size: 11px; color: #999; }

        /* Badge */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-unpaid { background: #f8d7da; color: #721c24; }
        .badge-partial { background: #fff3cd; color: #856404; }
        .badge-returned { background: #cfe2ff; color: #084298; }

        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .invoice { padding: 15px; }
        }
    </style>
</head>
<body>
<?php if (!$forPdf): ?>
<!-- Print Bar -->
<div class="no-print" style="text-align:center; padding:12px; background:#f0f0f0; display:flex; justify-content:center; gap:10px;">
    <button id="btnPrint" style="padding:8px 24px; cursor:pointer; border:none; background:#4e73df; color:#fff; border-radius:6px; font-size:13px; font-weight:600;">
        Print Invoice
    </button>
    <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=<?= urlencode($type) ?>&id=<?= $docId ?>" style="padding:8px 24px; cursor:pointer; border:none; background:#1cc88a; color:#fff; border-radius:6px; font-size:13px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center;">
        Download PDF
    </a>
    <button id="btnClose" style="padding:8px 24px; cursor:pointer; border:1px solid #ccc; background:#fff; border-radius:6px; font-size:13px;">
        Close
    </button>
</div>
<?php endif; ?>

<div class="invoice">
    <!-- ===== HEADER ===== -->
    <div class="header">
        <div class="company-block">
            <?php if (!empty($company['company_logo'])): ?>
                <img src="<?= APP_URL ?>/<?= Helper::escape($company['company_logo']) ?>" alt="Company Logo" class="company-logo">
            <?php endif; ?>
            <h1><?= Helper::escape($company['company_name'] ?? APP_NAME) ?></h1>
            <div class="company-details">
                <?php if (!empty($company['company_address'])): ?>
                    <?= Helper::escape($company['company_address']) ?><br>
                <?php endif; ?>
                <?php
                $cityState = trim(($company['company_city'] ?? '') . ', ' . ($company['company_state'] ?? ''), ', ');
                if ($cityState): ?>
                    <?= Helper::escape($cityState) ?>
                    <?php if (!empty($company['company_zip'])): ?> - <?= Helper::escape($company['company_zip']) ?><?php endif; ?><br>
                <?php endif; ?>
                <?php if (!empty($company['company_phone'])): ?>
                    Phone: <?= Helper::escape($company['company_phone']) ?><br>
                <?php endif; ?>
                <?php if (!empty($company['company_email'])): ?>
                    Email: <?= Helper::escape($company['company_email']) ?><br>
                <?php endif; ?>
                <?php if ($isGst && !empty($company['tax_number'])): ?>
                    <strong>GSTIN: <?= Helper::escape($company['tax_number']) ?></strong>
                <?php elseif (!$isGst && !empty($company['tax_number'])): ?>
                    <?= Helper::escape($company['tax_number']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="invoice-block">
            <div class="invoice-title"><?= Helper::escape($invoiceTitle) ?></div>
            <div class="invoice-number"><?= Helper::escape($data['invoice_number'] ?? $data['reference_number'] ?? '') ?></div>
            <div class="invoice-date">
                Date: <?= Helper::formatDate($data[$type === 'sale' ? 'sale_date' : 'purchase_date'] ?? date('Y-m-d')) ?>
            </div>
            <?php if ($showPaidDue): ?>
            <div style="margin-top:5px;">
                <span class="badge badge-<?= Helper::escape($displayStatus) ?>"><?= strtoupper(Helper::escape($displayStatus)) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== PARTY INFO ===== -->
    <div class="party-info">
        <div>
            <div class="label"><?= $type === 'sale' ? 'Bill To' : 'Supplier' ?></div>
            <div class="party-name"><?= Helper::escape($data[$type === 'sale' ? 'customer_name' : 'supplier_name'] ?? 'N/A') ?></div>
            <div class="party-detail">
                <?php 
                $partyPhone = $data[$type === 'sale' ? 'customer_phone' : 'supplier_phone'] ?? '';
                $partyAddress = $data[$type === 'sale' ? 'customer_address' : 'supplier_address'] ?? '';
                $partyGst = $data[$type === 'sale' ? 'customer_tax_number' : 'supplier_tax_number'] ?? '';
                ?>
                <?php if ($partyPhone): ?>Ph: <?= Helper::escape($partyPhone) ?><br><?php endif; ?>
                <?php if ($partyAddress): ?><?= Helper::escape($partyAddress) ?><br><?php endif; ?>
                <?php if ($isGst && $partyGst): ?>GSTIN: <?= Helper::escape($partyGst) ?><?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <?php if (!empty($data['reference_number'])): ?>
                <div class="label">Reference</div>
                <div><?= Helper::escape($data['reference_number']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== ITEMS TABLE ===== -->
    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Product</th>
                <?php if ($showHsnColumn): ?><th style="text-align:left; width:80px;">HSN/SAC</th><?php endif; ?>
                <th style="text-align:center; width:60px;">Qty</th>
                <th style="text-align:right; width:90px;">Rate</th>
                <?php if ($isTaxEnabled && $isGst): ?>
                <?php if ($showDiscount): ?><th style="text-align:right; width:70px;">Disc</th><?php endif; ?>
                <th style="text-align:right; width:60px;">GST %</th>
                <th style="text-align:right; width:80px;">GST Amt</th>
                <?php elseif ($isTaxEnabled && !$isGst): ?>
                <?php if ($showDiscount): ?><th style="text-align:right; width:70px;">Disc</th><?php endif; ?>
                <?php else: ?>
                <?php if ($showDiscount): ?><th style="text-align:right; width:70px;">Disc</th><?php endif; ?>
                <?php endif; ?>
                <th style="text-align:right; width:90px;">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($data['items'])): $i=0; foreach ($data['items'] as $item): $i++; ?>
        <tr>
            <td><?= $i ?></td>
            <td><?= Helper::escape($item['product_name'] ?? '') ?></td>
            <?php if ($showHsnColumn): ?><td><?= !empty($item['hsn_code']) ? Helper::escape($item['hsn_code']) : '-' ?></td><?php endif; ?>
            <td style="text-align:center;">
                <?= Helper::formatQty($item['quantity'] ?? 0) ?>
                <?php if ($showUnit && isset($item['unit_name'])): ?> <?= Helper::escape($item['unit_name']) ?><?php endif; ?>
            </td>
            <td style="text-align:right;"><?= $formatMoney($item['unit_price'] ?? 0) ?></td>
            <?php if ($isTaxEnabled && $isGst): ?>
            <?php if ($showDiscount): ?><td style="text-align:right;"><?= (($item['discount'] ?? 0) > 0) ? $formatMoney($item['discount'] ?? 0) : '-' ?></td><?php endif; ?>
            <td style="text-align:right;"><?= $item['tax_rate'] ?? 0 ?>%</td>
            <td style="text-align:right;"><?= $formatMoney($item['tax_amount'] ?? 0) ?></td>
            <?php elseif ($isTaxEnabled && !$isGst): ?>
            <?php if ($showDiscount): ?><td style="text-align:right;"><?= (($item['discount'] ?? 0) > 0) ? $formatMoney($item['discount'] ?? 0) : '-' ?></td><?php endif; ?>
            <?php else: ?>
            <?php if ($showDiscount): ?><td style="text-align:right;"><?= (($item['discount'] ?? 0) > 0) ? $formatMoney($item['discount'] ?? 0) : '-' ?></td><?php endif; ?>
            <?php endif; ?>
            <td style="text-align:right; font-weight:600;"><?= $formatMoney($item['total'] ?? 0) ?></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- ===== SUMMARY SECTION ===== -->
    <div class="summary-section">
        <div class="summary-left">
            <?php if (!empty($bankDetails)): ?>
            <div class="bank-section">
                <div class="bank-title">Bank Details</div>
                <div class="bank-info"><?= nl2br(Helper::escape($bankDetails)) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="summary">
            <div class="summary-row"><span>Subtotal</span><span><?= $formatMoney($data['subtotal'] ?? 0) ?></span></div>
            <?php if ($isTaxEnabled && ($data['tax_amount'] ?? 0) > 0): ?>
                <?php if ($isGst && $gstType === 'igst'): ?>
                <div class="summary-row"><span>IGST</span><span><?= $formatMoney($igstAmount) ?></span></div>
                <?php elseif ($isGst && $gstType === 'cgst_sgst'): ?>
                <div class="summary-row"><span>CGST</span><span><?= $formatMoney($cgstAmount) ?></span></div>
                <div class="summary-row"><span>SGST</span><span><?= $formatMoney($sgstAmount) ?></span></div>
                <?php else: ?>
                <div class="summary-row"><span>Tax</span><span><?= $formatMoney($data['tax_amount']) ?></span></div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (($data['discount_amount'] ?? 0) > 0): ?>
            <div class="summary-row"><span>Discount</span><span>-<?= $formatMoney($data['discount_amount']) ?></span></div>
            <?php endif; ?>
            <?php if ($showChargeBreakup): ?>
                <?php if ($freightCharge > 0): ?>
                <div class="summary-row"><span>Freight</span><span><?= $formatMoney($freightCharge) ?></span></div>
                <?php endif; ?>
                <?php if ($loadingCharge > 0): ?>
                <div class="summary-row"><span>Loading</span><span><?= $formatMoney($loadingCharge) ?></span></div>
                <?php endif; ?>
                <?php if ($freightCharge > 0 && $loadingCharge > 0): ?>
                <div class="summary-row"><span>Total Charges</span><span><?= $formatMoney($shippingCharge) ?></span></div>
                <?php endif; ?>
            <?php elseif (($data['shipping_cost'] ?? 0) > 0): ?>
            <div class="summary-row"><span>Shipping</span><span><?= $formatMoney($data['shipping_cost']) ?></span></div>
            <?php endif; ?>
            <?php if (isset($data['round_off']) && $data['round_off'] != 0): ?>
            <div class="summary-row"><span>Round Off</span><span><?= $formatMoney($data['round_off']) ?></span></div>
            <?php endif; ?>
            <div class="summary-row total"><span>Grand Total</span><span><?= $formatMoney($grandTotal) ?></span></div>
            <?php if ($type === 'sale' && $returnedAmount > 0): ?>
            <div class="summary-row"><span>Returned</span><span style="color:#fd7e14;">-<?= $formatMoney($returnedAmount) ?></span></div>
            <div class="summary-row"><span>Net Total</span><span><?= $formatMoney($effectiveTotal) ?></span></div>
            <?php endif; ?>
            <?php if ($showPaidDue): ?>
            <div class="summary-row"><span>Paid</span><span style="color:#28a745;"><?= $formatMoney($displayPaid) ?></span></div>
            <?php if ($displayDue > 0): ?>
            <div class="summary-row"><span>Balance Due</span><span style="color:#dc3545; font-weight:600;"><?= $formatMoney($displayDue) ?></span></div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== TERMS ===== -->
    <?php if (!empty($invoiceTerms)): ?>
    <div class="terms-section">
        <div class="terms-title">Terms & Conditions</div>
        <div class="terms-text"><?= nl2br(Helper::escape($invoiceTerms)) ?></div>
    </div>
    <?php endif; ?>

    <!-- ===== SIGNATURE ===== -->
    <div class="signature-section">
        <div class="sig-line"></div>
        <div class="sig-label"><?= Helper::escape($signatureLabel) ?></div>
        <div class="sig-company">For <?= Helper::escape($company['company_name'] ?? APP_NAME) ?></div>
    </div>

    <!-- ===== FOOTER ===== -->
    <div class="footer">
        <?php if (!empty($data['note'])): ?>
        <p style="margin-bottom:8px; color:#666;">Note: <?= Helper::escape($data['note']) ?></p>
        <?php endif; ?>
        <?php if (!empty($footerText)): ?>
        <p><?= Helper::escape($footerText) ?></p>
        <?php else: ?>
        <p>Thank you for your business! | <?= Helper::escape($company['company_name'] ?? APP_NAME) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php if (!$forPdf): ?>
<script nonce="<?= $cspNonce ?>">
    const printBtn = document.getElementById('btnPrint');
    const closeBtn = document.getElementById('btnClose');
    const closeUrl = <?= json_encode($closeUrl) ?>;

    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            if (window.history.length > 1) {
                window.history.back();
                setTimeout(function() {
                    // Fallback when opened directly in a separate tab.
                    if (document.visibilityState === 'visible') {
                        window.location.href = closeUrl;
                    }
                }, 300);
                return;
            }
            window.location.href = closeUrl;
        });
    }
</script>
<?php endif; ?>
</body>
</html>

