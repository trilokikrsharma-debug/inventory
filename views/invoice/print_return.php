<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php
    $isGst = ($company['enable_gst'] ?? 1) ? true : false;
    $documentTitle = (($data['status'] ?? 'posted') === 'cancelled') ? 'Cancelled Credit Note' : 'Credit Note';
    $documentNumber = $data['return_number'] ?? '';
    $documentDate = $data['return_date'] ?? date('Y-m-d');
    $noteText = $data['note'] ?? '';
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
        <div class="text-right">
            <?php if (($data['status'] ?? 'posted') === 'cancelled'): ?>
            <div class="space-bottom-6"><strong style="color:#b42318;">Status:</strong> <span style="color:#b42318;">Cancelled</span></div>
            <?php endif; ?>
            <div class="label">Against Invoice</div>
            <div class="font-semibold"><?= Helper::escape($data['invoice_number'] ?? 'N/A') ?></div>
            <?php if (!empty($data['note'])): ?>
            <div class="space-top-6">
                <div class="label">Reason</div>
                <div class="meta-small"><?= Helper::escape($data['note']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($data['cancel_reason'])): ?>
            <div class="space-top-6">
                <div class="label">Cancellation Reason</div>
                <div class="meta-small"><?= Helper::escape($data['cancel_reason']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Returned Items Table -->
    <table>
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th>Product</th>
                <th class="col-qty-wide">Qty Returned</th>
                <th class="col-money-xl">Unit Price</th>
                <th class="col-money-xl">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($data['items'])): $i=0; foreach ($data['items'] as $item): $i++; ?>
        <tr>
            <td><?= $i ?></td>
            <td>
                <?= Helper::escape($item['product_name'] ?? '') ?>
                <?php if (!empty($item['sku'])): ?>
                <br><small class="sku-note">SKU: <?= Helper::escape($item['sku'] ?? '') ?></small>
                <?php endif; ?>
            </td>
            <td class="td-center"><?= Helper::formatQty($item['quantity'] ?? 0) ?></td>
            <td class="td-right"><?= Helper::formatCurrency($item['unit_price'] ?? 0) ?></td>
            <td class="td-right td-strong"><?= Helper::formatCurrency($item['total'] ?? 0) ?></td>
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
                <span class="total-refund"><?= Helper::formatCurrency($data['total_amount'] ?? 0) ?></span>
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
