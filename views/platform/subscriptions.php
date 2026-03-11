<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-credit-card text-success me-2"></i> Subscriptions Watch</h1>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom-0">
        <h6 class="m-0 font-weight-bold text-success">Active & Historical Gateway Subscriptions</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Gateway ID</th>
                        <th>Tenant</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Started At</th>
                        <th>Current Cycle Ends</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($subscriptions as $s): ?>
                    <tr>
                        <td class="ps-4 text-muted">
                            <code><?php echo htmlspecialchars($s['razorpay_subscription_id']); ?></code>
                        </td>
                        <td><strong><?php echo htmlspecialchars($s['company_name']); ?></strong></td>
                        <td><span class="badge bg-dark"><?php echo htmlspecialchars($s['plan_name']); ?></span></td>
                        <td>
                            <?php if($s['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php elseif($s['status'] === 'halted'): ?>
                                <span class="badge bg-warning text-dark">Halted</span>
                            <?php elseif($s['status'] === 'cancelled'): ?>
                                <span class="badge bg-danger">Cancelled</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($s['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?php echo empty($s['current_start']) ? 'N/A' : date('Y-m-d H:i', strtotime($s['current_start'])); ?></td>
                        <td class="text-muted small"><strong><?php echo empty($s['current_end']) ? 'N/A' : date('Y-m-d H:i', strtotime($s['current_end'])); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($subscriptions)): ?>
            <div class="p-4 text-center text-muted">No Razorpay subscriptions initiated yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
