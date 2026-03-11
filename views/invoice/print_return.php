<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php
    $isGst = ($company['enable_gst'] ?? 1) ? true : false;
    $documentTitle = 'Credit Note';
    $documentNumber = $data['return_number'] ?? '';
    $documentDate = $data['return_date'] ?? date('Y-m-d');
    $noteText = $data['reason'] ?? '';
    ?>
    <title><?= Helper::escape($documentTitle) ?> - <?= Helper::escape($documentNumber) ?></title>
    <?php include __DIR__ . '/_partials/_styles.php'; ?>
</head>
<body>
<?php $printLabel = 'Print Credit Note'; include __DIR__ . '/_partials/_print_bar.php'; ?>

<div class="invoice">
    <!-- Header -->
    <?php include __DIR__ . '/_partials/_header.php'; ?>

    <!-- Party Info -->
    <div class="party-info">
        <div>
            <div class="label">Customer</div>
            <div class="party-name"><?= Helper::escape($data['customer_name'] ?? '') ?></div>
            <div class="party-detail">
                <?php if (!empty($data['customer_phone'])): ?>Ph: <?= Helper::escape($data['customer_phone']) ?><br><?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <div class="label">Against Invoice</div>
            <div style="font-weight:600;"><?= Helper::escape($data['invoice_number'] ?? 'N/A') ?></div>
            <?php if (!empty($data['reason'])): ?>
            <div style="margin-top:6px;">
                <div class="label">Reason</div>
                <div style="font-size:12px; color:#666;"><?= Helper::escape($data['reason']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Returned Items Table -->
    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Product</th>
                <th style="text-align:center; width:80px;">Qty Returned</th>
                <th style="text-align:right; width:100px;">Unit Price</th>
                <th style="text-align:right; width:100px;">Total</th>
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
            <td style="text-align:center;"><?= Helper::formatQty($item['quantity'] ?? 0) ?></td>
            <td style="text-align:right;"><?= Helper::formatCurrency($item['unit_price'] ?? 0) ?></td>
            <td style="text-align:right; font-weight:600;"><?= Helper::formatCurrency($item['total'] ?? 0) ?></td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary-section">
        <div class="summary-left"></div>
        <div class="summary">
            <div class="summary-row total">
                <span>Total Refund</span>
                <span style="color:#dc3545;"><?= Helper::formatCurrency($data['total_amount'] ?? 0) ?></span>
            </div>
        </div>
    </div>

    <!-- Signature -->
    <?php include __DIR__ . '/_partials/_signature.php'; ?>

    <!-- Footer -->
    <?php $noteText = ''; include __DIR__ . '/_partials/_footer.php'; ?>
</div>
</body>
</html>
