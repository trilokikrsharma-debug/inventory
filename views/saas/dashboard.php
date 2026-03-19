<?php
$tenantPlanName = (string)($tenant['plan_name'] ?? 'Starter');
$tenantSubStatus = strtolower((string)($tenant['subscription_status'] ?? 'trial'));
$statusClass = 'secondary';
if ($tenantSubStatus === 'active') {
    $statusClass = 'success';
} elseif ($tenantSubStatus === 'trial') {
    $statusClass = 'warning text-dark';
} elseif (in_array($tenantSubStatus, ['inactive', 'suspended', 'cancelled'], true)) {
    $statusClass = 'danger';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-1"><i class="fas fa-crown me-2 text-primary"></i>SaaS Plan Dashboard</h2>
        <p class="text-muted mb-0">Track your current plan, upgrade options, and recent billing.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=saas_billing&action=subscribe" class="btn btn-primary">
        <i class="fas fa-arrow-up me-1"></i>Manage Plan
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Current Plan</div>
                <h4 class="mb-2"><?= e($tenantPlanName) ?></h4>
                <span class="badge bg-<?= $statusClass ?> text-uppercase"><?= e($tenantSubStatus) ?></span>
                <?php if (!empty($tenant['trial_ends_at'])): ?>
                    <div class="small text-muted mt-2">Trial ends: <?= e(date('Y-m-d', strtotime($tenant['trial_ends_at']))) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Users</div>
                <h4 class="mb-2"><?= (int)$userCount ?> / <?= (int)($tenant['max_users'] ?? 0) ?></h4>
                <div class="small text-muted">Plan user limit tracking</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Current Price</div>
                <h4 class="mb-2">Rs <?= number_format((float)($tenant['offer_price'] ?? $tenant['price'] ?? 0), 2) ?></h4>
                <div class="small text-muted text-uppercase"><?= e((string)($tenant['billing_type'] ?? $tenant['billing_cycle'] ?? 'monthly')) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0">Available Plans</h6>
    </div>
    <div class="card-body">
        <?php if (!empty($plans)): ?>
            <div class="row g-3">
                <?php foreach ($plans as $plan): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold"><?= e($plan['name'] ?? 'Plan') ?></div>
                            <div class="text-primary fw-bold">Rs <?= number_format(SaaSBillingHelper::effectivePlanPrice($plan), 2) ?></div>
                            <div class="small text-muted text-uppercase">
                                <?= e((string)($plan['billing_type'] ?? 'monthly')) ?> / <?= (int)($plan['duration_days'] ?? 30) ?> days
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-muted">No active plans available.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Recent Billing History</h6>
        <a href="<?= APP_URL ?>/index.php?page=saas_dashboard&action=billing_history" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Payment ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($history)): ?>
                    <?php foreach ($history as $row): ?>
                    <tr>
                        <td class="ps-3"><code><?= e($row['razorpay_payment_id'] ?? '-') ?></code></td>
                        <td>Rs <?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                        <td><span class="badge bg-secondary text-uppercase"><?= e($row['status'] ?? '-') ?></span></td>
                        <td class="small text-muted"><?= !empty($row['billing_date']) ? e(date('Y-m-d H:i', strtotime($row['billing_date']))) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No billing history yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
