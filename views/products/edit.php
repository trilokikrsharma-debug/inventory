<?php $pageTitle = 'Edit Product'; ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=products">Products</a></li><li class="breadcrumb-item active">Edit</li></ol></nav>
</div>
<form method="POST" enctype="multipart/form-data">
    <?= CSRF::field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h6><i class="fas fa-edit me-2"></i>Edit Product</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= Helper::escape($product['name']) ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" value="<?= Helper::escape($product['sku'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control" value="<?= Helper::escape($product['barcode'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">HSN / SAC</label>
                            <input type="text" name="hsn_code" class="form-control" value="<?= Helper::escape($product['hsn_code'] ?? '') ?>" placeholder="e.g. 8471">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>><?= Helper::escape($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= $product['brand_id'] == $b['id'] ? 'selected' : '' ?>><?= Helper::escape($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <select name="unit_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($units as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $product['unit_id'] == $u['id'] ? 'selected' : '' ?>><?= Helper::escape($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?= Helper::escape($product['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-tag me-2"></i>Pricing</h6></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">MRP</label><input type="number" name="mrp" class="form-control" step="0.01" value="<?= $product['mrp'] ?? '' ?>" placeholder="e.g. 500.00"></div>
                    <div class="mb-3"><label class="form-label">Purchase Price</label><input type="number" name="purchase_price" class="form-control" step="0.01" value="<?= $product['purchase_price'] ?>" required></div>
                    <div class="mb-3"><label class="form-label">Selling Price</label><input type="number" name="selling_price" class="form-control" step="0.01" value="<?= $product['selling_price'] ?>" required></div>
                    <div class="mb-3"><label class="form-label">Tax Rate (%)</label><input type="number" name="tax_rate" class="form-control" step="0.01" value="<?= $product['tax_rate'] ?? '' ?>"></div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-warehouse me-2"></i>Stock</h6></div>
                <div class="card-body">
                    <div class="mb-3"><label class="form-label">Current Stock</label><input type="text" class="form-control" value="<?= Helper::formatQty($product['current_stock']) ?>" disabled></div>
                    <div class="mb-3"><label class="form-label">Low Stock Alert</label><input type="number" name="low_stock_alert" class="form-control" value="<?= $product['low_stock_alert'] ?? '' ?>"></div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-image me-2"></i>Image</h6></div>
                <div class="card-body">
                    <?php if ($product['image']): ?><img src="<?= APP_URL ?>/<?= $product['image'] ?>" class="img-thumbnail mb-2" style="max-height:100px;" loading="lazy"><?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="mb-3"><label class="form-label">Status</label><select name="is_active" class="form-select"><option value="1" <?= $product['is_active'] ? 'selected' : '' ?>>Active</option><option value="0" <?= !$product['is_active'] ? 'selected' : '' ?>>Inactive</option></select></div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="fas fa-save me-1"></i>Update</button>
                <a href="<?= APP_URL ?>/index.php?page=products" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
