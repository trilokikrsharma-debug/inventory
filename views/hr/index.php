<?php
$employees = is_array($employees ?? null) ? $employees : ['data' => [], 'page' => 1, 'totalPages' => 1, 'total' => 0];
$stats = is_array($stats ?? null) ? $stats : ['total_employees' => 0, 'active_employees' => 0, 'on_leave_employees' => 0, 'inactive_employees' => 0];
$search = (string)($search ?? '');
$status = (string)($status ?? '');
$summaryCards = [
    ['label' => 'Total Employees', 'value' => (int)$stats['total_employees'], 'icon' => 'fa-users', 'tone' => 'primary', 'hint' => 'Current employee master count'],
    ['label' => 'Active', 'value' => (int)$stats['active_employees'], 'icon' => 'fa-user-check', 'tone' => 'success', 'hint' => 'Available and working'],
    ['label' => 'On Leave', 'value' => (int)$stats['on_leave_employees'], 'icon' => 'fa-umbrella-beach', 'tone' => 'warning', 'hint' => 'Temporarily unavailable'],
    ['label' => 'Inactive', 'value' => (int)$stats['inactive_employees'], 'icon' => 'fa-user-slash', 'tone' => 'secondary', 'hint' => 'Archived or inactive staff'],
];
$attendanceSummary = is_array($attendanceSummary ?? null) ? $attendanceSummary : [];
$leaveSummary = is_array($leaveSummary ?? null) ? $leaveSummary : [];
$upcomingHolidays = is_array($upcomingHolidays ?? null) ? $upcomingHolidays : [];
$shiftCount = (int)($shiftCount ?? 0);
$payrollSnapshot = is_array($payrollSnapshot ?? null) ? $payrollSnapshot : ['has_run' => false, 'status' => 'draft', 'pending_items' => 0, 'paid_items' => 0, 'employee_count' => 0, 'net_amount' => 0.0];
$month = (string)($month ?? date('Y-m'));
?>
<style>
    .hr-hero {
        position: relative;
        overflow: hidden;
        border: 0;
        border-radius: 1.25rem;
        background:
            radial-gradient(circle at top right, rgba(37, 99, 235, 0.22), transparent 28%),
            linear-gradient(135deg, #0f172a 0%, #16233a 48%, #1f3a5f 100%);
        color: #e5eefc;
    }
    .hr-hero::after {
        content: '';
        position: absolute;
        inset: auto -60px -60px auto;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, rgba(255,255,255,0.14), transparent 68%);
        pointer-events: none;
    }
    .hr-summary-card {
        border: 1px solid rgba(15, 23, 42, 0.07);
        border-radius: 1rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
    }
    .hr-summary-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .hr-directory-card {
        border: 0;
        border-radius: 1.25rem;
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .hr-table thead th {
        font-size: 0.78rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #64748b;
        border-bottom-width: 1px;
    }
    .hr-table tbody tr:hover {
        background: rgba(37, 99, 235, 0.035);
    }
    .hr-name {
        font-weight: 700;
        color: #0f172a;
    }
    .hr-subtle {
        color: #64748b;
        font-size: 0.82rem;
    }
    .hr-empty {
        padding: 4rem 1.25rem;
        text-align: center;
        color: #64748b;
    }
    [data-theme="dark"] .hr-summary-card,
    [data-theme="dark"] .hr-directory-card {
        background: var(--bg-card);
        border-color: var(--border-color);
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.24);
    }
    [data-theme="dark"] .hr-name {
        color: var(--text-primary);
    }
    [data-theme="dark"] .hr-subtle,
    [data-theme="dark"] .hr-table thead th,
    [data-theme="dark"] .hr-summary-card .text-muted,
    [data-theme="dark"] .hr-directory-card .text-muted {
        color: var(--text-muted) !important;
    }
    [data-theme="dark"] .hr-table tbody tr:hover {
        background: rgba(78, 115, 223, 0.12);
    }
    [data-theme="dark"] .hr-summary-card .border,
    [data-theme="dark"] .hr-directory-card .border,
    [data-theme="dark"] .hr-summary-card .rounded-4 {
        border-color: var(--border-color) !important;
        background: var(--surface-soft);
    }
    @media (max-width: 991.98px) {
        .hr-hero .card-body {
            padding: 1.5rem !important;
        }
    }
    @media (max-width: 767.98px) {
        .hr-summary-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
        }
    }
</style>

<div class="hr-page-shell list-page-shell">
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">HR</li>
            </ol>
        </nav>
        <h2 class="h4 mb-1"><i class="fas fa-id-badge me-2 text-primary"></i>Employee Master</h2>
        <p class="text-muted mb-0">Manage employee records, status, and joining details.</p>
    </div>
    <div class="hr-page-actions app-page-actions">
        <a href="<?= APP_URL ?>/index.php?page=hr&action=attendance" class="btn btn-outline-primary"><i class="fas fa-calendar-check me-1"></i>Attendance</a>
        <a href="<?= APP_URL ?>/index.php?page=hr&action=leaves" class="btn btn-outline-secondary"><i class="fas fa-plane-departure me-1"></i>Leaves</a>
        <a href="<?= APP_URL ?>/index.php?page=hr&action=holidays" class="btn btn-outline-info"><i class="fas fa-calendar-day me-1"></i>Holidays</a>
        <a href="<?= APP_URL ?>/index.php?page=hr&action=shifts" class="btn btn-outline-secondary"><i class="fas fa-business-time me-1"></i>Shifts</a>
        <a href="<?= APP_URL ?>/index.php?page=hr&action=payroll" class="btn btn-outline-secondary"><i class="fas fa-wallet me-1"></i>Payroll</a>
        <a href="<?= APP_URL ?>/index.php?page=hr&action=create" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Employee</a>
    </div>
</div>

<div class="card hr-hero mt-3">
    <div class="card-body p-4 p-lg-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="small text-uppercase fw-semibold mb-2 hr-hero-eyebrow">HR Workspace</div>
                <h3 class="mb-2 fw-bold">Employee records in one place</h3>
                <p class="mb-0 hr-hero-copy">
                    Maintain employee identity, designation, department, contact details, and current workforce status with tenant-scoped controls.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-inline-flex flex-column align-items-lg-end">
                    <span class="badge rounded-pill text-bg-light mb-2 px-3 py-2">Master Data</span>
                    <span class="small hr-hero-copy">Structured for future attendance, leave, and payroll modules.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <?php foreach ($summaryCards as $card): ?>
    <div class="col-md-6 col-xl-3">
        <div class="card hr-summary-card h-100">
            <div class="card-body d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="text-muted small text-uppercase fw-semibold mb-2"><?= Helper::escape($card['label']) ?></div>
                    <div class="display-6 fw-bold mb-1"><?= (int)$card['value'] ?></div>
                    <div class="hr-subtle"><?= Helper::escape($card['hint']) ?></div>
                </div>
                <div class="hr-summary-icon bg-<?= Helper::escape($card['tone']) ?> bg-opacity-10 text-<?= Helper::escape($card['tone']) ?>">
                    <i class="fas <?= Helper::escape($card['icon']) ?>"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card hr-summary-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-2">Holiday Calendar</div>
                        <h5 class="mb-1">Upcoming non-working days</h5>
                        <div class="hr-subtle">Keep teams aligned with public, optional, and company holidays.</div>
                    </div>
                    <a href="<?= APP_URL ?>/index.php?page=hr&action=holidays" class="btn btn-sm btn-outline-info">Open</a>
                </div>
                <?php if (empty($upcomingHolidays)): ?>
                <div class="text-muted">No upcoming holidays configured.</div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($upcomingHolidays, 0, 4) as $holiday): ?>
                    <div class="list-group-item px-0">
                        <div class="fw-semibold"><?= Helper::escape($holiday['holiday_name'] ?? '-') ?></div>
                        <div class="hr-subtle"><?= Helper::formatDate($holiday['holiday_date'] ?? date('Y-m-d'), 'd M Y') ?> • <?= Helper::escape(ucfirst($holiday['holiday_type'] ?? 'public')) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card hr-summary-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-2">Shift Roster</div>
                        <h5 class="mb-1">Shift definitions and weekly offs</h5>
                        <div class="hr-subtle">Define standard work windows before assigning them operationally.</div>
                    </div>
                    <a href="<?= APP_URL ?>/index.php?page=hr&action=shifts" class="btn btn-sm btn-outline-secondary">Open</a>
                </div>
                <div class="display-6 fw-bold mb-2"><?= $shiftCount ?></div>
                <div class="hr-subtle">Configured shift templates available for workforce scheduling.</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card hr-summary-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-2">Attendance Snapshot</div>
                        <h5 class="mb-1">Attendance for <?= Helper::escape(date('F Y', strtotime($month . '-01'))) ?></h5>
                        <div class="hr-subtle">Live present, absent, half-day, and late activity.</div>
                    </div>
                    <a href="<?= APP_URL ?>/index.php?page=hr&action=attendance&month=<?= urlencode($month) ?>" class="btn btn-sm btn-outline-primary">Open</a>
                </div>
                <div class="row g-3">
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Present</div><div class="fs-4 fw-bold text-success"><?= (int)($attendanceSummary['present_days'] ?? 0) ?></div></div></div>
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Late</div><div class="fs-4 fw-bold text-warning"><?= (int)($attendanceSummary['late_days'] ?? 0) ?></div></div></div>
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Half Day</div><div class="fs-4 fw-bold text-info"><?= (int)($attendanceSummary['half_days'] ?? 0) ?></div></div></div>
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Absent</div><div class="fs-4 fw-bold text-danger"><?= (int)($attendanceSummary['absent_days'] ?? 0) ?></div></div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card hr-summary-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-2">Leave Workflow</div>
                        <h5 class="mb-1">Approval queue and leave requests</h5>
                        <div class="hr-subtle">Track pending, approved, and rejected requests centrally.</div>
                    </div>
                    <a href="<?= APP_URL ?>/index.php?page=hr&action=leaves" class="btn btn-sm btn-outline-secondary">Open</a>
                </div>
                <div class="row g-3">
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Pending</div><div class="fs-4 fw-bold text-warning"><?= (int)($leaveSummary['pending_requests'] ?? 0) ?></div></div></div>
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Mgr Review</div><div class="fs-4 fw-bold text-info"><?= (int)($leaveSummary['pending_manager_requests'] ?? 0) ?></div></div></div>
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Approved</div><div class="fs-4 fw-bold text-success"><?= (int)($leaveSummary['approved_requests'] ?? 0) ?></div></div></div>
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Rejected</div><div class="fs-4 fw-bold text-danger"><?= (int)($leaveSummary['rejected_requests'] ?? 0) ?></div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card hr-summary-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold mb-2">Payroll Operations</div>
                        <h5 class="mb-1">Monthly payroll control for <?= Helper::escape(date('F Y', strtotime($month . '-01'))) ?></h5>
                        <div class="hr-subtle">Track draft, approved, and payout progress without opening the payroll register.</div>
                    </div>
                    <a href="<?= APP_URL ?>/index.php?page=hr&action=payroll&month=<?= urlencode($month) ?>" class="btn btn-sm btn-outline-secondary">Open</a>
                </div>
                <div class="row g-3">
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Run Status</div><div class="fs-4 fw-bold <?= ($payrollSnapshot['status'] ?? '') === 'paid' ? 'text-success' : (($payrollSnapshot['status'] ?? '') === 'approved' ? 'text-info' : 'text-warning') ?>"><?= Helper::escape(ucfirst((string)($payrollSnapshot['status'] ?? 'draft'))) ?></div></div></div>
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Pending Payouts</div><div class="fs-4 fw-bold text-warning"><?= (int)($payrollSnapshot['pending_items'] ?? 0) ?></div></div></div>
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Paid Items</div><div class="fs-4 fw-bold text-success"><?= (int)($payrollSnapshot['paid_items'] ?? 0) ?></div></div></div>
                    <div class="col-6 col-xl-3"><div class="border rounded-4 p-3"><div class="text-muted small">Net Payroll</div><div class="fs-5 fw-bold text-primary"><?= Helper::formatCurrency($payrollSnapshot['net_amount'] ?? 0) ?></div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card hr-directory-card hr-table-card list-card mt-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-1"><i class="fas fa-users me-2 text-primary"></i>Employee Directory</h5>
            <div class="hr-subtle">Search and manage employee master records across your tenant.</div>
        </div>
        <form class="hr-filter-form" method="GET">
            <input type="hidden" name="page" value="hr">
            <input type="text" name="search" class="form-control form-control-sm hr-filter-field-wide" placeholder="Search employees..." value="<?= Helper::escape($search) ?>">
            <select name="status" class="form-select form-select-sm hr-filter-field">
                <option value="">All Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="on_leave" <?= $status === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search me-1"></i>Search</button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table hr-table table-hover align-middle mb-0 text-nowrap list-table hr-table-compact">
                <thead>
                    <tr>
                        <th class="ps-3">Code</th>
                        <th>Employee Name</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Date Joined</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees['data'])): ?>
                    <tr>
                        <td colspan="7" class="hr-empty">
                            <div class="mb-3"><i class="fas fa-id-card-clip fa-3x text-primary opacity-25"></i></div>
                            <div class="fw-semibold mb-1">No employees found</div>
                            <div class="mb-3">Create your first employee record to start building the HR master.</div>
                            <a href="<?= APP_URL ?>/index.php?page=hr&action=create" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Employee</a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($employees['data'] as $emp): ?>
                    <tr>
                        <td class="ps-3"><code><?= Helper::escape($emp['employee_code']) ?></code></td>
                        <td>
                            <div class="hr-name"><?= Helper::escape($emp['full_name']) ?></div>
                            <div class="hr-subtle"><?= Helper::escape($emp['email'] ?? ($emp['phone'] ?? 'No contact added')) ?></div>
                        </td>
                        <td>
                            <div><?= Helper::escape($emp['designation']) ?></div>
                            <div class="hr-subtle"><?= Helper::escape($emp['department'] ?? 'No department') ?></div>
                        </td>
                        <td><?= Helper::escape($emp['department'] ?? '-') ?></td>
                        <td>
                            <?php
                            $statusValue = (string)($emp['status'] ?? 'inactive');
                            $badge = $statusValue === 'active' ? 'bg-success' : ($statusValue === 'on_leave' ? 'bg-warning text-dark' : 'bg-secondary');
                            ?>
                            <span class="badge <?= $badge ?>"><?= Helper::escape(ucwords(str_replace('_', ' ', $statusValue))) ?></span>
                        </td>
                        <td>
                            <div><?= !empty($emp['joined_on']) ? Helper::formatDate($emp['joined_on'], 'd M Y') : '-' ?></div>
                            <div class="hr-subtle">Joined</div>
                        </td>
                        <td class="pe-3 text-end">
                            <div class="action-btns justify-content-end">
                                <a href="<?= APP_URL ?>/index.php?page=hr&action=view_employee&id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-outline-primary btn-icon" title="View"><i class="fas fa-eye"></i></a>
                                <a href="<?= APP_URL ?>/index.php?page=hr&action=edit&id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-outline-secondary btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=delete" class="d-inline" data-confirm="Delete this employee?">
                                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf_token ?? '' ?>">
                                    <input type="hidden" name="id" value="<?= (int)$emp['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (($employees['totalPages'] ?? 1) > 1): ?>
    <div class="card-footer">
        <?= Helper::pagination((int)$employees['page'], (int)$employees['totalPages'], APP_URL . '/index.php?page=hr&search=' . urlencode($search) . '&status=' . urlencode($status)) ?>
    </div>
    <?php endif; ?>
</div>
</div>
