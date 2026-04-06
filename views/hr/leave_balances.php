<?php
$employees = is_array($employees ?? null) ? $employees : [];
$balanceMap = is_array($balanceMap ?? null) ? $balanceMap : [];
$policyMap = is_array($policyMap ?? null) ? $policyMap : [];
$accrualMonth = (string)($accrualMonth ?? date('Y-m'));
$leaveTypes = ['casual', 'earned', 'sick', 'unpaid', 'other'];
?>
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=hr">HR</a></li>
                <li class="breadcrumb-item active">Leave Balances</li>
            </ol>
        </nav>
        <h2 class="h4 mb-1"><i class="fas fa-layer-group me-2 text-primary"></i>Leave Balances</h2>
        <p class="text-muted mb-0">Maintain opening, accrued, used, and available balances per employee and leave type.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=hr" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>HR Home</a>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-1">Accrual Policies</h5>
                <div class="text-muted small">Define monthly leave credits, carry-forward caps, and policy effective dates.</div>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=save_leave_policy">
                    <?= CSRF::field() ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Monthly Accrual</th>
                                    <th>Carry Forward Cap</th>
                                    <th>Effective From</th>
                                    <th>Active</th>
                                    <th>Last Run</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaveTypes as $leaveType): $policy = $policyMap[$leaveType] ?? []; ?>
                                <tr>
                                    <td class="fw-semibold"><?= Helper::escape(ucfirst($leaveType)) ?></td>
                                    <td><input type="number" name="monthly_accrual_days[<?= Helper::escape($leaveType) ?>]" class="form-control form-control-sm" step="0.01" min="0" value="<?= Helper::escape($policy['monthly_accrual_days'] ?? 0) ?>"></td>
                                    <td><input type="number" name="max_carry_forward[<?= Helper::escape($leaveType) ?>]" class="form-control form-control-sm" step="0.01" min="0" value="<?= Helper::escape($policy['max_carry_forward'] ?? '') ?>"></td>
                                    <td><input type="date" name="effective_from[<?= Helper::escape($leaveType) ?>]" class="form-control form-control-sm" value="<?= Helper::escape($policy['effective_from'] ?? date('Y-m-01')) ?>"></td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_active[]" value="<?= Helper::escape($leaveType) ?>" <?= !empty($policy['is_active']) ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td class="small text-muted"><?= !empty($policy['last_processed_month']) ? Helper::escape($policy['last_processed_month']) : 'Not run yet' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Policies</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-1">Monthly Accrual Run</h5>
                <div class="text-muted small">Apply active leave policies to all eligible employees for a selected month.</div>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=process_leave_accruals">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Accrual Month</label>
                        <input type="month" name="month" class="form-control" value="<?= Helper::escape($accrualMonth) ?>" required>
                    </div>
                    <div class="small text-muted mb-3">
                        Already-processed policy months are skipped, so monthly accrual runs stay idempotent.
                    </div>
                    <button type="submit" class="btn btn-dark w-100"><i class="fas fa-play me-1"></i>Process Leave Accrual</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th class="text-end">Opening</th>
                        <th class="text-end">Accrued</th>
                        <th class="text-end">Used</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No employees found.</td></tr>
                    <?php else: ?>
                    <?php foreach ($employees as $employee): ?>
                    <?php foreach ($leaveTypes as $leaveType): $balance = $balanceMap[(int)$employee['id']][$leaveType] ?? null; ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= Helper::escape($employee['full_name'] ?? '-') ?></div>
                            <div class="small text-muted"><?= Helper::escape($employee['employee_code'] ?? '-') ?></div>
                        </td>
                        <td><?= Helper::escape(ucfirst($leaveType)) ?></td>
                        <td class="text-end"><?= Helper::formatQty($balance['opening_days'] ?? 0) ?></td>
                        <td class="text-end"><?= Helper::formatQty($balance['accrued_days'] ?? 0) ?></td>
                        <td class="text-end"><?= Helper::formatQty($balance['used_days'] ?? 0) ?></td>
                        <td class="text-end fw-bold text-primary"><?= Helper::formatQty($balance['available_days'] ?? 0) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#leaveBalanceModal<?= (int)$employee['id'] ?>_<?= Helper::escape($leaveType) ?>"><i class="fas fa-pen"></i></button>
                            <div class="modal fade text-start" id="leaveBalanceModal<?= (int)$employee['id'] ?>_<?= Helper::escape($leaveType) ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=update_leave_balance">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="employee_id" value="<?= (int)$employee['id'] ?>">
                                            <input type="hidden" name="leave_type" value="<?= Helper::escape($leaveType) ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update <?= Helper::escape(ucfirst($leaveType)) ?> Balance</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">Opening</label>
                                                        <input type="number" name="opening_days" class="form-control" step="0.01" min="0" value="<?= Helper::escape($balance['opening_days'] ?? 0) ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Accrued</label>
                                                        <input type="number" name="accrued_days" class="form-control" step="0.01" min="0" value="<?= Helper::escape($balance['accrued_days'] ?? 0) ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Used</label>
                                                        <input type="number" name="used_days" class="form-control" step="0.01" min="0" value="<?= Helper::escape($balance['used_days'] ?? 0) ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
