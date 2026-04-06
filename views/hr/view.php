<?php
$employee = is_array($employee ?? null) ? $employee : [];
$statusValue = (string)($employee['status'] ?? 'inactive');
$statusClass = $statusValue === 'active' ? 'bg-success' : ($statusValue === 'on_leave' ? 'bg-warning text-dark' : 'bg-secondary');
?>
<style>
    .hr-profile-card,
    .hr-detail-card {
        border: 0;
        border-radius: 1.25rem;
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .hr-profile-hero {
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.2), transparent 34%),
            linear-gradient(135deg, #0f172a 0%, #1e293b 48%, #1d4ed8 100%);
        color: #e5eefc;
    }
    .hr-avatar {
        width: 72px;
        height: 72px;
        border-radius: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.12);
        font-size: 1.6rem;
    }
    [data-theme="dark"] .hr-profile-card,
    [data-theme="dark"] .hr-detail-card {
        background: var(--bg-card);
        border-color: var(--border-color);
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.24);
    }
    [data-theme="dark"] .hr-profile-card .table,
    [data-theme="dark"] .hr-detail-card .table,
    [data-theme="dark"] .hr-profile-card .text-muted,
    [data-theme="dark"] .hr-detail-card .text-muted {
        color: var(--text-secondary) !important;
    }
    [data-theme="dark"] .hr-profile-card .border-top,
    [data-theme="dark"] .hr-detail-card .border,
    [data-theme="dark"] .hr-detail-card .rounded-4 {
        border-color: var(--border-color) !important;
        background: var(--surface-soft);
    }
</style>

<div class="hr-page-shell detail-page-shell">
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=hr">HR</a></li>
            <li class="breadcrumb-item active"><?= Helper::escape($employee['full_name'] ?? 'Employee') ?></li>
        </ol>
    </nav>
    <div class="hr-page-actions detail-page-actions">
        <a href="<?= APP_URL ?>/index.php?page=hr&action=attendance&employee_id=<?= (int)($employee['id'] ?? 0) ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-calendar-check me-1"></i>Attendance</a>
        <a href="<?= APP_URL ?>/index.php?page=hr&action=leaves&employee_id=<?= (int)($employee['id'] ?? 0) ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-plane-departure me-1"></i>Leaves</a>
        <a href="<?= APP_URL ?>/index.php?page=hr&action=leave_balances" class="btn btn-outline-info btn-sm"><i class="fas fa-layer-group me-1"></i>Leave Balance</a>
        <a href="<?= APP_URL ?>/index.php?page=hr&action=payroll" class="btn btn-outline-secondary btn-sm"><i class="fas fa-wallet me-1"></i>Payroll</a>
        <a href="<?= APP_URL ?>/index.php?page=hr&action=edit&id=<?= (int)($employee['id'] ?? 0) ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit me-1"></i>Edit</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card hr-profile-card">
            <div class="card-body hr-profile-hero p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="hr-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <div class="small text-uppercase fw-semibold mb-2 hr-hero-eyebrow">Employee Profile</div>
                        <h4 class="mb-1"><?= Helper::escape($employee['full_name'] ?? '-') ?></h4>
                        <p class="mb-3 hr-hero-copy"><?= Helper::escape($employee['designation'] ?? '-') ?></p>
                        <span class="badge <?= $statusClass ?>">
                            <?= Helper::escape(ucwords(str_replace('_', ' ', $statusValue))) ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body border-top">
                <table class="table table-sm mb-0 detail-table">
                    <tr><td class="text-muted">Code</td><td><?= Helper::escape($employee['employee_code'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Department</td><td><?= Helper::escape($employee['department'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Email</td><td><?= Helper::escape($employee['email'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Phone</td><td><?= Helper::escape($employee['phone'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Joined</td><td><?= !empty($employee['joined_on']) ? Helper::formatDate($employee['joined_on'], 'd-m-Y') : '-' ?></td></tr>
                    <tr><td class="text-muted">Salary</td><td><?= isset($employee['salary']) && $employee['salary'] !== null ? Helper::formatCurrency($employee['salary']) : '-' ?></td></tr>
                    <tr><td class="text-muted">Reporting Manager</td><td><?= Helper::escape($employee['reporting_manager_name'] ?? '-') ?></td></tr>
                    <tr>
                        <td class="text-muted">Shift</td>
                        <td>
                            <?php if (!empty($employee['shift_name'])): ?>
                                <?= Helper::escape($employee['shift_name']) ?>
                                <?php if (!empty($employee['shift_start_time']) && !empty($employee['shift_end_time'])): ?>
                                    <div class="small text-muted"><?= Helper::escape(substr((string)$employee['shift_start_time'], 0, 5)) ?> - <?= Helper::escape(substr((string)$employee['shift_end_time'], 0, 5)) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($employee['weekly_off_day'])): ?>
                                    <div class="small text-muted">Weekly off: <?= Helper::escape($employee['weekly_off_day']) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card hr-detail-card h-100">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="text-muted small mb-1">Work Email</div>
                            <div class="fw-semibold"><?= Helper::escape($employee['email'] ?? '-') ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="text-muted small mb-1">Phone</div>
                            <div class="fw-semibold"><?= Helper::escape($employee['phone'] ?? '-') ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="text-muted small mb-1">Joining Date</div>
                            <div class="fw-semibold"><?= !empty($employee['joined_on']) ? Helper::formatDate($employee['joined_on'], 'd M Y') : '-' ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="text-muted small mb-1">Salary</div>
                            <div class="fw-semibold"><?= isset($employee['salary']) && $employee['salary'] !== null ? Helper::formatCurrency($employee['salary']) : '-' ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="text-muted small mb-1">Assigned Shift</div>
                            <div class="fw-semibold"><?= Helper::escape($employee['shift_name'] ?? '-') ?></div>
                            <?php if (!empty($employee['shift_start_time']) && !empty($employee['shift_end_time'])): ?>
                            <div class="small text-muted"><?= Helper::escape(substr((string)$employee['shift_start_time'], 0, 5)) ?> - <?= Helper::escape(substr((string)$employee['shift_end_time'], 0, 5)) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="text-muted small mb-1">Reporting Manager</div>
                            <div class="fw-semibold"><?= Helper::escape($employee['reporting_manager_name'] ?? '-') ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <div class="text-muted small mb-1">Weekly Off</div>
                            <div class="fw-semibold"><?= Helper::escape($employee['weekly_off_day'] ?? '-') ?></div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($employee['notes'])): ?>
                <div class="border rounded-4 p-3 mt-3">
                    <div class="text-muted small mb-1">Notes</div>
                    <div><?= nl2br(Helper::escape($employee['notes'])) ?></div>
                </div>
                <?php endif; ?>
                <div class="border rounded-4 p-3 mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small">Leave Balances</div>
                        <a href="<?= APP_URL ?>/index.php?page=hr&action=leave_balances" class="small text-decoration-none">Manage</a>
                    </div>
                    <?php if (!empty($leaveBalances)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 detail-table">
                            <tbody>
                                <?php foreach ($leaveBalances as $balance): ?>
                                <tr>
                                    <td><?= Helper::escape(ucfirst($balance['leave_type'] ?? '-')) ?></td>
                                    <td class="text-end"><?= Helper::formatQty($balance['available_days'] ?? 0) ?> days</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-muted">No leave balances configured.</div>
                    <?php endif; ?>
                </div>
                <div class="border rounded-4 p-3 mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small">Recent Attendance</div>
                        <a href="<?= APP_URL ?>/index.php?page=hr&action=attendance&employee_id=<?= (int)($employee['id'] ?? 0) ?>" class="small text-decoration-none">View All</a>
                    </div>
                    <?php if (!empty($attendanceEntries)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 detail-table">
                            <tbody>
                                <?php foreach ($attendanceEntries as $entry): ?>
                                <tr>
                                    <td><?= Helper::formatDate($entry['attendance_date'], 'd M Y') ?></td>
                                    <td><?= Helper::escape(ucwords(str_replace('_', ' ', $entry['status'] ?? '-'))) ?></td>
                                    <td class="text-muted"><?= !empty($entry['check_in_time']) ? substr((string)$entry['check_in_time'], 0, 5) : '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-muted">No attendance logged yet.</div>
                    <?php endif; ?>
                </div>
                <div class="border rounded-4 p-3 mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="text-muted small">Leave Requests</div>
                        <a href="<?= APP_URL ?>/index.php?page=hr&action=leaves&employee_id=<?= (int)($employee['id'] ?? 0) ?>" class="small text-decoration-none">View All</a>
                    </div>
                    <?php if (!empty($leaveRequests)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 detail-table">
                            <tbody>
                                <?php foreach ($leaveRequests as $request): ?>
                                <tr>
                                    <td><?= Helper::escape(ucfirst($request['leave_type'] ?? '-')) ?></td>
                                    <td><?= Helper::formatDate($request['start_date'], 'd M') ?> - <?= Helper::formatDate($request['end_date'], 'd M') ?></td>
                                    <td><?= Helper::escape(ucfirst($request['status'] ?? '-')) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-muted">No leave requests yet.</div>
                    <?php endif; ?>
                </div>
                <?php if (empty($employee['notes'])): ?>
                <div class="border rounded-4 p-4 mt-3 text-center text-muted">
                    <i class="fas fa-note-sticky me-1"></i>No internal notes added for this employee.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>
