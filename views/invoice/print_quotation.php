<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php
    $isGst = ($company['enable_gst'] ?? 1) ? true : false;
    $isTaxEnabled = ($company['enable_tax'] ?? 1) ? true : false;
    $documentTitle = 'Quotation';
    $documentNumber = $data['quotation_number'] ?? '';
    $documentDate = $data['quotation_date'] ?? date('Y-m-d');
    $showUnit = ($company['show_unit_on_invoice'] ?? 0) ? true : false;
    $showDiscount = (!isset($company['show_discount_on_invoice']) || !empty($company['show_discount_on_invoice'])) ? true : false;
    $bankDetails = $company['invoice_bank_details'] ?? '';

    // Status badge
    $statusClass = 'status-' . ($data['status'] ?? 'draft');
    $badgeHtml = '<span class="status-badge ' . $statusClass . '">' . strtoupper($data['status'] ?? 'draft') . '</span>';

    // Validity info
    $extraInfo = '';
    if (!empty($data['valid_until'])) {
        $extraInfo = 'Valid Until: ' . Helper::formatDate($data['valid_until']);
    }

    // Terms: use quotation-specific terms if available, otherwise fall back to company terms
    $termsText = $data['terms'] ?? ($company['invoice_terms'] ?? '');
    $noteText = $data['note'] ?? '';
    ?>
    <title><?= Helper::escape($documentTitle) ?> - <?= Helper::escape($documentNumber) ?></title>
    <?php include __DIR__ . '/_partials/_styles.php'; ?>
</head>
<body>
<?php $printLabel = 'Print Quotation'; include __DIR__ . '/_partials/_print_bar.php'; ?>

<div class="invoice">
    <!-- Header -->
    <?php include __DIR__ . '/_partials/_header.php'; ?>

    <!-- Customer Info -->
    <div class="party-info">
        <div>
            <div class="label">Quotation For</div>
            <div class="party-name"><?= Helper::escape($data['customer_name'] ?? '') ?></div>
            <div class="party-detail">
                <?php if (!empty($data['customer_phone'])): ?>Ph: <?= Helper::escape($data['customer_phone']) ?><br><?php endif; ?>
                <?php if (!empty($data['customer_email'])): ?>Email: <?= Helper::escape($data['customer_email']) ?><br><?php endif; ?>
                <?php if (!empty($data['customer_address'])): ?><?= Helper::escape($data['customer_address']) ?><?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <?php if (!empty($data['valid_until'])): ?>
                <div class="label">Valid Until</div>
                <div style="font-weight:600;"><?= Helper::formatDate($data['valid_until']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Product</th>
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
            <td>
                <?= Helper::escape($item['product_name'] ?? '') ?>
                <?php if (!empty($item['sku'])): ?>
                <br><small style="color:#888;">SKU: <?= Helper::escape($item['sku'] ?? '') ?></small>
                <?php endif; ?>
            </td>
            <td style="text-align:center;">
                <?= Helper::formatQty($item['quantity'] ?? 0) ?>
                <?php if ($showUnit && isset($item['unit_name'])): ?> <?= Helper::escape($item['unit_name']) ?><?php endif; ?>
            </td>
            <td style="text-align:right;"><?= Helper::formatCurrency($item['unit_price'] ?? 0) ?></td>
            <?php if ($isTaxEnabled && $isGst): ?>
            <?php if ($showDiscount): ?><td style="text-align:right;"><?= (($item['discount'] ?? 0) > 0) ? Helper::formatCurrency($item['discount'] ?? 0) : '—' ?></td><?php endif; ?>
            <td style="text-align:right;"><?= $item['tax_rate'] ?? 0 ?>%</td>
            <td style="text-align:right;"><?= Helper::formatCurrency($item['tax_amount'] ?? 0) ?></td>
            <?php elseif ($isTaxEnabled && !$isGst): ?>
            <?php if ($showDiscount): ?><td style="text-align:right;"><?= (($item['discount'] ?? 0) > 0) ? Helper::formatCurrency($item['discount'] ?? 0) : '—' ?></td><?php endif; ?>
            <?php else: ?>
            <?php if ($showDiscount): ?><td style="text-align:right;"><?= (($item['discount'] ?? 0) > 0) ? Helper::formatCurrency($item['discount'] ?? 0) : '—' ?></td><?php endif; ?>
            <?php endif; ?>
            <td style="text-align:right; font-weight:600;"><?= Helper::formatCurrency($item['total'] ?? 0) ?></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary-section">
        <div class="summary-left">
            <?php if (!empty($bankDetails)): ?>
            <div class="bank-section">
                <div class="bank-title"><i>🏦</i> Bank Details</div>
                <div class="bank-info"><?= nl2br(Helper::escape($bankDetails)) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <div class="summary">
            <div class="summary-row"><span>Subtotal</span><span><?= Helper::formatCurrency($data['subtotal'] ?? 0) ?></span></div>
            <?php if ($isTaxEnabled && ($data['tax_amount'] ?? 0) > 0): ?>
                <?php if ($isGst): ?>
                <div class="summary-row"><span>CGST</span><span><?= Helper::formatCurrency(($data['tax_amount'] ?? 0) / 2) ?></span></div>
                <div class="summary-row"><span>SGST</span><span><?= Helper::formatCurrency(($data['tax_amount'] ?? 0) / 2) ?></span></div>
                <?php else: ?>
                <div class="summary-row"><span>Tax</span><span><?= Helper::formatCurrency($data['tax_amount']) ?></span></div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (($data['discount_amount'] ?? 0) > 0): ?>
            <div class="summary-row"><span>Discount</span><span>-<?= Helper::formatCurrency($data['discount_amount']) ?></span></div>
            <?php endif; ?>
            <?php if (($data['shipping_cost'] ?? 0) > 0): ?>
            <div class="summary-row"><span>Shipping</span><span><?= Helper::formatCurrency($data['shipping_cost']) ?></span></div>
            <?php endif; ?>
            <div class="summary-row total"><span>Grand Total</span><span><?= Helper::formatCurrency($data['grand_total'] ?? 0) ?></span></div>
        </div>
    </div>

    <!-- Terms -->
    <?php include __DIR__ . '/_partials/_terms.php'; ?>

    <!-- Signature -->
    <?php include __DIR__ . '/_partials/_signature.php'; ?>

    <!-- Footer -->
    <?php include __DIR__ . '/_partials/_footer.php'; ?>
</div>
</body>
</html>
