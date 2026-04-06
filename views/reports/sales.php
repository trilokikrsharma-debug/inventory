<?php $pageTitle = 'Sales Report'; ?>
<div class="report-page-shell">
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=reports">Reports</a></li><li class="breadcrumb-item active">Sales</li></ol></nav>
    <div class="report-page-actions">
        <form method="POST" action="<?= APP_URL ?>/index.php?page=reports&action=queue_export">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
            <input type="hidden" name="report_type" value="sales">
            <input type="hidden" name="from_date" value="<?= Helper::escape($fromDate) ?>">
            <input type="hidden" name="to_date" value="<?= Helper::escape($toDate) ?>">
            <input type="hidden" name="customer_id" value="<?= (int)$customerId ?>">
            <input type="hidden" name="warehouse_id" value="<?= (int)($warehouseId ?? 0) ?>">
            <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-file-arrow-down me-1"></i>Queue CSV</button>
        </form>
        <button type="button" class="btn btn-outline-primary btn-sm" data-print-target="reportTable"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>
<div class="card mb-3 report-filter-card"><div class="card-body py-2">
    <form class="report-filter-form" method="GET"><input type="hidden" name="page" value="reports"><input type="hidden" name="action" value="sales">
        <div class="report-filter-field"><label class="form-label small mb-0">From</label><input type="date" name="from_date" class="form-control form-control-sm" value="<?= Helper::escape($fromDate) ?>"></div>
        <div class="report-filter-field"><label class="form-label small mb-0">To</label><input type="date" name="to_date" class="form-control form-control-sm" value="<?= Helper::escape($toDate) ?>"></div>
        <div class="report-filter-field report-filter-field-wide"><label class="form-label small mb-0">Customer</label><select name="customer_id" class="form-select form-select-sm"><option value="">All</option><?php foreach ($customers as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (string)$customerId === (string)$c['id'] ? 'selected' : '' ?>><?= Helper::escape($c['name']) ?></option><?php endforeach; ?></select></div>
        <?php if (!empty($warehouses)): ?>
        <div class="report-filter-field report-filter-field-wide"><label class="form-label small mb-0">Warehouse</label><select name="warehouse_id" class="form-select form-select-sm"><option value="">All</option><?php foreach ($warehouses as $warehouse): ?><option value="<?= (int)$warehouse['id'] ?>" <?= (string)($warehouseId ?? 0) === (string)$warehouse['id'] ? 'selected' : '' ?>><?= Helper::escape($warehouse['name']) ?></option><?php endforeach; ?></select></div>
        <?php endif; ?>
        <button class="btn btn-sm btn-primary report-filter-submit"><i class="fas fa-filter me-1"></i>Filter</button>
    </form>
</div></div>

<?php
$totalSales = 0; $totalPaid = 0; $totalDue = 0;
if (!empty($sales['data'])): foreach ($sales['data'] as $s) { $totalSales += $s['grand_total']; $totalPaid += $s['paid_amount']; $totalDue += $s['due_amount']; } endif;
?>

<div class="row g-3 mb-3 report-summary-grid">
    <div class="col-md-4"><div class="stat-card stat-success"><div class="stat-value"><?= Helper::formatCurrency($totalSales) ?></div><div class="stat-label">Total Sales</div></div></div>
    <div class="col-md-4"><div class="stat-card stat-primary"><div class="stat-value"><?= Helper::formatCurrency($totalPaid) ?></div><div class="stat-label">Collected</div></div></div>
    <div class="col-md-4"><div class="stat-card stat-danger"><div class="stat-value"><?= Helper::formatCurrency($totalDue) ?></div><div class="stat-label">Outstanding</div></div></div>
</div>

<div class="card report-data-card" id="reportTable">
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0 report-data-table">
        <thead><tr><th>Invoice</th><th>Customer</th><?php if (!empty($warehouses)): ?><th>Warehouse</th><?php endif; ?><th>Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (!empty($sales['data'])): foreach ($sales['data'] as $s): ?>
        <tr>
            <td><a href="<?= APP_URL ?>/index.php?page=sales&action=view_sale&id=<?= $s['id'] ?>"><?= Helper::escape($s['invoice_number']) ?></a></td>
            <td><?= Helper::escape($s['customer_name']) ?></td>
            <?php if (!empty($warehouses)): ?><td><?= Helper::escape($s['warehouse_name'] ?? '-') ?></td><?php endif; ?>
            <td><?= Helper::formatDate($s['sale_date']) ?></td>
            <td class="text-end fw-bold"><?= Helper::formatCurrency($s['grand_total']) ?></td>
            <td class="text-end text-success"><?= Helper::formatCurrency($s['paid_amount']) ?></td>
            <td class="text-end text-danger"><?= Helper::formatCurrency($s['due_amount']) ?></td>
            <td><?= Helper::paymentBadge($s['payment_status']) ?></td>
        </tr>
        <?php endforeach; else: ?><tr><td colspan="<?= !empty($warehouses) ? '8' : '7' ?>" class="text-center py-3 text-muted">No data</td></tr><?php endif; ?>
        <tr class="fw-bold"><td colspan="<?= !empty($warehouses) ? '4' : '3' ?>">TOTAL</td><td class="text-end"><?= Helper::formatCurrency($totalSales) ?></td><td class="text-end text-success"><?= Helper::formatCurrency($totalPaid) ?></td><td class="text-end text-danger"><?= Helper::formatCurrency($totalDue) ?></td><td></td></tr>
        </tbody>
    </table></div></div>
</div>
</div>
