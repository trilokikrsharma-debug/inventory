<?php
$analysis = is_array($analysis ?? null) ? $analysis : null;
$dryRun = !empty($dryRun);
$summary = is_array($analysis['summary'] ?? null) ? $analysis['summary'] : [
    'total_rows' => 0,
    'valid_rows' => 0,
    'invalid_rows' => 0,
];
?>
<div class="page-header">
    <div>
        <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= APP_URL ?>">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php?page=products">Products</a></li><li class="breadcrumb-item active">Bulk Import</li></ol></nav>
        <h1 class="h3 mb-1">Bulk Import Products</h1>
        <p class="text-muted mb-0">Upload a CSV file, validate the rows, and create products in one pass.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/index.php?page=products&action=download_template" class="btn btn-outline-secondary"><i class="fas fa-download me-1"></i>Download Template</a>
        <a href="<?= APP_URL ?>/index.php?page=products" class="btn btn-primary"><i class="fas fa-arrow-left me-1"></i>Back to Products</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-file-csv me-2"></i>Upload CSV</h6>
            </div>
            <div class="card-body">
                <form action="<?= APP_URL ?>/index.php?page=products&action=import" method="POST" enctype="multipart/form-data">
                    <?= CSRF::field() ?>
                    <div class="mb-3">
                        <label class="form-label">CSV File</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,text/csv" required>
                        <div class="form-text">Required columns: <code>name</code>, <code>purchase_price</code>, <code>selling_price</code>.</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="dryRun" name="dry_run" value="1" <?= $dryRun ? 'checked' : '' ?>>
                        <label class="form-check-label" for="dryRun">Dry run only</label>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i><?= $dryRun ? 'Validate Import' : 'Import Products' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-circle-info me-2"></i>CSV Notes</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0 ps-3">
                    <li>Use category, brand, and unit by existing name or numeric ID.</li>
                    <li><code>is_active</code> accepts <code>1</code>, <code>0</code>, <code>yes</code>, <code>no</code>, <code>active</code>, or <code>inactive</code>.</li>
                    <li>Rows with invalid values are skipped until the file validates cleanly.</li>
                    <li>SKU values must be unique across the file and existing active products.</li>
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
                    <div class="col-sm-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Valid Rows</div>
                            <div class="h4 mb-0 text-success"><?= (int)$summary['valid_rows'] ?></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Invalid Rows</div>
                            <div class="h4 mb-0 text-danger"><?= (int)$summary['invalid_rows'] ?></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Mode</div>
                            <div class="h4 mb-0"><?= $dryRun ? 'Dry Run' : 'Import' ?></div>
                        </div>
                    </div>
                </div>

                <?php if (empty($analysis['rows'])): ?>
                <div class="text-muted">No data rows were found in the uploaded CSV.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analysis['rows'] as $row): ?>
                            <?php $item = $row['normalized'] ?? []; ?>
                            <tr>
                                <td><?= (int)($row['row_number'] ?? 0) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e($item['name'] ?? '-') ?></div>
                                    <div class="small text-muted">
                                        Buy <?= Helper::formatCurrency($item['purchase_price'] ?? 0) ?>, Sell <?= Helper::formatCurrency($item['selling_price'] ?? 0) ?>
                                    </div>
                                </td>
                                <td><code><?= e($item['sku'] ?? '-') ?></code></td>
                                <td>
                                    <?php if (!empty($row['valid'])): ?>
                                    <span class="badge bg-success">Ready</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Invalid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['errors'])): ?>
                                    <div class="small text-danger"><?= e(implode(' ', (array)$row['errors'])) ?></div>
                                    <?php else: ?>
                                    <div class="small text-muted">No validation errors.</div>
                                    <?php endif; ?>
                                </td>
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
