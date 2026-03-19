<?php $pageTitle = 'Stock Report'; ?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=reports">Reports</a></li><li class="breadcrumb-item active">Stock</li></ol></nav>
    <div class="d-flex gap-2">
        <form method="POST" action="<?= APP_URL ?>/index.php?page=reports&action=queue_export">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
            <input type="hidden" name="report_type" value="stock">
            <input type="hidden" name="search" value="<?= Helper::escape($search ?? '') ?>">
            <input type="hidden" name="category_id" value="<?= (int)($categoryId ?? 0) ?>">
            <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-file-arrow-down me-1"></i>Queue CSV</button>
        </form>
        <button type="button" class="btn btn-outline-primary btn-sm" data-print-target="reportTable"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body py-2">
        <form class="d-flex gap-2 flex-wrap align-items-end" method="GET">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="stock">
            <div>
                <label class="form-label small mb-0">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= Helper::escape($search ?? '') ?>" placeholder="Product / SKU">
            </div>
            <div>
                <label class="form-label small mb-0">Category</label>
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (($categories ?? []) as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= (string)($categoryId ?? 0) === (string)$cat['id'] ? 'selected' : '' ?>>
                        <?= Helper::escape($cat['name'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
        </form>
    </div>
</div>
<?php $totalValue = 0; $lowCount = 0; $settings = (new SettingsModel())->getSettings(); ?>
<div class="card" id="reportTable"><div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0">
    <thead><tr><th>#</th><th>Product</th><th>Category</th><th>Purchase Price</th><th>Selling Price</th><th>Stock</th><th>Stock Value</th><th>Status</th></tr></thead>
    <tbody>
    <?php if (!empty($products['data'])): $i=0; foreach ($products['data'] as $p): $i++;
        $value = $p['current_stock'] * $p['purchase_price']; $totalValue += $value;
        $threshold = $p['low_stock_alert'] ?? $settings['low_stock_threshold'] ?? 10;
        $isLow = $p['current_stock'] <= $threshold; if ($isLow) $lowCount++;
    ?>
    <tr class="<?= $isLow ? 'table-danger' : '' ?>">
        <td><?= $i ?></td><td class="fw-bold"><?= Helper::escape($p['name']) ?></td>
        <td><?= Helper::escape($p['category_name'] ?? '-') ?></td>
        <td><?= Helper::formatCurrency($p['purchase_price']) ?></td>
        <td><?= Helper::formatCurrency($p['selling_price']) ?></td>
        <td><span class="badge bg-<?= $isLow ? 'danger':'success' ?>"><?= Helper::formatQty($p['current_stock']) ?> <?= $p['unit_name'] ?? '' ?></span></td>
        <td class="fw-bold"><?= Helper::formatCurrency($value) ?></td>
        <td><?= $isLow ? '<span class="badge bg-danger">Low</span>' : '<span class="badge bg-success">OK</span>' ?></td>
    </tr>
    <?php endforeach; endif; ?>
    <tr class="fw-bold"><td colspan="6">Total Stock Value</td><td colspan="2"><?= Helper::formatCurrency($totalValue) ?></td></tr>
    </tbody>
</table></div></div></div>
<div class="mt-2"><small class="text-muted"><span class="badge bg-danger"><?= $lowCount ?></span> products below threshold</small></div>
