<?php $pageTitle = 'Edit Purchase: ' . Helper::escape($purchase['invoice_number']); ?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=purchases">Purchases</a></li><li class="breadcrumb-item active">Edit <?= Helper::escape($purchase['invoice_number']) ?></li></ol></nav></div>

<div class="alert alert-info py-2 small"><i class="fas fa-info-circle me-1"></i>Editing will <strong>reverse old stock/balance</strong> and <strong>apply new values</strong> automatically.</div>

<form method="POST" id="purchaseForm">
    <?= CSRF::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-info-circle me-2"></i>Purchase Details</h6></div>
                <div class="card-body"><div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required><option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>" <?= $s['id'] == $purchase['supplier_id'] ? 'selected' : '' ?>><?= Helper::escape($s['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-4"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="purchase_date" class="form-control" value="<?= $purchase['purchase_date'] ?>" required></div>
                    <div class="col-md-4"><label class="form-label">Reference No.</label><input type="text" name="reference_number" class="form-control" value="<?= Helper::escape($purchase['reference_number'] ?? '') ?>"></div>
                </div></div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-list me-2"></i>Items</h6><button type="button" class="btn btn-sm btn-primary" id="addItemBtn"><i class="fas fa-plus me-1"></i>Add Item</button></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table mb-0" id="itemsTable">
                    <thead><tr><th style="width:30%">Product</th><th>Qty</th><th>Price</th><th>Discount</th><th>Tax %</th><th>Total</th><th></th></tr></thead>
                    <tbody id="itemsBody"></tbody>
                    <tfoot><tr><td colspan="5" class="text-end fw-bold">Subtotal:</td><td class="fw-bold" id="subtotalDisplay">₹0.00</td><td></td></tr></tfoot>
                </table></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-calculator me-2"></i>Summary</h6></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label small">Discount</label><input type="number" name="discount_amount" class="form-control form-control-sm" step="0.01" value="<?= $purchase['discount_amount'] ?>" id="discountInput"></div>
                    <div class="mb-2"><label class="form-label small">Shipping</label><input type="number" name="shipping_cost" class="form-control form-control-sm" step="0.01" value="<?= $purchase['shipping_cost'] ?>" id="shippingInput"></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><span id="summarySubtotal">₹0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Tax</span><span id="summaryTax">₹0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Discount</span><span id="summaryDiscount">-₹0.00</span></div>
                    <div class="d-flex justify-content-between mb-2"><span>Shipping</span><span id="summaryShipping">₹0.00</span></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3 fs-5 fw-bold"><span>Grand Total</span><span id="summaryGrand" class="text-primary">₹0.00</span></div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-money-bill me-2"></i>Payment</h6></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label small">Paid Amount</label><input type="number" name="paid_amount" class="form-control" step="0.01" value="<?= $purchase['paid_amount'] ?>" id="paidInput"></div>
                    <div class="mb-2"><label class="form-label small">Note</label><textarea name="note" class="form-control form-control-sm" rows="2"><?= Helper::escape($purchase['note'] ?? '') ?></textarea></div>
                </div>
            </div>
            <button type="submit" class="btn btn-warning w-100 btn-lg"><i class="fas fa-save me-2"></i>Update Purchase</button>
            <a href="<?= APP_URL ?>/index.php?page=purchases&action=view_purchase&id=<?= $purchase['id'] ?>" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
        </div>
    </div>
</form>

<?php
$existingItems = json_encode(array_map(function($item) {
    return [
        'product_id'   => $item['product_id'],
        'product_name' => Helper::decodeHtmlEntities($item['product_name'] ?? ''),
        'quantity'     => (float)$item['quantity'],
        'unit_price'   => (float)$item['unit_price'],
        'discount'     => (float)$item['discount'],
        'tax_rate'     => (float)$item['tax_rate'],
    ];
}, $purchase['items']));

$inlineScript = "
let itemIndex = 0;
const APP = '" . APP_URL . "';
const existingItems = " . $existingItems . ";

document.getElementById('addItemBtn').addEventListener('click', () => addItemRow());

function addItemRow(prefill) {
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type=\"text\" class=\"form-control form-control-sm product-search\" placeholder=\"Search product...\">
            <input type=\"hidden\" name=\"product_id[]\" class=\"product-id\"></td>
        <td><input type=\"number\" name=\"quantity[]\" class=\"form-control form-control-sm qty-input\" step=\"0.001\" value=\"1\" min=\"0.001\"></td>
        <td><input type=\"number\" name=\"unit_price[]\" class=\"form-control form-control-sm price-input\" step=\"0.01\" value=\"0\"></td>
        <td><input type=\"number\" name=\"item_discount[]\" class=\"form-control form-control-sm disc-input\" step=\"0.01\" value=\"0\"></td>
        <td><input type=\"number\" name=\"item_tax_rate[]\" class=\"form-control form-control-sm tax-input\" step=\"0.01\" value=\"0\"></td>
        <td class=\"fw-bold item-total\">₹0.00</td>
        <td><button type=\"button\" class=\"btn btn-sm btn-outline-danger\" onclick=\"this.closest('tr').remove(); calculateTotals();\"><i class=\"fas fa-times\"></i></button></td>
    `;
    document.getElementById('itemsBody').appendChild(row);
    if (prefill) {
        row.querySelector('.product-id').value = prefill.product_id;
        row.querySelector('.product-search').value = prefill.product_name;
        row.querySelector('.qty-input').value = prefill.quantity;
        row.querySelector('.price-input').value = prefill.unit_price;
        row.querySelector('.disc-input').value = prefill.discount;
        row.querySelector('.tax-input').value = prefill.tax_rate;
    }
    const searchInput = row.querySelector('.product-search');
    let timeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        if (this.value.length < 2) return;
        timeout = setTimeout(() => {
            fetch(APP + '/index.php?page=products&action=search&term=' + encodeURIComponent(this.value))
                .then(r => r.json())
                .then(data => showProductDropdown(data, row, searchInput));
        }, 300);
    });
    row.querySelectorAll('.qty-input, .price-input, .disc-input, .tax-input').forEach(input => {
        input.addEventListener('input', calculateTotals);
    });
    itemIndex++;
    calculateTotals();
}

function showProductDropdown(products, row, input) {
    let existing = row.querySelector('.product-dropdown');
    if (existing) existing.remove();
    if (products.length === 0) return;
    const dropdown = document.createElement('div');
    dropdown.className = 'product-dropdown';
    dropdown.style.cssText = 'position:absolute;z-index:1000;background:var(--card-bg,#fff);border:1px solid var(--border-color,#ddd);border-radius:6px;max-height:200px;overflow-y:auto;width:100%;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
    products.forEach(p => {
        const item = document.createElement('div');
        item.style.cssText = 'padding:8px 12px;cursor:pointer;font-size:0.85rem;';
        item.innerHTML = '<strong>' + p.name + '</strong> <span style=\"opacity:0.6\">(Stock: ' + p.current_stock + ')</span>';
        item.addEventListener('mouseenter', () => item.style.background = '#f0f0f0');
        item.addEventListener('mouseleave', () => item.style.background = 'transparent');
        item.addEventListener('click', () => {
            row.querySelector('.product-id').value = p.id;
            input.value = p.name_raw || p.name;
            row.querySelector('.price-input').value = p.purchase_price;
            row.querySelector('.tax-input').value = p.tax_rate || 0;
            dropdown.remove();
            calculateTotals();
        });
        dropdown.appendChild(item);
    });
    input.parentElement.style.position = 'relative';
    input.parentElement.appendChild(dropdown);
    document.addEventListener('click', function handler(e) {
        if (!input.parentElement.contains(e.target)) {
            dropdown.remove();
            document.removeEventListener('click', handler);
        }
    });
}

function calculateTotals() {
    let subtotal = 0, totalTax = 0;
    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
        const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
        const disc = parseFloat(row.querySelector('.disc-input')?.value) || 0;
        const taxRate = parseFloat(row.querySelector('.tax-input')?.value) || 0;
        const itemSub = (qty * price) - disc;
        const tax = itemSub * (taxRate / 100);
        row.querySelector('.item-total').textContent = '₹' + (itemSub + tax).toFixed(2);
        subtotal += itemSub;
        totalTax += tax;
    });
    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const shipping = parseFloat(document.getElementById('shippingInput').value) || 0;
    const grand = subtotal - discount + totalTax + shipping;
    document.getElementById('subtotalDisplay').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('summarySubtotal').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('summaryTax').textContent = '₹' + totalTax.toFixed(2);
    document.getElementById('summaryDiscount').textContent = '-₹' + discount.toFixed(2);
    document.getElementById('summaryShipping').textContent = '₹' + shipping.toFixed(2);
    document.getElementById('summaryGrand').textContent = '₹' + grand.toFixed(2);
}

document.getElementById('discountInput').addEventListener('input', calculateTotals);
document.getElementById('shippingInput').addEventListener('input', calculateTotals);

// Prefill existing items
existingItems.forEach(item => addItemRow(item));
if (existingItems.length === 0) addItemRow();
";
?>
