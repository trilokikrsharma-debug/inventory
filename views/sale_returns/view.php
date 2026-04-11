<?php $pageTitle = 'Return: ' . Helper::escape($return['return_number']); ?>
<div class="detail-page-shell">
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=sale_returns">Returns</a></li>
        <li class="breadcrumb-item active"><?= Helper::escape($return['return_number']) ?></li>
    </ol></nav>
    <div class="detail-page-actions">
        <a href="<?= APP_URL ?>/index.php?page=sale_returns" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
        <a href="<?= APP_URL ?>/index.php?page=invoice&type=return&id=<?= $return['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-invoice me-1"></i>Credit Note</a>
        <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=return&id=<?= $return['id'] ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-download me-1"></i>Download PDF</a>
        <button type="button" id="printSaleReturnBtn" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print me-1"></i>Print</button>
    </div>
</div>

<script nonce="<?= $cspNonce ?? '' ?>">
document.getElementById('printSaleReturnBtn')?.addEventListener('click', function () {
    window.print();
});
</script>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card detail-card">
            <div class="card-body p-4">
                <div class="row mb-4 g-3">
                    <div class="col-md-6">
                        <span class="badge bg-warning text-dark fs-6"><?= Helper::escape($return['return_number']) ?></span>
                        <?= (($return['status'] ?? 'posted') === 'cancelled') ? '<span class="badge bg-danger fs-6 ms-2">Cancelled</span>' : '<span class="badge bg-success fs-6 ms-2">Posted</span>' ?>
                        <div class="mt-2"><strong>Against Invoice:</strong> <?= Helper::escape($return['invoice_number'] ?? 'N/A') ?></div>
                        <div><strong>Customer:</strong> <?= Helper::escape($return['customer_name'] ?? '') ?></div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="text-muted">Return Date</div>
                        <div class="fw-bold"><?= Helper::formatDate($return['return_date']) ?></div>
                        <?php if (!empty($return['note'])): ?>
                        <div class="mt-2"><small class="text-muted">Reason: <?= Helper::escape($return['note']) ?></small></div>
                        <?php endif; ?>
                        <?php if (($return['status'] ?? 'posted') === 'cancelled'): ?>
                        <div class="mt-2"><small class="text-danger">Cancelled by <?= Helper::escape($return['cancelled_by_name'] ?? 'system') ?> on <?= Helper::escape($return['cancelled_at'] ?? '') ?></small></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table detail-table mb-0">
                        <thead><tr><th>#</th><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        <?php $i = 0; foreach ($return['items'] as $item): $i++; ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= Helper::escape($item['product_name']) ?></td>
                            <td class="text-center"><?= Helper::formatQty($item['quantity']) ?></td>
                            <td class="text-end"><?= Helper::formatCurrency($item['unit_price']) ?></td>
                            <td class="text-end fw-bold"><?= Helper::formatCurrency($item['total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card detail-card">
            <div class="card-header"><h6>Summary</h6></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2 fs-5 fw-bold">
                    <span>Total Returned</span>
                    <span class="text-danger"><?= Helper::formatCurrency($return['total_amount']) ?></span>
                </div>
                <hr>
                <?php if (($return['status'] ?? 'posted') === 'cancelled'): ?>
                <div class="alert alert-danger py-2 small">
                    <i class="fas fa-ban me-1"></i>
                    This return was cancelled. Stock reversal and customer receivable impact were rolled back.
                    <?php if (!empty($return['cancel_reason'])): ?>
                    <div class="mt-1">Reason: <?= Helper::escape($return['cancel_reason']) ?></div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-success py-2 small">
                    <i class="fas fa-check-circle me-1"></i>
                    Stock restored. Customer balance reduced by <?= Helper::formatCurrency($return['total_amount']) ?>.
                </div>
                <?php endif; ?>
                <div class="mt-3">
                    <a href="<?= APP_URL ?>/index.php?page=sales&action=view_sale&id=<?= $return['sale_id'] ?>" class="btn btn-outline-primary w-100">
                        <i class="fas fa-file-invoice me-1"></i>View Original Sale
                    </a>
                </div>
                <?php if (($return['status'] ?? 'posted') !== 'cancelled' && (Session::hasPermission('returns.cancel') || Session::hasPermission('returns.create'))): ?>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=sale_returns&action=cancel" class="mt-3">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    <input type="hidden" name="id" value="<?= $return['id'] ?>">
                    <div class="mb-2">
                        <label class="form-label small">Cancel Reason</label>
                        <textarea name="cancel_reason" class="form-control form-control-sm" rows="3" maxlength="500" placeholder="Why is this return being cancelled?" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Cancel this sale return and reverse its accounting impact?');">
                        <i class="fas fa-ban me-1"></i>Cancel Return
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
