<?php
/**
 * Backup & Restore Controller — Multi-Tenant Safe
 *
 * SECURITY ARCHITECTURE:
 *  - NON-super-admin users can ONLY export their own company's data
 *    via tenant-filtered CSV/SQL export (per-company logical backup).
 *  - SUPER-ADMIN users can perform full database backup/restore
 *    (for platform-level disaster recovery only).
 *  - Restore is restricted to super-admin only (prevents one tenant
 *    from overwriting the entire shared database).
 *  - Backup files are stored in per-company subdirectories to prevent
 *    cross-tenant file access.
 *
 * MEMORY SAFETY:
 *  - All exports use streaming writes (chunked queries + fwrite)
 *  - No full-table load into memory
 *
 * @version 2.0 — Tenant-safe rewrite
 */
class BackupController extends Controller {
    private const MAX_RESTORE_FILE_BYTES = 52428800;

    protected $allowedActions = ['index', 'create', 'download', 'delete', 'restore'];
    private string $backupDir;
    private BackupRestoreService $backupRestoreService;
    private ?BackupManagementService $backupManagementService = null;

    /**
     * Tables that contain per-tenant data (have company_id column).
     * These are exported with WHERE company_id = ? for tenant backups.
     */
    private static $tenantTables = [
        'products', 'categories', 'brands', 'units',
        'customers', 'suppliers',
        'sales', 'sale_items', 'sale_returns', 'sale_return_items',
        'purchases', 'purchase_items',
        'payments', 'quotations', 'quotation_items',
        'stock_history', 'activity_log',
        'users', 'company_settings',
    ];

    public function __construct() {
        $this->backupDir = BackupService::resolveBackupRoot();
        $this->backupRestoreService = new BackupRestoreService();
        BackupService::ensureDir($this->backupDir);
        BackupService::ensureDir($this->getFullBackupDir());
    }

    // =========================================================
    // INDEX — Show backup page
    // =========================================================

    public function index() {
        $this->requireFeature('backup_restore');
        $this->requirePermission('backup.manage');

        $companyId = $this->activeCompanyId();
        $isSuperAdmin = Session::isSuperAdmin();
        $backups = $this->getBackupList($companyId, $isSuperAdmin);

        // Get tenant data stats
        $db = Database::getInstance();
        $stats = $this->getTenantStats($db, $companyId);

        $this->view('backup.index', [
            'pageTitle'    => 'Backup & Restore',
            'backups'      => $backups,
            'tableCount'   => $stats['tableCount'],
            'dbSize'       => $stats['estimatedSize'],
            'dbName'       => $stats['label'],
            'isSuperAdmin' => $isSuperAdmin,
            'companyId'    => $companyId,
        ]);
    }

    // =========================================================
    // CREATE — Generate tenant-scoped or full backup
    // =========================================================

    public function create() {
        $this->requireFeature('backup_restore');
        $this->requirePermission('backup.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=backup');
            return;
        }

        $this->validateCSRF();

        $companyId = $this->activeCompanyId();
        $isSuperAdmin = Session::isSuperAdmin();
        $backupType = strtolower(trim((string)$this->post('backup_type', 'tenant')));

        if (!in_array($backupType, ['tenant', 'full'], true)) {
            Helper::securityLog('BACKUP_TYPE_BLOCKED', 'Invalid backup_type: ' . $backupType);
            $this->setFlash('error', 'Invalid backup type requested.');
            $this->redirect('index.php?page=backup');
            return;
        }

        // SECURITY: Only super-admin can create full backups
        if ($backupType === 'full') {
            $this->requireSuperAdmin();
        } elseif ($companyId <= 0) {
            $this->setFlash('error', 'Tenant backup requires an active tenant context.');
            $this->redirect('index.php?page=backup');
            return;
        }

        if ($this->hasPendingBackupJob($companyId, $backupType)) {
            $this->setFlash('info', 'A backup job of this type is already pending or processing.');
            $this->redirect('index.php?page=backup');
            return;
        }
        $currentUser = Session::get('user') ?? [];
        $queuePayload = [
            'company_id' => $companyId,
            'backup_type' => $backupType,
            'is_super_admin' => (bool)$isSuperAdmin,
            'user_id' => (int)($currentUser['id'] ?? 0),
            'requested_at' => date(DATETIME_FORMAT_DB),
        ];

        try {
            $jobId = JobDispatcher::dispatch('backup', 'ProcessBackup', $queuePayload, 2, 2);
            $this->logActivity('Queued backup job #' . $jobId, 'backup', $jobId, $backupType);
            $this->setFlash('success', 'Backup queued successfully. It will appear in the list once processing completes.');
            $this->redirect('index.php?page=backup');
            return;
        } catch (\Throwable $queueError) {
            error_log('[Backup] Queue dispatch failed, falling back to sync: ' . $queueError->getMessage());
        }

        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            $timestamp = date('Y-m-d_H-i-s');

            if ($backupType === 'full' && $isSuperAdmin) {
                $filepath = $this->getFullBackupDir() . '/full_backup_' . $timestamp . '.sql';
                BackupService::ensureDir(dirname($filepath));
                BackupService::createFullBackup($pdo, $filepath);
                $displayName = basename($filepath);
            } else {
                $filepath = $this->getTenantBackupDir($companyId) . '/company_' . $companyId . '_backup_' . $timestamp . '.sql';
                BackupService::ensureDir(dirname($filepath));
                BackupService::createTenantBackup($pdo, $companyId, (string)(Tenant::company()['name'] ?? 'Unknown'), $filepath);
                $displayName = basename($filepath);
            }
            $this->logActivity('Created backup: ' . $displayName, 'backup', null, $backupType);
            $this->setFlash('success', 'Backup created successfully! File: ' . $displayName);

        } catch (Exception $e) {
            if (isset($filepath) && file_exists($filepath)) {
                @unlink($filepath);
            }
            error_log('[Backup] Create failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to create backup. Please try again.');
        }

        $this->redirect('index.php?page=backup');
    }

    // =========================================================
    // DOWNLOAD — Serve backup file (tenant-isolated)
    // =========================================================

    public function download() {
        $this->requireFeature('backup_restore');
        $this->requirePermission('backup.manage');

        if (!$this->isPost()) {
            $this->redirect('index.php?page=backup');
            return;
        }

        $this->validateCSRF();

        $file = $this->post('file');
        if (!$file) {
            $this->setFlash('error', 'No file specified.');
            $this->redirect('index.php?page=backup');
            return;
        }

        // Sanitize filename — prevent directory traversal
        $file = basename($file);
        $companyId = $this->activeCompanyId();
        $isSuperAdmin = Session::isSuperAdmin();
        $filepath = $this->resolveVisibleBackupPath($file, $companyId, $isSuperAdmin);

        if (!$filepath || !file_exists($filepath)) {
            Helper::securityLog('BACKUP_DOWNLOAD_BLOCKED', 'File not found or denied: ' . $file);
            $this->setFlash('error', 'Backup file not found or access denied.');
            $this->redirect('index.php?page=backup');
            return;
        }
        // Force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        ob_clean();
        flush();
        readfile($filepath);
        exit;
    }

    // =========================================================
    // DELETE — Remove backup file (tenant-isolated)
    // =========================================================

    public function delete() {
        $this->requireFeature('backup_restore');
        $this->requirePermission('backup.manage');
        if (!$this->isPost()) {
            $this->redirect('index.php?page=backup');
            return;
        }

        $this->validateCSRF();

        $file = $this->post('file');
        if (!$file) {
            $this->setFlash('error', 'No file specified.');
            $this->redirect('index.php?page=backup');
            return;
        }

        $file = basename($file);
        $companyId = $this->activeCompanyId();
        $isSuperAdmin = Session::isSuperAdmin();
        $filepath = $this->resolveVisibleBackupPath($file, $companyId, $isSuperAdmin);

        if (!$filepath || !file_exists($filepath)) {
            Helper::securityLog('BACKUP_DELETE_BLOCKED', 'File not found or denied: ' . $file);
            $this->setFlash('error', 'Backup file not found or access denied.');
            $this->redirect('index.php?page=backup');
            return;
        }

        if (unlink($filepath)) {
            BackupService::deleteManifest($filepath);
            $this->logActivity('Deleted backup: ' . $file, 'backup', null, $file);
            CSRF::rotateToken();
            $this->setFlash('success', 'Backup file deleted successfully.');
        } else {
            Helper::securityLog('BACKUP_DELETE_FAILED', 'unlink() failed for: ' . $filepath);
            $this->setFlash('error', 'Failed to delete backup file.');
        }
        $this->redirect('index.php?page=backup');
    }

    // =========================================================
    // RESTORE — Super-admin only (full DB restore)
    // =========================================================

    public function restore() {
        $this->requireFeature('backup_restore');
        $this->requirePermission('backup.manage');
        // SECURITY: Restore is super-admin ONLY — it affects all tenants
        $this->requireSuperAdmin();

        if (!$this->isPost()) {
            $this->redirect('index.php?page=backup');
            return;
        }

        $this->validateCSRF();

        $restoreLockHandle = null;

        try {
            $restoreLockHandle = $this->acquireRestoreLock();
            $source = $this->post('restore_source'); // 'upload' or 'existing'
            $sqlContent = '';
            $restoreLabel = 'uploaded file';

            if ($source === 'existing') {
                $file = basename($this->post('backup_file'));
                // Only allow restoring from known full backup directories
                $filepath = $this->resolveFullBackupPath($file);

                if (!$filepath || !file_exists($filepath)) {
                    throw new Exception("Backup file not found in full backup directory.");
                }

                if (filesize($filepath) > self::MAX_RESTORE_FILE_BYTES) {
                    throw new Exception("Backup file too large. Maximum size is 50MB.");
                }

                $integrity = BackupService::verifyIntegrity($filepath);
                if (!($integrity['ok'] ?? false) && ($integrity['reason'] ?? '') === 'Backup manifest not found.') {
                    $integrity = BackupService::backfillManifestForExistingBackup($filepath, [
                        'backup_type' => 'full',
                        'company_id' => null,
                        'company_name' => null,
                    ]);
                }
                if (!($integrity['ok'] ?? false)) {
                    $reason = (string)($integrity['reason'] ?? 'Integrity verification failed.');
                    Helper::securityLog('BACKUP_RESTORE_BLOCKED', 'Integrity check failed for ' . $file . ': ' . $reason);
                    throw new Exception('Backup integrity check failed. ' . $reason);
                }

                $sqlContent = file_get_contents($filepath);
                $restoreLabel = $file;
            } else {
                if (empty($_FILES['backup_file']['tmp_name']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Please upload a valid SQL backup file.");
                }
                $uploadedFile = $_FILES['backup_file'];
                $ext = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

                if ($ext !== 'sql') {
                    throw new Exception("Only .sql files are allowed.");
                }

                // Max 50MB
                if ($uploadedFile['size'] > self::MAX_RESTORE_FILE_BYTES) {
                    throw new Exception("File too large. Maximum size is 50MB.");
                }

                $sqlContent = file_get_contents($uploadedFile['tmp_name']);
                $restoreLabel = $_FILES['backup_file']['name'] ?? 'uploaded file';
            }

            if (empty(trim($sqlContent))) {
                throw new Exception("The backup file is empty.");
            }

            $db = Database::getInstance();
            $pdo = $db->getConnection();
            $statements = $this->validateRestoreSql($sqlContent);

            // Safety net: capture the current live database before destructive restore.
            $preRestorePath = $this->getFullBackupDir() . '/pre_restore_' . date('Y-m-d_H-i-s') . '.sql';
            BackupService::ensureDir(dirname($preRestorePath));
            BackupService::createFullBackup($pdo, $preRestorePath);

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            }

            // SECURITY: Execute SQL safely — pre-validated statements only.
            $executed = $this->executeRestoreStatements($pdo, $statements);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            $this->logActivity('Restored full database from backup', 'backup', null, $restoreLabel);
            CSRF::rotateToken();
            $this->setFlash('success', 'Database restored successfully. Safety backup saved as ' . basename($preRestorePath) . '. You may need to re-login.');

        } catch (Exception $e) {
            try {
                $pdo = Database::getInstance()->getConnection();
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Exception $ex) {}

            error_log('[Backup] Restore failed: ' . $e->getMessage());
            $this->setFlash('error', 'Restore failed: ' . $e->getMessage());
        } finally {
            $this->releaseRestoreLock($restoreLockHandle);
        }
        $this->redirect('index.php?page=backup');
    }

    // =========================================================
    // PRIVATE: Safe SQL Restore (Statement-by-Statement with Blocklist)
    // =========================================================

    /**
     * Execute SQL restore safely by scanning for dangerous patterns
     * and running statements one at a time.
     *
     * SECURITY: Blocks GRANT, REVOKE, DROP DATABASE, CREATE USER,
     * INTO OUTFILE/DUMPFILE, LOAD_FILE, and shell-related commands.
     *
     * @param  string $sqlContent Raw SQL content from backup file
     * @return array<int, string> Number of statements executed
     * @throws \RuntimeException if prohibited SQL is detected
     */
    private function validateRestoreSql(string $sqlContent): array {
        return $this->backupRestoreService->validateRestoreSql($sqlContent);
    }

    private function executeRestoreStatements(\PDO $pdo, array $statements): int {
        return $this->backupRestoreService->executeRestoreStatements($pdo, $statements);
    }

    /**
     * Split SQL content into executable statements while respecting quoted
     * strings and skipping line/block comments.
     *
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sqlContent): array {
        return $this->backupRestoreService->splitSqlStatements($sqlContent);
    }
    // =========================================================
    // PRIVATE: Tenant-Scoped Backup (Logical Export)
    // =========================================================

    /**
     * Create a per-company backup containing ONLY the current tenant's data.
     * Uses prepared statements for company_id filtering and streams output.
     *
     * @param PDO    $pdo       Database connection
     * @param int    $companyId Company to export
     * @param string $filepath  Output file path
     */
    // =========================================================
    // PRIVATE: File Path Helpers (Tenant Isolation)
    // =========================================================

    /**
     * Get per-tenant backup directory.
     * Each company's backups are stored in a separate subdirectory
     * to prevent cross-tenant file access.
     */
    private function getTenantBackupDir($companyId) {
        return $this->backupDir . '/company_' . (int)$companyId;
    }
    /**
     * Get full backup directory (super-admin only).
     */
    private function getFullBackupDir() {
        return $this->backupDir . '/full';
    }

    /**
     * Legacy full-backup directory from older deployments.
     */
    private function getLegacyFullBackupDir() {
        return $this->legacyBackupRoot() . '/full';
    }

    /**
     * Ensure a directory exists.
     */
    /**
     * Legacy upload-based backup location kept for restore compatibility.
     */
    private function legacyBackupRoot(): string {
        return BASE_PATH . '/uploads/backups';
    }

    /**
     * Resolve a filename to an absolute path, ensuring the current user
     * has access rights. Returns null if access is denied.
     *
     * @param string $filename  Sanitized basename
     * @param int    $companyId Current tenant
     * @param bool   $isSuperAdmin
     * @return string|null  Absolute path or null
     */
    private function resolveFilePath($filename, $companyId, $isSuperAdmin) {
        if (!$this->isValidBackupFilename($filename)) {
            return null;
        }

        // Check tenant backup directory first
        if ((int)$companyId > 0) {
            $tenantPath = $this->getTenantBackupDir($companyId) . '/' . $filename;
            $tenantPath = $this->assertManagedBackupPath($tenantPath);
            if ($tenantPath !== null) {
                return $tenantPath;
            }
        }

        // Check legacy root backup directory (pre-migration backups)
        $legacyRoot = $this->legacyBackupRoot();
        $legacyPath = $legacyRoot . '/' . $filename;
        $legacyPath = $this->assertManagedBackupPath($legacyPath);
        if ($legacyPath !== null && $isSuperAdmin) {
            return $legacyPath;
        }

        // Check full backup directory (super-admin only)
        if ($isSuperAdmin) {
            $fullPath = $this->getFullBackupDir() . '/' . $filename;
            $fullPath = $this->assertManagedBackupPath($fullPath);
            if ($fullPath !== null) {
                return $fullPath;
            }

            $legacyFullPath = $this->getLegacyFullBackupDir() . '/' . $filename;
            $legacyFullPath = $this->assertManagedBackupPath($legacyFullPath);
            if ($legacyFullPath !== null) {
                return $legacyFullPath;
            }
        }
        return null;
    }

    /**
     * Resolve a backup file strictly from the current user's visible backup list.
     * This keeps delete/download behavior aligned with what the UI actually shows.
     */
    private function resolveVisibleBackupPath(string $filename, int $companyId, bool $isSuperAdmin): ?string {
        return $this->backupManagementService()->resolveVisibleBackupPath($filename, $companyId, $isSuperAdmin);
    }
    // =========================================================
    // PRIVATE: Backup Listing (Tenant-Scoped)
    // =========================================================

    /**
     * Get the backup list visible to the current user.
     * Regular users see only their company's backups.
     * Super-admins additionally see full platform backups.
     */
    private function getBackupList($companyId, $isSuperAdmin) {
        return $this->backupManagementService()->getBackupList((int)$companyId, (bool)$isSuperAdmin);
    }

    /**
     * Scan a directory for .sql files and append to the results array.
     */
    private function scanBackupDir($dir, &$backups, $type) {
        return;
    }

    /**
     * Resolve full-backup files from current and legacy locations.
     */
    private function resolveFullBackupPath(string $file): ?string {
        return $this->backupManagementService()->resolveFullBackupPath($file);
    }

    private function isValidBackupFilename(string $filename): bool {
        return $this->backupManagementService()->isValidBackupFilename($filename);
    }

    private function assertManagedBackupPath(string $path): ?string {
        return $this->backupManagementService()->assertManagedBackupPath($path);
    }

    private function allowedBackupRoots(): array {
        return array_values(array_unique([
            $this->backupDir,
            $this->getFullBackupDir(),
            $this->legacyBackupRoot(),
            $this->getLegacyFullBackupDir(),
        ]));
    }

    private function hasPendingBackupJob(int $companyId, string $backupType): bool {
        try {
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT id, payload
                 FROM jobs
                 WHERE queue = 'backup'
                   AND status IN ('pending', 'processing')
                 ORDER BY created_at DESC
                 LIMIT 25"
            );

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $job) {
                $payload = json_decode((string)($job['payload'] ?? '{}'), true) ?: [];
                $jobCompanyId = (int)($payload['company_id'] ?? 0);
                $jobType = strtolower(trim((string)($payload['backup_type'] ?? 'tenant')));

                if ($jobType === $backupType && $jobCompanyId === $companyId) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            error_log('[Backup] Pending job check failed: ' . $e->getMessage());
        }

        return false;
    }

    private function acquireRestoreLock() {
        $lockPath = $this->backupDir . '/restore.lock';
        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            throw new \RuntimeException('Unable to initialize restore lock.');
        }

        if (!@flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new \RuntimeException('Another restore operation is already running.');
        }

        ftruncate($handle, 0);
        fwrite($handle, (string)getmypid());
        fflush($handle);

        return $handle;
    }

    private function releaseRestoreLock($handle): void {
        if (!is_resource($handle)) {
            return;
        }

        @flock($handle, LOCK_UN);
        fclose($handle);
    }
    // =========================================================
    // PRIVATE: Utility
    // =========================================================

    /**
     * Get statistics about the current tenant's data.
     */
    private function getTenantStats($db, $companyId) {
        return $this->backupManagementService()->getTenantStats($db, (int)$companyId);
    }

    private function backupManagementService(): BackupManagementService {
        if ($this->backupManagementService === null) {
            $this->backupManagementService = new BackupManagementService(
                $this->backupDir,
                $this->legacyBackupRoot(),
                $this->getFullBackupDir(),
                $this->getLegacyFullBackupDir(),
                self::$tenantTables
            );
        }

        return $this->backupManagementService;
    }

    private function activeCompanyId(): int {
        return Session::isSuperAdmin() ? (int)(Tenant::id() ?? 0) : Tenant::require();
    }
}
