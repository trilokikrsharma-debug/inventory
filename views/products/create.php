<?php $pageTitle = 'Add Product'; ?>
<?php $gstEnabled = !empty($settings['enable_gst'] ?? false); ?>
<?php $customFieldsPretty = (string)($customFieldsPretty ?? ''); ?>
<?php $hasCustomFields = Session::isSuperAdmin() || Tenant::canUse('custom_fields'); ?>
<?php $hasWarehouseFeature = !empty($hasWarehouseFeature); ?>
<?php $warehouses = is_array($warehouses ?? null) ? $warehouses : []; ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=products">Products</a></li><li class="breadcrumb-item active">Add</li></ol></nav>
</div>

<form method="POST" enctype="multipart/form-data">
    <?= CSRF::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h6><i class="fas fa-info-circle me-2"></i>Product Information</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" placeholder="e.g. PRD-001">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <!-- hsn_gst_check --><?php if (!empty($settings['enable_gst'])): ?><label class="form-label">HSN / SAC</label>
                            <input type="text" name="hsn_code" class="form-control" placeholder="e.g. 8471"><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= Helper::escape($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">Select Brand</option>
                                <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= Helper::escape($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <select name="unit_id" class="form-select">
                                <option value="">Select Unit</option>
                                <?php foreach ($units as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= Helper::escape($u['name']) ?> (<?= $u['short_name'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <?php if ($hasCustomFields): ?>
                        <div class="col-12">
                            <label class="form-label">Custom Fields (JSON)</label>
                            <textarea name="custom_fields_json" class="form-control font-monospace" rows="6" placeholder='{"Shelf":"A-12","Internal Code":"P-001"}'><?= Helper::escape($customFieldsPretty) ?></textarea>
                            <div class="form-text">Optional JSON object for extra product metadata.</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-tag me-2"></i><?= $gstEnabled ? "Pricing & Tax" : "Pricing" ?></h6></div>
                <div class="card-body">
                    <?php
                    $gstRates = [0, 3, 5, 12, 18, 28];
                    $defaultTaxRate = (float)($settings['tax_rate'] ?? 18);
                    $gstEnabled = !empty($settings['enable_gst']);
                    ?>
                    <?php if ($gstEnabled): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">GST Rate (%) <span class="text-danger">*</span></label>
                        <select name="tax_rate" id="productTaxRate" class="form-select">
                            <?php foreach ($gstRates as $rate): ?>
                            <option value="<?= $rate ?>" <?= $rate == $defaultTaxRate ? 'selected' : '' ?>><?= $rate ?>%<?= $rate == 0 ? ' (Exempt)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">GST percentage applicable to this product</small>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">MRP <small class="text-muted">(optional)</small></label>
                        <input type="number" name="mrp" id="productMrp" class="form-control" step="0.01" placeholder="e.g. 500.00">
                    </div>
                    <?php if ($gstEnabled): ?>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                            <input type="number" name="purchase_price" id="productPurchasePrice" class="form-control" step="0.01" required placeholder="Without GST">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted">Purchase + GST</label>
                            <input type="text" id="purchasePriceWithGst" class="form-control" disabled placeholder="Auto">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                        <input type="number" name="purchase_price" id="productPurchasePrice" class="form-control" step="0.01" required>
                    </div>
                    <?php endif; ?>
                    <?php if ($gstEnabled): ?>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                            <input type="number" name="selling_price" id="productSellingPrice" class="form-control" step="0.01" required placeholder="Without GST">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted">Selling + GST</label>
                            <input type="text" id="sellingPriceWithGst" class="form-control" disabled placeholder="Auto">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                        <input type="number" name="selling_price" id="productSellingPrice" class="form-control" step="0.01" required>
                    </div>
                    <?php endif; ?>
                    <?php if ($gstEnabled): ?>
                    <div class="alert alert-light border small p-2 mb-0" id="profitInfo">
                        <i class="fas fa-chart-line me-1 text-success"></i>
                        <span id="profitDisplay">Enter prices to see profit margin</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-warehouse me-2"></i>Stock</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Opening Stock</label>
                        <input type="number" name="opening_stock" class="form-control" step="0.001" value="0">
                    </div>
                    <?php if ($hasWarehouseFeature): ?>
                    <div class="mb-3">
                        <label class="form-label">Opening Warehouse</label>
                        <select name="opening_warehouse_id" class="form-select">
                            <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?= (int)$warehouse['id'] ?>" <?= !empty($warehouse['is_default']) ? 'selected' : '' ?>>
                                <?= Helper::escape($warehouse['name']) ?><?= !empty($warehouse['code']) ? ' (' . Helper::escape($warehouse['code']) . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Opening stock will be booked into this warehouse.</div>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Low Stock Alert Level</label>
                        <input type="number" name="low_stock_alert" class="form-control" placeholder="Use default">
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-image me-2"></i>Image</h6></div>
                <div class="card-body">
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="d-flex gap-2">
                <input type="hidden" name="is_active" value="1">
                <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-save me-1"></i>Save Product</button>
                <a href="<?= APP_URL ?>/index.php?page=products" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>

<?php if (!empty($settings['enable_gst'])): ?>
<?php $inlineScript = "
function calcProductPrices() {
    const taxRate = parseFloat(document.getElementById('productTaxRate')?.value) || 0;
    const pp = parseFloat(document.getElementById('productPurchasePrice')?.value) || 0;
    const sp = parseFloat(document.getElementById('productSellingPrice')?.value) || 0;
    const ppGst = document.getElementById('purchasePriceWithGst');
    const spGst = document.getElementById('sellingPriceWithGst');
    const profitEl = document.getElementById('profitDisplay');
    
    if (ppGst) ppGst.value = pp > 0 ? '₹' + (pp * (1 + taxRate/100)).toFixed(2) : '';
    if (spGst) spGst.value = sp > 0 ? '₹' + (sp * (1 + taxRate/100)).toFixed(2) : '';
    
    if (profitEl && pp > 0 && sp > 0) {
        const profit = sp - pp;
        const margin = ((profit / sp) * 100).toFixed(1);
        profitEl.innerHTML = 'Profit: <strong>₹' + profit.toFixed(2) + '</strong> | Margin: <strong>' + margin + '%</strong>';
        profitEl.parentElement.className = profit >= 0 ? 'alert alert-success border small p-2 mb-0' : 'alert alert-danger border small p-2 mb-0';
    }
}
['productTaxRate','productPurchasePrice','productSellingPrice'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', calcProductPrices);
    if (el) el.addEventListener('change', calcProductPrices);
});
"; ?>
<?php endif; ?>
</form>
