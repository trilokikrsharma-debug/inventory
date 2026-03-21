<?php
/**
 * Backup & Restore View
 * 
 * Displays backup management interface with create, download,
 * delete, and restore functionality.
 */
?>

<style>
    .backup-hero {
        background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 50%, #1b2838 100%);
        border-radius: 20px;
        padding: 2rem 2.5rem;
        color: #fff;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    .backup-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(78, 115, 223, 0.15) 0%, transparent 70%);
        border-radius: 50%;
    }
    .backup-hero::after {
        content: '';
        position: absolute;
        bottom: -60%;
        left: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(40, 167, 69, 0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    .backup-hero h2 {
        font-weight: 700;
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }
    .backup-hero p {
        opacity: 0.8;
        margin-bottom: 0;
        position: relative;
        z-index: 1;
    }
    .backup-hero .hero-icon {
        font-size: 4rem;
        opacity: 0.15;
        position: absolute;
        right: 2rem;
        top: 50%;
        transform: translateY(-50%);
    }

    .stat-card {
        background: var(--card-bg, #fff);
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid var(--border-color, #e3e6f0);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .stat-card .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        margin-bottom: 1rem;
    }
    .stat-card .stat-icon.bg-primary-soft {
        background: rgba(78, 115, 223, 0.12);
        color: #4e73df;
    }
    .stat-card .stat-icon.bg-success-soft {
        background: rgba(40, 167, 69, 0.12);
        color: #28a745;
    }
    .stat-card .stat-icon.bg-warning-soft {
        background: rgba(255, 193, 7, 0.12);
        color: #e6a000;
    }
    .stat-card .stat-icon.bg-info-soft {
        background: rgba(23, 162, 184, 0.12);
        color: #17a2b8;
    }
    .stat-card .stat-value {
        font-size: 1.65rem;
        font-weight: 700;
        color: var(--text-primary, #2d3436);
        line-height: 1.2;
    }
    .stat-card .stat-label {
        font-size: 0.82rem;
        color: var(--text-secondary, #636e72);
        margin-top: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .section-card {
        background: var(--card-bg, #fff);
        border-radius: 16px;
        border: 1px solid var(--border-color, #e3e6f0);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .section-card .section-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border-color, #e3e6f0);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .section-card .section-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        color: var(--text-primary, #2d3436);
    }
    .section-card .section-body {
        padding: 1.5rem;
    }

    .btn-create-backup {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        color: #fff;
        padding: 0.7rem 1.8rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.92rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    .btn-create-backup:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        color: #fff;
    }

    .backup-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .backup-table thead th {
        background: var(--table-header-bg, #f8f9fc);
        color: var(--text-secondary, #636e72);
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.85rem 1rem;
        border-bottom: 2px solid var(--border-color, #e3e6f0);
    }
    .backup-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color, #e3e6f0);
        color: var(--text-primary, #2d3436);
        vertical-align: middle;
    }
    .backup-table tbody tr:last-child td {
        border-bottom: none;
    }
    .backup-table tbody tr {
        transition: background 0.2s ease;
    }
    .backup-table tbody tr:hover {
        background: var(--table-hover-bg, rgba(78, 115, 223, 0.04));
    }

    .file-icon {
        width: 40px;
        height: 40px;
        background: rgba(78, 115, 223, 0.1);
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #4e73df;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .btn-action {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color, #e3e6f0);
        background: transparent;
        color: var(--text-secondary, #636e72);
        transition: all 0.2s ease;
        font-size: 0.9rem;
    }
    .btn-action:hover {
        background: #4e73df;
        border-color: #4e73df;
        color: #fff;
        transform: scale(1.1);
    }
    .btn-action.btn-download:hover {
        background: #28a745;
        border-color: #28a745;
    }
    .btn-action.btn-delete:hover {
        background: #dc3545;
        border-color: #dc3545;
    }
    .btn-action.btn-restore-existing:hover {
        background: #fd7e14;
        border-color: #fd7e14;
    }

    .restore-zone {
        border: 2px dashed var(--border-color, #d1d9e6);
        border-radius: 16px;
        padding: 2.5rem 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        background: var(--card-bg, #fafbfe);
    }
    .restore-zone:hover,
    .restore-zone.dragover {
        border-color: #fd7e14;
        background: rgba(253, 126, 20, 0.04);
    }
    .restore-zone .upload-icon {
        font-size: 3rem;
        color: #fd7e14;
        opacity: 0.6;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    .restore-zone:hover .upload-icon {
        opacity: 1;
        transform: scale(1.1);
    }
    .restore-zone h6 {
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-primary, #2d3436);
    }
    .restore-zone p {
        color: var(--text-secondary, #636e72);
        font-size: 0.88rem;
        margin-bottom: 0;
    }
    .restore-zone .selected-file {
        margin-top: 1rem;
        padding: 0.6rem 1rem;
        background: rgba(253, 126, 20, 0.08);
        border-radius: 8px;
        display: none;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: #fd7e14;
    }

    .btn-restore {
        background: linear-gradient(135deg, #fd7e14, #e85d04);
        border: none;
        color: #fff;
        padding: 0.7rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.92rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(253, 126, 20, 0.3);
    }
    .btn-restore:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(253, 126, 20, 0.4);
        color: #fff;
    }
    .btn-restore:disabled {
        opacity: 0.5;
        transform: none;
        box-shadow: none;
        cursor: not-allowed;
    }

    .warning-box {
        background: rgba(255, 193, 7, 0.08);
        border: 1px solid rgba(255, 193, 7, 0.3);
        border-radius: 12px;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }
    .warning-box i {
        color: #e6a000;
        font-size: 1.2rem;
        margin-top: 2px;
        flex-shrink: 0;
    }
    .warning-box .warning-text {
        font-size: 0.88rem;
        color: var(--text-primary, #2d3436);
    }
    .warning-box .warning-text strong {
        color: #e6a000;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
    }
    .empty-state i {
        font-size: 3.5rem;
        color: var(--text-secondary, #b2bec3);
        opacity: 0.4;
        margin-bottom: 1rem;
    }
    .empty-state h6 {
        font-weight: 700;
        color: var(--text-primary, #2d3436);
        margin-bottom: 0.5rem;
    }
    .empty-state p {
        color: var(--text-secondary, #636e72);
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    /* Modal customizations */
    .modal-confirm .modal-content {
        border-radius: 16px;
        border: none;
        overflow: hidden;
    }
    .modal-confirm .modal-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: #fff;
        border: none;
        padding: 1.25rem 1.5rem;
    }
    .modal-confirm .modal-header.restore-header {
        background: linear-gradient(135deg, #fd7e14, #e85d04);
    }
    .modal-confirm .modal-body {
        padding: 1.5rem;
    }
    .modal-confirm .modal-footer {
        border: none;
        padding: 1rem 1.5rem;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-in {
        animation: fadeInUp 0.4s ease forwards;
    }
    .delay-1 { animation-delay: 0.05s; }
    .delay-2 { animation-delay: 0.1s; }
    .delay-3 { animation-delay: 0.15s; }
    .delay-4 { animation-delay: 0.2s; }
</style>

<!-- Hero Header -->
<div class="backup-hero animate-in">
    <i class="fas fa-shield-halved hero-icon"></i>
    <h2><i class="fas fa-database me-2" style="color: #4e73df;"></i> Backup & Restore</h2>
    <p>Protect your data. Create backups, download them, or restore from a previous backup file.</p>
    <?php if (!empty($isSuperAdmin)): ?>
    <span class="badge" style="background:rgba(255,255,255,0.15);font-size:0.75rem;padding:0.4rem 0.8rem;border-radius:8px;position:relative;z-index:1;">
        <i class="fas fa-crown me-1" style="color:#ffd700;"></i> Super Admin — Full platform access
    </span>
    <?php else: ?>
    <span class="badge" style="background:rgba(255,255,255,0.15);font-size:0.75rem;padding:0.4rem 0.8rem;border-radius:8px;position:relative;z-index:1;">
        <i class="fas fa-building me-1"></i> Company backup only
    </span>
    <?php endif; ?>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="stat-card animate-in delay-1">
            <div class="stat-icon bg-primary-soft">
                <i class="fas fa-database"></i>
            </div>
            <div class="stat-value"><?= htmlspecialchars($dbName) ?></div>
            <div class="stat-label">Database Name</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card animate-in delay-2">
            <div class="stat-icon bg-success-soft">
                <i class="fas fa-table"></i>
            </div>
            <div class="stat-value"><?= $tableCount ?></div>
            <div class="stat-label">Tables with Data</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card animate-in delay-3">
            <div class="stat-icon bg-info-soft">
                <i class="fas fa-hard-drive"></i>
            </div>
            <div class="stat-value"><?= Helper::formatFileSize($dbSize) ?></div>
            <div class="stat-label">Estimated Data Size</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card animate-in delay-4">
            <div class="stat-icon bg-warning-soft">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="stat-value"><?= count($backups) ?></div>
            <div class="stat-label">Saved Backups</div>
        </div>
    </div>
</div>

<!-- Backup Section -->
<div class="section-card animate-in delay-2">
    <div class="section-header">
        <h5>
            <i class="fas fa-box-archive" style="color: #28a745;"></i>
            Backup Files
        </h5>
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" action="<?= APP_URL ?>/index.php?page=backup&action=create" id="createBackupForm">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                <input type="hidden" name="backup_type" value="tenant">
                <button type="submit" class="btn-create-backup" id="btnCreateBackup">
                    <i class="fas fa-building"></i>
                    Backup My Company Data
                </button>
            </form>
            <?php if (!empty($isSuperAdmin)): ?>
            <form method="POST" action="<?= APP_URL ?>/index.php?page=backup&action=create" id="createFullBackupForm">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                <input type="hidden" name="backup_type" value="full">
                <button type="submit" class="btn-create-backup" id="btnCreateFullBackup" 
                        style="background:linear-gradient(135deg, #4e73df, #224abe);box-shadow:0 4px 15px rgba(78,115,223,0.3);">
                    <i class="fas fa-server"></i>
                    Full DB Backup
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="section-body" style="padding: 0;">
        <?php if (empty($backups)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h6>No Backups Yet</h6>
            <p>Click "Create New Backup" to generate your first database backup.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="backup-table">
                <thead>
                    <tr>
                        <th style="width: 35%;">File Name</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Created</th>
                        <th style="width: 160px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="file-icon">
                                    <i class="fas fa-file-code"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.92rem;"><?= htmlspecialchars($backup['filename']) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-secondary, #636e72);">.sql backup file</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                                $type = $backup['type'] ?? 'unknown';
                                $typeColor = ['tenant' => '#28a745', 'full' => '#4e73df', 'legacy' => '#6c757d'][$type] ?? '#999';
                                $typeIcon = ['tenant' => 'fa-building', 'full' => 'fa-server', 'legacy' => 'fa-clock-rotate-left'][$type] ?? 'fa-file';
                                $typeLabel = ['tenant' => 'Company', 'full' => 'Full DB', 'legacy' => 'Legacy'][$type] ?? ucfirst($type);
                            ?>
                            <span class="badge" style="background: <?= $typeColor ?>; font-size: 0.72rem; padding: 0.35rem 0.65rem; border-radius: 6px;">
                                <i class="fas <?= $typeIcon ?> me-1"></i><?= $typeLabel ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-weight: 600;"><?= Helper::formatFileSize($backup['size']) ?></span>
                        </td>
                        <td>
                            <div style="font-size: 0.9rem;"><?= date('d M Y', strtotime($backup['created'])) ?></div>
                            <div style="font-size: 0.78rem; color: var(--text-secondary, #636e72);"><?= date('h:i A', strtotime($backup['created'])) ?></div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <!-- Download -->
                                <a href="<?= APP_URL ?>/index.php?page=backup&action=download&file=<?= urlencode($backup['filename']) ?>" 
                                   class="btn-action btn-download" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                <?php if (!empty($isSuperAdmin) && ($backup['type'] ?? '') === 'full'): ?>
                                <!-- Restore from existing (super-admin + full backup only) -->
                                <button
                                    type="button"
                                    class="btn-action btn-restore-existing js-restore-existing"
                                    title="Restore This Backup"
                                    data-backup-file="<?= htmlspecialchars((string)$backup['filename'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-rotate-left"></i>
                                </button>
                                <?php endif; ?>
                                <!-- Delete -->
                                <button
                                    type="button"
                                    class="btn-action btn-delete js-delete-backup"
                                    title="Delete"
                                    data-backup-file="<?= htmlspecialchars((string)$backup['filename'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($isSuperAdmin)): ?>
<!-- Restore Section — Super Admin Only -->
<div class="section-card animate-in delay-3">
    <div class="section-header">
        <h5>
            <i class="fas fa-upload" style="color: #fd7e14;"></i>
            Restore from File Upload
            <span class="badge bg-danger" style="font-size:0.7rem;margin-left:0.5rem;">Super Admin Only</span>
        </h5>
    </div>
    <div class="section-body">
        <form method="POST" action="<?= APP_URL ?>/index.php?page=backup&action=restore" enctype="multipart/form-data" id="restoreUploadForm">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
            <input type="hidden" name="restore_source" value="upload">

            <div class="restore-zone" id="restoreDropZone" onclick="document.getElementById('restoreFileInput').click()">
                <input type="file" name="backup_file" id="restoreFileInput" accept=".sql" style="display: none;" onchange="handleFileSelect(this)">
                <div class="upload-icon">
                    <i class="fas fa-cloud-arrow-up"></i>
                </div>
                <h6>Drop your .sql backup file here</h6>
                <p>or click to browse &middot; Maximum 50MB &middot; Only .sql files</p>
                <div class="selected-file" id="selectedFileInfo">
                    <i class="fas fa-file-code"></i>
                    <span id="selectedFileName"></span>
                </div>
            </div>

            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="warning-text">
                    <strong>⚠ Warning:</strong> Restoring a <strong>full database backup</strong> will replace ALL tenant data for ALL companies.
                    This action is irreversible. Only use full-platform backups created by a super admin.
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="button" class="btn-restore" id="btnRestoreUpload" disabled onclick="confirmRestoreUpload()">
                    <i class="fas fa-rotate-left"></i>
                    Restore Full Database
                </button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<!-- Non-super-admin info -->
<div class="section-card animate-in delay-3">
    <div class="section-body text-center" style="padding: 2rem;">
        <i class="fas fa-shield-halved" style="font-size: 2rem; color: var(--text-secondary); opacity: 0.4; margin-bottom: 0.75rem;"></i>
        <h6 style="font-weight: 700; color: var(--text-primary);">Database Restore</h6>
        <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 0;">Full database restore is restricted to super administrators. Contact your system admin if you need to restore data.</p>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade modal-confirm" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash-can me-2"></i> Delete Backup</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this backup file?</p>
                <p class="mb-0" style="font-weight: 600; color: #dc3545;" id="deleteFileName"></p>
                <p class="mt-2 text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=backup&action=delete" id="deleteForm">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    <input type="hidden" name="file" id="deleteFileInput">
                    <button type="submit" class="btn btn-danger" style="border-radius: 10px;">
                        <i class="fas fa-trash-can me-1"></i> Yes, Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal (for upload) -->
<div class="modal fade modal-confirm" id="restoreUploadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header restore-header">
                <h5 class="modal-title"><i class="fas fa-rotate-left me-2"></i> Confirm Restore</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #fd7e14;"></i>
                </div>
                <p class="text-center" style="font-weight: 600; font-size: 1.05rem;">Are you sure you want to restore the database?</p>
                <p class="text-center text-muted small mb-0">All current data will be <strong>permanently replaced</strong> with the data from the backup file. This cannot be undone.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px; min-width: 100px;">Cancel</button>
                <button type="button" class="btn btn-warning text-white" style="border-radius: 10px; min-width: 100px;" onclick="submitRestoreUpload()">
                    <i class="fas fa-rotate-left me-1"></i> Yes, Restore
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal (for existing file) -->
<div class="modal fade modal-confirm" id="restoreExistingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header restore-header">
                <h5 class="modal-title"><i class="fas fa-rotate-left me-2"></i> Confirm Restore</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #fd7e14;"></i>
                </div>
                <p class="text-center" style="font-weight: 600; font-size: 1.05rem;">Restore from existing backup?</p>
                <p class="text-center mb-1" style="color: #fd7e14; font-weight: 600;" id="restoreExistingFileName"></p>
                <p class="text-center text-muted small mb-0">All current data will be <strong>permanently replaced</strong>. This cannot be undone.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px; min-width: 100px;">Cancel</button>
                <form method="POST" action="<?= APP_URL ?>/index.php?page=backup&action=restore" id="restoreExistingForm">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrfToken ?>">
                    <input type="hidden" name="restore_source" value="existing">
                    <input type="hidden" name="backup_file" id="restoreExistingFileInput">
                    <button type="submit" class="btn btn-warning text-white" style="border-radius: 10px; min-width: 100px;">
                        <i class="fas fa-rotate-left me-1"></i> Yes, Restore
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ==========================================
// Delete confirmation
// ==========================================
function confirmDelete(filename) {
    document.getElementById('deleteFileName').textContent = filename;
    document.getElementById('deleteFileInput').value = filename;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// ==========================================
// Restore from existing backup
// ==========================================
function confirmRestoreExisting(filename) {
    document.getElementById('restoreExistingFileName').textContent = filename;
    document.getElementById('restoreExistingFileInput').value = filename;
    new bootstrap.Modal(document.getElementById('restoreExistingModal')).show();
}

document.querySelectorAll('.js-delete-backup').forEach((button) => {
    button.addEventListener('click', () => {
        confirmDelete(button.dataset.backupFile || '');
    });
});

document.querySelectorAll('.js-restore-existing').forEach((button) => {
    button.addEventListener('click', () => {
        confirmRestoreExisting(button.dataset.backupFile || '');
    });
});

// ==========================================
// File upload handling
// ==========================================
function handleFileSelect(input) {
    const file = input.files[0];
    const infoDiv = document.getElementById('selectedFileInfo');
    const nameSpan = document.getElementById('selectedFileName');
    const btn = document.getElementById('btnRestoreUpload');

    if (file) {
        nameSpan.textContent = file.name + ' (' + formatSize(file.size) + ')';
        infoDiv.style.display = 'inline-flex';
        btn.disabled = false;
    } else {
        infoDiv.style.display = 'none';
        btn.disabled = true;
    }
}

function formatSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// ==========================================
// Restore from upload
// ==========================================
function confirmRestoreUpload() {
    new bootstrap.Modal(document.getElementById('restoreUploadModal')).show();
}

function submitRestoreUpload() {
    bootstrap.Modal.getInstance(document.getElementById('restoreUploadModal')).hide();
    document.getElementById('restoreUploadForm').submit();
}

// ==========================================
// Drag & Drop
// ==========================================
const dropZone = document.getElementById('restoreDropZone');
const fileInput = document.getElementById('restoreFileInput');

['dragenter', 'dragover'].forEach(event => {
    dropZone.addEventListener(event, function(e) {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
});

['dragleave', 'drop'].forEach(event => {
    dropZone.addEventListener(event, function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });
});

dropZone.addEventListener('drop', function(e) {
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect(fileInput);
    }
});

// ==========================================
// Create backup loading state
// ==========================================
document.getElementById('createBackupForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnCreateBackup');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Backup...';
    btn.disabled = true;
});

// Full backup loading state (if button exists)
const fullForm = document.getElementById('createFullBackupForm');
if (fullForm) {
    fullForm.addEventListener('submit', function() {
        const btn = document.getElementById('btnCreateFullBackup');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Full Backup...';
        btn.disabled = true;
    });
}
</script>
