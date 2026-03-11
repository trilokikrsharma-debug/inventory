<?php
/**
 * Shared Company Header Partial
 *
 * Expected variables:
 *   $company        - Company settings array
 *   $documentTitle  - e.g. 'Tax Invoice', 'Payment Receipt', 'Credit Note'
 *   $documentNumber - e.g. 'INV-00042', 'PAY-00008'
 *   $documentDate   - Formatted date string (raw, will be formatted here)
 *   $isGst          - (optional) Whether GST is enabled
 *   $badgeHtml      - (optional) HTML for status/payment badge
 *   $extraInfo      - (optional) Extra lines below date (e.g. validity for quotations)
 */
$isGst = $isGst ?? (($company['enable_gst'] ?? 1) ? true : false);
$badgeHtml = $badgeHtml ?? '';
$extraInfo = $extraInfo ?? '';
?>
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
        <div class="invoice-title"><?= Helper::escape($documentTitle) ?></div>
        <div class="invoice-number"><?= Helper::escape($documentNumber) ?></div>
        <div class="invoice-date">Date: <?= Helper::formatDate($documentDate) ?></div>
        <?php if ($badgeHtml): ?>
        <div style="margin-top:5px;"><?= $badgeHtml ?></div>
        <?php endif; ?>
        <?php if ($extraInfo): ?>
        <div style="margin-top:3px; font-size:11px; color:#666;"><?= $extraInfo ?></div>
        <?php endif; ?>
    </div>
</div>
