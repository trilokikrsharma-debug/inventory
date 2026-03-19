<?php $pageTitle = 'New Sale'; ?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=sales">Sales</a></li><li class="breadcrumb-item active">New</li></ol></nav></div>

<form method="POST" id="saleForm">
    <?= CSRF::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-info-circle me-2"></i>Sale Details</h6></div>
                <div class="card-body"><div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select" required><option value="">Select</option>
                            <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= Helper::escape($c['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-4"><label class="form-label">Reference</label><input type="text" name="reference_number" class="form-control"></div>
                </div></div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-list me-2"></i>Items</h6><button type="button" class="btn btn-sm btn-primary" id="addItemBtn"><i class="fas fa-plus me-1"></i>Add</button></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table mb-0" id="itemsTable">
                    <thead><tr><th style="width:30%">Product</th><th>Qty</th><th>Price</th><th>Disc</th>
                        <?php if((!isset($settings['enable_tax']) || $settings['enable_tax']) && (!isset($settings['enable_gst']) || $settings['enable_gst'])): ?>
                        <th>Tax%</th>
                        <?php endif; ?>
                        <th>Total</th><th></th></tr></thead>
                    <tbody id="itemsBody"></tbody>
                    <tfoot><tr><td colspan="<?= ((!isset($settings['enable_tax']) || $settings['enable_tax']) && (!isset($settings['enable_gst']) || $settings['enable_gst'])) ? 5 : 4 ?>" class="text-end fw-bold">Subtotal:</td><td class="fw-bold" id="subtotalDisplay">₹0.00</td><td></td></tr></tfoot>
                </table></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-calculator me-2"></i>Summary</h6></div>
                <div class="card-body">
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
                    <hr>
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span id="summarySubtotal">₹0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Tax</span><span id="summaryTax">Rs.0.00</span></div>
                    <div class="d-flex justify-content-between mb-2" id="summaryCgstRow" style="display:none;"><span>CGST</span><span id="summaryCgst">Rs.0.00</span></div>
                    <div class="d-flex justify-content-between mb-2" id="summarySgstRow" style="display:none;"><span>SGST</span><span id="summarySgst">Rs.0.00</span></div>
                    <div class="d-flex justify-content-between mb-2" id="summaryIgstRow" style="display:none;"><span>IGST</span><span id="summaryIgst">Rs.0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Discount</span><span id="summaryDiscount">-Rs.0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Freight</span><span id="summaryFreight">Rs.0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Loading</span><span id="summaryLoading">Rs.0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Total Charges</span><span id="summaryShipping">Rs.0.00</span></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3 fs-5 fw-bold"><span>Grand Total</span><span id="summaryGrand" class="text-primary">₹0.00</span></div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-money-bill me-2"></i>Payment</h6></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label small">Paid</label><input type="number" name="paid_amount" class="form-control" step="0.01" value="0" id="paidInput" min="0"></div>
                    <div class="mb-2"><label class="form-label small">Method</label><select name="payment_method" class="form-select form-select-sm"><option value="cash">Cash</option><option value="bank">Bank</option><option value="online">UPI / Online</option><option value="cheque">Cheque</option><option value="other">Other</option></select></div>
                    <div class="mb-2"><label class="form-label small">Note</label><textarea name="note" class="form-control form-control-sm" rows="2"></textarea></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="fas fa-save me-2"></i>Save Sale</button>
        </div>
    </div>
</form>

<?php $inlineScript = "
let itemIndex = 0;
const currentTaxStatus = " . ((!isset($settings['enable_tax']) || $settings['enable_tax']) ? 'true' : 'false') . ";
const currentGstStatus = " . ((!isset($settings['enable_gst']) || $settings['enable_gst']) ? 'true' : 'false') . ";
const autoRoundOffEnabled = " . (!empty($settings['auto_round_off_rupee']) ? 'true' : 'false') . ";
const taxCalculationEnabled = currentTaxStatus && currentGstStatus;
const APP = '" . APP_URL . "';
document.getElementById('addItemBtn').addEventListener('click', addItem);

document.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'a') {
        e.preventDefault();
        addItem();
    } else if (e.altKey && e.key === 's') {
        e.preventDefault();
        document.getElementById('saleForm').submit();
    }
});

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
        d.style.cssText = 'padding:8px 12px;cursor:pointer;font-size:0.85rem;color:var(--body-color, #212529);';
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
                item.style.background = 'var(--hover-bg, #f8f9fa)';
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
"; ?>


