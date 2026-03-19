<?php $pageTitle = 'Product Details'; ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=products">Products</a></li><li class="breadcrumb-item active"><?= Helper::escape($product['name']) ?></li></ol></nav>
    <a href="<?= APP_URL ?>/index.php?page=products&action=edit&id=<?= $product['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($product['image']): ?>
                <img src="<?= APP_URL ?>/<?= $product['image'] ?>" class="img-fluid rounded mb-3" style="max-height:200px;" loading="lazy">
                <?php else: ?>
                <div class="py-4"><i class="fas fa-box-open fa-3x text-muted opacity-25"></i></div>
                <?php endif; ?>
                <h5 class="mb-1"><?= Helper::escape($product['name']) ?></h5>
                <p class="text-muted mb-2"><?= Helper::escape($product['sku'] ?? 'No SKU') ?></p>
                <span class="badge <?= $product['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $product['is_active'] ? 'Active' : 'Inactive' ?></span>
            </div>
            <div class="card-body border-top">
                <div class="row text-center">
                    <div class="col-4"><div class="text-muted small">Purchase</div><div class="fw-bold"><?= Helper::formatCurrency($product['purchase_price']) ?></div></div>
                    <div class="col-4"><div class="text-muted small">Selling</div><div class="fw-bold text-success"><?= Helper::formatCurrency($product['selling_price']) ?></div></div>
                    <div class="col-4"><div class="text-muted small">Stock</div><div class="fw-bold"><?= Helper::formatQty($product['current_stock']) ?> <?= $product['unit_short'] ?? '' ?></div></div>
                </div>
            </div>
            <div class="card-body border-top">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">MRP</td><td class="text-end fw-bold"><?= isset($product['mrp']) && $product['mrp'] ? Helper::formatCurrency($product['mrp']) : '-' ?></td></tr>
                    <tr><td class="text-muted">Category</td><td class="text-end"><?= Helper::escape($product['category_name'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Brand</td><td class="text-end"><?= Helper::escape($product['brand_name'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Unit</td><td class="text-end"><?= Helper::escape($product['unit_name'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">HSN / SAC</td><td class="text-end"><?= !empty($product['hsn_code']) ? Helper::escape($product['hsn_code']) : '-' ?></td></tr>
                    <tr><td class="text-muted">Tax Rate</td><td class="text-end"><?= $product['tax_rate'] !== null ? $product['tax_rate'] . '%' : 'Default' ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6><i class="fas fa-history me-2"></i>Stock History</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead><tr><th>Date</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>Note</th><th>By</th></tr></thead>
                        <tbody>
                        <?php if (!empty($stockHistory)): foreach ($stockHistory as $sh): ?>
                        <tr>
                            <td><?= Helper::formatDate($sh['created_at'], 'd-m-Y H:i') ?></td>
                        <td><?php
                            $typeLabels = [
                                'opening' => ['Opening', 'info'],
                                'purchase' => ['Purchase', 'success'],
                                'purchase_return' => ['Pur. Return', 'warning'],
                                'purchase_edit' => ['Pur. Edit', 'primary'],
                                'purchase_edit_reverse' => ['Pur. Edit Rev.', 'secondary'],
                                'purchase_cancel' => ['Pur. Cancel', 'danger'],
                                'sale' => ['Sale', 'danger'],
                                'sale_return' => ['Sale Return', 'warning'],
                                'sale_edit' => ['Sale Edit', 'primary'],
                                'sale_edit_reverse' => ['Sale Edit Rev.', 'secondary'],
                                'sale_cancel' => ['Sale Cancel', 'success'],
                                'return' => ['Return', 'warning'],
                                'adjustment' => ['Adjustment', 'dark'],
                            ];
                            $t = $typeLabels[$sh['type']] ?? [ucfirst(str_replace('_', ' ', $sh['type'] ?: 'Unknown')), 'secondary'];
                        ?><span class="badge bg-<?= $t[1] ?>"><?= $t[0] ?></span></td>
                            <td class="fw-bold <?= $sh['quantity'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= $sh['quantity'] >= 0 ? '+' : '' ?><?= Helper::formatQty($sh['quantity']) ?></td>
                            <td><?= Helper::formatQty($sh['stock_before']) ?></td>
                            <td><?= Helper::formatQty($sh['stock_after']) ?></td>
                            <td class="text-muted"><?= Helper::escape($sh['note'] ?? '') ?></td>
                            <td><?= Helper::escape($sh['user_name'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center py-3 text-muted">No stock history</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
