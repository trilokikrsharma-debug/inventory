<?php $pageTitle = 'New Purchase'; ?>
<?php $hasWarehouseFeature = !empty($hasWarehouseFeature); ?>
<?php $warehouses = is_array($warehouses ?? null) ? $warehouses : []; ?>
<?php $supplierCount = count($suppliers ?? []); ?>
<?php
$quickMasterLinks = [
    'supplier' => APP_URL . '/index.php?page=suppliers&action=create',
    'product' => APP_URL . '/index.php?page=products&action=create',
    'category' => APP_URL . '/index.php?page=categories&action=create',
    'brand' => APP_URL . '/index.php?page=brands&action=create',
    'unit' => APP_URL . '/index.php?page=units&action=create',
    'customer' => APP_URL . '/index.php?page=customers&action=create',
];
?>
<div class="sales-entry-shell">
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=purchases">Purchases</a></li><li class="breadcrumb-item active">New</li></ol></nav></div>

<form method="POST" id="purchaseForm">
    <?= CSRF::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3 sales-entry-card border-info" style="background-color: var(--surface-bg, #f8f9fa);">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0 text-info fw-bold"><i class="fas fa-magic me-2"></i>AI Smart Scan (Enterprise)</h6>
                            <small class="text-muted">Upload an invoice photo/PDF to auto-fill details using Gemini AI.</small>
                        </div>
                        <input type="file" id="aiScanInput" class="d-none" accept="image/jpeg,image/png,image/webp,application/pdf">
                        <button type="button" class="btn btn-info text-white shadow-sm" onclick="document.getElementById('aiScanInput').click();" id="aiScanBtn">
                            <i class="fas fa-upload me-1"></i> Upload & Scan
                        </button>
                    </div>
                </div>
            </div>
            <div class="card mb-3 sales-entry-card">
                <div class="card-header"><h6><i class="fas fa-info-circle me-2"></i>Purchase Details</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label d-flex justify-content-between align-items-center gap-2"><span>Supplier <span class="text-danger">*</span></span><button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['supplier']) ?>" data-master-title="Add Supplier"><i class="fas fa-plus me-1"></i>Add Supplier</button></label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>"><?= Helper::escape($s['name']) ?></option><?php endforeach; ?>
                            </select>
                            <?php if ($supplierCount === 0): ?>
                            <div class="form-text text-danger"><i class="fas fa-circle-exclamation me-1"></i>No suppliers available. Press <strong>Alt+Shift+S</strong> to open supplier create in a new tab.</div>
                            <?php else: ?>
                            <div class="form-text">If the supplier is missing, press <strong>Alt+Shift+S</strong> to add one without losing this draft.</div>
                            <?php endif; ?>
                        </div>
                        <?php if ($hasWarehouseFeature): ?>
                        <div class="col-md-4"><label class="form-label">Warehouse <span class="text-danger">*</span></label>
                            <select name="warehouse_id" class="form-select" required>
                                <option value="">Select Warehouse</option>
                                <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?= (int)$warehouse['id'] ?>" <?= !empty($warehouse['is_default']) ? 'selected' : '' ?>>
                                    <?= Helper::escape($warehouse['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Reference No.</label><input type="text" name="reference_number" class="form-control" placeholder="Bill/Challan No."></div>
                    </div>
                </div>
            </div>
            <div class="card mb-3 sales-entry-card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2"><h6><i class="fas fa-list me-2"></i>Items</h6><div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-sm btn-outline-primary js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['product']) ?>" data-master-title="Add Product"><i class="fas fa-box-open me-1"></i>New Product</button><button type="button" class="btn btn-sm btn-primary" id="addItemBtn"><i class="fas fa-plus me-1"></i>Add Item</button></div></div>
                <div class="card-body p-0">
                    <div class="table-responsive"><table class="table mb-0 sales-items-table" id="itemsTable">
                        <thead><tr><th style="width:30%">Product</th><th>Qty</th><th>Price</th><th>Discount</th><?php if((!isset($settings['enable_tax']) || $settings['enable_tax']) && (!isset($settings['enable_gst']) || $settings['enable_gst'])): ?><th>Tax %</th><?php endif; ?><th>Total</th><th></th></tr></thead>
                        <tbody id="itemsBody"></tbody>
                        <tfoot>
                            <tr><td colspan="<?= ((!isset($settings['enable_tax']) || $settings['enable_tax']) && (!isset($settings['enable_gst']) || $settings['enable_gst'])) ? 5 : 4 ?>" class="text-end fw-bold">Subtotal:</td><td class="fw-bold" id="subtotalDisplay">₹0.00</td><td></td></tr>
                        </tfoot>
                    </table></div>
                    <div class="px-3 py-2 border-top bg-light small text-muted">
                        Product search works inside item rows. If a product is missing, press <strong>Alt+Shift+P</strong> for product create.
                        For product master dependencies use <strong>Alt+Shift+C</strong> category, <strong>Alt+Shift+B</strong> brand, <strong>Alt+Shift+U</strong> unit.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="sales-entry-summary">
            <div class="card mb-3 sales-entry-summary-card border-primary-subtle">
                <div class="card-header"><h6><i class="fas fa-bolt me-2"></i>Quick Setup While Staying Here</h6></div>
                <div class="card-body">
                    <div class="alert alert-light border small mb-3">
                        Purchase page needs <strong>Supplier</strong> first, then <strong>Product</strong>. Product creation may need <strong>Category</strong>, <strong>Brand</strong>, and <strong>Unit</strong>. <strong>Customer</strong> is not needed for purchases, but shortcut is kept for quick sales-side setup.
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['supplier']) ?>" data-master-title="Add Supplier"><span><i class="fas fa-truck-field me-2"></i>Add Supplier</span><span class="text-muted">Alt+Shift+S</span></button>
                        <button type="button" class="btn btn-outline-primary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['product']) ?>" data-master-title="Add Product"><span><i class="fas fa-box-open me-2"></i>Add Product</span><span class="text-muted">Alt+Shift+P</span></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['category']) ?>" data-master-title="Add Category"><span><i class="fas fa-layer-group me-2"></i>Add Category</span><span class="text-muted">Alt+Shift+C</span></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['brand']) ?>" data-master-title="Add Brand"><span><i class="fas fa-tags me-2"></i>Add Brand</span><span class="text-muted">Alt+Shift+B</span></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['unit']) ?>" data-master-title="Add Unit"><span><i class="fas fa-ruler-combined me-2"></i>Add Unit</span><span class="text-muted">Alt+Shift+U</span></button>
                        <button type="button" class="btn btn-outline-secondary btn-sm justify-content-between d-flex align-items-center js-master-modal-trigger" data-master-link="<?= Helper::escape($quickMasterLinks['customer']) ?>" data-master-title="Add Customer"><span><i class="fas fa-user-plus me-2"></i>Add Customer</span><span class="text-muted">Alt+Shift+Y</span></button>
                        <button type="button" class="btn btn-success btn-sm justify-content-between d-flex align-items-center" id="reloadMastersBtn"><span><i class="fas fa-rotate-right me-2"></i>Reload Lists</span><span class="text-muted">Alt+Shift+R</span></button>
                    </div>
                    <div class="form-text mt-2">Open master forms in this page, save there, then press <strong>Reload Lists</strong>. Your draft will be restored automatically.</div>
                </div>
            </div>
            <div class="card mb-3 sales-entry-summary-card">
                <div class="card-header"><h6><i class="fas fa-calculator me-2"></i>Summary</h6></div>
                <div class="card-body">
                    <div class="sales-entry-summary-grid">
                    <div class="mb-2"><label class="form-label small">Discount</label><input type="number" name="discount_amount" class="form-control form-control-sm" step="0.01" value="0" id="discountInput"></div>
                    <div class="mb-2"><label class="form-label small">Shipping</label><input type="number" name="shipping_cost" class="form-control form-control-sm" step="0.01" value="0" id="shippingInput"></div>
                    </div>
                    <hr>
                    <div class="sales-entry-summary-line mb-2"><span class="sales-entry-summary-label">Subtotal</span><span class="sales-entry-summary-value" id="summarySubtotal">₹0.00</span></div>
                    <div class="sales-entry-summary-line mb-2"><span class="sales-entry-summary-label">Tax</span><span class="sales-entry-summary-value" id="summaryTax">₹0.00</span></div>
                    <div class="sales-entry-summary-line mb-2"><span class="sales-entry-summary-label">Discount</span><span class="sales-entry-summary-value" id="summaryDiscount">-₹0.00</span></div>
                    <div class="sales-entry-summary-line mb-2"><span class="sales-entry-summary-label">Shipping</span><span class="sales-entry-summary-value" id="summaryShipping">₹0.00</span></div>
                    <hr>
                    <div class="sales-entry-summary-line sales-entry-grand-total mb-3 fs-5 fw-bold"><span class="sales-entry-summary-label">Grand Total</span><span class="sales-entry-summary-value text-primary" id="summaryGrand">₹0.00</span></div>
                </div>
            </div>
            <div class="card mb-3 sales-entry-summary-card">
                <div class="card-header"><h6><i class="fas fa-money-bill me-2"></i>Payment</h6></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label small">Paid Amount</label><input type="number" name="paid_amount" class="form-control" step="0.01" value="0" id="paidInput"></div>
                    <div class="mb-2"><label class="form-label small">Method</label><select name="payment_method" class="form-select form-select-sm"><option value="cash">Cash</option><option value="bank">Bank</option><option value="online">UPI / Online</option><option value="cheque">Cheque</option><option value="other">Other</option></select></div>
                    <div class="mb-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="received">Received</option><option value="pending">Pending</option></select></div>
                    <div class="mb-2"><label class="form-label small">Note</label><textarea name="note" class="form-control form-control-sm" rows="2"></textarea></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="fas fa-save me-2"></i>Save Purchase</button>
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
const taxCalculationEnabled = currentTaxStatus && currentGstStatus;
const APP = '" . APP_URL . "';
const PURCHASE_QUICK_LINKS = {
    supplier: '" . $quickMasterLinks['supplier'] . "',
    product: '" . $quickMasterLinks['product'] . "',
    category: '" . $quickMasterLinks['category'] . "',
    brand: '" . $quickMasterLinks['brand'] . "',
    unit: '" . $quickMasterLinks['unit'] . "',
    customer: '" . $quickMasterLinks['customer'] . "'
};
const PURCHASE_DRAFT_KEY = 'purchaseCreateDraft';
document.getElementById('addItemBtn').addEventListener('click', addItem);
const masterModalEl = document.getElementById('masterSetupModal');
const masterModal = masterModalEl ? new bootstrap.Modal(masterModalEl) : null;
const masterFrame = document.getElementById('masterSetupFrame');
const masterModalLabel = document.getElementById('masterSetupModalLabel');

function openMasterModal(url, title) {
    savePurchaseDraft();
    if (masterModalLabel) masterModalLabel.textContent = title || 'Quick Setup';
    if (masterFrame) masterFrame.src = url;
    if (masterModal) masterModal.show();
}

function savePurchaseDraft() {
    const form = document.getElementById('purchaseForm');
    if (!form) return;
    const draft = {
        supplier_id: form.querySelector('[name=\"supplier_id\"]')?.value || '',
        warehouse_id: form.querySelector('[name=\"warehouse_id\"]')?.value || '',
        purchase_date: form.querySelector('[name=\"purchase_date\"]')?.value || '',
        reference_number: form.querySelector('[name=\"reference_number\"]')?.value || '',
        discount_amount: form.querySelector('[name=\"discount_amount\"]')?.value || '0',
        shipping_cost: form.querySelector('[name=\"shipping_cost\"]')?.value || '0',
        paid_amount: form.querySelector('[name=\"paid_amount\"]')?.value || '0',
        payment_method: form.querySelector('[name=\"payment_method\"]')?.value || '',
        status: form.querySelector('[name=\"status\"]')?.value || '',
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
    sessionStorage.setItem(PURCHASE_DRAFT_KEY, JSON.stringify(draft));
}

function restorePurchaseDraft() {
    const raw = sessionStorage.getItem(PURCHASE_DRAFT_KEY);
    if (!raw) return;
    try {
        const draft = JSON.parse(raw);
        const form = document.getElementById('purchaseForm');
        if (!form) return;
        const assign = function(name, value) {
            const field = form.querySelector('[name=\"' + name + '\"]');
            if (field && value !== undefined && value !== null) field.value = value;
        };
        assign('supplier_id', draft.supplier_id);
        assign('warehouse_id', draft.warehouse_id);
        assign('purchase_date', draft.purchase_date);
        assign('reference_number', draft.reference_number);
        assign('discount_amount', draft.discount_amount);
        assign('shipping_cost', draft.shipping_cost);
        assign('paid_amount', draft.paid_amount);
        assign('payment_method', draft.payment_method);
        assign('status', draft.status);
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
        console.error('Failed to restore purchase draft', err);
    }
}

document.querySelectorAll('.js-master-modal-trigger').forEach(function(button) {
    button.addEventListener('click', function() {
        openMasterModal(this.dataset.masterLink, this.dataset.masterTitle);
    });
});

document.getElementById('reloadMastersBtn')?.addEventListener('click', function() {
    savePurchaseDraft();
    window.location.reload();
});

window.addEventListener('beforeunload', savePurchaseDraft);
document.getElementById('purchaseForm')?.addEventListener('submit', function() {
    sessionStorage.removeItem(PURCHASE_DRAFT_KEY);
});

if (masterModalEl) {
    masterModalEl.addEventListener('hidden.bs.modal', function() {
        if (masterFrame) masterFrame.src = 'about:blank';
    });
}

document.addEventListener('keydown', function(e) {
    const key = (e.key || '').toLowerCase();
    if (e.altKey && e.shiftKey && key === 's') {
        e.preventDefault();
        openMasterModal(PURCHASE_QUICK_LINKS.supplier, 'Add Supplier');
    } else if (e.altKey && e.shiftKey && key === 'p') {
        e.preventDefault();
        openMasterModal(PURCHASE_QUICK_LINKS.product, 'Add Product');
    } else if (e.altKey && e.shiftKey && key === 'c') {
        e.preventDefault();
        openMasterModal(PURCHASE_QUICK_LINKS.category, 'Add Category');
    } else if (e.altKey && e.shiftKey && key === 'b') {
        e.preventDefault();
        openMasterModal(PURCHASE_QUICK_LINKS.brand, 'Add Brand');
    } else if (e.altKey && e.shiftKey && key === 'u') {
        e.preventDefault();
        openMasterModal(PURCHASE_QUICK_LINKS.unit, 'Add Unit');
    } else if (e.altKey && e.shiftKey && key === 'y') {
        e.preventDefault();
        openMasterModal(PURCHASE_QUICK_LINKS.customer, 'Add Customer');
    } else if (e.altKey && e.shiftKey && key === 'r') {
        e.preventDefault();
        savePurchaseDraft();
        window.location.reload();
    } else if (e.altKey && key === 'a') {
        e.preventDefault();
        addItem();
    } else if (e.altKey && key === 's') {
        e.preventDefault();
        document.getElementById('purchaseForm').submit();
    }
});
restorePurchaseDraft();

function addItem() {
    const row = document.createElement('tr');
    
    let taxColHtml = taxCalculationEnabled ? \"<td><input type=\\\"number\\\" name=\\\"item_tax_rate[]\\\" class=\\\"form-control form-control-sm tax\\\" step=\\\"0.01\\\" value=\\\"0\\\"></td>\" : \"<input type=\\\"hidden\\\" name=\\\"item_tax_rate[]\\\" class=\\\"tax\\\" value=\\\"0\\\">\";
    
    row.innerHTML = `
        <td><input type=\"text\" class=\"form-control form-control-sm product-search\" placeholder=\"Search... (Alt+A to add new)\"><input type=\"hidden\" name=\"product_id[]\" class=\"product-id\"></td>
        <td><input type=\"number\" name=\"quantity[]\" class=\"form-control form-control-sm qty\" step=\"0.001\" value=\"1\" min=\"0.001\"></td>
        <td><input type=\"number\" name=\"unit_price[]\" class=\"form-control form-control-sm price\" step=\"0.01\" value=\"0\"></td>
        <td><input type=\"number\" name=\"item_discount[]\" class=\"form-control form-control-sm disc\" step=\"0.01\" value=\"0\"></td>
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
            row.querySelector('.price').value = p.purchase_price || 0;
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
        r.querySelector('.row-total').textContent = '₹'+(s+tx).toFixed(2);
        sub += s; tax += tx;
    });
    const disc = parseFloat(document.getElementById('discountInput').value)||0;
    const ship = parseFloat(document.getElementById('shippingInput').value)||0;
    const grand = sub - disc + tax + ship;
    document.getElementById('subtotalDisplay').textContent = '₹'+sub.toFixed(2);
    document.getElementById('summarySubtotal').textContent = '₹'+sub.toFixed(2);
    document.getElementById('summaryTax').textContent = '₹'+tax.toFixed(2);
    document.getElementById('summaryDiscount').textContent = '-₹'+disc.toFixed(2);
    document.getElementById('summaryShipping').textContent = '₹'+ship.toFixed(2);
    document.getElementById('summaryGrand').textContent = '₹'+grand.toFixed(2);
}
document.getElementById('discountInput').addEventListener('input', calc);
document.getElementById('shippingInput').addEventListener('input', calc);
addItem();

// AI Integration
document.getElementById('aiScanInput').addEventListener('change', function(e) {
    if (!this.files.length) return;
    const btn = document.getElementById('aiScanBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class=\"fas fa-spinner fa-spin me-1\"></i> Scanning...';
    
    const formData = new FormData();
    formData.append('invoice_file', this.files[0]);
    formData.append('csrf_token', document.querySelector('input[name=\"csrf_token\"]').value);
    
    fetch(APP + '/index.php?page=ai&action=scan_invoice', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class=\"fas fa-upload me-1\"></i> Upload & Scan';
        if (data.success && data.data) {
            const result = data.data;
            if (result.invoice_no) document.querySelector('input[name=\"reference_number\"]').value = result.invoice_no;
            if (result.date) document.querySelector('input[name=\"purchase_date\"]').value = result.date;
            
            // Note: We might not automatically select Supplier because it requires matching ID,
            // but we can at least show a note or try to match.
            if (result.party_name) {
                const s = document.querySelector('select[name=\"supplier_id\"]');
                Array.from(s.options).forEach(opt => {
                    if (opt.text.toLowerCase().includes(result.party_name.toLowerCase())) s.value = opt.value;
                });
            }
            
            // Add items
            if (result.items && result.items.length) {
                // Clear initial empty item if needed
                const trs = document.querySelectorAll('#itemsBody tr');
                if (trs.length === 1 && !trs[0].querySelector('.product-id').value) trs[0].remove();
                
                result.items.forEach(item => {
                    addItem();
                    const lastTr = document.querySelector('#itemsBody tr:last-child');
                    // We don't have product IDs easily mapped to names via OCR, so just put the raw name
                    // In a full implementation, we'd search or create product. Here, we just fill the text.
                    lastTr.querySelector('.product-search').value = item.name || '';
                    lastTr.querySelector('.qty').value = item.qty || 1;
                    lastTr.querySelector('.price').value = item.rate || 0;
                });
                calc();
                alert('Success: Invoice scanned and data populated. Please verify items.');
            }
        } else {
            alert('AI Scan Failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class=\"fas fa-upload me-1\"></i> Upload & Scan';
        alert('AI Scan Error: ' + err.message);
    });
});
"; ?>
