<?php $pageTitle = 'Add Product'; ?>
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
                        <div class="col-md-3">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" placeholder="e.g. PRD-001">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control">
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
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-tag me-2"></i>Pricing</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">MRP</label>
                        <input type="number" name="mrp" class="form-control" step="0.01" placeholder="e.g. 500.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purchase Price <span class="text-danger">*</span></label>
                        <input type="number" name="purchase_price" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Selling Price <span class="text-danger">*</span></label>
                        <input type="number" name="selling_price" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" class="form-control" step="0.01" placeholder="Use default">
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header"><h6><i class="fas fa-warehouse me-2"></i>Stock</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Opening Stock</label>
                        <input type="number" name="opening_stock" class="form-control" step="0.001" value="0">
                    </div>
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
</form>
