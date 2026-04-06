<?php
/**
 * Shared Footer Partial
 *
 * Expected variables:
 *   $company  - Company settings array
 *   $noteText - (optional) Note text to display above footer
 */
$footerText = $company['invoice_footer_text'] ?? '';
$noteText = $noteText ?? '';
$notesLabel = trim((string)($company['invoice_notes_label'] ?? 'Notes'));
if ($notesLabel === '') {
    $notesLabel = 'Notes';
}
?>
<div class="footer">
    <?php if (!empty($noteText)): ?>
    <p style="margin-bottom:8px; color:#666;"><?= Helper::escape($notesLabel) ?>: <?= Helper::escape($noteText) ?></p>
    <?php endif; ?>
    <?php if (!empty($footerText)): ?>
    <p><?= Helper::escape($footerText) ?></p>
    <?php else: ?>
    <p>Thank you for your business! | <?= Helper::escape($company['company_name'] ?? APP_NAME) ?></p>
    <?php endif; ?>
</div>
