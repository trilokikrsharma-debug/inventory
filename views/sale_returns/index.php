<?php $pageTitle = 'Sale Returns'; ?>
<div class="page-header">
    <div>
        <h4 class="mb-0">Sale Returns</h4>
        <small class="text-muted">Manage customer returns and stock reversals</small>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=sale_returns&action=create" class="btn btn-warning">
        <i class="fas fa-undo me-2"></i>New Return
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="sale_returns">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search return#, customer, invoice..." value="<?= Helper::escape($search) ?>">
            </div>
            <div class="col-md-2"><input type="date" name="from_date" class="form-control form-control-sm" value="<?= $fromDate ?>"></div>
            <div class="col-md-2"><input type="date" name="to_date" class="form-control form-control-sm" value="<?= $toDate ?>"></div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="<?= APP_URL ?>/index.php?page=sale_returns" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Return No.</th>
                        <th>Sale Invoice</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Reason</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($returns['data'])): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-undo fa-2x mb-2 opacity-25 d-block"></i>No returns found.</td></tr>
                <?php else: $i = ($returns['page'] - 1) * $returns['perPage']; foreach ($returns['data'] as $r): $i++; ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><span class="badge bg-warning text-dark"><?= Helper::escape($r['return_number']) ?></span></td>
                    <td><?= Helper::escape($r['invoice_number'] ?? '-') ?></td>
                    <td><?= Helper::escape($r['customer_name'] ?? '-') ?></td>
                    <td class="fw-bold text-danger"><?= Helper::formatCurrency($r['total_amount']) ?></td>
                    <td><?= Helper::formatDate($r['return_date']) ?></td>
                    <td><small class="text-muted"><?= Helper::escape($r['reason'] ?? '') ?></small></td>
                    <td>
                        <div class="d-flex gap-1">
                        <a href="<?= APP_URL ?>/index.php?page=sale_returns&action=detail&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                        <a href="<?= APP_URL ?>/index.php?page=invoice&type=return&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-info" target="_blank" title="Print"><i class="fas fa-print"></i></a>
                        <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=return&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-success" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($returns['totalPages'] > 1): ?>
    <div class="card-footer">
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $returns['totalPages']; $p++): ?>
            <li class="page-item <?= $p == $returns['page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=sale_returns&search=<?= urlencode($search) ?>&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>&pg=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>
