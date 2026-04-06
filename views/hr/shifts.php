<?php
$shifts = is_array($shifts ?? null) ? $shifts : [];
$hasAttendancePolicyHelper = class_exists('HrAttendancePolicyService');
?>
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=hr">HR</a></li>
                <li class="breadcrumb-item active">Shifts</li>
            </ol>
        </nav>
        <h2 class="h4 mb-1"><i class="fas fa-business-time me-2 text-primary"></i>Shift Scheduling</h2>
        <p class="text-muted mb-0">Define shift templates with working hours and weekly offs.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=hr" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>HR Home</a>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent"><h5 class="mb-0">Create Shift</h5></div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=create_shift">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Shift Name</label>
                        <input type="text" name="shift_name" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Grace Period (minutes)</label>
                            <input type="number" name="grace_period_minutes" class="form-control" min="0" max="180" step="1" value="15">
                            <div class="form-text">Used for automatic late marking when check-in exceeds shift start plus grace time.</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Weekly Off Day</label>
                        <select name="weekly_off_day" class="form-select">
                            <?php foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day): ?>
                            <option value="<?= Helper::escape($day) ?>"><?= Helper::escape($day) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="shift_default">
                        <label class="form-check-label" for="shift_default">Set as default shift</label>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-save me-1"></i>Save Shift</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent"><h5 class="mb-0">Shift Templates</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Shift</th>
                                <th>Working Hours</th>
                                <th>Late Cutoff</th>
                                <th>Weekly Off</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($shifts)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No shifts configured yet.</td></tr>
                            <?php else: ?>
                            <?php foreach ($shifts as $shift): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= Helper::escape($shift['shift_name'] ?? '-') ?></div>
                                    <?php if (!empty($shift['is_default'])): ?><span class="badge bg-primary-subtle text-primary">Default</span><?php endif; ?>
                                </td>
                                <td><?= substr((string)($shift['start_time'] ?? '00:00'), 0, 5) ?> - <?= substr((string)($shift['end_time'] ?? '00:00'), 0, 5) ?></td>
                                <td>
                                    <?= (int)($shift['grace_period_minutes'] ?? 15) ?> mins
                                    <div class="small text-muted">
                                        Cutoff <?= Helper::escape($hasAttendancePolicyHelper ? (HrAttendancePolicyService::cutoffTime((string)($shift['start_time'] ?? ''), (int)($shift['grace_period_minutes'] ?? 15)) ?? '-') : '-') ?>
                                    </div>
                                </td>
                                <td><?= Helper::escape($shift['weekly_off_day'] ?? '-') ?></td>
                                <td><?= Helper::escape($shift['notes'] ?? '-') ?></td>
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
