<?php $pageTitle = 'Quotations'; ?>
<div class="page-header">
    <div>
        <h4 class="mb-0">Quotations</h4>
        <small class="text-muted">Create and manage customer quotations</small>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=quotations&action=create" class="btn btn-success">
        <i class="fas fa-plus me-2"></i>New Quotation
    </a>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card text-center py-2">
        <div class="fw-bold fs-4"><?= $totals['total'] ?></div>
        <small class="text-muted">Total</small>
    </div></div>
    <div class="col-md-3"><div class="card text-center py-2">
        <div class="fw-bold fs-4 text-secondary"><?= $totals['draft'] ?></div>
        <small class="text-muted">Draft</small>
    </div></div>
    <div class="col-md-3"><div class="card text-center py-2">
        <div class="fw-bold fs-4 text-info"><?= $totals['sent'] ?></div>
        <small class="text-muted">Sent</small>
    </div></div>
    <div class="col-md-3"><div class="card text-center py-2">
        <div class="fw-bold fs-4 text-success"><?= $totals['converted'] ?></div>
        <small class="text-muted">Converted</small>
    </div></div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="quotations">
            <div class="col-md-3"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search #, customer..." value="<?= Helper::escape($search) ?>"></div>
            <div class="col-md-2"><input type="date" name="from_date" class="form-control form-control-sm" value="<?= $fromDate ?>"></div>
            <div class="col-md-2"><input type="date" name="to_date" class="form-control form-control-sm" value="<?= $toDate ?>"></div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="draft" <?= $status==='draft'?'selected':'' ?>>Draft</option>
                    <option value="sent" <?= $status==='sent'?'selected':'' ?>>Sent</option>
                    <option value="converted" <?= $status==='converted'?'selected':'' ?>>Converted</option>
                    <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="<?= APP_URL ?>/index.php?page=quotations" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>#</th><th>Quotation No</th><th>Customer</th><th>Date</th><th>Valid Until</th><th>Amount</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                <?php if (empty($quotations['data'])): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-file-invoice fa-2x mb-2 opacity-25 d-block"></i>No quotations found.</td></tr>
                <?php else: $i = ($quotations['page'] - 1) * $quotations['perPage']; foreach ($quotations['data'] as $q): $i++; ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><a href="<?= APP_URL ?>/index.php?page=quotations&action=detail&id=<?= $q['id'] ?>" class="fw-bold"><?= Helper::escape($q['quotation_number']) ?></a></td>
                    <td><?= Helper::escape($q['customer_name'] ?? '') ?></td>
                    <td><?= Helper::formatDate($q['quotation_date']) ?></td>
                    <td>
                        <?php if ($q['valid_until']): ?>
                            <?php $expired = strtotime($q['valid_until']) < time() && $q['status'] === 'sent'; ?>
                            <span class="<?= $expired ? 'text-danger' : '' ?>"><?= Helper::formatDate($q['valid_until']) ?></span>
                            <?php if ($expired): ?><span class="badge bg-danger ms-1 ">Expired</span><?php endif; ?>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td class="fw-bold"><?= Helper::formatCurrency($q['grand_total']) ?></td>
                    <td>
                        <?php $statusColors = ['draft'=>'secondary','sent'=>'info','converted'=>'success','cancelled'=>'danger']; ?>
                        <span class="badge bg-<?= $statusColors[$q['status']] ?? 'secondary' ?>"><?= ucfirst($q['status']) ?></span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= APP_URL ?>/index.php?page=quotations&action=detail&id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                            <a href="<?= APP_URL ?>/index.php?page=invoice&type=quotation&id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-info" target="_blank" title="Print"><i class="fas fa-print"></i></a>
                            <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=quotation&id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-success" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
                            <?php if ($q['status'] !== 'converted' && $q['status'] !== 'cancelled'): ?>
                            <?php if (Session::hasPermission('quotations.convert')): ?>
                            <form method="POST" data-confirm="Convert to Sale?">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="id" value="<?= $q['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Convert to Sale"
                                    formaction="<?= APP_URL ?>/index.php?page=quotations&action=convert">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if (Session::hasPermission('quotations.delete')): ?>
                            <form method="POST" action="<?= APP_URL ?>/index.php?page=quotations&action=delete" data-confirm="Delete this quotation?">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="id" value="<?= $q['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($quotations['totalPages'] > 1): ?>
    <div class="card-footer">
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $quotations['totalPages']; $p++): ?>
            <li class="page-item <?= $p == $quotations['page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=quotations&search=<?= urlencode($search) ?>&status=<?= $status ?>&pg=<?= $p ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>
