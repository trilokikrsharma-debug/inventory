<?php $pageTitle = 'Purchase Report'; ?>
<div class="report-page-shell">
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=reports">Reports</a></li><li class="breadcrumb-item active">Purchases</li></ol></nav>
    <div class="report-page-actions">
        <form method="POST" action="<?= APP_URL ?>/index.php?page=reports&action=queue_export">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
            <input type="hidden" name="report_type" value="purchases">
            <input type="hidden" name="from_date" value="<?= Helper::escape($fromDate) ?>">
            <input type="hidden" name="to_date" value="<?= Helper::escape($toDate) ?>">
            <input type="hidden" name="supplier_id" value="<?= (int)$supplierId ?>">
            <input type="hidden" name="warehouse_id" value="<?= (int)($warehouseId ?? 0) ?>">
            <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-file-arrow-down me-1"></i>Queue CSV</button>
        </form>
        <button type="button" class="btn btn-outline-primary btn-sm" data-print-target="reportTable"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>
<div class="card mb-3 report-filter-card"><div class="card-body py-2">
    <form class="report-filter-form" method="GET"><input type="hidden" name="page" value="reports"><input type="hidden" name="action" value="purchases">
        <div class="report-filter-field"><label class="form-label small mb-0">From</label><input type="date" name="from_date" class="form-control form-control-sm" value="<?= Helper::escape($fromDate) ?>"></div>
        <div class="report-filter-field"><label class="form-label small mb-0">To</label><input type="date" name="to_date" class="form-control form-control-sm" value="<?= Helper::escape($toDate) ?>"></div>
        <div class="report-filter-field report-filter-field-wide"><label class="form-label small mb-0">Supplier</label><select name="supplier_id" class="form-select form-select-sm"><option value="">All</option><?php foreach ($suppliers as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (string)$supplierId === (string)$s['id'] ? 'selected' : '' ?>><?= Helper::escape($s['name']) ?></option><?php endforeach; ?></select></div>
        <?php if (!empty($warehouses)): ?>
        <div class="report-filter-field report-filter-field-wide"><label class="form-label small mb-0">Warehouse</label><select name="warehouse_id" class="form-select form-select-sm"><option value="">All</option><?php foreach ($warehouses as $warehouse): ?><option value="<?= (int)$warehouse['id'] ?>" <?= (string)($warehouseId ?? 0) === (string)$warehouse['id'] ? 'selected' : '' ?>><?= Helper::escape($warehouse['name']) ?></option><?php endforeach; ?></select></div>
        <?php endif; ?>
        <button class="btn btn-sm btn-primary report-filter-submit"><i class="fas fa-filter me-1"></i>Filter</button>
    </form>
</div></div>
<?php $total = 0; $paid = 0; $due = 0; if (!empty($purchases['data'])): foreach ($purchases['data'] as $p) { $total += $p['grand_total']; $paid += $p['paid_amount']; $due += $p['due_amount']; } endif; ?>
<div class="row g-3 mb-3 report-summary-grid">
    <div class="col-md-4"><div class="stat-card stat-primary"><div class="stat-value"><?= Helper::formatCurrency($total) ?></div><div class="stat-label">Total Purchases</div></div></div>
    <div class="col-md-4"><div class="stat-card stat-success"><div class="stat-value"><?= Helper::formatCurrency($paid) ?></div><div class="stat-label">Paid</div></div></div>
    <div class="col-md-4"><div class="stat-card stat-danger"><div class="stat-value"><?= Helper::formatCurrency($due) ?></div><div class="stat-label">Outstanding</div></div></div>
</div>
<div class="card report-data-card" id="reportTable"><div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0 report-data-table">
    <thead><tr><th>Invoice</th><th>Supplier</th><?php if (!empty($warehouses)): ?><th>Warehouse</th><?php endif; ?><th>Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th></tr></thead>
    <tbody>
    <?php if (!empty($purchases['data'])): foreach ($purchases['data'] as $p): ?>
    <tr><td><a href="<?= APP_URL ?>/index.php?page=purchases&action=view_purchase&id=<?= $p['id'] ?>"><?= Helper::escape($p['invoice_number']) ?></a></td><td><?= Helper::escape($p['supplier_name']) ?></td><?php if (!empty($warehouses)): ?><td><?= Helper::escape($p['warehouse_name'] ?? '-') ?></td><?php endif; ?><td><?= Helper::formatDate($p['purchase_date']) ?></td><td class="text-end fw-bold"><?= Helper::formatCurrency($p['grand_total']) ?></td><td class="text-end text-success"><?= Helper::formatCurrency($p['paid_amount']) ?></td><td class="text-end text-danger"><?= Helper::formatCurrency($p['due_amount']) ?></td><td><?= Helper::paymentBadge($p['payment_status']) ?></td></tr>
    <?php endforeach; else: ?><tr><td colspan="<?= !empty($warehouses) ? '8' : '7' ?>" class="text-center py-3 text-muted">No data</td></tr><?php endif; ?>
    <tr class="fw-bold"><td colspan="<?= !empty($warehouses) ? '4' : '3' ?>">TOTAL</td><td class="text-end"><?= Helper::formatCurrency($total) ?></td><td class="text-end text-success"><?= Helper::formatCurrency($paid) ?></td><td class="text-end text-danger"><?= Helper::formatCurrency($due) ?></td><td></td></tr>
    </tbody>
</table></div></div></div>
</div>
