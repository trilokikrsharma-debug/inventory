<?php $pageTitle = 'Payment: ' . Helper::escape($payment['payment_number']); ?>
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=payments">Payments</a></li><li class="breadcrumb-item active"><?= Helper::escape($payment['payment_number']) ?></li></ol></nav>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=invoice&type=<?= $payment['type'] === 'receipt' ? 'receipt' : 'payment' ?>&id=<?= $payment['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-invoice me-1"></i>Print Receipt</a>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h6><i class="fas fa-money-bill me-2"></i><?= Helper::escape($payment['payment_number']) ?></h6><span class="badge bg-<?= $payment['type']==='receipt'?'success':'primary' ?>"><?= ucfirst($payment['type']) ?></span></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Party</td><td class="fw-bold"><?= Helper::escape($payment['customer_name'] ?? $payment['supplier_name'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Amount</td><td class="fw-bold fs-5 text-primary"><?= Helper::formatCurrency($payment['amount']) ?></td></tr>
                    <tr><td class="text-muted">Date</td><td><?= Helper::formatDate($payment['payment_date']) ?></td></tr>
                    <tr><td class="text-muted">Method</td><td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= Helper::escape(Helper::paymentMethodLabel($payment['payment_method'] ?? 'cash')) ?></span></td></tr>
                    <?php if ($payment['reference_number']): ?><tr><td class="text-muted">Reference</td><td><?= Helper::escape($payment['reference_number']) ?></td></tr><?php endif; ?>
                    <?php if ($payment['bank_name']): ?><tr><td class="text-muted">Bank</td><td><?= Helper::escape($payment['bank_name']) ?></td></tr><?php endif; ?>
                    <?php if ($payment['note']): ?><tr><td class="text-muted">Note</td><td><?= Helper::escape($payment['note']) ?></td></tr><?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
