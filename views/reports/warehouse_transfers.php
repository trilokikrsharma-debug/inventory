<?php
$pageTitle = 'Warehouse Transfer Report';
$transfers = is_array($transfers ?? null) ? $transfers : [];
$summary = is_array($summary ?? null) ? $summary : [];
$warehouses = is_array($warehouses ?? null) ? $warehouses : [];
$fromDate = (string)($fromDate ?? date('Y-m-01'));
$toDate = (string)($toDate ?? date('Y-m-d'));
$status = (string)($status ?? '');
$warehouseId = (int)($warehouseId ?? 0);
?>
<div class="report-page-shell">
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=reports">Reports</a></li><li class="breadcrumb-item active">Warehouse Transfers</li></ol></nav>
    <div class="report-page-actions">
        <form method="POST" action="<?= APP_URL ?>/index.php?page=reports&action=queue_export">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
            <input type="hidden" name="report_type" value="warehouse_transfers">
            <input type="hidden" name="from_date" value="<?= Helper::escape($fromDate) ?>">
            <input type="hidden" name="to_date" value="<?= Helper::escape($toDate) ?>">
            <input type="hidden" name="status" value="<?= Helper::escape($status) ?>">
            <input type="hidden" name="warehouse_id" value="<?= (int)$warehouseId ?>">
            <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-file-arrow-down me-1"></i>Queue CSV</button>
        </form>
        <button type="button" class="btn btn-outline-primary btn-sm" data-print-target="reportTable"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>
<div class="card mb-3 report-filter-card"><div class="card-body py-2">
    <form class="report-filter-form" method="GET"><input type="hidden" name="page" value="reports"><input type="hidden" name="action" value="warehouse_transfers">
        <div class="report-filter-field"><label class="form-label small mb-0">From</label><input type="date" name="from_date" class="form-control form-control-sm" value="<?= Helper::escape($fromDate) ?>"></div>
        <div class="report-filter-field"><label class="form-label small mb-0">To</label><input type="date" name="to_date" class="form-control form-control-sm" value="<?= Helper::escape($toDate) ?>"></div>
        <div class="report-filter-field report-filter-field-wide"><label class="form-label small mb-0">Status</label><select name="status" class="form-select form-select-sm"><option value="">All</option><option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option><option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option><option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option></select></div>
        <div class="report-filter-field report-filter-field-wide"><label class="form-label small mb-0">Warehouse</label><select name="warehouse_id" class="form-select form-select-sm"><option value="">All</option><?php foreach ($warehouses as $warehouse): ?><option value="<?= (int)$warehouse['id'] ?>" <?= (string)$warehouseId === (string)$warehouse['id'] ? 'selected' : '' ?>><?= Helper::escape($warehouse['name']) ?></option><?php endforeach; ?></select></div>
        <button class="btn btn-sm btn-primary report-filter-submit"><i class="fas fa-filter me-1"></i>Filter</button>
    </form>
</div></div>

<div class="row g-3 mb-3 report-summary-grid">
    <div class="col-md-3"><div class="stat-card stat-primary"><div class="stat-value"><?= (int)($summary['total_transfers'] ?? 0) ?></div><div class="stat-label">Transfers</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-warning"><div class="stat-value"><?= (int)($summary['pending_transfers'] ?? 0) ?></div><div class="stat-label">Pending Approval</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-success"><div class="stat-value"><?= (int)($summary['approved_transfers'] ?? 0) ?></div><div class="stat-label">Approved</div></div></div>
    <div class="col-md-3"><div class="stat-card stat-danger"><div class="stat-value"><?= (int)($summary['rejected_transfers'] ?? 0) ?></div><div class="stat-label">Rejected</div></div></div>
</div>

<div class="card report-data-card" id="reportTable"><div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0 report-data-table">
    <thead><tr><th>Transfer</th><th>Route</th><th>Date</th><th>Status</th><th>Items</th><th class="text-end">Quantity</th><th>Audit</th></tr></thead>
    <tbody>
    <?php if (!empty($transfers)): foreach ($transfers as $transfer): ?>
    <tr>
        <td><div class="fw-bold"><?= Helper::escape($transfer['transfer_number'] ?? '-') ?></div><?php if (!empty($transfer['reference_number'])): ?><div class="small text-muted"><?= Helper::escape($transfer['reference_number']) ?></div><?php endif; ?></td>
        <td><div><?= Helper::escape($transfer['source_warehouse_name'] ?? '-') ?></div><div class="small text-muted"><i class="fas fa-arrow-right me-1"></i><?= Helper::escape($transfer['destination_warehouse_name'] ?? '-') ?></div></td>
        <td><?= Helper::formatDate($transfer['transfer_date'] ?? date('Y-m-d')) ?></td>
        <td><span class="badge <?= ($transfer['status'] ?? '') === 'approved' ? 'bg-success' : (($transfer['status'] ?? '') === 'rejected' ? 'bg-danger' : 'bg-warning text-dark') ?>"><?= Helper::escape(ucfirst($transfer['status'] ?? 'pending')) ?></span></td>
        <td><?= (int)($transfer['item_count'] ?? 0) ?></td>
        <td class="text-end fw-bold"><?= Helper::formatQty($transfer['total_quantity'] ?? 0) ?></td>
        <td><div><?= Helper::escape($transfer['created_by_name'] ?? 'System') ?></div><div class="small text-muted"><?= !empty($transfer['approved_by_name']) ? 'Approved by ' . Helper::escape($transfer['approved_by_name']) : (!empty($transfer['rejected_by_name']) ? 'Rejected by ' . Helper::escape($transfer['rejected_by_name']) : 'Awaiting approval') ?></div><?php if (!empty($transfer['rejection_reason'])): ?><div class="small text-danger"><?= Helper::escape($transfer['rejection_reason']) ?></div><?php endif; ?></td>
    </tr>
    <?php endforeach; else: ?><tr><td colspan="7" class="text-center py-3 text-muted">No transfer data</td></tr><?php endif; ?>
    </tbody>
</table></div></div></div>
</div>
