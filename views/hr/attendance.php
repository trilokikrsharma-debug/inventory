<?php
$employees = is_array($employees ?? null) ? $employees : [];
$entries = is_array($entries ?? null) ? $entries : [];
$summary = is_array($summary ?? null) ? $summary : [];
$month = (string)($month ?? date('Y-m'));
$status = (string)($status ?? '');
$employeeId = (int)($employeeId ?? 0);
$selectedDate = (string)($selectedDate ?? date('Y-m-d'));
$selectedContext = is_array($selectedContext ?? null) ? $selectedContext : [];
$hasAttendancePolicyHelper = class_exists('HrAttendancePolicyService');
?>
<div class="hr-page-shell report-page-shell">
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=hr">HR</a></li>
                <li class="breadcrumb-item active">Attendance</li>
            </ol>
        </nav>
        <h2 class="h4 mb-1"><i class="fas fa-calendar-check me-2 text-primary"></i>Attendance Management</h2>
        <p class="text-muted mb-0">Mark daily attendance and review monthly workforce availability.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=hr" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>HR Home</a>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100 hr-soft-panel">
            <div class="card-header bg-transparent"><h5 class="mb-0">Mark Attendance</h5></div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=mark_attendance">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="attendance_month" value="<?= Helper::escape($month) ?>">
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
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="attendance_date" class="form-control" value="<?= Helper::escape($selectedDate) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="half_day">Half Day</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check In</label>
                            <input type="time" name="check_in_time" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check Out</label>
                            <input type="time" name="check_out_time" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3">
                        <?php if (!empty($selectedContext)): ?>
                        <div class="alert alert-light border mb-3">
                            <div class="fw-semibold mb-1">Attendance Context</div>
                            <?php if (!empty($selectedContext['holiday_label'])): ?>
                            <div class="small text-muted"><?= Helper::escape($selectedContext['holiday_label']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($selectedContext['weekly_off_label'])): ?>
                            <div class="small text-muted"><?= Helper::escape($selectedContext['weekly_off_label']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($selectedContext['shift_name'])): ?>
                            <div class="small text-muted">
                                Shift: <?= Helper::escape($selectedContext['shift_name']) ?>
                                <?php if (!empty($selectedContext['shift_start_time']) && !empty($selectedContext['shift_end_time'])): ?>
                                    (<?= Helper::escape(substr((string)$selectedContext['shift_start_time'], 0, 5)) ?> - <?= Helper::escape(substr((string)$selectedContext['shift_end_time'], 0, 5)) ?>)
                                <?php endif; ?>
                            </div>
                            <?php if (isset($selectedContext['grace_period_minutes']) && !empty($selectedContext['shift_start_time'])): ?>
                            <div class="small text-muted">
                                Late cutoff: <?= Helper::escape($hasAttendancePolicyHelper ? (HrAttendancePolicyService::cutoffTime((string)$selectedContext['shift_start_time'], (int)$selectedContext['grace_period_minutes']) ?? '-') : '-') ?>
                                (<?= (int)$selectedContext['grace_period_minutes'] ?> min grace)
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <label class="form-label">Note</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Shift remark, field visit, exception note, etc. Auto late-marking applies when check-in crosses the shift cutoff."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-save me-1"></i>Save Attendance</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="row g-3">
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Present</div><div class="display-6 fw-bold text-success"><?= (int)($summary['present_days'] ?? 0) ?></div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Late</div><div class="display-6 fw-bold text-warning"><?= (int)($summary['late_days'] ?? 0) ?></div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Half Day</div><div class="display-6 fw-bold text-info"><?= (int)($summary['half_days'] ?? 0) ?></div></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100 hr-stat-card"><div class="card-body"><div class="text-muted small">Absent</div><div class="display-6 fw-bold text-danger"><?= (int)($summary['absent_days'] ?? 0) ?></div></div></div></div>
        </div>

        <div class="card border-0 shadow-sm mt-3 hr-table-card report-data-card">
            <div class="card-header bg-transparent">
                <form class="report-filter-form" method="GET">
                    <input type="hidden" name="page" value="hr">
                    <input type="hidden" name="action" value="attendance">
                    <div class="report-filter-field">
                        <label class="form-label small mb-0">Month</label>
                        <input type="month" name="month" class="form-control form-control-sm" value="<?= Helper::escape($month) ?>">
                    </div>
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
                        <label class="form-label small mb-0">Date</label>
                        <input type="date" name="attendance_date" class="form-control form-control-sm" value="<?= Helper::escape($selectedDate) ?>">
                    </div>
                    <div class="report-filter-field">
                        <label class="form-label small mb-0">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="present" <?= $status === 'present' ? 'selected' : '' ?>>Present</option>
                            <option value="late" <?= $status === 'late' ? 'selected' : '' ?>>Late</option>
                            <option value="half_day" <?= $status === 'half_day' ? 'selected' : '' ?>>Half Day</option>
                            <option value="absent" <?= $status === 'absent' ? 'selected' : '' ?>>Absent</option>
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
                                <th>Date</th>
                                <th>Status</th>
                                <th>Shift</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No attendance entries found for this selection.</td></tr>
                            <?php else: ?>
                            <?php foreach ($entries as $entry): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= Helper::escape($entry['full_name']) ?></div>
                                    <div class="small text-muted"><?= Helper::escape($entry['employee_code']) ?> • <?= Helper::escape($entry['designation'] ?? '-') ?></div>
                                </td>
                                <td><?= Helper::formatDate($entry['attendance_date'], 'd M Y') ?></td>
                                <td><span class="badge <?= ($entry['status'] ?? '') === 'present' ? 'bg-success' : (($entry['status'] ?? '') === 'late' ? 'bg-warning text-dark' : (($entry['status'] ?? '') === 'half_day' ? 'bg-info text-dark' : 'bg-danger')) ?>"><?= Helper::escape(ucwords(str_replace('_', ' ', $entry['status'] ?? ''))) ?></span></td>
                                <td>
                                    <div><?= Helper::escape($entry['shift_name'] ?? '-') ?></div>
                                    <?php if (!empty($entry['shift_start_time']) && !empty($entry['shift_end_time'])): ?>
                                    <div class="small text-muted"><?= Helper::escape(substr((string)$entry['shift_start_time'], 0, 5)) ?> - <?= Helper::escape(substr((string)$entry['shift_end_time'], 0, 5)) ?></div>
                                    <?php endif; ?>
                                    <?php if (isset($entry['grace_period_minutes']) && !empty($entry['shift_start_time'])): ?>
                                    <div class="small text-muted">
                                        Cutoff <?= Helper::escape($hasAttendancePolicyHelper ? (HrAttendancePolicyService::cutoffTime((string)$entry['shift_start_time'], (int)$entry['grace_period_minutes']) ?? '-') : '-') ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($entry['holiday_name'])): ?>
                                    <div class="small text-danger">Holiday: <?= Helper::escape($entry['holiday_name']) ?></div>
                                    <?php elseif (!empty($entry['weekly_off_day']) && date('l', strtotime((string)$entry['attendance_date'])) === (string)$entry['weekly_off_day']): ?>
                                    <div class="small text-muted">Weekly off: <?= Helper::escape($entry['weekly_off_day']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($entry['check_in_time']) ? substr((string)$entry['check_in_time'], 0, 5) : '-' ?></td>
                                <td><?= !empty($entry['check_out_time']) ? substr((string)$entry['check_out_time'], 0, 5) : '-' ?></td>
                                <td><?= Helper::escape($entry['note'] ?? '-') ?></td>
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
