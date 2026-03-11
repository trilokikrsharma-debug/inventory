<?php $pageTitle = 'Purchases'; ?>
<div class="page-header">
    <div><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Purchases</li></ol></nav></div>
    <a href="<?= APP_URL ?>/index.php?page=purchases&action=create" class="btn btn-primary"><i class="fas fa-plus me-1"></i>New Purchase</a>
</div>
<div class="card">
    <div class="card-header">
        <h6><i class="fas fa-cart-shopping me-2"></i>Purchase List</h6>
        <form class="d-flex gap-2 flex-wrap" method="GET"><input type="hidden" name="page" value="purchases">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Invoice..." value="<?= Helper::escape($filters['search'] ?? '') ?>" style="width:120px;">
            <select name="supplier_id" class="form-select form-select-sm" style="width:140px;"><option value="">All Suppliers</option><?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>" <?= ($filters['supplier_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= Helper::escape($s['name']) ?></option><?php endforeach; ?></select>
            <input type="date" name="from_date" class="form-control form-control-sm" value="<?= $filters['from_date'] ?? '' ?>" style="width:130px;">
            <input type="date" name="to_date" class="form-control form-control-sm" value="<?= $filters['to_date'] ?? '' ?>" style="width:130px;">
            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
            <a href="<?= APP_URL ?>/index.php?page=purchases" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
        </form>
    </div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0">
        <thead><tr><th>Invoice</th><th>Supplier</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (!empty($purchases['data'])): foreach ($purchases['data'] as $p): ?>
        <tr>
            <td><a href="<?= APP_URL ?>/index.php?page=purchases&action=view_purchase&id=<?= $p['id'] ?>" class="fw-bold"><?= Helper::escape($p['invoice_number']) ?></a></td>
            <td><?= Helper::escape($p['supplier_name']) ?></td>
            <td><?= Helper::formatDate($p['purchase_date']) ?></td>
            <td class="text-end fw-bold"><?= Helper::formatCurrency($p['grand_total']) ?></td>
            <td class="text-end text-success"><?= Helper::formatCurrency($p['paid_amount']) ?></td>
            <td class="text-end text-danger"><?= Helper::formatCurrency($p['due_amount']) ?></td>
            <td><?= Helper::paymentBadge($p['payment_status']) ?></td>
            <td><div class="action-btns">
                <a href="<?= APP_URL ?>/index.php?page=purchases&action=view_purchase&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon"><i class="fas fa-eye"></i></a>
                <a href="<?= APP_URL ?>/index.php?page=invoice&type=purchase&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-info btn-icon" target="_blank" title="Print"><i class="fas fa-print"></i></a>
                <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=purchase&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success btn-icon" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
                <?php if (Session::hasPermission('purchases.edit')): ?>
                <a href="<?= APP_URL ?>/index.php?page=purchases&action=edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning btn-icon"><i class="fas fa-edit"></i></a>
                <?php endif; ?>
                <?php if (Session::hasPermission('purchases.delete')): ?>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=purchases&action=delete" class="d-inline" data-confirm="Delete this purchase?">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon"><i class="fas fa-trash"></i></button>
                </form>
                <?php endif; ?>
            </div></td>
        </tr>
        <?php endforeach; else: ?><tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-cart-shopping fa-2x mb-2 opacity-25 d-block"></i>No purchases found.</td></tr><?php endif; ?>
        </tbody>
    </table></div></div>
    <?php if (($purchases['totalPages'] ?? 0) > 1): ?>
    <div class="card-footer"><?= Helper::pagination($purchases['page'], $purchases['totalPages'], APP_URL . '/index.php?page=purchases') ?></div>
    <?php endif; ?>
</div>
