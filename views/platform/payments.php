<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="fas fa-money-check me-2 text-success"></i>SaaS Payment Logs</h2>
        <p class="text-muted mb-0">Recent Razorpay payment attempts, captures, and failures.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=platform&action=dashboard" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Company</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Gateway Refs</th>
                        <th>Failure Reason</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $row): ?>
                    <tr>
                        <td class="ps-4 fw-semibold"><?= e($row['company_name'] ?? 'N/A') ?></td>
                        <td><?= e($row['plan_name'] ?? '-') ?></td>
                        <td>Rs <?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                        <td>
                            <?php $st = (string)($row['status'] ?? 'created'); ?>
                            <span class="badge bg-<?= $st === 'captured' ? 'success' : (($st === 'failed' || $st === 'error') ? 'danger' : 'secondary') ?> text-uppercase">
                                <?= e($st) ?>
                            </span>
                        </td>
                        <td class="small">
                            <?php if (!empty($row['razorpay_order_id'])): ?>
                                <div>Order: <code><?= e($row['razorpay_order_id']) ?></code></div>
                            <?php endif; ?>
                            <?php if (!empty($row['razorpay_subscription_id'])): ?>
                                <div>Sub: <code><?= e($row['razorpay_subscription_id']) ?></code></div>
                            <?php endif; ?>
                            <?php if (!empty($row['razorpay_payment_id'])): ?>
                                <div>Pay: <code><?= e($row['razorpay_payment_id']) ?></code></div>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= e($row['failure_reason'] ?? '-') ?></td>
                        <td class="small text-muted"><?= !empty($row['created_at']) ? e(date('Y-m-d H:i', strtotime($row['created_at']))) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No payment logs available.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
