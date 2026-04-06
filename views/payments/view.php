<?php $pageTitle = 'Payment: ' . Helper::escape($payment['payment_number']); ?>
<div class="detail-page-shell">
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=payments">Payments</a></li><li class="breadcrumb-item active"><?= Helper::escape($payment['payment_number']) ?></li></ol></nav>
    <div class="detail-page-actions">
        <a href="<?= APP_URL ?>/index.php?page=payments" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
        <a href="<?= APP_URL ?>/index.php?page=invoice&type=<?= $payment['type'] === 'receipt' ? 'receipt' : 'payment' ?>&id=<?= $payment['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-invoice me-1"></i>Print Receipt</a>
        <a href="<?= APP_URL ?>/index.php?page=invoice&action=download&type=<?= $payment['type'] === 'receipt' ? 'receipt' : 'payment' ?>&id=<?= $payment['id'] ?>" class="btn btn-outline-success btn-sm"><i class="fas fa-download me-1"></i>Download PDF</a>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card detail-card">
            <div class="card-header"><h6><i class="fas fa-money-bill me-2"></i><?= Helper::escape($payment['payment_number']) ?></h6><span class="badge bg-<?= $payment['type']==='receipt'?'success':'primary' ?>"><?= ucfirst($payment['type']) ?></span></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm mb-0 detail-table">
                    <tr>
                        <td class="text-muted">Party</td>
                        <td class="fw-bold">
                            <?= Helper::escape($payment['customer_name'] ?? $payment['supplier_name'] ?? $payment['payroll_employee_name'] ?? '-') ?>
                            <?php if (!empty($payment['payroll_item_id'])): ?>
                            <div class="small text-muted">Payroll payout<?= !empty($payment['payroll_employee_code']) ? ' • ' . Helper::escape($payment['payroll_employee_code']) : '' ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr><td class="text-muted">Amount</td><td class="fw-bold fs-5 text-primary"><?= Helper::formatCurrency($payment['amount']) ?></td></tr>
                    <tr><td class="text-muted">Date</td><td><?= Helper::formatDate($payment['payment_date']) ?></td></tr>
                    <tr><td class="text-muted">Method</td><td><span class="badge bg-secondary bg-opacity-10 text-secondary"><?= Helper::escape(Helper::paymentMethodLabel($payment['payment_method'] ?? 'cash')) ?></span></td></tr>
                    <?php if ($payment['reference_number']): ?><tr><td class="text-muted">Reference</td><td><?= Helper::escape($payment['reference_number']) ?></td></tr><?php endif; ?>
                    <?php if ($payment['bank_name']): ?><tr><td class="text-muted">Bank</td><td><?= Helper::escape($payment['bank_name']) ?></td></tr><?php endif; ?>
                    <?php if ($payment['note']): ?><tr><td class="text-muted">Note</td><td class="text-wrap"><?= Helper::escape($payment['note']) ?></td></tr><?php endif; ?>
                </table>
                </div>
            </div>
        </div>
        <?php if (!empty($payment['journal_entries'])): ?>
        <div class="card detail-card mt-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-book me-2"></i>Finance Journal Trace</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle detail-table">
                        <thead>
                            <tr>
                                <th>Side</th>
                                <th>Account</th>
                                <th>Memo</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($payment['journal_entries'] ?? []) as $entry): ?>
                            <tr>
                                <td><span class="badge <?= ($entry['entry_side'] ?? '') === 'debit' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' ?>"><?= Helper::escape(ucfirst($entry['entry_side'] ?? '')) ?></span></td>
                                <td>
                                    <div class="fw-semibold"><?= Helper::escape($entry['account_name'] ?? '-') ?></div>
                                    <div class="small text-muted"><?= Helper::escape($entry['account_code'] ?? '-') ?></div>
                                </td>
                                <td class="small text-muted"><?= Helper::escape($entry['memo'] ?? '-') ?></td>
                                <td class="text-end fw-semibold"><?= Helper::formatCurrency($entry['amount'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
