<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php
    $isGst = ($company['enable_gst'] ?? 1) ? true : false;
    $isReceipt = ($data['type'] ?? 'receipt') === 'receipt';
    $documentTitle = $isReceipt ? 'Payment Receipt' : 'Payment Voucher';
    $documentNumber = $data['payment_number'] ?? '';
    $documentDate = $data['payment_date'] ?? date('Y-m-d');
    $partyName = $data['customer_name'] ?? $data['supplier_name'] ?? '';
    $partyPhone = $data['customer_phone'] ?? $data['supplier_phone'] ?? '';
    $noteText = $data['note'] ?? '';
    ?>
    <title><?= Helper::escape($documentTitle) ?> - <?= Helper::escape($documentNumber) ?></title>
    <?php include __DIR__ . '/_partials/_styles.php'; ?>
</head>
<body>
<?php $printLabel = 'Print Receipt'; include __DIR__ . '/_partials/_print_bar.php'; ?>

<div class="invoice">
    <!-- Header -->
    <?php include __DIR__ . '/_partials/_header.php'; ?>

    <!-- Party Info -->
    <div class="party-info">
        <div>
            <div class="label"><?= $isReceipt ? 'Received From' : 'Paid To' ?></div>
            <div class="party-name"><?= Helper::escape($partyName) ?></div>
            <div class="party-detail">
                <?php if ($partyPhone): ?>Ph: <?= Helper::escape($partyPhone) ?><br><?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <?php if (!empty($data['reference_number'])): ?>
                <div class="label">Reference</div>
                <div><?= Helper::escape($data['reference_number']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment Details -->
    <table class="detail-table">
        <tbody>
            <tr>
                <td class="detail-label">Amount</td>
                <td class="detail-value amount-value"><?= Helper::formatCurrency($data['amount'] ?? 0) ?></td>
            </tr>
            <tr>
                <td class="detail-label">Payment Date</td>
                <td class="detail-value"><?= Helper::formatDate($data['payment_date'] ?? date('Y-m-d')) ?></td>
            </tr>
            <tr>
                <td class="detail-label">Payment Method</td>
                <td class="detail-value"><?= Helper::escape(Helper::paymentMethodLabel($data['payment_method'] ?? 'cash')) ?></td>
            </tr>
            <?php if (!empty($data['bank_name'])): ?>
            <tr>
                <td class="detail-label">Bank</td>
                <td class="detail-value"><?= Helper::escape($data['bank_name']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($data['reference_number'])): ?>
            <tr>
                <td class="detail-label">Reference No.</td>
                <td class="detail-value"><?= Helper::escape($data['reference_number']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($data['note'])): ?>
            <tr>
                <td class="detail-label">Note</td>
                <td class="detail-value" style="font-weight:normal; color:#666;"><?= Helper::escape($data['note']) ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Amount in words box -->
    <div style="background:#f8f9fc; border-radius:8px; padding:14px 16px; margin-bottom:25px;">
        <div style="font-size:11px; color:#4e73df; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
            Amount in Words
        </div>
        <div style="font-size:13px; font-weight:600; color:#333;">
            <?= Helper::escape(Helper::numberToWords($data['amount'] ?? 0)) ?>
        </div>
    </div>

    <!-- Signature -->
    <?php include __DIR__ . '/_partials/_signature.php'; ?>

    <!-- Footer -->
    <?php $noteText = ''; include __DIR__ . '/_partials/_footer.php'; ?>
</div>
</body>
</html>
