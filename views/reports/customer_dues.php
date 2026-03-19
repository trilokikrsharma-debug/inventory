<?php $pageTitle = 'Customer Dues'; ?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=reports">Reports</a></li><li class="breadcrumb-item active">Customer Dues</li></ol></nav>
    <div class="d-flex gap-2">
        <form method="POST" action="<?= APP_URL ?>/index.php?page=reports&action=queue_export">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
            <input type="hidden" name="report_type" value="customer_dues">
            <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-file-arrow-down me-1"></i>Queue CSV</button>
        </form>
        <button type="button" class="btn btn-outline-primary btn-sm" data-print-target="reportTable"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>
<?php $totalDue = 0; if (!empty($customers)) foreach ($customers as $c) $totalDue += ($c['current_balance'] ?? 0); ?>
<div class="stat-card stat-danger mb-3"><div class="stat-value"><?= Helper::formatCurrency($totalDue) ?></div><div class="stat-label">Total Customer Dues</div></div>
<div class="card" id="reportTable"><div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0">
    <thead><tr><th>#</th><th>Customer</th><th>Phone</th><th>City</th><th class="text-end">Balance Due</th><th>Action</th></tr></thead>
    <tbody>
    <?php if (!empty($customers)): $i=0; foreach ($customers as $c): $i++; ?>
    <tr><td><?= $i ?></td><td class="fw-bold"><?= Helper::escape($c['name']) ?></td><td><?= Helper::escape($c['phone'] ?? '-') ?></td><td><?= Helper::escape($c['city'] ?? '-') ?></td><td class="text-end fw-bold text-danger"><?= Helper::formatCurrency($c['current_balance']) ?></td>
    <td><a href="<?= APP_URL ?>/index.php?page=payments&action=create&type=receipt&customer_id=<?= $c['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-money-bill me-1"></i>Receive</a></td></tr>
    <?php endforeach; else: ?><tr><td colspan="6" class="text-center py-3 text-muted">No dues</td></tr><?php endif; ?>
    </tbody>
</table></div></div></div>
