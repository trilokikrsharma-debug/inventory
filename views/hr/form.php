<?php
$employee = is_array($employee ?? null) ? $employee : [];
$shifts = is_array($shifts ?? null) ? $shifts : [];
$managers = is_array($managers ?? null) ? $managers : [];
$formAction = (string)($formAction ?? 'create');
$isEdit = str_starts_with($formAction, 'edit');
?>
<style>
    .hr-form-shell {
        border: 0;
        border-radius: 1.25rem;
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .hr-form-hero {
        background:
            radial-gradient(circle at top right, rgba(56, 189, 248, 0.18), transparent 30%),
            linear-gradient(135deg, #0f172a 0%, #172554 100%);
        color: #e5eefc;
    }
    .hr-form-section {
        border: 1px solid rgba(15, 23, 42, 0.07);
        border-radius: 1rem;
        padding: 1.25rem;
        background: #fff;
    }
</style>

<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=hr">HR</a></li>
            <li class="breadcrumb-item active"><?= Helper::escape($pageTitle ?? 'Employee') ?></li>
        </ol>
    </nav>
</div>

<div class="card hr-form-shell">
    <div class="card-body hr-form-hero p-4 p-lg-5 border-bottom">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="small text-uppercase fw-semibold mb-2" style="letter-spacing:.12em;color:#93c5fd;"><?= $isEdit ? 'Update Employee' : 'New Employee' ?></div>
                <h3 class="mb-2 fw-bold"><?= $isEdit ? 'Refine employee profile details' : 'Create a clean employee master record' ?></h3>
                <p class="mb-0" style="max-width:720px;color:#c8d7ee;">
                    Keep identity, department, status, and workforce details structured so later HR modules can build on consistent master data.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <span class="badge rounded-pill text-bg-light px-3 py-2">Employee Master</span>
            </div>
        </div>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=<?= Helper::escape($formAction) ?>">
            <?= CSRF::field() ?>
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="hr-form-section h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">Core Identity</h5>
                                <div class="text-muted small">Primary employee identity and org placement.</div>
                            </div>
                            <span class="badge text-bg-light">Required Fields</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Employee Code <span class="text-danger">*</span></label>
                                <input type="text" name="employee_code" class="form-control" value="<?= Helper::escape($employee['employee_code'] ?? '') ?>" maxlength="30" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="<?= Helper::escape($employee['full_name'] ?? '') ?>" maxlength="150" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Designation <span class="text-danger">*</span></label>
                                <input type="text" name="designation" class="form-control" value="<?= Helper::escape($employee['designation'] ?? '') ?>" maxlength="120" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <input type="text" name="department" class="form-control" value="<?= Helper::escape($employee['department'] ?? '') ?>" maxlength="120">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= Helper::escape($employee['email'] ?? '') ?>" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= Helper::escape($employee['phone'] ?? '') ?>" maxlength="20">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="hr-form-section h-100">
                        <h5 class="mb-1">Employment Status</h5>
                        <div class="text-muted small mb-3">Operational status and start-date tracking.</div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php $currentStatus = (string)($employee['status'] ?? 'active'); ?>
                                    <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="on_leave" <?= $currentStatus === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                                    <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Joining Date <span class="text-danger">*</span></label>
                                <input type="date" name="joined_on" class="form-control" value="<?= Helper::escape($employee['joined_on'] ?? date('Y-m-d')) ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Salary</label>
                                <input type="number" name="salary" class="form-control" step="0.01" min="0" value="<?= Helper::escape($employee['salary'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Assigned Shift</label>
                                <?php $currentShiftId = (int)($employee['shift_id'] ?? 0); ?>
                                <select name="shift_id" class="form-select">
                                    <option value="">No shift assigned</option>
                                    <?php foreach ($shifts as $shift): ?>
                                    <option value="<?= (int)($shift['id'] ?? 0) ?>" <?= $currentShiftId === (int)($shift['id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= Helper::escape($shift['shift_name'] ?? 'Shift') ?>
                                        (<?= Helper::escape(substr((string)($shift['start_time'] ?? ''), 0, 5)) ?> - <?= Helper::escape(substr((string)($shift['end_time'] ?? ''), 0, 5)) ?>)
                                        <?php if (!empty($shift['weekly_off_day'])): ?>
                                            • Off <?= Helper::escape($shift['weekly_off_day']) ?>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Use shift assignment to standardize weekly off and attendance context.</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Reporting Manager</label>
                                <?php $currentManagerId = (int)($employee['reporting_manager_user_id'] ?? 0); ?>
                                <select name="reporting_manager_user_id" class="form-select">
                                    <option value="">No reporting manager</option>
                                    <?php foreach ($managers as $manager): ?>
                                    <option value="<?= (int)($manager['id'] ?? 0) ?>" <?= $currentManagerId === (int)($manager['id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= Helper::escape($manager['full_name'] ?? 'User') ?>
                                        <?php if (!empty($manager['role_name'])): ?>
                                            • <?= Helper::escape($manager['role_name']) ?>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Used for manager-stage leave approvals before final HR review.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="hr-form-section">
                        <h5 class="mb-1">Internal Notes</h5>
                        <div class="text-muted small mb-3">Store role context, handover notes, or internal remarks.</div>
                        <textarea name="notes" class="form-control" rows="5"><?= Helper::escape($employee['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Employee</button>
                <a href="<?= APP_URL ?>/index.php?page=hr" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
