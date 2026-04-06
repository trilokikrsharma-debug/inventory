<?php
$year = (int)($year ?? date('Y'));
$holidays = is_array($holidays ?? null) ? $holidays : [];
?>
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=hr">HR</a></li>
                <li class="breadcrumb-item active">Holidays</li>
            </ol>
        </nav>
        <h2 class="h4 mb-1"><i class="fas fa-calendar-day me-2 text-primary"></i>Holiday Calendar</h2>
        <p class="text-muted mb-0">Configure public, optional, and company holidays for workforce planning.</p>
    </div>
    <a href="<?= APP_URL ?>/index.php?page=hr" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>HR Home</a>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent"><h5 class="mb-0">Add Holiday</h5></div>
            <div class="card-body">
                <form method="POST" action="<?= APP_URL ?>/index.php?page=hr&action=create_holiday">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Holiday Name</label>
                        <input type="text" name="holiday_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Holiday Date</label>
                        <input type="date" name="holiday_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="holiday_type" class="form-select">
                            <option value="public">Public</option>
                            <option value="optional">Optional</option>
                            <option value="company">Company</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Save Holiday</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <form method="GET" class="d-flex gap-2 align-items-end">
                    <input type="hidden" name="page" value="hr">
                    <input type="hidden" name="action" value="holidays">
                    <div>
                        <label class="form-label small mb-0">Year</label>
                        <input type="number" name="year" class="form-control form-control-sm" min="2020" max="2100" value="<?= $year ?>">
                    </div>
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Holiday</th>
                                <th>Type</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($holidays)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No holidays configured for this year.</td></tr>
                            <?php else: ?>
                            <?php foreach ($holidays as $holiday): ?>
                            <tr>
                                <td><?= Helper::formatDate($holiday['holiday_date'] ?? date('Y-m-d'), 'd M Y') ?></td>
                                <td class="fw-semibold"><?= Helper::escape($holiday['holiday_name'] ?? '-') ?></td>
                                <td><span class="badge bg-info-subtle text-info"><?= Helper::escape(ucfirst($holiday['holiday_type'] ?? 'public')) ?></span></td>
                                <td><?= Helper::escape($holiday['notes'] ?? '-') ?></td>
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
