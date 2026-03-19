<?php $pageTitle = 'Customer: ' . Helper::escape($customer['name']); ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=customers">Customers</a></li><li class="breadcrumb-item active"><?= Helper::escape($customer['name']) ?></li></ol></nav>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=customers&action=edit&id=<?= $customer['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
        <?php if (Session::hasPermission('customers.edit')): ?>
        <form method="POST" action="<?= APP_URL ?>/index.php?page=customers&action=recalculate_balance" class="d-inline">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
            <input type="hidden" name="id" value="<?= $customer['id'] ?>">
            <button type="submit" class="btn btn-outline-info btn-sm" title="Fix balance from transactions"><i class="fas fa-sync me-1"></i>Recalculate Balance</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3"><?= Helper::escape($customer['name']) ?></h5>
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Phone</td><td><?= Helper::escape($customer['phone'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Email</td><td><?= Helper::escape($customer['email'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">City</td><td><?= Helper::escape($customer['city'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Address</td><td><?= Helper::escape($customer['address'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Tax No.</td><td><?= Helper::escape($customer['tax_number'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Opening Bal.</td><td><?= Helper::formatCurrency($customer['opening_balance']) ?></td></tr>
                    <tr><td class="text-muted fw-bold">Current Bal.</td><td class="fw-bold <?= $customer['current_balance'] > 0 ? 'text-danger' : 'text-success' ?>"><?= Helper::formatCurrency($customer['current_balance']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h6><i class="fas fa-book me-2"></i>Ledger</h6>
                <form class="d-flex gap-2" method="GET"><input type="hidden" name="page" value="customers"><input type="hidden" name="action" value="view_customer"><input type="hidden" name="id" value="<?= $customer['id'] ?>">
                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?= Helper::escape($_GET['from_date'] ?? '') ?>" style="width:130px;">
                    <input type="date" name="to_date" class="form-control form-control-sm" value="<?= Helper::escape($_GET['to_date'] ?? '') ?>" style="width:130px;">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
                    <a href="<?= APP_URL ?>/index.php?page=invoice&action=statement&party=customer&id=<?= $customer['id'] ?>&from_date=<?= Helper::escape($_GET['from_date'] ?? '') ?>&to_date=<?= Helper::escape($_GET['to_date'] ?? '') ?>" class="btn btn-sm btn-outline-success" title="Download Statement PDF"><i class="fas fa-file-pdf me-1"></i>Statement</a>
                </form>
            </div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0">
                <thead><tr><th>Date/Time</th><th>Type</th><th>Invoice/Ref</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
                <tbody>
                <?php if (!empty($ledger)):
                    $runningBal = (float)($customer['opening_balance'] ?? 0);
                    foreach ($ledger as $l):
                        $runningBal += (float)($l['debit'] ?? 0) - (float)($l['credit'] ?? 0);
                        $badgeColor = $l['type'] === 'Sale' ? 'primary' : ($l['type'] === 'Receipt' ? 'success' : 'warning');
                ?>
                <tr>
                    <td><?= Helper::formatDate($l['txn_at'] ?? $l['date'], 'd-m-Y H:i') ?></td>
                    <td><span class="badge bg-<?= $badgeColor ?>"><?= $l['type'] ?></span></td>
                    <td><?= Helper::escape($l['reference'] ?? '-') ?></td>
                    <td class="text-danger"><?= ($l['debit'] ?? 0) > 0 ? Helper::formatCurrency($l['debit']) : '-' ?></td>
                    <td class="text-success"><?= ($l['credit'] ?? 0) > 0 ? Helper::formatCurrency($l['credit']) : '-' ?></td>
                    <td class="fw-bold"><?= Helper::formatCurrency($runningBal) ?></td>
                </tr>
                <?php endforeach; else: ?><tr><td colspan="6" class="text-center py-3 text-muted">No transactions</td></tr><?php endif; ?>
                </tbody>
            </table></div></div>
        </div>
    </div>
</div>
