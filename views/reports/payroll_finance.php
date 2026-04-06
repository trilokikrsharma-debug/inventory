<?php
$pageTitle = 'Payroll Finance Report';
$report = is_array($report ?? null) ? $report : [];
$summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
$runs = is_array($report['runs'] ?? null) ? $report['runs'] : [];
$entries = is_array($report['entries'] ?? null) ? $report['entries'] : [];
$fromMonth = (string)($fromMonth ?? date('Y-01'));
$toMonth = (string)($toMonth ?? date('Y-m'));
?>
<div class="report-page-shell">
<div class="page-header">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=reports">Reports</a></li><li class="breadcrumb-item active">Payroll Finance</li></ol></nav>
    <div class="report-page-actions">
        <form method="POST" action="<?= APP_URL ?>/index.php?page=reports&action=queue_export">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
            <input type="hidden" name="report_type" value="payroll_finance">
            <input type="hidden" name="from_month" value="<?= Helper::escape($fromMonth) ?>">
            <input type="hidden" name="to_month" value="<?= Helper::escape($toMonth) ?>">
            <button type="submit" class="btn btn-outline-success btn-sm"><i class="fas fa-file-arrow-down me-1"></i>Queue CSV</button>
        </form>
    </div>
</div>

<div class="card mb-3 report-filter-card">
    <div class="card-body py-2">
        <form class="report-filter-form" method="GET">
            <input type="hidden" name="page" value="reports">
            <input type="hidden" name="action" value="payroll_finance">
            <div class="report-filter-field report-filter-field-wide">
                <label class="form-label small mb-0">From Month</label>
                <input type="month" name="from_month" class="form-control form-control-sm" value="<?= Helper::escape($fromMonth) ?>">
            </div>
            <div class="report-filter-field report-filter-field-wide">
                <label class="form-label small mb-0">To Month</label>
                <input type="month" name="to_month" class="form-control form-control-sm" value="<?= Helper::escape($toMonth) ?>">
            </div>
            <button class="btn btn-sm btn-primary report-filter-submit"><i class="fas fa-filter me-1"></i>Filter</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-3 report-summary-grid">
    <div class="col-md-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Runs</div><div class="display-6 fw-bold"><?= (int)($summary['run_count'] ?? 0) ?></div></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Gross</div><div class="fw-bold text-primary"><?= Helper::formatCurrency($summary['gross_amount'] ?? 0) ?></div></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Deductions</div><div class="fw-bold text-danger"><?= Helper::formatCurrency($summary['deduction_amount'] ?? 0) ?></div></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Net</div><div class="fw-bold text-dark"><?= Helper::formatCurrency($summary['net_amount'] ?? 0) ?></div></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Paid</div><div class="fw-bold text-success"><?= Helper::formatCurrency($summary['paid_amount'] ?? 0) ?></div></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-muted small">Pending</div><div class="fw-bold text-warning"><?= Helper::formatCurrency($summary['pending_amount'] ?? 0) ?></div></div></div></div>
</div>

<div class="card mb-4 report-data-card">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-wallet me-2"></i>Payroll Runs</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0 report-data-table">
                <thead><tr><th>Month</th><th>Status</th><th>Employees</th><th>Pending Payouts</th><th>Paid Items</th><th class="text-end">Net</th><th>Audit</th></tr></thead>
                <tbody>
                    <?php if (empty($runs)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No payroll runs found for this period.</td></tr>
                    <?php else: foreach ($runs as $run): ?>
                    <tr>
                        <td class="fw-semibold"><?= Helper::escape(date('F Y', strtotime(($run['payroll_month'] ?? date('Y-m')) . '-01'))) ?></td>
                        <td><span class="badge <?= ($run['status'] ?? '') === 'paid' ? 'bg-success' : (($run['status'] ?? '') === 'approved' ? 'bg-info text-dark' : 'bg-warning text-dark') ?>"><?= Helper::escape(ucfirst($run['status'] ?? 'processed')) ?></span></td>
                        <td><?= (int)($run['employee_count'] ?? 0) ?></td>
                        <td><?= (int)($run['pending_items'] ?? 0) ?></td>
                        <td><?= (int)($run['paid_items'] ?? 0) ?></td>
                        <td class="text-end fw-bold"><?= Helper::formatCurrency($run['net_amount'] ?? 0) ?></td>
                        <td>
                            <div><?= Helper::escape($run['processed_by_name'] ?? '-') ?></div>
                            <div class="small text-muted"><?= !empty($run['approved_by_name']) ? 'Approved by ' . Helper::escape($run['approved_by_name']) : 'Awaiting approval' ?></div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card report-data-card">
    <div class="card-header"><h6 class="mb-0"><i class="fas fa-book me-2"></i>Payroll Journal Entries</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0 report-data-table">
                <thead><tr><th>Date</th><th>Payroll Month</th><th>Payment</th><th>Employee</th><th>Account</th><th>Side</th><th>Method</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No payroll journal entries found for this period.</td></tr>
                    <?php else: foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= Helper::formatDate($entry['payment_date'] ?? date('Y-m-d')) ?></td>
                        <td><?= Helper::escape(date('M Y', strtotime(($entry['payroll_month'] ?? date('Y-m')) . '-01'))) ?></td>
                        <td class="fw-semibold"><?= Helper::escape($entry['payment_number'] ?? '-') ?></td>
                        <td>
                            <?= Helper::escape($entry['employee_name'] ?? '-') ?>
                            <div class="small text-muted"><?= Helper::escape($entry['employee_code'] ?? '-') ?></div>
                        </td>
                        <td>
                            <?= Helper::escape($entry['account_name'] ?? '-') ?>
                            <div class="small text-muted"><?= Helper::escape($entry['account_code'] ?? '-') ?></div>
                        </td>
                        <td><span class="badge <?= ($entry['entry_side'] ?? '') === 'debit' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' ?>"><?= Helper::escape(ucfirst($entry['entry_side'] ?? '-')) ?></span></td>
                        <td><?= Helper::escape(Helper::paymentMethodLabel($entry['payment_method'] ?? 'cash')) ?></td>
                        <td class="text-end fw-bold"><?= Helper::formatCurrency($entry['amount'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
