<?php $pageTitle = 'Sales'; ?>
<style>
    .sales-shell .card {
        overflow: hidden;
    }
    .sales-filter-bar {
        align-items: center;
    }
    .sales-table {
        min-width: 860px;
    }
    .sales-table td {
        vertical-align: middle;
    }
    .sales-table .amount-main {
        color: var(--text-primary);
        font-variant-numeric: tabular-nums;
    }
    .sales-table .amount-paid {
        color: var(--success-dark);
        font-variant-numeric: tabular-nums;
    }
    .sales-table .amount-due {
        color: var(--danger-dark);
        font-variant-numeric: tabular-nums;
    }
    .sales-table .status-badge {
        white-space: nowrap;
    }
    .sales-table .status-badge .badge {
        min-width: 92px;
        justify-content: center;
        display: inline-flex;
        align-items: center;
    }
    .sales-table .action-btns {
        flex-wrap: nowrap;
    }
    [data-theme="dark"] .sales-shell .card {
        border-color: var(--border-color);
    }
    [data-theme="dark"] .sales-table .amount-main {
        color: var(--text-primary);
    }
    [data-theme="dark"] .sales-table .amount-paid {
        color: #63d7aa;
    }
    [data-theme="dark"] .sales-table .amount-due {
        color: #ff8f86;
    }
    [data-theme="dark"] .sales-table code {
        background: var(--surface-soft);
        color: var(--text-primary);
        padding: 0.12rem 0.4rem;
        border-radius: 0.45rem;
    }
    @media (max-width: 767.98px) {
        .sales-filter-bar > * {
            flex: 1 1 100%;
        }
        .sales-filter-bar .btn,
        .sales-filter-bar a.btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>
<div class="sales-shell">
<div class="page-header">
    <div><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item active">Sales</li></ol></nav></div>
    <div class="app-page-actions d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=sales&action=create" class="btn btn-primary"><i class="fas fa-plus me-1"></i>New Sale</a>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h6><i class="fas fa-receipt me-2"></i>Sales List</h6>
        <form class="d-flex gap-2 flex-wrap app-filter-form app-filter-form-compact sales-filter-bar" method="GET"><input type="hidden" name="page" value="sales">
            <input type="text" name="search" class="form-control form-control-sm app-filter-field app-filter-field-search" placeholder="Invoice..." value="<?= Helper::escape($filters['search'] ?? '') ?>">
            <select name="customer_id" class="form-select form-select-sm app-filter-field app-filter-field-select"><option value="">All Customers</option><?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= ($filters['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= Helper::escape($c['name']) ?></option><?php endforeach; ?></select>
            <input type="date" name="from_date" class="form-control form-control-sm app-filter-field app-filter-field-date" value="<?= $filters['from_date'] ?? '' ?>">
            <input type="date" name="to_date" class="form-control form-control-sm app-filter-field app-filter-field-date" value="<?= $filters['to_date'] ?? '' ?>">
            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
            <a href="<?= APP_URL ?>/index.php?page=sales" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
        </form>
    </div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-striped mb-0 sales-table">
        <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (!empty($sales['data'])): foreach ($sales['data'] as $s): ?>
        <tr>
            <td><a href="<?= APP_URL ?>/index.php?page=sales&action=view_sale&id=<?= $s['id'] ?>" class="fw-bold"><?= Helper::escape($s['invoice_number']) ?></a></td>
            <td><?= Helper::escape($s['customer_name']) ?></td>
            <td><?= Helper::formatDate($s['sale_date']) ?></td>
            <td class="text-end fw-bold amount-main"><?= Helper::formatCurrency($s['grand_total']) ?></td>
            <td class="text-end amount-paid"><?= Helper::formatCurrency($s['paid_amount']) ?></td>
            <td class="text-end amount-due"><?= Helper::formatCurrency($s['due_amount']) ?></td>
            <td class="status-badge"><?= Helper::paymentBadge($s['payment_status']) ?></td>
            <td><div class="action-btns">
                <a href="<?= APP_URL ?>/index.php?page=sales&action=view_sale&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon"><i class="fas fa-eye"></i></a>
                <a href="<?= APP_URL ?>/index.php?page=invoice&type=sale&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-info btn-icon" target="_blank" title="Print"><i class="fas fa-print"></i></a>
                <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=sale&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-success btn-icon" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
                <?php if (Session::hasPermission('sales.edit')): ?>
                <a href="<?= APP_URL ?>/index.php?page=sales&action=edit&id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-warning btn-icon"><i class="fas fa-edit"></i></a>
                <?php endif; ?>
                <?php if (Session::hasPermission('sales.delete')): ?>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=sales&action=delete" class="d-inline" data-confirm="Delete this sale?">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary btn-icon"><i class="fas fa-trash"></i></button>
                </form>
                <?php endif; ?>
            </div></td>
        </tr>
        <?php endforeach; else: ?><tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-receipt fa-2x mb-2 opacity-25 d-block"></i>No sales found.</td></tr><?php endif; ?>
        </tbody>
    </table></div></div>
    <?php if (($sales['totalPages'] ?? 0) > 1): ?>
    <div class="card-footer"><?= Helper::pagination($sales['page'], $sales['totalPages'], APP_URL . '/index.php?page=sales') ?></div>
    <?php endif; ?>
</div>
</div>
