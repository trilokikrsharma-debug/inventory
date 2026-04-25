<?php
$pageTitle = 'Settings';
$allowedTabs = ['company', 'tax', 'invoice', 'prefixes'];
$activeTab = $_GET['tab'] ?? 'company';
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'company';
}
$previewCurrency = (string)($settings['currency_symbol'] ?? 'Rs. ');
$previewTaxEnabled = !isset($settings['enable_tax']) || !empty($settings['enable_tax']);
$previewGstEnabled = !isset($settings['enable_gst']) || !empty($settings['enable_gst']);
$previewShowPaidDue = !isset($settings['show_paid_due_on_invoice']) || !empty($settings['show_paid_due_on_invoice']);
$previewShowPaymentStatus = !isset($settings['invoice_show_payment_status']) || !empty($settings['invoice_show_payment_status']);
$previewShowUnit = !empty($settings['show_unit_on_invoice']);
$previewShowDiscount = !isset($settings['show_discount_on_invoice']) || !empty($settings['show_discount_on_invoice']);
$previewShowHsn = !isset($settings['show_hsn_on_invoice']) || !empty($settings['show_hsn_on_invoice']);
?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Settings</li></ol></nav></div>

<style>
    .settings-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .settings-tab {
        padding: 0.6rem 1.2rem;
        border-radius: 10px;
        border: 1px solid var(--border-color, #e3e6f0);
        background: var(--card-bg, #fff);
        color: var(--text-primary, #2d3436);
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }
    .settings-tab:hover {
        border-color: #4e73df;
        color: #4e73df;
    }
    .settings-tab.active {
        background: linear-gradient(135deg, #4e73df, #224abe);
        color: #fff;
        border-color: transparent;
    }
    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    .toggle-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e3e6f0);
        border-radius: 14px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    .toggle-card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    }
    .toggle-card .toggle-info h6 {
        margin: 0 0 0.2rem;
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--text-primary, #2d3436);
    }
    .toggle-card .toggle-info p {
        margin: 0;
        font-size: 0.82rem;
        color: var(--text-secondary, #636e72);
    }
    .form-switch-lg .form-check-input {
        width: 3em;
        height: 1.5em;
        cursor: pointer;
    }

    .gst-fields { transition: opacity 0.3s ease; }
    .gst-fields.disabled { opacity: 0.4; pointer-events: none; }
    .settings-hidden { display: none !important; }
    .settings-table-cell-hidden { display: none; }
    .settings-block-hidden { display: none; }
    .settings-dimmed { opacity: 0.4; pointer-events: none; }

    .section-divider {
        border: 0;
        height: 1px;
        background: var(--border-color, #e3e6f0);
        margin: 1.5rem 0;
    }
    .section-label {
        font-weight: 700;
        font-size: 0.8rem;
        color: var(--text-secondary, #636e72);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .settings-preview-image-logo { max-height: 80px; }
    .settings-preview-image-signature { max-height: 70px; }
    .settings-preview-image-seal { max-height: 85px; }
    .settings-icon-primary { color: #4e73df; }
    .settings-icon-success { color: #28a745; }
    .settings-icon-info { color: #17a2b8; }
    .settings-icon-brand { color: #0d6efd; }
    .settings-icon-purple { color: #6f42c1; }
    .settings-icon-danger { color: #dc3545; }
    .settings-icon-orange { color: #fd7e14; }
    .settings-icon-teal { color: #20c997; }
    .settings-alert-note {
        border-radius: 12px;
        font-size: 0.85rem;
    }
    .settings-help-copy {
        font-size: 0.88rem;
        color: var(--text-secondary, #636e72);
    }
    .settings-help-copy strong {
        color: var(--text-primary, #2d3436);
    }
    .settings-preview-card-body {
        font-size: 0.8rem;
    }
    .settings-preview-shell {
        border: 1px solid var(--border-color, #e3e6f0);
        border-radius: 10px;
        padding: 1rem;
        background: var(--bg-card, #fff);
    }
    .settings-preview-head {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #4e73df;
    }
    .settings-preview-company {
        color: #4e73df;
        font-size: 0.9rem;
    }
    .settings-preview-company-address,
    .settings-preview-number,
    .settings-preview-signature {
        font-size: 0.7rem;
        color: #888;
    }
    .settings-preview-right {
        text-align: right;
    }
    .settings-preview-title {
        font-weight: 700;
        font-size: 0.75rem;
        color: #4e73df;
        text-transform: uppercase;
    }
    .settings-preview-subtitle {
        font-size: 0.68rem;
        color: #6c757d;
        margin-top: 2px;
    }
    .settings-preview-status-wrap {
        margin-top: 4px;
    }
    .settings-preview-status-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.65rem;
        font-weight: 700;
        background: #f8d7da;
        color: #721c24;
    }
    .settings-preview-table {
        width: 100%;
        font-size: 0.7rem;
        border-collapse: collapse;
    }
    .settings-preview-table-head {
        background: #4e73df;
        color: #fff;
    }
    .settings-preview-cell {
        padding: 4px;
    }
    .settings-preview-cell-sm {
        padding: 3px;
    }
    .settings-preview-text-center { text-align: center; }
    .settings-preview-text-right { text-align: right; }
    .settings-preview-text-left { text-align: left; }
    .settings-preview-summary {
        text-align: right;
        margin-top: 0.5rem;
        font-size: 0.75rem;
        border-top: 1px solid #eee;
        padding-top: 0.5rem;
        color: #555;
    }
    .settings-preview-grand-total {
        font-weight: 700;
        font-size: 0.8rem;
        color: #222;
    }
    .settings-preview-paid {
        color: #28a745;
    }
    .settings-preview-due {
        color: #dc3545;
        font-weight: 600;
    }
    .settings-preview-signature-block {
        text-align: right;
        margin-top: 1rem;
        padding-top: 0.5rem;
        border-top: 1px dashed #ddd;
        font-size: 0.7rem;
        color: #888;
    }
    .settings-preview-note {
        font-size: 0.75rem;
    }
    .settings-save-btn {
        border-radius: 10px;
        font-weight: 600;
    }
</style>

<form method="POST" enctype="multipart/form-data"><?= CSRF::field() ?>

<!-- Settings Tabs -->
<div class="settings-tabs">
    <a class="settings-tab <?= $activeTab === 'company' ? 'active' : '' ?>" data-tab="company" href="<?= APP_URL ?>/index.php?page=settings&tab=company">
        <i class="fas fa-building"></i> Company
    </a>
    <a class="settings-tab <?= $activeTab === 'tax' ? 'active' : '' ?>" data-tab="tax" href="<?= APP_URL ?>/index.php?page=settings&tab=tax">
        <i class="fas fa-percent"></i> Tax & GST
    </a>
    <a class="settings-tab <?= $activeTab === 'invoice' ? 'active' : '' ?>" data-tab="invoice" href="<?= APP_URL ?>/index.php?page=settings&tab=invoice">
        <i class="fas fa-file-invoice"></i> Invoice
    </a>
    <a class="settings-tab <?= $activeTab === 'prefixes' ? 'active' : '' ?>" data-tab="prefixes" href="<?= APP_URL ?>/index.php?page=settings&tab=prefixes">
        <i class="fas fa-hashtag"></i> Prefixes
    </a>
</div>

<!-- ========== TAB 1: Company ========== -->
<div class="tab-pane <?= $activeTab === 'company' ? 'active' : '' ?>" id="tab-company">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-building me-2"></i>Company Information</h6></div>
                <div class="card-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Company Name</label><input type="text" name="company_name" class="form-control" value="<?= Helper::escape($settings['company_name'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="company_phone" class="form-control" value="<?= Helper::escape($settings['company_phone'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="company_email" class="form-control" value="<?= Helper::escape($settings['company_email'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Website</label><input type="text" name="company_website" class="form-control" value="<?= Helper::escape($settings['company_website'] ?? '') ?>"></div>
                    <div class="col-12"><label class="form-label">Address</label><textarea name="company_address" class="form-control" rows="2"><?= Helper::escape($settings['company_address'] ?? '') ?></textarea></div>
                    <div class="col-md-4"><label class="form-label">City</label><input type="text" name="company_city" class="form-control" value="<?= Helper::escape($settings['company_city'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">State</label><input type="text" name="company_state" class="form-control" value="<?= Helper::escape($settings['company_state'] ?? '') ?>"></div>
                    <div class="col-md-4"><label class="form-label">ZIP Code</label><input type="text" name="company_zip" class="form-control" value="<?= Helper::escape($settings['company_zip'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Country</label><input type="text" name="company_country" class="form-control" value="<?= Helper::escape($settings['company_country'] ?? 'India') ?>"></div>
                </div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-image me-2"></i>Logo</h6></div>
                <div class="card-body text-center">
                    <?php $companyLogoSrc = Helper::uploadedImageSrc($settings['company_logo'] ?? ''); ?>
                    <?php if ($companyLogoSrc !== ''): ?><img src="<?= Helper::escape($companyLogoSrc) ?>" class="img-fluid mb-2 settings-preview-image-logo"><?php endif; ?>
                    <input type="file" name="company_logo" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-signature me-2"></i>Seal & Signature</h6></div>
                <div class="card-body">
                    <div class="mb-3 text-center">
                        <?php $signaturePreviewSrc = Helper::uploadedImageSrc($settings['invoice_signature_image'] ?? ''); ?>
                        <?php if ($signaturePreviewSrc !== ''): ?><img src="<?= Helper::escape($signaturePreviewSrc) ?>" class="img-fluid mb-2 settings-preview-image-signature"><?php endif; ?>
                        <label class="form-label">Signature Image</label>
                        <input type="file" name="invoice_signature_image" class="form-control" accept="image/*">
                        <small class="text-muted">Best results: transparent PNG, landscape signature.</small>
                    </div>
                    <div class="text-center">
                        <?php $sealPreviewSrc = Helper::uploadedImageSrc($settings['invoice_seal_image'] ?? ''); ?>
                        <?php if ($sealPreviewSrc !== ''): ?><img src="<?= Helper::escape($sealPreviewSrc) ?>" class="img-fluid mb-2 settings-preview-image-seal"><?php endif; ?>
                        <label class="form-label">Seal Image</label>
                        <input type="file" name="invoice_seal_image" class="form-control" accept="image/*">
                        <small class="text-muted">Best results: square PNG with transparent background.</small>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-coins me-2"></i>Currency</h6></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Currency Symbol</label><input type="text" name="currency_symbol" class="form-control" value="<?= Helper::escape($settings['currency_symbol'] ?? '₹') ?>"></div>
                    <div class="mb-3"><label class="form-label">Currency Code</label><input type="text" name="currency_code" class="form-control" value="<?= Helper::escape($settings['currency_code'] ?? 'INR') ?>"></div>
                    <div class="mb-3"><label class="form-label">Date Format</label><input type="text" name="date_format" class="form-control" value="<?= Helper::escape($settings['date_format'] ?? 'd-m-Y') ?>" placeholder="e.g. d-m-Y"></div>
                    <div class="mb-3"><label class="form-label">Timezone</label><input type="text" name="timezone" class="form-control" value="<?= Helper::escape($settings['timezone'] ?? 'Asia/Kolkata') ?>" placeholder="e.g. Asia/Kolkata"></div>
                    <div class="mb-3"><label class="form-label">Low Stock Threshold</label><input type="number" name="low_stock_threshold" class="form-control" value="<?= $settings['low_stock_threshold'] ?? 10 ?>"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========== TAB 2: Tax & GST ========== -->
<div class="tab-pane <?= $activeTab === 'tax' ? 'active' : '' ?>" id="tab-tax">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-percent me-2"></i>Tax Configuration</h6></div>
                <div class="card-body">
                    <!-- Enable Tax Toggle -->
                    <div class="toggle-card">
                        <div class="toggle-info">
                            <h6><i class="fas fa-calculator me-2 settings-icon-primary"></i>Enable Tax</h6>
                            <p>Enable or disable tax calculation on invoices</p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" id="enableTaxSwitch" name="enable_tax" value="1"
                                   <?= ($settings['enable_tax'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- GST Toggle -->
                    <div class="toggle-card" id="gstToggleCard">
                        <div class="toggle-info">
                            <h6><i class="fas fa-landmark me-2 settings-icon-success"></i>GST Business</h6>
                            <p>Enable GST for registered businesses. Disable for non-GST businesses (Bill of Supply)</p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" id="enableGstSwitch" name="enable_gst" value="1"
                                   <?= ($settings['enable_gst'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- GST-specific fields -->
                    <div class="gst-fields" id="gstFields">
                        <div id="gstOnlyFields">
                            <hr class="section-divider">
                            <div class="section-label"><i class="fas fa-file-invoice-dollar"></i> GST Details</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">GSTIN Number</label>
                                    <input type="text" name="tax_number" class="form-control" 
                                           value="<?= Helper::escape($settings['tax_number'] ?? '') ?>"
                                           placeholder="e.g. 27AABCU9603R1ZM">
                                    <small class="text-muted">15-digit GST Identification Number</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Default GST Rate (%)</label>
                                    <input type="number" name="tax_rate" class="form-control" step="0.01" 
                                           value="<?= $settings['tax_rate'] ?? 18 ?>">
                                    <small class="text-muted">Applied to products without specific tax rate</small>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3 settings-alert-note">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>GST Enabled:</strong> Invoice will show as <strong>"Tax Invoice"</strong> with GSTIN, tax columns (CGST/SGST/IGST), HSN codes, and tax breakup.
                            </div>
                        </div>

                        <div class="toggle-card mt-3">
                            <div class="toggle-info">
                                <h6><i class="fas fa-equals me-2 settings-icon-brand"></i>Auto Round Off to Nearest ₹1</h6>
                                <p>Automatically applies round-off on sale bills so final total rounds to nearest rupee.</p>
                            </div>
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" id="autoRoundOffSwitch" name="auto_round_off_rupee" value="1"
                                       <?= (!empty($settings['auto_round_off_rupee'])) ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- Non-GST Info -->
                    <div id="nonGstInfo" class="settings-hidden">
                        <hr class="section-divider">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Business Identifier <small class="text-muted">(optional)</small></label>
                                <input type="text" name="tax_number_nongst" class="form-control" 
                                       value="<?= Helper::escape($settings['tax_number'] ?? '') ?>"
                                       placeholder="e.g. PAN, TIN, MSME, etc.">
                                <small class="text-muted">Stored for display/reference only in non-GST mode.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Simple Tax Rate (%)</label>
                                <input type="number" name="tax_rate_nongst" class="form-control" step="0.01"
                                       value="<?= $settings['tax_rate'] ?? 0 ?>"
                                       placeholder="e.g. 5, 12, 18">
                                <small class="text-muted">Flat tax rate applied to all items (no GST breakup)</small>
                            </div>
                            <div class="col-md-12 mt-2">
                                <div class="alert alert-secondary mb-0 settings-alert-note">
                                    <i class="fas fa-ban me-2"></i>
                                    <strong>Simple Tax Mode (Non-GST).</strong> A flat tax rate will be applied to all items. No GST breakup (CGST/SGST/IGST) will be shown on invoices.
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-warning mt-3 settings-alert-note">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Non-GST Business:</strong> Your invoices will display as <strong>"Invoice"</strong> or <strong>"Bill of Supply"</strong>. A flat tax % is applied to items without CGST/SGST breakup.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6><i class="fas fa-question-circle me-2"></i>Help</h6></div>
                <div class="card-body settings-help-copy">
                    <div class="mb-3">
                        <strong>GST Business</strong>
                        <p class="mb-0">If you have a GSTIN, enable this. Your invoices will show as "Tax Invoice" with proper GST breakup (CGST+SGST or IGST).</p>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <strong>Non-GST Business</strong>
                        <p class="mb-0">If you're not GST registered, disable GST. Invoices will show as "Invoice". No tax will be applied. This is suitable for businesses below the GST threshold.</p>
                    </div>
                    <hr>
                    <div>
                        <strong>Tax Disabled</strong>
                        <p class="mb-0">If you disable tax entirely, no tax will be calculated on any transaction.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========== TAB 3: Invoice Customization ========== -->
<div class="tab-pane <?= $activeTab === 'invoice' ? 'active' : '' ?>" id="tab-invoice">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-file-invoice me-2"></i>Invoice Appearance</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sale Invoice Title <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_title" class="form-control" 
                                   value="<?= Helper::escape($settings['invoice_title'] ?? 'Tax Invoice') ?>"
                                   placeholder="e.g. Tax Invoice, Bill of Supply, Invoice">
                            <small class="text-muted">This title appears at the top of sale invoices</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purchase Invoice Title</label>
                            <input type="text" name="purchase_invoice_title" class="form-control" 
                                   value="<?= Helper::escape($settings['purchase_invoice_title'] ?? 'Purchase Bill') ?>"
                                   placeholder="e.g. Purchase Bill, Purchase Order">
                            <small class="text-muted">This title appears at the top of purchase invoices</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Invoice Subtitle</label>
                            <input type="text" name="invoice_subtitle" class="form-control" 
                                   value="<?= Helper::escape($settings['invoice_subtitle'] ?? '') ?>"
                                   placeholder="Optional subtitle below the invoice title">
                        </div>
                    </div>

                    <hr class="section-divider">
                    <div class="section-label"><i class="fas fa-pen-fancy"></i> Signature & Labels</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Signature Label</label>
                            <input type="text" name="invoice_signature_label" class="form-control" 
                                   value="<?= Helper::escape($settings['invoice_signature_label'] ?? 'Authorised Signatory') ?>"
                                   placeholder="e.g. Authorised Signatory, For Company Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notes Label</label>
                            <input type="text" name="invoice_notes_label" class="form-control" 
                                   value="<?= Helper::escape($settings['invoice_notes_label'] ?? 'Notes') ?>"
                                   placeholder="e.g. Notes, Remarks">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-eye me-2"></i>Invoice Display Options</h6></div>
                <div class="card-body">
                    <div class="toggle-card">
                        <div class="toggle-info">
                            <h6><i class="fas fa-image me-2 settings-icon-primary"></i>Show Logo on Invoice</h6>
                            <p>Displays the uploaded company logo on invoices and quotations.</p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" name="invoice_show_logo" value="1"
                                   <?= (!isset($settings['invoice_show_logo']) || !empty($settings['invoice_show_logo'])) ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="toggle-card">
                        <div class="toggle-info">
                            <h6><i class="fas fa-signature me-2 settings-icon-purple"></i>Show Signature on Invoice</h6>
                            <p>Displays the uploaded signature image in the invoice sign area.</p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" name="invoice_show_signature" value="1"
                                   <?= (!isset($settings['invoice_show_signature']) || !empty($settings['invoice_show_signature'])) ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="toggle-card">
                        <div class="toggle-info">
                            <h6><i class="fas fa-stamp me-2 settings-icon-danger"></i>Show Seal on Invoice</h6>
                            <p>Displays the uploaded seal image in the invoice sign area.</p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" name="invoice_show_seal" value="1"
                                   <?= (!isset($settings['invoice_show_seal']) || !empty($settings['invoice_show_seal'])) ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="toggle-card">
                        <div class="toggle-info">
                            <h6><i class="fas fa-receipt me-2 settings-icon-info"></i>Show Payment Status Badge</h6>
                            <p>Shows paid, unpaid, partial, or returned status in the invoice header.</p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" id="showPaymentStatusSwitch" name="invoice_show_payment_status" value="1"
                                   <?= $previewShowPaymentStatus ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="toggle-card">
                        <div class="toggle-info">
                            <h6><i class="fas fa-money-check-dollar me-2 settings-icon-success"></i>Show Paid / Due on Invoice</h6>
                            <p>When enabled, "Paid" and "Balance Due" amounts will be shown on the invoice PDF. Disable to hide payment info from printed invoices.</p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" id="showPaidDueSwitch" name="show_paid_due_on_invoice" value="1"
                                   <?= ($settings['show_paid_due_on_invoice'] ?? 1) ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="toggle-card">
                        <div class="toggle-info">
                            <h6><i class="fas fa-weight-scale me-2 settings-icon-purple"></i>Show Unit on Invoice</h6>
                            <p>When enabled, the product unit will be shown next to the quantity (e.g. 2 Nos instead of just 2).</p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" id="showUnitSwitch" name="show_unit_on_invoice" value="1"
                                   <?= (!empty($settings['show_unit_on_invoice'])) ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="toggle-card">
                        <div class="toggle-info">
                            <h6><i class="fas fa-tags me-2 settings-icon-orange"></i>Show Discount on Invoice</h6>
                            <p>When enabled, the discount column will be visible on printed invoices.</p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" id="showDiscountSwitch" name="show_discount_on_invoice" value="1"
                                   <?= (!isset($settings['show_discount_on_invoice']) || !empty($settings['show_discount_on_invoice'])) ? 'checked' : '' ?>>
                        </div>
                    </div>
                    <div class="toggle-card">
                        <div class="toggle-info">
                            <h6><i class="fas fa-barcode me-2 settings-icon-teal"></i>Show HSN/SAC on Invoice</h6>
                            <p>When enabled, HSN/SAC column is shown on GST invoices. In non-GST mode it stays hidden.</p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" id="showHsnSwitch" name="show_hsn_on_invoice" value="1"
                                   <?= (!isset($settings['show_hsn_on_invoice']) || !empty($settings['show_hsn_on_invoice'])) ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-file-lines me-2"></i>Invoice Content</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Footer Text</label>
                        <input type="text" name="invoice_footer_text" class="form-control" 
                               value="<?= Helper::escape($settings['invoice_footer_text'] ?? '') ?>"
                               placeholder="e.g. Thank you for your business!">
                        <small class="text-muted">Shown at the bottom of every invoice</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea name="invoice_terms" class="form-control" rows="3" 
                                  placeholder="e.g. Goods once sold will not be returned..."><?= Helper::escape($settings['invoice_terms'] ?? '') ?></textarea>
                        <small class="text-muted">Printed on invoices below the totals</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank Details (for payment)</label>
                        <textarea name="invoice_bank_details" class="form-control" rows="3" 
                                  placeholder="Bank: SBI&#10;A/C No: 1234567890&#10;IFSC: SBIN0001234"><?= Helper::escape($settings['invoice_bank_details'] ?? '') ?></textarea>
                        <small class="text-muted">Bank account details shown on invoice for payment reference</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <!-- Invoice Preview -->
            <div class="card">
                <div class="card-header"><h6><i class="fas fa-eye me-2"></i>Preview</h6></div>
                <div class="card-body settings-preview-card-body">
                    <div class="settings-preview-shell">
                        <div class="settings-preview-head">
                            <div>
                                <strong class="settings-preview-company"><?= Helper::escape($settings['company_name'] ?? 'Company Name') ?></strong><br>
                                <span class="settings-preview-company-address"><?= Helper::escape($settings['company_address'] ?? 'Address') ?></span>
                            </div>
                            <div class="settings-preview-right">
                                <div id="previewTitle" class="settings-preview-title">
                                    <?= Helper::escape($settings['invoice_title'] ?? 'Tax Invoice') ?>
                                </div>
                                <div id="previewSubtitle" class="settings-preview-subtitle<?= !empty($settings['invoice_subtitle']) ? '' : ' settings-block-hidden' ?>">
                                    <?= Helper::escape($settings['invoice_subtitle'] ?? '') ?>
                                </div>
                                <div class="settings-preview-number">INV-00001</div>
                                <div id="previewStatusWrap" class="settings-preview-status-wrap<?= $previewShowPaymentStatus ? '' : ' settings-block-hidden' ?>">
                                    <span class="settings-preview-status-badge">UNPAID</span>
                                </div>
                            </div>
                        </div>
                        <table class="settings-preview-table">
                            <thead>
                                <tr class="settings-preview-table-head">
                                    <th class="settings-preview-cell">Item</th>
                                    <th class="settings-preview-cell settings-preview-text-center">Qty</th>
                                    <th class="settings-preview-cell settings-preview-text-left<?= ($previewTaxEnabled && $previewGstEnabled && $previewShowHsn) ? '' : ' settings-table-cell-hidden' ?>" id="previewHsnCol">HSN/SAC</th>
                                    <th class="settings-preview-cell settings-preview-text-right">Rate</th>
                                    <th class="settings-preview-cell settings-preview-text-right<?= $previewShowDiscount ? '' : ' settings-table-cell-hidden' ?>" id="previewDiscountCol">Disc</th>
                                    <th class="settings-preview-cell settings-preview-text-right<?= ($previewTaxEnabled && $previewGstEnabled) ? '' : ' settings-table-cell-hidden' ?>" id="previewTaxRateCol" id="previewTaxRateColLabel">Tax %</th>
                                    <th class="settings-preview-cell settings-preview-text-right<?= ($previewTaxEnabled && $previewGstEnabled) ? '' : ' settings-table-cell-hidden' ?>" id="previewTaxAmtCol" id="previewTaxAmtColLabel">Tax Amt</th>
                                    <th class="settings-preview-cell settings-preview-text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="settings-preview-cell-sm">Sample Product</td>
                                    <td class="settings-preview-cell-sm settings-preview-text-center" id="previewQtyCell"><?= $previewShowUnit ? '2 Pcs' : '2' ?></td>
                                    <td class="settings-preview-cell-sm<?= ($previewTaxEnabled && $previewGstEnabled && $previewShowHsn) ? '' : ' settings-table-cell-hidden' ?>" id="previewHsnCell">8471</td>
                                    <td class="settings-preview-cell-sm settings-preview-text-right"><?= Helper::escape($previewCurrency) ?>500</td>
                                    <td class="settings-preview-cell-sm settings-preview-text-right<?= $previewShowDiscount ? '' : ' settings-table-cell-hidden' ?>" id="previewDiscountCell">-</td>
                                    <td class="settings-preview-cell-sm settings-preview-text-right preview-tax-cell<?= ($previewTaxEnabled && $previewGstEnabled) ? '' : ' settings-table-cell-hidden' ?>">18%</td>
                                    <td class="settings-preview-cell-sm settings-preview-text-right preview-tax-cell<?= ($previewTaxEnabled && $previewGstEnabled) ? '' : ' settings-table-cell-hidden' ?>"><?= Helper::escape($previewCurrency) ?>180</td>
                                    <td class="settings-preview-cell-sm settings-preview-text-right"><?= Helper::escape($previewCurrency) ?>1,180</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="settings-preview-summary">
                            <div id="previewTaxSummaryRow" class="<?= ($previewTaxEnabled && $previewGstEnabled) ? '' : 'settings-block-hidden' ?>">Tax: <?= Helper::escape($previewCurrency) ?>180.00</div>
                            <div class="settings-preview-grand-total">Grand Total: <?= Helper::escape($previewCurrency) ?>1,180.00</div>
                            <div id="previewPaidDueRow" class="<?= $previewShowPaidDue ? '' : 'settings-block-hidden' ?>">
                                <div class="settings-preview-paid">Paid: <?= Helper::escape($previewCurrency) ?>0.00</div>
                                <div class="settings-preview-due">Balance Due: <?= Helper::escape($previewCurrency) ?>1,180.00</div>
                            </div>
                        </div>
                        <div id="previewSignature" class="settings-preview-signature-block">
                            <?= Helper::escape($settings['invoice_signature_label'] ?? 'Authorised Signatory') ?>
                        </div>
                    </div>
                    <p class="text-muted text-center mt-2 mb-0 settings-preview-note">
                        <i class="fas fa-info-circle me-1"></i> Live preview updates as you type
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ========== TAB 4: Prefixes ========== -->
<div class="tab-pane <?= $activeTab === 'prefixes' ? 'active' : '' ?>" id="tab-prefixes">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-hashtag me-2"></i>Invoice Number Prefixes</h6></div>
                <div class="card-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Sale Invoice Prefix</label><input type="text" name="invoice_prefix" class="form-control" value="<?= Helper::escape($settings['invoice_prefix'] ?? 'INV-') ?>"><small class="text-muted">e.g. INV-, SALE-, SI-</small></div>
                    <div class="col-md-6"><label class="form-label">Purchase Prefix</label><input type="text" name="purchase_prefix" class="form-control" value="<?= Helper::escape($settings['purchase_prefix'] ?? 'PUR-') ?>"><small class="text-muted">e.g. PUR-, PO-, PI-</small></div>
                    <div class="col-md-6"><label class="form-label">Payment Prefix</label><input type="text" name="payment_prefix" class="form-control" value="<?= Helper::escape($settings['payment_prefix'] ?? 'PAY-') ?>"><small class="text-muted">e.g. PAY-, PMT-</small></div>
                    <div class="col-md-6"><label class="form-label">Receipt Prefix</label><input type="text" name="receipt_prefix" class="form-control" value="<?= Helper::escape($settings['receipt_prefix'] ?? 'RCT-') ?>"><small class="text-muted">e.g. RCT-, RCP-</small></div>
                </div></div>
            </div>
        </div>
    </div>
</div>

<!-- Save Button (always visible) -->
<div class="mt-3 mb-4">
    <button type="submit" class="btn btn-primary px-4 settings-save-btn">
        <i class="fas fa-save me-2"></i>Save All Settings
    </button>
</div>

</form>

<script nonce="<?= $cspNonce ?>">
// Tab switching
function switchTab(tabId, tabElement) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
    const pane = document.getElementById('tab-' + tabId);
    if (pane) pane.classList.add('active');
    if (tabElement) tabElement.classList.add('active');

    // Keep URL tab param in sync without full page reload.
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabId);
        window.history.replaceState({}, '', url.toString());
    } catch (e) {
        // Ignore URL API issues.
    }
}

// Toggle GST fields visibility
function toggleTaxFields() {
    const taxSwitch = document.getElementById('enableTaxSwitch');
    if (!taxSwitch) return;
    const enabled = taxSwitch.checked;
    const gstCard = document.getElementById('gstToggleCard');
    const gstFields = document.getElementById('gstFields');
    const gstOnlyFields = document.getElementById('gstOnlyFields');
    const nonGstInfo = document.getElementById('nonGstInfo');
    const gstSwitch = document.getElementById('enableGstSwitch');

    if (enabled) {
        gstCard.classList.remove('settings-dimmed');
        if (gstSwitch) {
            gstSwitch.disabled = false;
            if (!gstSwitch.checked) gstSwitch.checked = true;
        }
        toggleGstFields();
    } else {
        gstCard.classList.add('settings-dimmed');
        if (gstSwitch) {
            gstSwitch.checked = false;
            gstSwitch.disabled = true;
        }
        if (gstFields) gstFields.classList.remove('settings-hidden');
        if (gstOnlyFields) gstOnlyFields.classList.add('settings-hidden');
        if (nonGstInfo) nonGstInfo.classList.add('settings-hidden');
        updatePreviewLayout();
    }
}

function toggleGstFields() {
    const gstSwitch = document.getElementById('enableGstSwitch');
    if (!gstSwitch) return;
    const gstEnabled = gstSwitch.checked;
    const gstFields = document.getElementById('gstFields');
    const gstOnlyFields = document.getElementById('gstOnlyFields');
    const nonGstInfo = document.getElementById('nonGstInfo');
    const taxSwitch = document.getElementById('enableTaxSwitch');
    const titleField = document.querySelector('input[name="invoice_title"]');

    if (gstEnabled) {
        if (taxSwitch && !taxSwitch.checked) {
            taxSwitch.checked = true;
        }
        if (gstFields) gstFields.classList.remove('settings-hidden');
        if (gstOnlyFields) gstOnlyFields.classList.remove('settings-hidden');
        nonGstInfo.classList.add('settings-hidden');
        // Auto-suggest title
        if (titleField && (titleField.value === 'Bill of Supply' || titleField.value === '')) {
            titleField.value = 'Tax Invoice';
            updatePreview();
        }
    } else {
        // In India: GST OFF = Tax OFF
        if (taxSwitch && taxSwitch.checked) {
            taxSwitch.checked = false;
        }
        if (gstFields) gstFields.classList.remove('settings-hidden');
        if (gstOnlyFields) gstOnlyFields.classList.add('settings-hidden');
        nonGstInfo.classList.remove('settings-hidden');
        // Auto-suggest title
        if (titleField && (titleField.value === 'Tax Invoice' || titleField.value === '')) {
            titleField.value = 'Bill of Supply';
            updatePreview();
        }
    }
    updatePreviewLayout();
}

// Live preview updates
function updatePreview() {
    const title = document.querySelector('input[name="invoice_title"]');
    const subtitle = document.querySelector('input[name="invoice_subtitle"]');
    const sig = document.querySelector('input[name="invoice_signature_label"]');
    if (title) document.getElementById('previewTitle').textContent = title.value || 'Invoice';
    if (subtitle) {
        const subtitleNode = document.getElementById('previewSubtitle');
        if (subtitleNode) {
            subtitleNode.textContent = subtitle.value || '';
            subtitleNode.classList.toggle('settings-block-hidden', !subtitle.value);
        }
    }
    if (sig) document.getElementById('previewSignature').textContent = sig.value || 'Authorised Signatory';
}

function updatePreviewLayout() {
    const gstSwitch = document.getElementById('enableGstSwitch');
    const taxSwitch = document.getElementById('enableTaxSwitch');
    const showPaidDueSwitch = document.getElementById('showPaidDueSwitch');
    const showPaymentStatusSwitch = document.getElementById('showPaymentStatusSwitch');
    const showUnitSwitch = document.getElementById('showUnitSwitch');
    const showDiscountSwitch = document.getElementById('showDiscountSwitch');
    const showHsnSwitch = document.getElementById('showHsnSwitch');
    const gstEnabled = !!(gstSwitch && gstSwitch.checked);
    const taxEnabled = !!(taxSwitch && taxSwitch.checked);
    const showPaidDue = !!(showPaidDueSwitch && showPaidDueSwitch.checked);
    const showPaymentStatus = !!(showPaymentStatusSwitch && showPaymentStatusSwitch.checked);
    const showUnit = !!(showUnitSwitch && showUnitSwitch.checked);
    const showDiscount = !!(showDiscountSwitch && showDiscountSwitch.checked);
    const showHsn = !!(showHsnSwitch && showHsnSwitch.checked);
    const showTaxColumns = taxEnabled; // Tax columns shown for both GST and non-GST tax
    const showHsnColumn = showTaxColumns && showHsn;
    const discountCol = document.getElementById('previewDiscountCol');
    const discountCell = document.getElementById('previewDiscountCell');
    const hsnCol = document.getElementById('previewHsnCol');
    const hsnCell = document.getElementById('previewHsnCell');
    const taxRateCol = document.getElementById('previewTaxRateCol');
    const taxAmtCol = document.getElementById('previewTaxAmtCol');
    const taxCells = document.querySelectorAll('.preview-tax-cell');
    const qtyCell = document.getElementById('previewQtyCell');
    const paidDueRow = document.getElementById('previewPaidDueRow');
    const taxSummaryRow = document.getElementById('previewTaxSummaryRow');
    const statusWrap = document.getElementById('previewStatusWrap');

    if (discountCol) discountCol.classList.toggle('settings-table-cell-hidden', !showDiscount);
    if (discountCell) discountCell.classList.toggle('settings-table-cell-hidden', !showDiscount);
    if (hsnCol) hsnCol.classList.toggle('settings-table-cell-hidden', !showHsnColumn);
    if (hsnCell) hsnCell.classList.toggle('settings-table-cell-hidden', !showHsnColumn);
    if (taxRateCol) taxRateCol.classList.toggle('settings-table-cell-hidden', !showTaxColumns);
    if (taxAmtCol) taxAmtCol.classList.toggle('settings-table-cell-hidden', !showTaxColumns);
    taxCells.forEach(c => c.classList.toggle('settings-table-cell-hidden', !showTaxColumns));
    if (qtyCell) qtyCell.textContent = showUnit ? '2 Pcs' : '2';
    if (paidDueRow) paidDueRow.classList.toggle('settings-block-hidden', !showPaidDue);
    if (statusWrap) statusWrap.classList.toggle('settings-block-hidden', !showPaymentStatus);
    if (taxSummaryRow) taxSummaryRow.classList.toggle('settings-block-hidden', !showTaxColumns);
}

// Attach live preview listeners
document.addEventListener('DOMContentLoaded', function() {
    const titleField = document.querySelector('input[name="invoice_title"]');
    const sigField = document.querySelector('input[name="invoice_signature_label"]');
    const subtitleField = document.querySelector('input[name="invoice_subtitle"]');
    const taxSwitch = document.getElementById('enableTaxSwitch');
    const gstSwitch = document.getElementById('enableGstSwitch');
    const showPaidDueSwitch = document.getElementById('showPaidDueSwitch');
    const showPaymentStatusSwitch = document.getElementById('showPaymentStatusSwitch');
    const showUnitSwitch = document.getElementById('showUnitSwitch');
    const showDiscountSwitch = document.getElementById('showDiscountSwitch');
    const showHsnSwitch = document.getElementById('showHsnSwitch');
    if (titleField) titleField.addEventListener('input', updatePreview);
    if (subtitleField) subtitleField.addEventListener('input', updatePreview);
    if (sigField) sigField.addEventListener('input', updatePreview);
    if (taxSwitch) taxSwitch.addEventListener('change', toggleTaxFields);
    if (gstSwitch) gstSwitch.addEventListener('change', toggleGstFields);
    if (showPaidDueSwitch) showPaidDueSwitch.addEventListener('change', updatePreviewLayout);
    if (showPaymentStatusSwitch) showPaymentStatusSwitch.addEventListener('change', updatePreviewLayout);
    if (showUnitSwitch) showUnitSwitch.addEventListener('change', updatePreviewLayout);
    if (showDiscountSwitch) showDiscountSwitch.addEventListener('change', updatePreviewLayout);
    if (showHsnSwitch) showHsnSwitch.addEventListener('change', updatePreviewLayout);

    // CSP-safe tab click handling.
    document.querySelectorAll('.settings-tab').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            const tabId = this.getAttribute('data-tab');
            if (!tabId) return;
            e.preventDefault();
            switchTab(tabId, this);
        });
    });

    // Initialize state
    toggleTaxFields();
    updatePreview();
    updatePreviewLayout();
});
</script>
