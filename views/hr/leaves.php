<?php
$employees = is_array($employees ?? null) ? $employees : [];
$requests = is_array($requests ?? null) ? $requests : [];
$summary = is_array($summary ?? null) ? $summary : [];
$status = (string)($status ?? '');
$employeeId = (int)($employeeId ?? 0);
?>
<div class="hr-page-shell report-page-shell">
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=hr">HR</a></li>
                <li class="breadcrumb-item active">Leaves</li>
            </ol>
        </nav>
        <h2 class="h4 mb-1"><i class="fas fa-plane-departure me-2 text-primary"></i>Leave Management</h2>
        <p class="text-muted mb-0">Create, review, approve, and reject leave requests with a tenant-scoped approval flow.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=hr" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>HR Home</a>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100 hr-soft-panel">
            <div class="card-header bg-transparent"><h5 class="mb-0">Create Leave Request</h5></div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=create_leave">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select employee</option>
                            <?php foreach ($employees as $employee): ?>
                            <option value="<?= (int)$employee['id'] ?>" <?= $employeeId === (int)$employee['id'] ? 'selected' : '' ?>>
                                <?= Helper::escape($employee['full_name']) ?> (<?= Helper::escape($employee['employee_code']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Leave Type</label>
                        <select name="leave_type" class="form-select">
                            <option value="casual">Casual</option>
                            <option value="sick">Sick</option>
                            <option value="earned">Earned</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Why is this leave needed?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-paper-plane me-1"></i>Submit Leave Request</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="row g-3">
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Pending</div><div class="display-6 fw-bold text-warning"><?= (int)($summary['pending_requests'] ?? 0) ?></div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Approved</div><div class="display-6 fw-bold text-success"><?= (int)($summary['approved_requests'] ?? 0) ?></div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Rejected</div><div class="display-6 fw-bold text-danger"><?= (int)($summary['rejected_requests'] ?? 0) ?></div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Manager Review Pending</div><div class="display-6 fw-bold text-info"><?= (int)($summary['pending_manager_requests'] ?? 0) ?></div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm mt-3 hr-table-card report-data-card">
            <div class="card-header bg-transparent">
                <form class="report-filter-form" method="GET">
                    <input type="hidden" name="page" value="hr">
                    <input type="hidden" name="action" value="leaves">
                    <div class="report-filter-field report-filter-field-wide">
                        <label class="form-label small mb-0">Employee</label>
                        <select name="employee_id" class="form-select form-select-sm">
                            <option value="">All employees</option>
                            <?php foreach ($employees as $employee): ?>
                            <option value="<?= (int)$employee['id'] ?>" <?= $employeeId === (int)$employee['id'] ? 'selected' : '' ?>>
                                <?= Helper::escape($employee['full_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="report-filter-field">
                        <label class="form-label small mb-0">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <button class="btn btn-sm btn-outline-primary report-filter-submit"><i class="fas fa-filter me-1"></i>Filter</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-data-table hr-table-compact">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Days</th>
                                <th>Manager Stage</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No leave requests found for this selection.</td></tr>
                            <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                            <?php
                            $managerStatus = (string)($request['manager_status'] ?? 'not_required');
                            $finalStatus = (string)($request['status'] ?? 'pending');
                            $managerClass = $managerStatus === 'approved'
                                ? 'bg-success'
                                : ($managerStatus === 'rejected' ? 'bg-danger' : ($managerStatus === 'pending' ? 'bg-info text-dark' : 'bg-light text-dark'));
                            $statusClass = $finalStatus === 'approved'
                                ? 'bg-success'
                                : ($finalStatus === 'rejected' ? 'bg-danger' : ($finalStatus === 'cancelled' ? 'bg-secondary' : 'bg-warning text-dark'));
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= Helper::escape($request['full_name']) ?></div>
                                    <div class="small text-muted"><?= Helper::escape($request['employee_code']) ?> • <?= Helper::escape($request['designation'] ?? '-') ?></div>
                                </td>
                                <td><?= Helper::escape(ucfirst($request['leave_type'] ?? '-')) ?></td>
                                <td><?= Helper::formatDate($request['start_date'], 'd M Y') ?><br><span class="text-muted small">to <?= Helper::formatDate($request['end_date'], 'd M Y') ?></span></td>
                                <td><?= (int)($request['days_count'] ?? 0) ?></td>
                                <td>
                                    <span class="badge <?= $managerClass ?>"><?= Helper::escape(ucwords(str_replace('_', ' ', $managerStatus))) ?></span>
                                    <div class="small text-muted mt-1">
                                        <?= !empty($request['approver_user_name']) ? 'Approver: ' . Helper::escape($request['approver_user_name']) : 'No manager approval required' ?>
                                    </div>
                                    <?php if (!empty($request['manager_approved_by_name'])): ?>
                                    <div class="small text-muted"><?= Helper::escape($request['manager_approved_by_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $statusClass ?>"><?= Helper::escape(ucfirst($finalStatus)) ?></span></td>
                                <td class="text-wrap text-wrap-220"><?= Helper::escape($request['reason'] ?? '-') ?></td>
                                <td class="text-end">
                                    <?php if ($finalStatus === 'pending' && $managerStatus === 'pending'): ?>
                                    <div class="d-inline-flex gap-1">
                                        <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=manager_approve_leave" class="d-inline">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-info" title="Manager approve"><i class="fas fa-user-check"></i></button>
                                        </form>
                                        <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=manager_reject_leave" class="d-inline">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                                            <input type="hidden" name="rejection_reason" value="Rejected by manager">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Manager reject"><i class="fas fa-user-times"></i></button>
                                        </form>
                                    </div>
                                    <?php elseif ($finalStatus === 'pending'): ?>
                                    <div class="d-inline-flex gap-1">
                                        <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=approve_leave" class="d-inline">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button>
                                        </form>
                                        <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=reject_leave" class="d-inline">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$request['id'] ?>">
                                            <input type="hidden" name="rejection_reason" value="Rejected by HR">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                                        </form>
                                    </div>
                                    <?php else: ?>
                                    <span class="small text-muted"><?= !empty($request['approved_by_name']) ? Helper::escape($request['approved_by_name']) : 'Processed' ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
