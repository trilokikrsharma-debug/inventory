<?php $pageTitle = 'New Sale Return'; ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=sale_returns">Returns</a></li>
        <li class="breadcrumb-item active">New Return</li>
    </ol></nav>
</div>

<form method="POST" id="returnForm">
    <?= CSRF::field() ?>
    <div class="row g-3">
        <!-- Left: Sale Selection & Items -->
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-file-invoice me-2"></i>Select Original Sale</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sale Invoice <span class="text-danger">*</span></label>
                            <select name="sale_id" id="saleSelect" class="form-select" required onchange="loadSaleItems(this.value)">
                                <option value="">-- Select Sale --</option>
                                <?php foreach ($recentSales as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($sale && $sale['id'] == $s['id']) ? 'selected' : '' ?>>
                                    <?= Helper::escape($s['invoice_number']) ?> - <?= Helper::escape($s['customer_name'] ?? '') ?> (Due: <?= Helper::formatCurrency($s['due_amount']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Return Date</label>
                            <input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reason</label>
                            <input type="text" name="reason" class="form-control" placeholder="Defective, wrong item...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="card">
                <div class="card-header"><h6><i class="fas fa-list me-2"></i>Return Items</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="itemsTable">
                            <thead><tr>
                                <th>Product</th>
                                <th class="text-center" style="width:100px">Qty</th>
                                <th class="text-end" style="width:130px">Unit Price</th>
                                <th class="text-end" style="width:130px">Total</th>
                                <th style="width:40px"></th>
                            </tr></thead>
                            <tbody id="itemsBody">
                                <?php if ($sale && !empty($sale['items'])): foreach ($sale['items'] as $item): ?>
                                <tr class="item-row">
                                    <td>
                                        <input type="hidden" name="product_id[]" value="<?= $item['product_id'] ?>">
                                        <?= Helper::escape($item['product_name']) ?>
                                    </td>
                                    <td><input type="number" name="quantity[]" class="form-control form-control-sm qty-input text-center" value="<?= Helper::formatQty($item['quantity']) ?>" min="0.01" max="<?= $item['quantity'] ?>" step="0.01" onchange="calcRow(this)"></td>
                                    <td><input type="number" name="unit_price[]" class="form-control form-control-sm up-input text-end" value="<?= $item['unit_price'] ?>" step="0.01" onchange="calcRow(this)"></td>
                                    <td class="text-end fw-bold row-total"><?= Helper::formatCurrency($item['total']) ?></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove(); calcGrand();"><i class="fas fa-times"></i></button></td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!$sale): ?>
                    <div class="text-center text-muted py-4" id="noSaleMsg">
                        <i class="fas fa-arrow-up fa-2x mb-2"></i><br>Select a sale above to load items
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6>Return Summary</h6></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Return Amount</span>
                        <span class="fw-bold text-danger fs-5" id="grandTotal"><?= $sale ? Helper::formatCurrency(array_sum(array_column($sale['items'], 'total'))) : '₹ 0.00' ?></span>
                    </div>
                    <hr>
                    <div class="alert alert-warning py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Stock will be <strong>restored</strong> and customer balance will be <strong>reduced</strong>.
                    </div>
                    <button type="submit" class="btn btn-warning w-100 mt-2">
                        <i class="fas fa-undo me-2"></i>Process Return
                    </button>
                    <a href="<?= APP_URL ?>/index.php?page=sale_returns" class="btn btn-outline-secondary w-100 mt-2">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function calcRow(el) {
    const row  = el.closest('tr');
    const qty  = parseFloat(row.querySelector('.qty-input').value) || 0;
    const up   = parseFloat(row.querySelector('.up-input').value)  || 0;
    const tot  = qty * up;
    row.querySelector('.row-total').textContent = '₹ ' + tot.toFixed(2);
    calcGrand();
}

function calcGrand() {
    let grand = 0;
    document.querySelectorAll('.row-total').forEach(el => {
        grand += parseFloat(el.textContent.replace(/[^0-9.]/g, '')) || 0;
    });
    document.getElementById('grandTotal').textContent = '₹ ' + grand.toFixed(2);
}

function loadSaleItems(saleId) {
    if (!saleId) return;
    window.location.href = '<?= APP_URL ?>/index.php?page=sale_returns&action=create&sale_id=' + saleId;
}
</script>
