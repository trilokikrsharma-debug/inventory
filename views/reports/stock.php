<?php
$pageTitle = 'Stock Report';
$transferSummary = is_array($transferSummary ?? null) ? $transferSummary : [];
$recentTransfers = is_array($recentTransfers ?? null) ? $recentTransfers : [];
?>
<div class="report-page-shell">
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=reports">Reports</a></li><li class="breadcrumb-item active">Stock</li></ol></nav>
    <div class="report-page-actions">
        <form method="POST" action="<?= APP_URL ?>/index.php?page=reports&action=queue_export">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
            <input type="hidden" name="report_type" value="stock">
            <input type="hidden" name="search" value="<?= Helper::escape($search ?? '') ?>">
            <input type="hidden" name="category_id" value="<?= (int)($categoryId ?? 0) ?>">
            <input type="hidden" name="warehouse_id" value="<?= (int)($warehouseId ?? 0) ?>">
            <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-file-arrow-down me-1"></i>Queue CSV</button>
        </form>
        <button type="button" class="btn btn-outline-primary btn-sm" data-print-target="reportTable"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>
<?php if (!empty($warehouses)): ?>
<div class="row g-3 mb-3 report-summary-grid">
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Transfers</div><div class="display-6 fw-bold"><?= (int)($transferSummary['total_transfers'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Pending Approval</div><div class="display-6 fw-bold text-warning"><?= (int)($transferSummary['pending_transfers'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Approved</div><div class="display-6 fw-bold text-success"><?= (int)($transferSummary['approved_transfers'] ?? 0) ?></div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Rejected</div><div class="display-6 fw-bold text-danger"><?= (int)($transferSummary['rejected_transfers'] ?? 0) ?></div></div></div></div>
</div>
<?php endif; ?>
<div class="card mb-3 report-filter-card">
    <div class="card-body py-2">
        <form class="report-filter-form" method="GET">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="stock">
            <div class="report-filter-field report-filter-field-wide">
                <label class="form-label small mb-0">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= Helper::escape($search ?? '') ?>" placeholder="Product / SKU">
            </div>
            <div class="report-filter-field report-filter-field-wide">
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
            <?php if (!empty($warehouses)): ?>
            <div class="report-filter-field report-filter-field-wide">
                <label class="form-label small mb-0">Warehouse</label>
                <select name="warehouse_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (($warehouses ?? []) as $warehouse): ?>
                    <option value="<?= (int)$warehouse['id'] ?>" <?= (string)($warehouseId ?? 0) === (string)$warehouse['id'] ? 'selected' : '' ?>>
                        <?= Helper::escape($warehouse['name'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <button class="btn btn-sm btn-primary report-filter-submit"><i class="fas fa-filter me-1"></i>Filter</button>
        </form>
    </div>
</div>
<?php $totalValue = 0; $lowCount = 0; $settings = (new SettingsModel())->getSettings(); ?>
<div class="card report-data-card" id="reportTable"><div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0 report-data-table">
    <thead><tr><th>#</th><th>Product</th><th>Category</th><?php if (!empty($warehouses)): ?><th>Warehouse</th><?php endif; ?><th>Purchase Price</th><th>Selling Price</th><th>Stock</th><th>Stock Value</th><th>Status</th></tr></thead>
    <tbody>
    <?php if (!empty($products['data'])): $i=0; foreach ($products['data'] as $p): $i++;
        $stockQty = (float)($p['stock_quantity'] ?? $p['current_stock'] ?? 0);
        $value = $stockQty * $p['purchase_price']; $totalValue += $value;
        $threshold = $p['low_stock_alert'] ?? $settings['low_stock_threshold'] ?? 10;
        $isLow = $stockQty <= $threshold; if ($isLow) $lowCount++;
    ?>
    <tr class="<?= $isLow ? 'table-danger' : '' ?>">
        <td><?= $i ?></td><td class="fw-bold"><?= Helper::escape($p['name']) ?></td>
        <td><?= Helper::escape($p['category_name'] ?? '-') ?></td>
        <?php if (!empty($warehouses)): ?><td><?= Helper::escape($p['report_warehouse_name'] ?? '-') ?></td><?php endif; ?>
        <td><?= Helper::formatCurrency($p['purchase_price']) ?></td>
        <td><?= Helper::formatCurrency($p['selling_price']) ?></td>
        <td><span class="badge bg-<?= $isLow ? 'danger':'success' ?>"><?= Helper::formatQty($stockQty) ?> <?= $p['unit_name'] ?? '' ?></span></td>
        <td class="fw-bold"><?= Helper::formatCurrency($value) ?></td>
        <td><?= $isLow ? '<span class="badge bg-danger">Low</span>' : '<span class="badge bg-success">OK</span>' ?></td>
    </tr>
    <?php endforeach; endif; ?>
    <tr class="fw-bold"><td colspan="<?= !empty($warehouses) ? '7' : '6' ?>">Total Stock Value</td><td colspan="2"><?= Helper::formatCurrency($totalValue) ?></td></tr>
    </tbody>
</table></div></div></div>
<div class="mt-2"><small class="text-muted"><span class="badge bg-danger"><?= $lowCount ?></span> products below threshold</small></div>
<?php if (!empty($warehouses)): ?>
<div class="card mt-4 report-data-card">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-right-left me-2"></i>Recent Transfer Approvals</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentTransfers)): ?>
        <div class="p-4 text-center text-muted">No warehouse transfers recorded yet.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead><tr><th>Transfer</th><th>Route</th><th>Date</th><th>Status</th><th>Qty</th><th>Audit</th></tr></thead>
                <tbody>
                    <?php foreach ($recentTransfers as $transfer): ?>
                    <tr>
                        <td><span class="fw-semibold"><?= Helper::escape($transfer['transfer_number'] ?? '-') ?></span></td>
                        <td>
                            <?= Helper::escape($transfer['source_warehouse_name'] ?? '-') ?>
                            <span class="text-muted">to</span>
                            <?= Helper::escape($transfer['destination_warehouse_name'] ?? '-') ?>
                        </td>
                        <td><?= Helper::formatDate($transfer['transfer_date'] ?? date('Y-m-d')) ?></td>
                        <td><span class="badge <?= ($transfer['status'] ?? '') === 'approved' ? 'bg-success' : (($transfer['status'] ?? '') === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') ?>"><?= Helper::escape(ucfirst($transfer['status'] ?? 'pending')) ?></span></td>
                        <td class="fw-semibold"><?= Helper::formatQty($transfer['total_quantity'] ?? 0) ?></td>
                        <td>
                            <div><?= Helper::escape($transfer['created_by_name'] ?? 'System') ?></div>
                            <div class="small text-muted"><?= !empty($transfer['approved_by_name']) ? 'Approved by ' . Helper::escape($transfer['approved_by_name']) : (!empty($transfer['rejected_by_name']) ? 'Rejected by ' . Helper::escape($transfer['rejected_by_name']) : 'Awaiting approval') ?></div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
</div>
