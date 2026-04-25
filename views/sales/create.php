<?php $pageTitle = 'New Sale'; ?>
<?php $hasWarehouseFeature = !empty($hasWarehouseFeature); ?>
<?php $warehouses = is_array($warehouses ?? null) ? $warehouses : []; ?>
<?php $customerCount = count($customers ?? []); ?>
<?php
$quickMasterLinks = [
    'customer' => APP_URL . '/index.php?page=customers&action=create',
    'product' => APP_URL . '/index.php?page=products&action=create',
    'category' => APP_URL . '/index.php?page=categories&action=create',
    'brand' => APP_URL . '/index.php?page=brands&action=create',
    'unit' => APP_URL . '/index.php?page=units&action=create',
    'supplier' => APP_URL . '/index.php?page=suppliers&action=create',
];
?>
<div class="sales-entry-shell">
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=sales">Sales</a></li><li class="breadcrumb-item active">New</li></ol></nav>
    <div class="app-page-actions">
        <a href="<?= APP_URL ?>/index.php?page=sales" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Sales</a>
    </div>
</div>

<style>
    .ai-assistant-card {
        background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
        border: 1px solid rgba(37, 99, 235, 0.1);
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    .ai-assistant-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
    .ai-robot-icon { 
        font-size: 1.5rem; 
        color: #2563eb; 
        margin-right: 12px;
        filter: drop-shadow(0 0 5px rgba(37, 99, 235, 0.3));
    }
    .ai-thinking .ai-robot-icon { animation: ai-pulse 1.5s infinite ease-in-out; }
    @keyframes ai-pulse {
        0% { transform: scale(1); filter: drop-shadow(0 0 5px rgba(37, 99, 235, 0.3)); }
        50% { transform: scale(1.1); filter: drop-shadow(0 0 15px rgba(37, 99, 235, 0.6)); }
        100% { transform: scale(1); filter: drop-shadow(0 0 5px rgba(37, 99, 235, 0.3)); }
    }
    .ai-input-group { border-radius: 20px; overflow: hidden; border: 2px solid #e2e8f0; transition: border-color 0.3s; }
    .ai-input-group:focus-within { border-color: #2563eb; }
    #aiTextInput { border: none; box-shadow: none; padding-left: 15px; }
    .ai-btn-premium { border: none; border-radius: 0; padding: 0 15px; font-weight: 600; }
</style>

<form method="POST" id="saleForm">
    <?= CSRF::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3 ai-assistant-card" id="aiCard">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-robot ai-robot-icon"></i>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">AI Sales Assistant</h6>
                                <small class="text-muted">Dictate, type, or scan a chit (PO) to auto-fill items.</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-grow-1 justify-content-end" style="max-width: 500px;">
                            <div class="input-group ai-input-group flex-grow-1">
                                <input type="text" id="aiTextInput" class="form-control" placeholder="e.g. 5 Maggi, 2 Kurkure to Rohit">
                                <button type="button" class="btn btn-light ai-btn-premium text-primary" id="aiVoiceBtn" title="Speak">
                                    <i class="fas fa-microphone"></i>
                                </button>
                                <button type="button" class="btn btn-primary ai-btn-premium" id="aiTextBtn">
                                    Parse
                                </button>
                            </div>
                            <input type="file" id="aiScanInput" class="d-none" accept="image/*,application/pdf" capture="environment">
                            <button type="button" class="btn btn-success rounded-pill px-3" onclick="document.getElementById('aiScanInput').click();" id="aiScanBtn">
                                <i class="fas fa-camera me-1"></i> Scan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-3 sales-entry-card">
                <div class="card-header"><h6><i class="fas fa-info-circle me-2"></i>Sale Details</h6></div>
                <div class="card-body"><div class="row g-3 sales-entry-details-grid">
                    <div class="col-md-6 col-xl-4"><label class="form-label d-flex justify-content-between align-items-center gap-2"><span>Customer <span class="text-danger">*</span></span><button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['customer']) ?>" data-master-title="Add Customer"><i class="fas fa-plus me-1"></i>Add Customer</button></label>
                        <select name="customer_id" class="form-select" required><option value="">Select</option>
                            <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= Helper::escape($c['name']) ?></option><?php endforeach; ?>
                        </select>
                        <?php if ($customerCount === 0): ?>
                        <div class="form-text text-danger"><i class="fas fa-circle-exclamation me-1"></i>No customers available. Press <strong>Alt+Shift+Y</strong> to open customer create in a new tab.</div>
                        <?php else: ?>
                        <div class="form-text">If the customer is missing, press <strong>Alt+Shift+Y</strong> to add one without leaving this sale.</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($hasWarehouseFeature): ?>
                    <div class="col-md-6 col-xl-4"><label class="form-label">Warehouse <span class="text-danger">*</span></label>
                        <select name="warehouse_id" class="form-select" required><option value="">Select</option>
                            <?php foreach ($warehouses as $warehouse): ?><option value="<?= (int)$warehouse['id'] ?>" <?= !empty($warehouse['is_default']) ? 'selected' : '' ?>><?= Helper::escape($warehouse['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <?php endif; ?>
                    <div class="col-md-6 col-xl-4"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-6 col-xl-4"><label class="form-label">Reference</label><input type="text" name="reference_number" class="form-control"></div>
                </div></div>
            </div>
            <div class="card mb-3 sales-entry-card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2"><h6><i class="fas fa-list me-2"></i>Items</h6><div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-sm btn-outline-primary js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['product']) ?>" data-master-title="Add Product"><i class="fas fa-box-open me-1"></i>New Product</button><button type="button" class="btn btn-sm btn-primary" id="addItemBtn"><i class="fas fa-plus me-1"></i>Add</button></div></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table mb-0 sales-items-table" id="itemsTable">
                    <thead><tr><th style="width:30%">Product</th><th>Qty</th><th>Price</th><th>Disc</th>
                        <?php if((!isset($settings['enable_tax']) || $settings['enable_tax']) && (!isset($settings['enable_gst']) || $settings['enable_gst'])): ?>
                        <th>Tax%</th>
                        <?php endif; ?>
                        <th>Total</th><th></th></tr></thead>
                    <tbody id="itemsBody"></tbody>
                    <tfoot><tr><td colspan="<?= ((!isset($settings['enable_tax']) || $settings['enable_tax']) && (!isset($settings['enable_gst']) || $settings['enable_gst'])) ? 5 : 4 ?>" class="text-end fw-bold">Subtotal:</td><td class="fw-bold" id="subtotalDisplay">₹0.00</td><td></td></tr></tfoot>
                </table></div></div>
                <div class="px-3 py-2 border-top bg-light small text-muted">
                    Sales needs <strong>Customer</strong> first, then <strong>Product</strong>. If a product dependency is missing use
                    <strong>Alt+Shift+C</strong> category, <strong>Alt+Shift+B</strong> brand, <strong>Alt+Shift+U</strong> unit.
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="sales-entry-summary">
            <div class="card mb-3 sales-entry-summary-card border-primary-subtle">
                <div class="card-header"><h6><i class="fas fa-bolt me-2"></i>Quick Setup While Staying Here</h6></div>
                <div class="card-body">
                    <div class="alert alert-light border small mb-3">
                        Sale page normally needs <strong>Customer</strong> and <strong>Product</strong>. Product creation may need <strong>Category</strong>, <strong>Brand</strong>, and <strong>Unit</strong>. Supplier shortcut is optional for later purchase-side work.
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['customer']) ?>" data-master-title="Add Customer"><span><i class="fas fa-user-plus me-2"></i>Add Customer</span><span class="text-muted">Alt+Shift+Y</span></button>
                        <button type="button" class="btn btn-outline-primary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['product']) ?>" data-master-title="Add Product"><span><i class="fas fa-box-open me-2"></i>Add Product</span><span class="text-muted">Alt+Shift+P</span></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['category']) ?>" data-master-title="Add Category"><span><i class="fas fa-layer-group me-2"></i>Add Category</span><span class="text-muted">Alt+Shift+C</span></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['brand']) ?>" data-master-title="Add Brand"><span><i class="fas fa-tags me-2"></i>Add Brand</span><span class="text-muted">Alt+Shift+B</span></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['unit']) ?>" data-master-title="Add Unit"><span><i class="fas fa-ruler-combined me-2"></i>Add Unit</span><span class="text-muted">Alt+Shift+U</span></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['supplier']) ?>" data-master-title="Add Supplier"><span><i class="fas fa-truck-field me-2"></i>Add Supplier</span><span class="text-muted">Alt+Shift+S</span></button>
                        <button type="button" class="btn btn-success btn-sm justify-content-between d-flex align-items-center" id="reloadMastersBtn"><span><i class="fas fa-rotate-right me-2"></i>Reload Lists</span><span class="text-muted">Alt+Shift+R</span></button>
                    </div>
                    <div class="form-text mt-2">Open master forms in this page, save there, then press <strong>Reload Lists</strong>. Your draft will be restored automatically.</div>
                </div>
            </div>
            <div class="card mb-3 sales-entry-summary-card">
                <div class="card-header"><h6><i class="fas fa-calculator me-2"></i>Summary</h6></div>
                <div class="card-body">
                    <div class="sales-entry-summary-grid">
                    <div class="mb-2"><label class="form-label small">Discount</label><input type="number" name="discount_amount" class="form-control form-control-sm" step="0.01" value="0" id="discountInput" min="0"></div>
                    <?php if ((!isset($settings['enable_tax']) || $settings['enable_tax']) && (!isset($settings['enable_gst']) || $settings['enable_gst'])): ?>
                    <div class="mb-2">
                        <label class="form-label small">GST Mode</label>
                        <select name="gst_type" id="gstTypeInput" class="form-select form-select-sm">
                            <option value="auto" selected>Auto (By State)</option>
                            <option value="cgst_sgst">CGST + SGST</option>
                            <option value="igst">IGST</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="mb-2"><label class="form-label small">Gari Bhada / Freight</label><input type="number" name="freight_charge" class="form-control form-control-sm" step="0.01" value="0" id="freightInput" min="0"></div>
                    <div class="mb-2"><label class="form-label small">Loading</label><input type="number" name="loading_charge" class="form-control form-control-sm" step="0.01" value="0" id="loadingInput" min="0"></div>
                    <input type="hidden" name="shipping_cost" id="shippingInput" value="0">
                    <div class="mb-2"><label class="form-label small">Round Off</label><input type="number" name="round_off" class="form-control form-control-sm" step="0.01" value="0" id="roundOffInput" <?= !empty($settings['auto_round_off_rupee']) ? 'readonly' : '' ?>><?php if (!empty($settings['auto_round_off_rupee'])): ?><small class="text-muted">Auto mode: nearest Rs. 1 is applied automatically.</small><?php endif; ?></div>
                    </div>
                    <hr>
                    <div class="sales-entry-summary-line mb-2"><span class="sales-entry-summary-label">Subtotal</span><span class="sales-entry-summary-value" id="summarySubtotal">₹0.00</span></div>
                    <div class="sales-entry-summary-line mb-2"><span class="sales-entry-summary-label">Tax</span><span class="sales-entry-summary-value" id="summaryTax">Rs.0.00</span></div>
                    <div class="sales-entry-summary-line mb-2" id="summaryCgstRow" style="display:none;"><span class="sales-entry-summary-label">CGST</span><span class="sales-entry-summary-value" id="summaryCgst">Rs.0.00</span></div>
                    <div class="sales-entry-summary-line mb-2" id="summarySgstRow" style="display:none;"><span class="sales-entry-summary-label">SGST</span><span class="sales-entry-summary-value" id="summarySgst">Rs.0.00</span></div>
                    <div class="sales-entry-summary-line mb-2" id="summaryIgstRow" style="display:none;"><span class="sales-entry-summary-label">IGST</span><span class="sales-entry-summary-value" id="summaryIgst">Rs.0.00</span></div>
                    <div class="sales-entry-summary-line mb-2"><span class="sales-entry-summary-label">Discount</span><span class="sales-entry-summary-value" id="summaryDiscount">-Rs.0.00</span></div>
                    <div class="sales-entry-summary-line mb-2"><span class="sales-entry-summary-label">Freight</span><span class="sales-entry-summary-value" id="summaryFreight">Rs.0.00</span></div>
                    <div class="sales-entry-summary-line mb-2"><span class="sales-entry-summary-label">Loading</span><span class="sales-entry-summary-value" id="summaryLoading">Rs.0.00</span></div>
                    <div class="sales-entry-summary-line mb-2"><span class="sales-entry-summary-label">Total Charges</span><span class="sales-entry-summary-value" id="summaryShipping">Rs.0.00</span></div>
                    <hr>
                    <div class="sales-entry-summary-line sales-entry-grand-total mb-3 fs-5 fw-bold"><span class="sales-entry-summary-label">Grand Total</span><span class="sales-entry-summary-value text-primary" id="summaryGrand">₹0.00</span></div>
                </div>
            </div>
            <div class="card mb-3 sales-entry-summary-card">
                <div class="card-header"><h6><i class="fas fa-money-bill me-2"></i>Payment</h6></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label small">Paid</label><input type="number" name="paid_amount" class="form-control" step="0.01" value="0" id="paidInput" min="0"></div>
                    <div class="mb-2"><label class="form-label small">Method</label><select name="payment_method" class="form-select form-select-sm"><option value="cash">Cash</option><option value="bank">Bank</option><option value="online">UPI / Online</option><option value="cheque">Cheque</option><option value="other">Other</option></select></div>
                    <div class="mb-2"><label class="form-label small">Note</label><textarea name="note" class="form-control form-control-sm" rows="2"></textarea></div>
                </div>
            </div>
            <div class="sales-entry-summary-actions">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Save Sale</button>
                <a href="<?= APP_URL ?>/index.php?page=sales" class="btn btn-outline-secondary">Cancel</a>
            </div>
            </div>
        </div>
    </div>
</form>
</div>

<div class="modal fade" id="masterSetupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="masterSetupModalLabel">Quick Setup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="masterSetupFrame" src="about:blank" title="Quick Setup" style="width:100%;height:78vh;border:0;"></iframe>
            </div>
        </div>
    </div>
</div>

<?php $inlineScript = "
let itemIndex = 0;
const currentTaxStatus = " . ((!isset($settings['enable_tax']) || $settings['enable_tax']) ? 'true' : 'false') . ";
const currentGstStatus = " . ((!isset($settings['enable_gst']) || $settings['enable_gst']) ? 'true' : 'false') . ";
const autoRoundOffEnabled = " . (!empty($settings['auto_round_off_rupee']) ? 'true' : 'false') . ";
const taxCalculationEnabled = currentTaxStatus && currentGstStatus;
const APP = '" . APP_URL . "';
const SALES_QUICK_LINKS = {
    customer: '" . $quickMasterLinks['customer'] . "',
    product: '" . $quickMasterLinks['product'] . "',
    category: '" . $quickMasterLinks['category'] . "',
    brand: '" . $quickMasterLinks['brand'] . "',
    unit: '" . $quickMasterLinks['unit'] . "',
    supplier: '" . $quickMasterLinks['supplier'] . "'
};
const SALES_DRAFT_KEY = 'salesCreateDraft';
document.getElementById('addItemBtn').addEventListener('click', addItem);
const masterModalEl = document.getElementById('masterSetupModal');
const masterModal = masterModalEl ? new bootstrap.Modal(masterModalEl) : null;
const masterFrame = document.getElementById('masterSetupFrame');
const masterModalLabel = document.getElementById('masterSetupModalLabel');

function openMasterModal(url, title) {
    saveSalesDraft();
    if (masterModalLabel) masterModalLabel.textContent = title || 'Quick Setup';
    if (masterFrame) masterFrame.src = url;
    if (masterModal) masterModal.show();
}

function saveSalesDraft() {
    const form = document.getElementById('saleForm');
    if (!form) return;
    const draft = {
        customer_id: form.querySelector('[name=\"customer_id\"]')?.value || '',
        warehouse_id: form.querySelector('[name=\"warehouse_id\"]')?.value || '',
        sale_date: form.querySelector('[name=\"sale_date\"]')?.value || '',
        reference_number: form.querySelector('[name=\"reference_number\"]')?.value || '',
        discount_amount: form.querySelector('[name=\"discount_amount\"]')?.value || '0',
        gst_type: form.querySelector('[name=\"gst_type\"]')?.value || '',
        freight_charge: form.querySelector('[name=\"freight_charge\"]')?.value || '0',
        loading_charge: form.querySelector('[name=\"loading_charge\"]')?.value || '0',
        shipping_cost: form.querySelector('[name=\"shipping_cost\"]')?.value || '0',
        round_off: form.querySelector('[name=\"round_off\"]')?.value || '0',
        paid_amount: form.querySelector('[name=\"paid_amount\"]')?.value || '0',
        payment_method: form.querySelector('[name=\"payment_method\"]')?.value || '',
        note: form.querySelector('[name=\"note\"]')?.value || '',
        items: []
    };
    document.querySelectorAll('#itemsBody tr').forEach(function(row) {
        draft.items.push({
            product_id: row.querySelector('.product-id')?.value || '',
            product_name: row.querySelector('.product-search')?.value || '',
            quantity: row.querySelector('.qty')?.value || '1',
            price: row.querySelector('.price')?.value || '0',
            discount: row.querySelector('.disc')?.value || '0',
            tax: row.querySelector('.tax')?.value || '0'
        });
    });
    sessionStorage.setItem(SALES_DRAFT_KEY, JSON.stringify(draft));
}

function restoreSalesDraft() {
    const raw = sessionStorage.getItem(SALES_DRAFT_KEY);
    if (!raw) return;
    try {
        const draft = JSON.parse(raw);
        const form = document.getElementById('saleForm');
        if (!form) return;
        const assign = function(name, value) {
            const field = form.querySelector('[name=\"' + name + '\"]');
            if (field && value !== undefined && value !== null) field.value = value;
        };
        assign('customer_id', draft.customer_id);
        assign('warehouse_id', draft.warehouse_id);
        assign('sale_date', draft.sale_date);
        assign('reference_number', draft.reference_number);
        assign('discount_amount', draft.discount_amount);
        assign('gst_type', draft.gst_type);
        assign('freight_charge', draft.freight_charge);
        assign('loading_charge', draft.loading_charge);
        assign('shipping_cost', draft.shipping_cost);
        assign('round_off', draft.round_off);
        assign('paid_amount', draft.paid_amount);
        assign('payment_method', draft.payment_method);
        assign('note', draft.note);
        if (Array.isArray(draft.items) && draft.items.length) {
            document.getElementById('itemsBody').innerHTML = '';
            draft.items.forEach(function(item) {
                addItem();
                const row = document.querySelector('#itemsBody tr:last-child');
                if (!row) return;
                row.querySelector('.product-id').value = item.product_id || '';
                row.querySelector('.product-search').value = item.product_name || '';
                row.querySelector('.qty').value = item.quantity || '1';
                row.querySelector('.price').value = item.price || '0';
                row.querySelector('.disc').value = item.discount || '0';
                row.querySelector('.tax').value = item.tax || '0';
            });
        }
        calc();
    } catch (err) {
        console.error('Failed to restore sales draft', err);
    }
}

document.querySelectorAll('.js-master-modal-trigger').forEach(function(button) {
    button.addEventListener('click', function() {
        openMasterModal(this.dataset.masterLink, this.dataset.masterTitle);
    });
});

document.getElementById('reloadMastersBtn')?.addEventListener('click', function() {
    saveSalesDraft();
    window.location.reload();
});

window.addEventListener('beforeunload', saveSalesDraft);
document.getElementById('saleForm')?.addEventListener('submit', function() {
    sessionStorage.removeItem(SALES_DRAFT_KEY);
});

if (masterModalEl) {
    masterModalEl.addEventListener('hidden.bs.modal', function() {
        if (masterFrame) masterFrame.src = 'about:blank';
    });
}

document.addEventListener('keydown', function(e) {
    const key = (e.key || '').toLowerCase();
    if (e.altKey && e.shiftKey && key === 'y') {
        e.preventDefault();
        openMasterModal(SALES_QUICK_LINKS.customer, 'Add Customer');
    } else if (e.altKey && e.shiftKey && key === 'p') {
        e.preventDefault();
        openMasterModal(SALES_QUICK_LINKS.product, 'Add Product');
    } else if (e.altKey && e.shiftKey && key === 'c') {
        e.preventDefault();
        openMasterModal(SALES_QUICK_LINKS.category, 'Add Category');
    } else if (e.altKey && e.shiftKey && key === 'b') {
        e.preventDefault();
        openMasterModal(SALES_QUICK_LINKS.brand, 'Add Brand');
    } else if (e.altKey && e.shiftKey && key === 'u') {
        e.preventDefault();
        openMasterModal(SALES_QUICK_LINKS.unit, 'Add Unit');
    } else if (e.altKey && e.shiftKey && key === 's') {
        e.preventDefault();
        openMasterModal(SALES_QUICK_LINKS.supplier, 'Add Supplier');
    } else if (e.altKey && e.shiftKey && key === 'r') {
        e.preventDefault();
        saveSalesDraft();
        window.location.reload();
    } else if (e.altKey && key === 'a') {
        e.preventDefault();
        addItem();
    } else if (e.altKey && key === 's') {
        e.preventDefault();
        document.getElementById('saleForm').submit();
    }
});
restoreSalesDraft();

function addItem() {
    const row = document.createElement('tr');
    
    let taxColHtml = taxCalculationEnabled ? \"<td><input type=\\\"number\\\" name=\\\"item_tax_rate[]\\\" class=\\\"form-control form-control-sm tax\\\" step=\\\"0.01\\\" value=\\\"0\\\" min=\\\"0\\\" max=\\\"100\\\"></td>\" : \"<input type=\\\"hidden\\\" name=\\\"item_tax_rate[]\\\" class=\\\"tax\\\" value=\\\"0\\\">\";
    
    row.innerHTML = `
        <td><input type=\"text\" class=\"form-control form-control-sm product-search\" placeholder=\"Search... (Alt+A to add new)\"><input type=\"hidden\" name=\"product_id[]\" class=\"product-id\"></td>
        <td><input type=\"number\" name=\"quantity[]\" class=\"form-control form-control-sm qty\" step=\"0.001\" value=\"1\" min=\"0.001\"></td>
        <td><input type=\"number\" name=\"unit_price[]\" class=\"form-control form-control-sm price\" step=\"0.01\" value=\"0\" min=\"0\"></td>
        <td><input type=\"number\" name=\"item_discount[]\" class=\"form-control form-control-sm disc\" step=\"0.01\" value=\"0\" min=\"0\"></td>
        ` + taxColHtml + `
        <td class=\"fw-bold row-total\">₹0.00</td>
        <td><button type=\"button\" class=\"btn btn-sm btn-outline-danger btn-icon\" onclick=\"this.closest('tr').remove();calc();\"><i class=\"fas fa-times\"></i></button></td>
    `;
    document.getElementById('itemsBody').appendChild(row);
    
    const searchInput = row.querySelector('.product-search');
    let t;
    searchInput.addEventListener('input', function() {
        clearTimeout(t);
        if (this.value.length < 2) return;
        t = setTimeout(() => {
            fetch(APP + '/index.php?page=products&action=search&term=' + encodeURIComponent(this.value))
                .then(r => r.json()).then(data => showDD(data, row, this));
        }, 300);
    });
    row.querySelectorAll('.qty,.price,.disc,.tax').forEach(i => i.addEventListener('input', calc));
    itemIndex++;
    calc();
    setTimeout(() => searchInput.focus(), 100);
}

function showDD(products, row, input) {
    document.querySelectorAll('.pdd').forEach(el => el.remove());
    if (input._kdHandler) { input.removeEventListener('keydown', input._kdHandler); }
    if (!products.length) return;
    
    const dd = document.createElement('div');
    dd.className = 'pdd';
    
    const rect = input.getBoundingClientRect();
    dd.style.cssText = 'position:absolute;z-index:9999;background:var(--card-bg, #fff);border:1px solid var(--border-color, #dee2e6);border-radius:6px;max-height:200px;overflow-y:auto;width:' + rect.width + 'px;top:' + (rect.bottom + window.scrollY) + 'px;left:' + (rect.left + window.scrollX) + 'px;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
    
    let currentIndex = -1;
    let items = [];
    
    products.forEach((p, idx) => {
        const d = document.createElement('div');
        d.style.cssText = 'padding:8px 12px;cursor:pointer;font-size:0.85rem;color:var(--text-primary, #212529);';
        let mrpText = p.mrp ? ' | MRP: ₹' + p.mrp : '';
        const strong = document.createElement('strong');
        strong.textContent = p.name || '';
        const meta = document.createElement('span');
        meta.style.opacity = '0.6';
        meta.textContent = '(Stock: ' + (p.current_stock ?? '') + mrpText + ')';
        d.appendChild(strong);
        d.appendChild(document.createTextNode(' '));
        d.appendChild(meta);
        d.onmouseenter = () => { currentIndex = idx; updateSelection(); };
        d.onmouseleave = () => { d.style.background = 'transparent'; };
        d.onclick = () => {
            row.querySelector('.product-id').value = p.id;
            input.value = p.name_raw || p.name;
            row.querySelector('.price').value = p.selling_price;
            row.querySelector('.tax').value = taxCalculationEnabled ? (p.tax_rate || 0) : 0;
            dd.remove(); calc();
            if (input._kdHandler) input.removeEventListener('keydown', input._kdHandler);
            row.querySelector('.qty').focus();
            row.querySelector('.qty').select();
        };
        dd.appendChild(d);
        items.push(d);
    });
    
    document.body.appendChild(dd);
    
    function updateSelection() {
        items.forEach((item, index) => {
            if (index === currentIndex) {
                item.style.background = 'var(--surface-soft, #f8f9fa)';
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.style.background = 'transparent';
            }
        });
    }

    input._kdHandler = function(e) {
        if (!document.body.contains(dd)) {
            input.removeEventListener('keydown', input._kdHandler);
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentIndex = (currentIndex + 1) % items.length;
            updateSelection();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentIndex = (currentIndex - 1 + items.length) % items.length;
            updateSelection();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentIndex >= 0 && currentIndex < items.length) {
                items[currentIndex].click();
            }
        } else if (e.key === 'Escape') {
            dd.remove();
            input.removeEventListener('keydown', input._kdHandler);
        }
    };
    input.addEventListener('keydown', input._kdHandler);
    
    document.addEventListener('click', function h(e) {
        if (!dd.contains(e.target) && e.target !== input) { 
            dd.remove(); 
            if (input._kdHandler) input.removeEventListener('keydown', input._kdHandler);
            document.removeEventListener('click', h); 
        }
    });
}

function calc() {
    let sub = 0, tax = 0;
    document.querySelectorAll('#itemsBody tr').forEach(r => {
        const q = parseFloat(r.querySelector('.qty')?.value)||0;
        const p = parseFloat(r.querySelector('.price')?.value)||0;
        const d = parseFloat(r.querySelector('.disc')?.value)||0;
        const t = taxCalculationEnabled ? (parseFloat(r.querySelector('.tax')?.value)||0) : 0;
        const s = (q*p)-d;
        const tx = s*(t/100);
        r.querySelector('.row-total').textContent = 'Rs.'+(s+tx).toFixed(2);
        sub += s; tax += tx;
    });
    const disc = parseFloat(document.getElementById('discountInput').value)||0;
    const freight = parseFloat(document.getElementById('freightInput')?.value)||0;
    const loading = parseFloat(document.getElementById('loadingInput')?.value)||0;
    const ship = freight + loading;
    const gstType = document.getElementById('gstTypeInput') ? document.getElementById('gstTypeInput').value : 'auto';
    const shippingInput = document.getElementById('shippingInput');
    if (shippingInput) shippingInput.value = ship.toFixed(2);
    const roundInput = document.getElementById('roundOffInput');
    const baseGrand = sub - disc + tax + ship;
    let round = parseFloat(roundInput?.value)||0;
    if (autoRoundOffEnabled) {
        round = Math.round((Math.round(baseGrand) - baseGrand) * 100) / 100;
        if (roundInput) roundInput.value = round.toFixed(2);
    }
    const grand = baseGrand + round;
    document.getElementById('subtotalDisplay').textContent = 'Rs.'+sub.toFixed(2);
    document.getElementById('summarySubtotal').textContent = 'Rs.'+sub.toFixed(2);
    document.getElementById('summaryTax').textContent = 'Rs.'+tax.toFixed(2);
    if (taxCalculationEnabled) {
        const isIgst = gstType === 'igst';
        const cgstRow = document.getElementById('summaryCgstRow');
        const sgstRow = document.getElementById('summarySgstRow');
        const igstRow = document.getElementById('summaryIgstRow');
        if (isIgst) {
            if (cgstRow) cgstRow.style.display = 'none';
            if (sgstRow) sgstRow.style.display = 'none';
            if (igstRow) igstRow.style.display = '';
            const igst = document.getElementById('summaryIgst');
            if (igst) igst.textContent = 'Rs.'+tax.toFixed(2);
        } else {
            const half = tax / 2;
            if (cgstRow) cgstRow.style.display = '';
            if (sgstRow) sgstRow.style.display = '';
            if (igstRow) igstRow.style.display = 'none';
            const cgst = document.getElementById('summaryCgst');
            const sgst = document.getElementById('summarySgst');
            if (cgst) cgst.textContent = 'Rs.'+half.toFixed(2);
            if (sgst) sgst.textContent = 'Rs.'+half.toFixed(2);
        }
    }
    document.getElementById('summaryDiscount').textContent = '-Rs.'+disc.toFixed(2);
    const summaryFreight = document.getElementById('summaryFreight');
    const summaryLoading = document.getElementById('summaryLoading');
    if (summaryFreight) summaryFreight.textContent = 'Rs.'+freight.toFixed(2);
    if (summaryLoading) summaryLoading.textContent = 'Rs.'+loading.toFixed(2);
    document.getElementById('summaryShipping').textContent = 'Rs.'+ship.toFixed(2);
    document.getElementById('summaryGrand').textContent = 'Rs.'+grand.toFixed(2);
}
document.getElementById('discountInput').addEventListener('input', calc);
if (document.getElementById('freightInput')) document.getElementById('freightInput').addEventListener('input', calc);
if (document.getElementById('loadingInput')) document.getElementById('loadingInput').addEventListener('input', calc);
if (document.getElementById('gstTypeInput')) document.getElementById('gstTypeInput').addEventListener('change', calc);
if (!autoRoundOffEnabled) document.getElementById('roundOffInput').addEventListener('input', calc);
addItem();

// --- AI Voice, Text & Scan Integration (Advanced) ---

// Toast notification helper (replaces alert())
function aiToast(message, type) {
    type = type || 'info';
    const colors = { success: '#16a34a', error: '#dc2626', info: '#2563eb', warning: '#d97706' };
    const icons  = { success: 'check-circle', error: 'exclamation-triangle', info: 'robot', warning: 'exclamation-circle' };
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;padding:14px 22px;border-radius:10px;color:#fff;font-size:0.9rem;max-width:420px;box-shadow:0 8px 32px rgba(0,0,0,0.25);display:flex;align-items:center;gap:10px;animation:slideIn .3s ease;backdrop-filter:blur(8px);background:' + colors[type] + ';';
    toast.innerHTML = '<i class=\"fas fa-' + icons[type] + '\"></i><span>' + message + '</span>';
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 4500);
}

// Standard fetch headers for proper AJAX detection
function aiHeaders() {
    return { 'X-Requested-With': 'XMLHttpRequest' };
}

// Helper to populate items from AI response (with smart DB matching)
function populateAiItems(result) {
    if (result.customer_id) {
        const s = document.querySelector('select[name=\"customer_id\"]');
        const opt = s.querySelector('option[value=\"' + result.customer_id + '\"]');
        if (opt) { s.value = result.customer_id; aiToast('Customer: ' + result.customer_name + ' matched!', 'success'); }
    } else if (result.customer_name) {
        const s = document.querySelector('select[name=\"customer_id\"]');
        let matched = false;
        Array.from(s.options).forEach(opt => {
            if (opt.text.toLowerCase().includes(result.customer_name.toLowerCase())) {
                s.value = opt.value; matched = true;
            }
        });
        if (matched) aiToast('Customer matched!', 'success');
        else aiToast('Customer \\\"' + result.customer_name + '\\\" not found in your list.', 'warning');
    }

    if (result.items && result.items.length) {
        const trs = document.querySelectorAll('#itemsBody tr');
        if (trs.length === 1 && !trs[0].querySelector('.product-id').value) trs[0].remove();

        let matchedCount = 0, unmatchedNames = [];
        result.items.forEach(item => {
            addItem();
            const lastTr = document.querySelector('#itemsBody tr:last-child');
            lastTr.querySelector('.product-search').value = item.matched_name || item.name || '';
            lastTr.querySelector('.qty').value = item.qty || 1;
            if (item.product_id) {
                lastTr.querySelector('.product-id').value = item.product_id;
                if (item.rate) lastTr.querySelector('.price').value = item.rate;
                const taxInput = lastTr.querySelector('.tax');
                if (taxInput && item.tax_rate) taxInput.value = item.tax_rate;
                matchedCount++;
            } else {
                if (item.rate) lastTr.querySelector('.price').value = item.rate;
                unmatchedNames.push(item.name);
            }
        });
        calc();

        if (matchedCount === result.items.length) {
            aiToast(matchedCount + ' items auto-filled with prices & taxes!', 'success');
        } else if (matchedCount > 0) {
            aiToast(matchedCount + ' matched, ' + unmatchedNames.length + ' need manual selection.', 'warning');
        } else {
            aiToast('Items added. Please search & select products for prices.', 'info');
        }
    } else {
        aiToast('No items found. Please try rephrasing.', 'warning');
    }
}

// Robust fetch wrapper
function aiFetch(url, formData) {
    return fetch(url, { method: 'POST', body: formData, headers: aiHeaders() })
    .then(r => {
        const ct = r.headers.get('content-type') || '';
        if (ct.includes('application/json')) return r.json();
        return r.text().then(t => {
            try { return JSON.parse(t); } catch(e) { return { success: false, message: 'Server returned unexpected response. Please refresh & retry.' }; }
        });
    });
}

// 1. Text Parsing
document.getElementById('aiTextBtn').addEventListener('click', function() {
    const textStr = document.getElementById('aiTextInput').value.trim();
    if (!textStr) { aiToast('Please type something first.', 'warning'); return; }
    const btn = this;
    const originalHtml = btn.innerHTML;
    const aiCard = document.getElementById('aiCard');
    
    btn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i>';
    btn.disabled = true;
    aiCard.classList.add('ai-thinking');

    const formData = new FormData();
    formData.append('text', textStr);
    formData.append('csrf_token', document.querySelector('input[name=\"csrf_token\"]').value);

    aiFetch(APP + '/index.php?page=ai&action=parse_sales_text', formData)
        .then(data => {
            btn.innerHTML = originalHtml; btn.disabled = false;
            aiCard.classList.remove('ai-thinking');
            if (data.success && data.data) populateAiItems(data.data);
            else aiToast(data.message || 'AI could not parse the text.', 'error');
        })
        .catch(err => {
            btn.innerHTML = originalHtml; btn.disabled = false;
            aiCard.classList.remove('ai-thinking');
            aiToast('Network error: ' + err.message, 'error');
        });
});

document.getElementById('aiTextInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('aiTextBtn').click(); }
});

// 2. Voice Dictation (Hindi + English)
const voiceBtn = document.getElementById('aiVoiceBtn');
let recognition = null;
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
if (SpeechRecognition) {
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = true;
    recognition.lang = 'hi-IN';

    recognition.onstart = function() {
        voiceBtn.classList.replace('btn-outline-primary', 'btn-danger');
        voiceBtn.innerHTML = '<i class=\"fas fa-stop\"></i>';
        aiToast('Listening... Speak in Hindi or English', 'info');
    };
    recognition.onresult = function(event) {
        let transcript = '';
        for (let i = 0; i < event.results.length; i++) transcript += event.results[i][0].transcript;
        document.getElementById('aiTextInput').value = transcript;
        if (event.results[0].isFinal) document.getElementById('aiTextBtn').click();
    };
    recognition.onerror = function(event) {
        if (event.error !== 'aborted') aiToast('Voice error: ' + event.error, 'error');
    };
    recognition.onend = function() {
        voiceBtn.classList.replace('btn-danger', 'btn-outline-primary');
        voiceBtn.innerHTML = '<i class=\"fas fa-microphone\"></i>';
    };
    voiceBtn.addEventListener('click', () => {
        if (voiceBtn.classList.contains('btn-danger')) recognition.stop();
        else recognition.start();
    });
} else {
    voiceBtn.style.display = 'none';
}

// 3. Scan Image / PDF
document.getElementById('aiScanInput').addEventListener('change', function(e) {
    if (!this.files.length) return;
    const file = this.files[0];
    if (file.size > 10 * 1024 * 1024) {
        aiToast('File too large. Max 10MB allowed.', 'error');
        this.value = ''; return;
    }

    const btn = document.getElementById('aiScanBtn');
    const aiCard = document.getElementById('aiCard');
    btn.disabled = true;
    btn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i>';
    aiCard.classList.add('ai-thinking');
    aiToast('Scanning document with AI...', 'info');

    const formData = new FormData();
    formData.append('order_file', file);
    formData.append('csrf_token', document.querySelector('input[name=\"csrf_token\"]').value);

    aiFetch(APP + '/index.php?page=ai&action=scan_sales_order', formData)
    .then(data => {
        btn.disabled = false;
        aiCard.classList.remove('ai-thinking');
        btn.innerHTML = '<i class=\"fas fa-camera me-1\"></i> Scan';
        if (data.success && data.data) populateAiItems(data.data);
        else aiToast(data.message || 'Could not read the document.', 'error');
    })
    .catch(err => {
        btn.disabled = false;
        aiCard.classList.remove('ai-thinking');
        btn.innerHTML = '<i class=\"fas fa-camera me-1\"></i> Scan';
        aiToast('Scan error: ' + err.message, 'error');
    });
    this.value = '';
});

"; ?>
