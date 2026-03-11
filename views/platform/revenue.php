<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-chart-line text-primary me-2"></i> Platform Revenue</h1>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-success text-white shadow h-100 py-2 border-0">
            <div class="card-body py-4">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                            Monthly Recurring Revenue (MRR)</div>
                        <div class="h2 mb-0 font-weight-bold text-white">₹<?php echo number_format($mrr, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-primary text-white shadow h-100 py-2 border-0">
            <div class="card-body py-4">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                            Annual Run Rate (ARR)</div>
                        <div class="h2 mb-0 font-weight-bold text-white">₹<?php echo number_format($arr, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom-0">
        <h6 class="m-0 font-weight-bold text-primary">Raw Master Ledger History</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Gateway TXN ID</th>
                        <th>Tenant</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($history as $h): ?>
                    <tr>
                        <td class="ps-4 text-muted"><code><?php echo htmlspecialchars($h['razorpay_payment_id'] ?? 'unknown_txn'); ?></code></td>
                        <td><strong><?php echo htmlspecialchars($h['company_name']); ?></strong></td>
                        <td class="fw-bold">₹<?php echo number_format($h['amount'], 2); ?></td>
                        <td>
                            <?php if($h['status'] === 'captured' || $h['status'] === 'paid'): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php elseif($h['status'] === 'failed'): ?>
                                <span class="badge bg-danger">Failed</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($h['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4 text-muted small"><?php echo date('M d, Y H:i', strtotime($h['billing_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if(empty($history)): ?>
            <div class="p-4 text-center text-muted">No completed payment transactions exist yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
