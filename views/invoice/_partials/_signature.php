<?php
/**
 * Shared Signature Partial
 *
 * Expected variables:
 *   $company - Company settings array
 */
$signatureLabel = $company['invoice_signature_label'] ?? 'Authorised Signatory';
?>
<div class="signature-section">
    <div class="sig-line"></div>
    <div class="sig-label"><?= Helper::escape($signatureLabel) ?></div>
    <div class="sig-company">For <?= Helper::escape($company['company_name'] ?? APP_NAME) ?></div>
</div>
