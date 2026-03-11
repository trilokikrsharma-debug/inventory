<?php $pageTitle = 'Supplier: ' . Helper::escape($supplier['name']); ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=suppliers">Suppliers</a></li><li class="breadcrumb-item active"><?= Helper::escape($supplier['name']) ?></li></ol></nav>
    <a href="<?= APP_URL ?>/index.php?page=suppliers&action=edit&id=<?= $supplier['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
</div>
<div class="row g-3">
    <div class="col-lg-4"><div class="card"><div class="card-body">
        <h5 class="mb-3"><?= Helper::escape($supplier['name']) ?></h5>
        <table class="table table-sm mb-0">
            <tr><td class="text-muted">Phone</td><td><?= Helper::escape($supplier['phone'] ?? '-') ?></td></tr>
            <tr><td class="text-muted">Email</td><td><?= Helper::escape($supplier['email'] ?? '-') ?></td></tr>
            <tr><td class="text-muted">City</td><td><?= Helper::escape($supplier['city'] ?? '-') ?></td></tr>
            <tr><td class="text-muted">Address</td><td><?= Helper::escape($supplier['address'] ?? '-') ?></td></tr>
            <tr><td class="text-muted">Opening</td><td><?= Helper::formatCurrency($supplier['opening_balance']) ?></td></tr>
            <tr><td class="text-muted fw-bold">Balance</td><td class="fw-bold text-danger"><?= Helper::formatCurrency($supplier['current_balance']) ?></td></tr>
        </table>
    </div></div></div>
    <div class="col-lg-8"><div class="card">
        <div class="card-header"><h6><i class="fas fa-book me-2"></i>Ledger</h6>
            <form class="d-flex gap-2" method="GET"><input type="hidden" name="page" value="suppliers"><input type="hidden" name="action" value="view_supplier"><input type="hidden" name="id" value="<?= $supplier['id'] ?>">
                <input type="date" name="from_date" class="form-control form-control-sm" value="<?= Helper::escape($_GET['from_date'] ?? '') ?>" style="width:130px;"><input type="date" name="to_date" class="form-control form-control-sm" value="<?= Helper::escape($_GET['to_date'] ?? '') ?>" style="width:130px;"><button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
                <a href="<?= APP_URL ?>/index.php?page=invoice&action=statement&party=supplier&id=<?= $supplier['id'] ?>&from_date=<?= Helper::escape($_GET['from_date'] ?? '') ?>&to_date=<?= Helper::escape($_GET['to_date'] ?? '') ?>" class="btn btn-sm btn-outline-success" title="Download Statement PDF"><i class="fas fa-file-pdf me-1"></i>Statement</a>
            </form>
        </div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0">
            <thead><tr><th>Date</th><th>Type</th><th>Ref</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
            <tbody>
            <?php if (!empty($ledger)):
                $runningBal = (float)($supplier['opening_balance'] ?? 0);
                foreach ($ledger as $l):
                    $runningBal += (float)($l['debit'] ?? 0) - (float)($l['credit'] ?? 0);
                    $type = strtolower((string)($l['type'] ?? ''));
                    $badgeColor = $type === 'purchase' ? 'primary' : 'success';
            ?>
            <tr>
                <td><?= Helper::formatDate($l['date']) ?></td>
                <td><span class="badge bg-<?= $badgeColor ?>"><?= Helper::escape($l['type'] ?? '') ?></span></td>
                <td><?= Helper::escape($l['reference'] ?? '-') ?></td>
                <td class="text-danger"><?= ($l['debit'] ?? 0) > 0 ? Helper::formatCurrency($l['debit']) : '-' ?></td>
                <td class="text-success"><?= ($l['credit'] ?? 0) > 0 ? Helper::formatCurrency($l['credit']) : '-' ?></td>
                <td class="fw-bold"><?= Helper::formatCurrency($runningBal) ?></td>
            </tr>
            <?php endforeach; else: ?><tr><td colspan="6" class="text-center py-3 text-muted">No transactions</td></tr><?php endif; ?>
            </tbody>
        </table></div></div>
    </div></div>
</div>
