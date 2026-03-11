<?php $pageTitle = 'Customers'; ?>
<div class="page-header">
    <div><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Customers</li></ol></nav></div>
    <a href="<?= APP_URL ?>/index.php?page=customers&action=create" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Customer</a>
</div>
<div class="card">
    <div class="card-header">
        <h6><i class="fas fa-users me-2"></i>Customer List</h6>
        <form class="d-flex gap-2" method="GET"><input type="hidden" name="page" value="customers">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?= Helper::escape($search) ?>" style="width:200px;">
            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>City</th><th>Balance</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (!empty($customers['data'])): $i = ($customers['page']-1) * $customers['perPage'];
            foreach ($customers['data'] as $c): $i++; ?>
        <tr>
            <td><?= $i ?></td>
            <td><a href="<?= APP_URL ?>/index.php?page=customers&action=view_customer&id=<?= $c['id'] ?>" class="fw-bold"><?= Helper::escape($c['name']) ?></a></td>
            <td><?= Helper::escape($c['phone'] ?? '-') ?></td>
            <td><?= Helper::escape($c['city'] ?? '-') ?></td>
            <td class="fw-bold <?= ($c['current_balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>"><?= Helper::formatCurrency($c['current_balance'] ?? 0) ?></td>
            <td><div class="action-btns">
                <a href="<?= APP_URL ?>/index.php?page=customers&action=view_customer&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon"><i class="fas fa-eye"></i></a>
                <a href="<?= APP_URL ?>/index.php?page=customers&action=edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary btn-icon"><i class="fas fa-edit"></i></a>
                <?php if (Session::hasPermission('customers.delete')): ?>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=customers&action=delete" class="d-inline" data-confirm="Delete this customer?">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon"><i class="fas fa-trash"></i></button>
                </form>
                <?php endif; ?>
            </div></td>
        </tr>
        <?php endforeach; else: ?><tr><td colspan="6" class="text-center py-5 text-muted"><i class="fas fa-user-friends fa-2x mb-2 opacity-25 d-block"></i>No customers found.</td></tr><?php endif; ?>
        </tbody>
    </table></div></div>
    <?php if (($customers['totalPages'] ?? 0) > 1): ?>
    <div class="card-footer"><?= Helper::pagination($customers['page'], $customers['totalPages'], APP_URL . '/index.php?page=customers&search=' . urlencode($search)) ?></div>
    <?php endif; ?>
</div>
