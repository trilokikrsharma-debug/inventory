<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2 class="h3 mb-0 text-gray-800">
            <i class="fas fa-satellite-dish text-primary me-2"></i>Platform Overview
        </h2>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <h6 class="text-muted fw-bold mb-2 text-uppercase small">Total MRR</h6>
                <h3 class="fw-bold mb-1">Rs <?= number_format((float)($metrics['mrr'] ?? 0), 2) ?></h3>
                <span class="text-success small">Monthly recurring value</span>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <h6 class="text-muted fw-bold mb-2 text-uppercase small">Active Tenants</h6>
                <h3 class="fw-bold mb-1"><?= number_format((int)($metrics['activeTenants'] ?? 0)) ?></h3>
                <span class="text-primary small">Of <?= number_format((int)($metrics['totalTenants'] ?? 0)) ?> total tenants</span>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <h6 class="text-muted fw-bold mb-2 text-uppercase small">Active Subscriptions</h6>
                <h3 class="fw-bold mb-1"><?= number_format((int)($metrics['activeSubscriptions'] ?? 0)) ?></h3>
                <span class="text-info small">Billing status active</span>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body">
                <h6 class="text-muted fw-bold mb-2 text-uppercase small">Total SaaS Revenue</h6>
                <h3 class="fw-bold mb-1">Rs <?= number_format((float)($metrics['totalRevenue'] ?? 0), 2) ?></h3>
                <span class="text-muted small">Captured gateway payments</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><h6 class="mb-0">Plan-wise Subscribers</h6></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($planWiseSubscribers)): ?>
                        <?php foreach ($planWiseSubscribers as $row): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?= e($row['name']) ?></span>
                            <span class="badge bg-primary rounded-pill"><?= (int)$row['subscribers'] ?></span>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted">No active subscribers yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><h6 class="mb-0">Promo Stats</h6></div>
            <div class="card-body">
                <div class="mb-2">Total Promo Codes: <strong><?= (int)($promoUsageStats['total_codes'] ?? 0) ?></strong></div>
                <div class="mb-2">Total Usages: <strong><?= (int)($promoUsageStats['total_usage'] ?? 0) ?></strong></div>
                <div>Total Discount Given: <strong>Rs <?= number_format((float)($promoUsageStats['total_discount'] ?? 0), 2) ?></strong></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><h6 class="mb-0">Referral Stats</h6></div>
            <div class="card-body">
                <div class="mb-2">Pending: <strong><?= (int)($referralStats['pending'] ?? 0) ?></strong></div>
                <div class="mb-2">Successful: <strong><?= (int)($referralStats['successful'] ?? 0) ?></strong></div>
                <div>Rewarded: <strong><?= (int)($referralStats['rewarded'] ?? 0) ?></strong></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between">
                <h6 class="mb-0">Recent Payment Logs</h6>
                <a href="<?= APP_URL ?>/index.php?page=platform&action=payments" class="small">View all</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Company</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>At</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($recentPayments)): ?>
                            <?php foreach ($recentPayments as $row): ?>
                            <tr>
                                <td class="ps-3"><?= e($row['company_name'] ?? 'N/A') ?></td>
                                <td><?= e($row['plan_name'] ?? '-') ?></td>
                                <td>Rs <?= number_format((float)($row['amount'] ?? 0), 2) ?></td>
                                <td>
                                    <?php $st = (string)($row['status'] ?? 'created'); ?>
                                    <span class="badge bg-<?= $st === 'captured' ? 'success' : (($st === 'failed' || $st === 'error') ? 'danger' : 'secondary') ?> text-uppercase">
                                        <?= e($st) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= !empty($row['created_at']) ? e(date('Y-m-d H:i', strtotime($row['created_at']))) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No payment logs found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white"><h6 class="mb-0">Recent Failed Payments</h6></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($recentFailedPayments)): ?>
                        <?php foreach ($recentFailedPayments as $f): ?>
                        <li class="list-group-item">
                            <div class="fw-semibold"><?= e($f['company_name'] ?? 'N/A') ?></div>
                            <div class="small text-danger"><?= e($f['failure_reason'] ?? 'Unknown failure') ?></div>
                            <div class="small text-muted"><?= !empty($f['created_at']) ? e(date('Y-m-d H:i', strtotime($f['created_at']))) : '-' ?></div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted">No failed payment logs.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0">Recent Upgrades / Renewals / Cancellations</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Company</th>
                                <th>Plan</th>
                                <th>Change Type</th>
                                <th>Status</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($recentLifecycle)): ?>
                            <?php foreach ($recentLifecycle as $row): ?>
                            <tr>
                                <td class="ps-3"><?= e($row['company_name'] ?? 'N/A') ?></td>
                                <td><?= e($row['plan_name'] ?? '-') ?></td>
                                <td class="text-uppercase small"><?= e($row['change_type'] ?? '-') ?></td>
                                <td><span class="badge bg-secondary text-uppercase"><?= e($row['status'] ?? '-') ?></span></td>
                                <td class="small text-muted"><?= !empty($row['updated_at']) ? e(date('Y-m-d H:i', strtotime($row['updated_at']))) : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No lifecycle logs available.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom-0">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-server me-2"></i>Infrastructure Vitals</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped m-0">
                        <tr><td width="30%"><strong>Database Latency</strong></td><td><span class="badge bg-success"><?= e($sysHealth['latency'] ?? 'N/A') ?></span></td></tr>
                        <tr><td><strong>Redis Cluster</strong></td><td><?= ($sysHealth['redis'] ?? 'Missing') === 'Missing' ? '<span class="badge bg-danger">Not Connected</span>' : '<span class="badge bg-success">Connected</span>' ?></td></tr>
                        <tr><td><strong>Main Disk Storage</strong></td><td><?= e($sysHealth['disk'] ?? 'N/A') ?></td></tr>
                        <tr><td><strong>Worker Memory Footprint</strong></td><td><?= e($sysHealth['mem'] ?? 'N/A') ?></td></tr>
                        <tr><td><strong>Background Workers</strong></td><td><span class="badge bg-secondary"><?= number_format((int)($queue['pending'] ?? 0)) ?> Pending</span> / <span class="badge bg-danger"><?= number_format((int)($queue['failed'] ?? 0)) ?> Failed</span></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
