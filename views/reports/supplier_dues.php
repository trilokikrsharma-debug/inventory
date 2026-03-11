<?php $pageTitle = 'Supplier Dues'; ?>
<div class="page-header"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=reports">Reports</a></li><li class="breadcrumb-item active">Supplier Dues</li></ol></nav>
    <button type="button" class="btn btn-outline-primary btn-sm" data-print-target="reportTable"><i class="fas fa-print me-1"></i>Print</button>
</div>
<?php $totalDue = 0; if (!empty($suppliers)) foreach ($suppliers as $s) $totalDue += ($s['current_balance'] ?? 0); ?>
<div class="stat-card stat-danger mb-3"><div class="stat-value"><?= Helper::formatCurrency($totalDue) ?></div><div class="stat-label">Total Supplier Dues</div></div>
<div class="card" id="reportTable"><div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0">
    <thead><tr><th>#</th><th>Supplier</th><th>Phone</th><th>City</th><th class="text-end">Balance Due</th><th>Action</th></tr></thead>
    <tbody>
    <?php if (!empty($suppliers)): $i=0; foreach ($suppliers as $s): $i++; ?>
    <tr><td><?= $i ?></td><td class="fw-bold"><?= Helper::escape($s['name']) ?></td><td><?= Helper::escape($s['phone'] ?? '-') ?></td><td><?= Helper::escape($s['city'] ?? '-') ?></td><td class="text-end fw-bold text-danger"><?= Helper::formatCurrency($s['current_balance']) ?></td>
    <td><a href="<?= APP_URL ?>/index.php?page=payments&action=create&type=payment&supplier_id=<?= $s['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane me-1"></i>Pay</a></td></tr>
    <?php endforeach; else: ?><tr><td colspan="6" class="text-center py-3 text-muted">No dues</td></tr><?php endif; ?>
    </tbody>
</table></div></div></div>
