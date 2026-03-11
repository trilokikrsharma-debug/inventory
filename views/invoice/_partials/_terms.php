<?php
/**
 * Shared Terms & Conditions Partial
 *
 * Expected variables:
 *   $termsText - (optional) Terms text, defaults to company invoice_terms
 *   $company   - Company settings array (fallback for terms)
 */
$termsText = $termsText ?? ($company['invoice_terms'] ?? '');
?>
<?php if (!empty($termsText)): ?>
<div class="terms-section">
    <div class="terms-title">Terms & Conditions</div>
    <div class="terms-text"><?= nl2br(Helper::escape($termsText)) ?></div>
</div>
<?php endif; ?>
