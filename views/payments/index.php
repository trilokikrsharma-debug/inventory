<?php $pageTitle = 'Payments & Receipts'; ?>
<div class="page-header">
    <div><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Payments</li></ol></nav></div>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=payments&action=create&type=receipt" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>Receipt</a>
        <a href="<?= APP_URL ?>/index.php?page=payments&action=create&type=payment" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Payment</a>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h6><i class="fas fa-money-bill-wave me-2"></i>Transactions</h6>
        <form class="d-flex gap-2" method="GET"><input type="hidden" name="page" value="payments">
            <select name="type" class="form-select form-select-sm" style="width:120px;"><option value="">All</option><option value="receipt" <?= $type==='receipt'?'selected':'' ?>>Receipts</option><option value="payment" <?= $type==='payment'?'selected':'' ?>>Payments</option></select>
            <input type="date" name="from_date" class="form-control form-control-sm" value="<?= $filters['from_date'] ?? '' ?>" style="width:130px;">
            <input type="date" name="to_date" class="form-control form-control-sm" value="<?= $filters['to_date'] ?? '' ?>" style="width:130px;">
            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
            <a href="<?= APP_URL ?>/index.php?page=invoice&action=statement&party=receipt&id=0&from_date=<?= Helper::escape($filters['from_date'] ?? '') ?>&to_date=<?= Helper::escape($filters['to_date'] ?? '') ?>" class="btn btn-sm btn-outline-success" title="Download Receipt Register PDF"><i class="fas fa-file-pdf me-1"></i>Receipt Statement</a>
            <a href="<?= APP_URL ?>/index.php?page=invoice&action=statement&party=payment&id=0&from_date=<?= Helper::escape($filters['from_date'] ?? '') ?>&to_date=<?= Helper::escape($filters['to_date'] ?? '') ?>" class="btn btn-sm btn-outline-info" title="Download Payment Register PDF"><i class="fas fa-file-pdf me-1"></i>Payment Statement</a>
        </form>
    </div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0">
        <thead><tr><th>Number</th><th>Type</th><th>Party</th><th>Date</th><th>Method</th><th class="text-end">Amount</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (!empty($payments['data'])): foreach ($payments['data'] as $p): ?>
        <tr>
            <td class="fw-bold"><?= Helper::escape($p['payment_number']) ?></td>
            <td><span class="badge bg-<?= $p['type']==='receipt' ? 'success' : 'primary' ?>"><?= ucfirst($p['type']) ?></span></td>
            <td><?= Helper::escape($p['customer_name'] ?? $p['supplier_name'] ?? '-') ?></td>
            <td><?= Helper::formatDate($p['payment_date']) ?></td>
            <td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= Helper::escape(Helper::paymentMethodLabel($p['payment_method'] ?? 'cash')) ?></span></td>
            <td class="text-end fw-bold"><?= Helper::formatCurrency($p['amount']) ?></td>
            <td><div class="action-btns">
                <a href="<?= APP_URL ?>/index.php?page=payments&action=view_payment&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon"><i class="fas fa-eye"></i></a>
                <a href="<?= APP_URL ?>/index.php?page=invoice&type=<?= $p['type'] ?>&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info btn-icon" target="_blank" title="Print"><i class="fas fa-print"></i></a>
                <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=<?= $p['type'] ?>&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success btn-icon" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
                <?php if (Session::hasPermission('payments.delete')): ?>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=payments&action=delete" class="d-inline" data-confirm="Delete this payment?">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon"><i class="fas fa-trash"></i></button>
                </form>
                <?php endif; ?>
            </div></td>
        </tr>
        <?php endforeach; else: ?><tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-money-bill-wave fa-2x mb-2 opacity-25 d-block"></i>No payments found.</td></tr><?php endif; ?>
        </tbody>
    </table></div></div>
    <?php if (($payments['totalPages'] ?? 0) > 1): ?>
    <div class="card-footer"><?= Helper::pagination($payments['page'], $payments['totalPages'], APP_URL . '/index.php?page=payments&type=' . $type) ?></div>
    <?php endif; ?>
</div>
