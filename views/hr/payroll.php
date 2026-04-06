<?php
$month = (string)($month ?? date('Y-m'));
$rows = is_array($rows ?? null) ? $rows : [];
$totalPayroll = (float)($totalPayroll ?? 0);
$run = is_array($run ?? null) ? $run : null;
$recentRuns = is_array($recentRuns ?? null) ? $recentRuns : [];
$policy = is_array($policy ?? null) ? $policy : [];
?>
<div class="hr-page-shell report-page-shell">
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=hr">HR</a></li>
                <li class="breadcrumb-item active">Payroll</li>
            </ol>
        </nav>
        <h2 class="h4 mb-1"><i class="fas fa-wallet me-2 text-primary"></i>Payroll Engine</h2>
        <p class="text-muted mb-0">Process monthly payroll runs, track payout status, and generate payslips.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=hr" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>HR Home</a>
</div>

<div class="row g-3 mt-1 mb-3 report-summary-grid">
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Payroll Month</div><div class="display-6 fw-bold"><?= Helper::escape(date('F Y', strtotime($month . '-01'))) ?></div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Employees</div><div class="display-6 fw-bold"><?= count($rows) ?></div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Projected Net</div><div class="display-6 fw-bold text-primary"><?= Helper::formatCurrency($totalPayroll) ?></div></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Run Status</div><div class="display-6 fw-bold <?= ($run['status'] ?? '') === 'paid' ? 'text-success' : (($run['status'] ?? '') === 'approved' ? 'text-info' : (($run['status'] ?? '') === 'processed' ? 'text-warning' : 'text-secondary')) ?>"><?= Helper::escape($run ? ucfirst((string)$run['status']) : 'Draft') ?></div></div></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm hr-table-card report-data-card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-end gap-3 flex-wrap">
                <form class="report-filter-form" method="GET">
                    <input type="hidden" name="page" value="hr">
                    <input type="hidden" name="action" value="payroll">
                    <div class="report-filter-field">
                        <label class="form-label small mb-0">Month</label>
                        <input type="month" name="month" class="form-control form-control-sm" value="<?= Helper::escape($month) ?>">
                    </div>
                    <button class="btn btn-sm btn-outline-primary report-filter-submit"><i class="fas fa-filter me-1"></i>Refresh</button>
                </form>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=process_payroll">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="month" value="<?= Helper::escape($month) ?>">
                    <button type="submit" class="btn btn-primary" <?= (($run['status'] ?? '') === 'approved' || ($run['status'] ?? '') === 'paid') ? 'disabled' : '' ?>><i class="fas fa-gears me-1"></i><?= $run ? 'Reprocess Payroll' : 'Process Payroll' ?></button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-data-table hr-table-compact">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th class="text-end">Attendance</th>
                                <th class="text-end">Leave Days</th>
                                <th class="text-end">Gross</th>
                                <th class="text-end">Deduction</th>
                                <th class="text-end">Statutory</th>
                                <th class="text-end">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No employees available for payroll.</td></tr>
                            <?php else: ?>
                            <?php foreach ($rows as $row): $employee = (array)($row['employee'] ?? []); ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= Helper::escape($employee['full_name'] ?? '-') ?></div>
                                    <div class="small text-muted"><?= Helper::escape($employee['employee_code'] ?? '-') ?><?= !empty($employee['designation']) ? ' • ' . Helper::escape($employee['designation']) : '' ?></div>
                                </td>
                                <td class="text-end"><?= Helper::formatQty($row['attendance_units'] ?? 0) ?></td>
                                <td class="text-end"><?= (int)($row['approved_leave_days'] ?? 0) ?></td>
                                <td class="text-end"><?= Helper::formatCurrency($row['monthly_salary'] ?? 0) ?></td>
                                <td class="text-end text-danger"><?= Helper::formatCurrency($row['deduction_amount'] ?? 0) ?></td>
                                <td class="text-end text-danger">
                                    <?= Helper::formatCurrency($row['statutory_deduction_amount'] ?? 0) ?>
                                    <?php if ((float)($row['statutory_deduction_amount'] ?? 0) > 0): ?>
                                    <div class="small text-muted">
                                        PF <?= Helper::formatCurrency($row['pf_amount'] ?? 0) ?> •
                                        ESI <?= Helper::formatCurrency($row['esi_amount'] ?? 0) ?> •
                                        TDS <?= Helper::formatCurrency($row['tds_amount'] ?? 0) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-primary"><?= Helper::formatCurrency($row['net_salary'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="fw-bold">
                                <td colspan="6">Projected Total</td>
                                <td class="text-end"><?= Helper::formatCurrency($totalPayroll) ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($run): ?>
        <div class="card border-0 shadow-sm mt-4 hr-table-card report-data-card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Processed Run</h5>
                    <div class="text-muted small">Approval-controlled payout state, lock/freeze status, and payslip access.</div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <?php if (($run['status'] ?? '') === 'processed'): ?>
                    <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=approve_payroll" class="d-inline">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="id" value="<?= (int)($run['id'] ?? 0) ?>">
                        <input type="hidden" name="month" value="<?= Helper::escape($month) ?>">
                        <button type="submit" class="btn btn-sm btn-info text-white"><i class="fas fa-lock me-1"></i>Approve & Lock</button>
                    </form>
                    <?php elseif (($run['status'] ?? '') === 'approved'): ?>
                    <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=unlock_payroll" class="d-inline">
                        <?= CSRF::field() ?>
                        <input type="hidden" name="id" value="<?= (int)($run['id'] ?? 0) ?>">
                        <input type="hidden" name="month" value="<?= Helper::escape($month) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-lock-open me-1"></i>Unlock</button>
                    </form>
                    <?php endif; ?>
                    <span class="badge <?= ($run['status'] ?? '') === 'paid' ? 'bg-success' : (($run['status'] ?? '') === 'approved' ? 'bg-info text-dark' : 'bg-warning text-dark') ?>">
                    <?= Helper::escape(ucfirst((string)($run['status'] ?? 'processed'))) ?>
                    </span>
                </div>
            </div>
            <div class="card-body border-top bg-light-subtle">
                <div class="row g-3 small">
                    <div class="col-md-4"><span class="text-muted">Processed By:</span> <span class="fw-semibold"><?= Helper::escape($run['processed_by_name'] ?? '-') ?></span></div>
                    <div class="col-md-4"><span class="text-muted">Approved By:</span> <span class="fw-semibold"><?= Helper::escape($run['approved_by_name'] ?? '-') ?></span></div>
                    <div class="col-md-4"><span class="text-muted">Locked At:</span> <span class="fw-semibold"><?= !empty($run['locked_at']) ? Helper::formatDate($run['locked_at'], 'd M Y h:i A') : '-' ?></span></div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-data-table hr-table-compact">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th class="text-end">Gross</th>
                                <th class="text-end">Deduction</th>
                                <th class="text-end">Statutory</th>
                                <th class="text-end">Net</th>
                                <th>Finance</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($run['items'] ?? []) as $item): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= Helper::escape($item['full_name'] ?? '-') ?></div>
                                    <div class="small text-muted"><?= Helper::escape($item['employee_code'] ?? '-') ?><?= !empty($item['department']) ? ' • ' . Helper::escape($item['department']) : '' ?></div>
                                </td>
                                <td class="text-end">
                                    <?= Helper::formatCurrency($item['gross_salary'] ?? 0) ?>
                                    <?php if ((float)($item['allowance_amount'] ?? 0) > 0 || (float)($item['bonus_amount'] ?? 0) > 0): ?>
                                    <div class="small text-muted">
                                        + <?= Helper::formatCurrency(((float)($item['allowance_amount'] ?? 0) + (float)($item['bonus_amount'] ?? 0))) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-danger">
                                    <?= Helper::formatCurrency($item['deduction_amount'] ?? 0) ?>
                                    <?php if ((float)($item['other_deduction_amount'] ?? 0) > 0): ?>
                                    <div class="small text-muted">
                                        + <?= Helper::formatCurrency($item['other_deduction_amount'] ?? 0) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-danger">
                                    <?= Helper::formatCurrency($item['statutory_deduction_amount'] ?? 0) ?>
                                    <?php if ((float)($item['statutory_deduction_amount'] ?? 0) > 0): ?>
                                    <div class="small text-muted">
                                        PF <?= Helper::formatCurrency($item['pf_amount'] ?? 0) ?> •
                                        ESI <?= Helper::formatCurrency($item['esi_amount'] ?? 0) ?> •
                                        TDS <?= Helper::formatCurrency($item['tds_amount'] ?? 0) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-primary"><?= Helper::formatCurrency($item['net_salary'] ?? 0) ?></td>
                                <td>
                                    <?php if (!empty($item['payment_number'])): ?>
                                    <div class="fw-semibold">
                                        <a href="<?= APP_URL ?>/index.php?page=payments&action=view_payment&id=<?= (int)($item['payroll_payment_id'] ?? 0) ?>" class="text-decoration-none">
                                            <?= Helper::escape($item['payment_number']) ?>
                                        </a>
                                    </div>
                                    <div class="small text-muted">
                                        <?= Helper::escape(Helper::paymentMethodLabel($item['payment_method'] ?? 'cash')) ?>
                                        <?php if (!empty($item['payment_date'])): ?>
                                            • <?= Helper::formatDate($item['payment_date']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">Not posted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= ($item['payment_status'] ?? '') === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= Helper::escape(ucfirst((string)($item['payment_status'] ?? 'pending'))) ?></span>
                                    <?php if (($run['status'] ?? '') === 'approved' && ($item['payment_status'] ?? '') !== 'paid'): ?>
                                    <div class="small text-muted mt-1">Approved for payout</div>
                                    <?php elseif (($run['status'] ?? '') === 'processed'): ?>
                                    <div class="small text-muted mt-1">Awaiting approval lock</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= APP_URL ?>/index.php?page=hr&action=payslip&id=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                        <a href="<?= APP_URL ?>/index.php?page=hr&action=payslip&id=<?= (int)$item['id'] ?>&download=1" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-pdf"></i></a>
                                        <?php if (($item['payment_status'] ?? '') !== 'paid' && ($run['status'] ?? '') !== 'approved' && ($run['status'] ?? '') !== 'paid'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#payrollAdjustModal<?= (int)$item['id'] ?>"><i class="fas fa-sliders"></i></button>
                                        <?php endif; ?>
                                        <?php if (($item['payment_status'] ?? '') !== 'paid' && ($run['status'] ?? '') === 'approved'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#payrollPayModal<?= (int)$item['id'] ?>"><i class="fas fa-check"></i></button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (($item['payment_status'] ?? '') !== 'paid' && ($run['status'] ?? '') !== 'approved' && ($run['status'] ?? '') !== 'paid'): ?>
                                    <div class="modal fade text-start" id="payrollAdjustModal<?= (int)$item['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=update_payroll_item">
                                                    <?= CSRF::field() ?>
                                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                                    <input type="hidden" name="month" value="<?= Helper::escape($month) ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Adjust Payroll: <?= Helper::escape($item['full_name'] ?? '-') ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Allowance</label>
                                                                <input type="number" name="allowance_amount" class="form-control" step="0.01" min="0" value="<?= Helper::escape($item['allowance_amount'] ?? 0) ?>">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Bonus</label>
                                                                <input type="number" name="bonus_amount" class="form-control" step="0.01" min="0" value="<?= Helper::escape($item['bonus_amount'] ?? 0) ?>">
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label class="form-label">Additional Deduction</label>
                                                                <input type="number" name="other_deduction_amount" class="form-control" step="0.01" min="0" value="<?= Helper::escape($item['other_deduction_amount'] ?? 0) ?>">
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label class="form-label">Adjustment Notes</label>
                                                                <textarea name="adjustment_notes" class="form-control" rows="3"><?= Helper::escape($item['adjustment_notes'] ?? '') ?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-primary">Save Adjustments</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (($item['payment_status'] ?? '') !== 'paid' && ($run['status'] ?? '') === 'approved'): ?>
                                    <div class="modal fade text-start" id="payrollPayModal<?= (int)$item['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=mark_payroll_paid">
                                                    <?= CSRF::field() ?>
                                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                                    <input type="hidden" name="month" value="<?= Helper::escape($month) ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Post Payroll Payout: <?= Helper::escape($item['full_name'] ?? '-') ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-light border mb-3">
                                                            <div class="small text-muted">Net Salary</div>
                                                            <div class="fw-bold"><?= Helper::formatCurrency($item['net_salary'] ?? 0) ?></div>
                                                        </div>
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Payment Date</label>
                                                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Method</label>
                                                                <select name="payment_method" class="form-select">
                                                                    <option value="cash">Cash</option>
                                                                    <option value="bank">Bank</option>
                                                                    <option value="cheque">Cheque</option>
                                                                    <option value="online">Online</option>
                                                                    <option value="other">Other</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Reference Number</label>
                                                                <input type="text" name="reference_number" class="form-control" maxlength="100">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Bank / Channel</label>
                                                                <input type="text" name="bank_name" class="form-control" maxlength="255">
                                                            </div>
                                                            <div class="col-md-12">
                                                                <label class="form-label">Finance Note</label>
                                                                <textarea name="payment_note" class="form-control" rows="3" placeholder="Optional payout narration for finance register."></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-success">Post Payout</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4 hr-soft-panel">
            <div class="card-header bg-transparent">
                <h5 class="mb-1">Statutory Policy</h5>
                <div class="text-muted small">Configure PF, ESI, and TDS deductions used during payroll processing.</div>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=save_payroll_policy">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="month" value="<?= Helper::escape($month) ?>">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="enable_pf" value="1" id="enable_pf" <?= !empty($policy['enable_pf']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_pf">Enable PF</label>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">PF Rate %</label>
                            <input type="number" step="0.01" min="0" name="pf_rate" class="form-control form-control-sm" value="<?= Helper::escape($policy['pf_rate'] ?? 12) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">PF Cap</label>
                            <input type="number" step="0.01" min="0" name="pf_salary_cap" class="form-control form-control-sm" value="<?= Helper::escape($policy['pf_salary_cap'] ?? 15000) ?>">
                        </div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="enable_esi" value="1" id="enable_esi" <?= !empty($policy['enable_esi']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_esi">Enable ESI</label>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">ESI Rate %</label>
                            <input type="number" step="0.01" min="0" name="esi_rate" class="form-control form-control-sm" value="<?= Helper::escape($policy['esi_rate'] ?? 0.75) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">ESI Threshold</label>
                            <input type="number" step="0.01" min="0" name="esi_salary_threshold" class="form-control form-control-sm" value="<?= Helper::escape($policy['esi_salary_threshold'] ?? 21000) ?>">
                        </div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="enable_tds" value="1" id="enable_tds" <?= !empty($policy['enable_tds']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_tds">Enable TDS</label>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">TDS Rate %</label>
                            <input type="number" step="0.01" min="0" name="tds_rate" class="form-control form-control-sm" value="<?= Helper::escape($policy['tds_rate'] ?? 10) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Annual Threshold</label>
                            <input type="number" step="0.01" min="0" name="tds_annual_threshold" class="form-control form-control-sm" value="<?= Helper::escape($policy['tds_annual_threshold'] ?? 700000) ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary w-100">Save Policy</button>
                </form>
            </div>
        </div>
        <div class="card border-0 shadow-sm hr-soft-panel">
            <div class="card-header bg-transparent">
                <h5 class="mb-1">Recent Payroll Runs</h5>
                <div class="text-muted small">Most recent processed months and payout status.</div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentRuns)): ?>
                <div class="p-4 text-center text-muted">No payroll runs processed yet.</div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentRuns as $recent): ?>
                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-start" href="<?= APP_URL ?>/index.php?page=hr&action=payroll&month=<?= urlencode((string)$recent['payroll_month']) ?>">
                        <div>
                            <div class="fw-semibold"><?= Helper::escape(date('F Y', strtotime((string)$recent['payroll_month'] . '-01'))) ?></div>
                            <div class="small text-muted"><?= (int)($recent['employee_count'] ?? 0) ?> employees • <?= Helper::formatCurrency($recent['net_amount'] ?? 0) ?></div>
                        </div>
                        <span class="badge <?= ($recent['status'] ?? '') === 'paid' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= Helper::escape(ucfirst((string)($recent['status'] ?? 'processed'))) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
