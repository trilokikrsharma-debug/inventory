<?php
$analysis = is_array($analysis ?? null) ? $analysis : null;
$dryRun = !empty($dryRun);
$entityLabel = (string)($entityLabel ?? 'Contacts');
$entityKey = (string)($entityKey ?? 'customers');
$templateAction = (string)($templateAction ?? 'download_template');
$summary = is_array($analysis['summary'] ?? null) ? $analysis['summary'] : [
    'total_rows' => 0,
    'valid_rows' => 0,
    'invalid_rows' => 0,
];
?>
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=<?= Helper::escape($entityKey) ?>"><?= Helper::escape($entityLabel) ?></a></li><li class="breadcrumb-item active">Bulk Import</li></ol></nav>
        <h1 class="h3 mb-1">Bulk Import <?= Helper::escape($entityLabel) ?></h1>
        <p class="text-muted mb-0">Upload a CSV file, validate the rows, and create <?= strtolower($entityLabel) ?> in one pass.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=<?= Helper::escape($entityKey) ?>&action=<?= Helper::escape($templateAction) ?>" class="btn btn-outline-secondary"><i class="fas fa-download me-1"></i>Download Template</a>
        <a href="<?= APP_URL ?>/index.php?page=<?= Helper::escape($entityKey) ?>" class="btn btn-primary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-file-csv me-2"></i>Upload CSV</h6></div>
            <div class="card-body">
                <form action="<?= APP_URL ?>/index.php?page=<?= Helper::escape($entityKey) ?>&action=import" method="POST" enctype="multipart/form-data">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label">CSV File</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,text/csv" required>
                        <div class="form-text">Required column: <code>name</code>.</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="dryRun" name="dry_run" value="1" <?= $dryRun ? 'checked' : '' ?>>
                        <label class="form-check-label" for="dryRun">Dry run only</label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i><?= $dryRun ? 'Validate Import' : 'Import ' . Helper::escape($entityLabel) ?></button>
                </form>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-circle-info me-2"></i>CSV Notes</h6></div>
            <div class="card-body">
                <ul class="mb-0 ps-3">
                    <li>Supported columns: <code>name,email,phone,address,city,state,zip,tax_number,opening_balance,is_active</code>.</li>
                    <li><code>is_active</code> accepts <code>1</code>, <code>0</code>, <code>yes</code>, <code>no</code>, <code>active</code>, or <code>inactive</code>.</li>
                    <li>Rows with invalid values are blocked until the file validates cleanly.</li>
                    <li>Email and phone values are checked against existing records and duplicates inside the file.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list-check me-2"></i>Validation Report</h6>
                <span class="badge text-bg-light"><?= (int)$summary['total_rows'] ?> rows</span>
            </div>
            <div class="card-body">
                <?php if (!$analysis): ?>
                <div class="text-muted">Upload a CSV file to preview valid rows and validation errors.</div>
                <?php else: ?>
                <div class="row g-3 mb-4">
                    <div class="col-sm-4"><div class="border rounded p-3 h-100"><div class="text-muted small">Valid Rows</div><div class="h4 mb-0 text-success"><?= (int)$summary['valid_rows'] ?></div></div></div>
                    <div class="col-sm-4"><div class="border rounded p-3 h-100"><div class="text-muted small">Invalid Rows</div><div class="h4 mb-0 text-danger"><?= (int)$summary['invalid_rows'] ?></div></div></div>
                    <div class="col-sm-4"><div class="border rounded p-3 h-100"><div class="text-muted small">Mode</div><div class="h4 mb-0"><?= $dryRun ? 'Dry Run' : 'Import' ?></div></div></div>
                </div>
                <?php if (empty($analysis['rows'])): ?>
                <div class="text-muted">No data rows were found in the uploaded CSV.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Row</th><th>Name</th><th>Contact</th><th>Status</th><th>Details</th></tr></thead>
                        <tbody>
                            <?php foreach ($analysis['rows'] as $row): ?>
                            <?php $item = $row['normalized'] ?? []; ?>
                            <tr>
                                <td><?= (int)($row['row_number'] ?? 0) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= Helper::escape($item['name'] ?? '-') ?></div>
                                    <div class="small text-muted"><?= Helper::escape($item['city'] ?? '-') ?><?= !empty($item['state']) ? ', ' . Helper::escape($item['state']) : '' ?></div>
                                </td>
                                <td>
                                    <div><?= Helper::escape($item['email'] ?? '-') ?></div>
                                    <div class="small text-muted"><?= Helper::escape($item['phone'] ?? '-') ?></div>
                                </td>
                                <td><?php if (!empty($row['valid'])): ?><span class="badge bg-success">Ready</span><?php else: ?><span class="badge bg-danger">Invalid</span><?php endif; ?></td>
                                <td><?php if (!empty($row['errors'])): ?><div class="small text-danger"><?= Helper::escape(implode(' ', (array)$row['errors'])) ?></div><?php else: ?><div class="small text-muted">No validation errors.</div><?php endif; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

