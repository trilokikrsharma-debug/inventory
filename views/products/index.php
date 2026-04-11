<?php $pageTitle = 'Products'; ?>
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Products</li></ol></nav>
    </div>
    <div class="d-flex gap-2 app-page-actions">
        <?php if (!Session::isSuperAdmin() && Tenant::id() !== null && Tenant::canUse('multi_warehouse')): ?>
        <a href="<?= APP_URL ?>/index.php?page=warehouses" class="btn btn-outline-secondary"><i class="fas fa-warehouse me-1"></i>Warehouses</a>
        <?php endif; ?>
        <?php if (Session::isSuperAdmin() || Tenant::canUse('bulk_import')): ?>
        <a href="<?= APP_URL ?>/index.php?page=products&action=import" class="btn btn-outline-secondary"><i class="fas fa-file-import me-1"></i>Bulk Import</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/index.php?page=products&action=create" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Product</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6><i class="fas fa-boxes-stacked me-2"></i>Product List</h6>
        <form class="d-flex gap-2 app-filter-form app-filter-form-compact" method="GET">
            <input type="hidden" name="page" value="products">
            <input type="text" name="search" class="form-control form-control-sm app-filter-field app-filter-field-search" placeholder="Search..." value="<?= Helper::escape($search) ?>">
            <select name="category_id" class="form-select form-select-sm app-filter-field app-filter-field-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= Helper::escape($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>#</th><th>Product</th><th>SKU</th><th>Category</th><th>Purchase Price</th>
                        <th>Selling Price</th><th>Stock</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($products['data'])): $i = ($products['page']-1) * $products['perPage'];
                    foreach ($products['data'] as $p): $i++; ?>
                <tr>
                    <td><?= $i ?></td>
                    <td>
                        <div class="fw-bold"><?= Helper::escape($p['name']) ?></div>
                        <?php if ($p['brand_name']): ?><small class="text-muted"><?= Helper::escape($p['brand_name']) ?></small><?php endif; ?>
                        <?php if (!empty($p['hsn_code'])): ?><br><small class="text-muted">HSN: <?= Helper::escape($p['hsn_code']) ?></small><?php endif; ?>
                    </td>
                    <td><code><?= Helper::escape($p['sku'] ?? '-') ?></code></td>
                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= Helper::escape($p['category_name'] ?? '-') ?></span></td>
                    <td><?= Helper::formatCurrency($p['purchase_price']) ?></td>
                    <td class="fw-bold">
                        <?= Helper::formatCurrency($p['selling_price']) ?>
                        <?php if(isset($p['mrp']) && $p['mrp']): ?>
                        <br><small class="text-muted fw-normal">MRP: <?= Helper::formatCurrency($p['mrp']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $settings = (new SettingsModel())->getSettings();
                        $threshold = $p['low_stock_alert'] ?? $settings['low_stock_threshold'] ?? 10;
                        $stockClass = $p['current_stock'] <= $threshold ? 'bg-danger' : 'bg-success';
                        ?>
                        <span class="badge <?= $stockClass ?>"><?= Helper::formatQty($p['current_stock']) ?> <?= $p['unit_name'] ?? '' ?></span>
                    </td>
                    <td><?= $p['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="<?= APP_URL ?>/index.php?page=products&action=view_product&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" title="View"><i class="fas fa-eye"></i></a>
                            <a href="<?= APP_URL ?>/index.php?page=products&action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php if (Session::hasPermission('products.delete')): ?>
                            <form method="POST" action="<?= APP_URL ?>/index.php?page=products&action=<?= $p['is_active'] ? 'archive' : 'restore' ?>" class="d-inline" data-confirm="<?= $p['is_active'] ? 'Archive this product from new transactions?' : 'Restore this product for new transactions?' ?>">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon" title="<?= $p['is_active'] ? 'Archive' : 'Restore' ?>">
                                    <i class="fas <?= $p['is_active'] ? 'fa-box-archive' : 'fa-rotate-left' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= APP_URL ?>/index.php?page=products&action=delete" class="d-inline" data-confirm="Remove this product? If it has transactional history, it will be archived instead.">
                                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-box-open fa-2x mb-2 opacity-25 d-block"></i>No products found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($products['totalPages'] > 1): ?>
    <div class="card-footer"><?= Helper::pagination($products['page'], $products['totalPages'], APP_URL . '/index.php?page=products&search=' . urlencode($search) . '&category_id=' . $categoryId) ?></div>
    <?php endif; ?>
</div>
