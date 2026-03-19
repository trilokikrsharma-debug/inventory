<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Billing History</h2>
        <p class="text-muted mb-0">Full payment trail for your tenant account.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=saas_dashboard" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Payment ID</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th>Billing Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($history)): ?>
                    <?php foreach ($history as $row): ?>
                    <tr>
                        <td class="ps-3"><code><?= e($row['razorpay_payment_id'] ?? '-') ?></code></td>
                        <td>Rs <?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                        <td><?= e($row['currency'] ?? 'INR') ?></td>
                        <td><span class="badge bg-secondary text-uppercase"><?= e($row['status'] ?? '-') ?></span></td>
                        <td class="small text-muted"><?= !empty($row['billing_date']) ? e(date('Y-m-d H:i', strtotime($row['billing_date']))) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No billing history found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
