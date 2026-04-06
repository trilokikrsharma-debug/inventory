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

    /**
     * System-level tables that should NOT be included in tenant exports.
     * These are only exported in super-admin full backups.
     */
    private static $systemOnlyTables = [
        'companies', 'roles', 'permissions', 'role_permissions', 'migrations',
    ];

    public function __construct() {
        $this->backupDir = BackupService::resolveBackupRoot();
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
     * @param  PDO    $pdo        Database connection
     * @param  string $sqlContent Raw SQL content from backup file
     * @return int    Number of statements executed
     * @throws \RuntimeException if prohibited SQL is detected
     */
    private function validateRestoreSql(string $sqlContent): array {
        // Blocklist: patterns that should NEVER appear in a legitimate backup
        $blocked = [
            '/\bGRANT\b/i',
            '/\bREVOKE\b/i',
            '/\bINTO\s+OUTFILE\b/i',
            '/\bINTO\s+DUMPFILE\b/i',
            '/\bLOAD_FILE\s*\(/i',
            '/\bDROP\s+DATABASE\b/i',
            '/\bCREATE\s+USER\b/i',
            '/\bALTER\s+USER\b/i',
            '/\bSET\s+PASSWORD\b/i',
            '/\bSYSTEM\s*\(/i',
            '/\bSHELL\b/i',
            '/\bDEFINER\s*=/i',
            '/\bCREATE\s+TRIGGER\b/i',
            '/\bCREATE\s+PROCEDURE\b/i',
            '/\bCREATE\s+FUNCTION\b/i',
            '/\bCREATE\s+EVENT\b/i',
            '/\bTRUNCATE\s+TABLE\b/i',
        ];

        foreach ($blocked as $pattern) {
            if (preg_match($pattern, $sqlContent)) {
                Helper::securityLog('RESTORE_BLOCKED', 'Prohibited SQL pattern detected: ' . $pattern);
                throw new \RuntimeException('Restore blocked: SQL file contains prohibited statements.');
            }
        }

        $statements = $this->splitSqlStatements($sqlContent);
        if (empty($statements)) {
            throw new \RuntimeException('Restore blocked: SQL file does not contain executable statements.');
        }

        $hasSchemaStatement = false;
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') {
                continue;
            }

            $upperStmt = strtoupper(ltrim($stmt));
        $allowedPrefixes = ['CREATE ', 'INSERT ', 'DROP TABLE', 'SET ', 'START ', 'COMMIT', 'ALTER TABLE', 'LOCK ', 'UNLOCK '];
            $isAllowed = false;
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($upperStmt, $prefix)) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                throw new \RuntimeException('Restore blocked: SQL contains unsupported statement type.');
            }

            if (str_starts_with($upperStmt, 'CREATE ') || str_starts_with($upperStmt, 'ALTER TABLE') || str_starts_with($upperStmt, 'DROP TABLE')) {
                $hasSchemaStatement = true;
            }
        }

        if (!$hasSchemaStatement) {
            throw new \RuntimeException('Restore blocked: SQL file does not look like a full schema backup.');
        }

        return $statements;
    }

    private function executeRestoreStatements(\PDO $pdo, array $statements): int {
        $executed = 0;
        foreach ($statements as $stmt) {
            $stmt = trim((string)$stmt);
            if ($stmt === '') {
                continue;
            }

            $pdo->exec($stmt);
            $executed++;
        }

        return $executed;
    }

    /**
     * Split SQL content into executable statements while respecting quoted
     * strings and skipping line/block comments.
     *
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sqlContent): array {
        $statements = [];
        $buffer = '';
        $length = strlen($sqlContent);
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sqlContent[$i];
            $next = $i + 1 < $length ? $sqlContent[$i + 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                }
                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            if (!$inSingle && !$inDouble && !$inBacktick) {
                if ($char === '-' && $next === '-') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
                if ($char === '#') {
                    $inLineComment = true;
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $i++;
                    continue;
                }
            }

            if ($inSingle) {
                $buffer .= $char;
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === "'") {
                    $inSingle = false;
                }
                continue;
            }

            if ($inDouble) {
                $buffer .= $char;
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inDouble = false;
                }
                continue;
            }

            if ($inBacktick) {
                $buffer .= $char;
                if ($char === '`') {
                    $inBacktick = false;
                }
                continue;
            }

            if ($char === "'") {
                $inSingle = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '"') {
                $inDouble = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '`') {
                $inBacktick = true;
                $buffer .= $char;
                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
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
        $filename = basename($filename);
        if (!$this->isValidBackupFilename($filename)) {
            return null;
        }
        $backups = $this->getBackupList($companyId, $isSuperAdmin);

        foreach ($backups as $backup) {
            $candidateName = basename((string)($backup['filename'] ?? ''));
            $candidatePath = (string)($backup['path'] ?? '');

            if ($candidateName !== $filename || $candidatePath === '') {
                continue;
            }

            $candidatePath = $this->assertManagedBackupPath($candidatePath);
            if ($candidatePath !== null) {
                return $candidatePath;
            }
        }

        return $this->resolveFilePath($filename, $companyId, $isSuperAdmin);
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
        $backups = [];

        // Always include tenant-specific backups
        if ((int)$companyId > 0) {
            $tenantDir = $this->getTenantBackupDir($companyId);
            $this->scanBackupDir($tenantDir, $backups, 'tenant');
        }

        // Super-admin: also include full backups and legacy backups
        if ($isSuperAdmin) {
            $this->scanBackupDir($this->getFullBackupDir(), $backups, 'full');

            // Legacy: root-level backup files (from before tenant isolation)
            $legacyRoot = $this->legacyBackupRoot();
            if ($legacyRoot !== $this->backupDir) {
                $legacyFiles = glob($legacyRoot . '/*.sql');
                if ($legacyFiles) {
                    foreach ($legacyFiles as $file) {
                        $backups[] = [
                            'filename' => basename($file),
                            'size'     => filesize($file),
                            'created'  => date('Y-m-d H:i:s', filemtime($file)),
                            'path'     => $file,
                            'type'     => 'legacy',
                        ];
                    }
                }
            }

            if ($this->getLegacyFullBackupDir() !== $this->getFullBackupDir()) {
                $this->scanBackupDir($this->getLegacyFullBackupDir(), $backups, 'legacy_full');
            }
        }

        // Sort newest first
        usort($backups, function($a, $b) {
            return strtotime($b['created']) - strtotime($a['created']);
        });

        return $backups;
    }

    /**
     * Scan a directory for .sql files and append to the results array.
     */
    private function scanBackupDir($dir, &$backups, $type) {
        if (!is_dir($dir)) return;

        $files = glob($dir . '/*.sql');
        if (!$files) return;

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size'     => filesize($file),
                'created'  => date('Y-m-d H:i:s', filemtime($file)),
                'path'     => $file,
                'type'     => $type,
            ];
        }
    }

    /**
     * Resolve full-backup files from current and legacy locations.
     */
    private function resolveFullBackupPath(string $file): ?string {
        if (!$this->isValidBackupFilename($file)) {
            return null;
        }

        $current = $this->getFullBackupDir() . '/' . $file;
        $current = $this->assertManagedBackupPath($current);
        if ($current !== null) {
            return $current;
        }

        $legacy = $this->getLegacyFullBackupDir() . '/' . $file;
        $legacy = $this->assertManagedBackupPath($legacy);
        if ($legacy !== null) {
            return $legacy;
        }

        return null;
    }

    private function isValidBackupFilename(string $filename): bool {
        return (bool)preg_match('/\A[a-zA-Z0-9._-]+\.sql\z/', $filename);
    }

    private function assertManagedBackupPath(string $path): ?string {
        if ($path === '' || !is_file($path)) {
            return null;
        }

        $realPath = realpath($path);
        if ($realPath === false || strtolower(pathinfo($realPath, PATHINFO_EXTENSION)) !== 'sql') {
            return null;
        }

        foreach ($this->allowedBackupRoots() as $root) {
            $realRoot = realpath($root);
            if ($realRoot !== false && str_starts_with($realPath, rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
                return $realPath;
            }
        }

        Helper::securityLog('BACKUP_PATH_BLOCKED', 'Blocked backup path outside allowed roots: ' . $path);
        return null;
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
     * Check if a table has a specific column.
     * Used to verify company_id existence before filtering.
     */
    private function tableHasColumn($pdo, $table, $column) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get statistics about the current tenant's data.
     */
    private function getTenantStats($db, $companyId) {
        if ((int)$companyId <= 0) {
            $pdo = $db->getConnection();
            $tableCount = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetchColumn();
            $dbName = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();

            return [
                'tableCount' => $tableCount,
                'totalRows' => 0,
                'estimatedSize' => 0,
                'label' => ($dbName !== '' ? $dbName : 'Platform Database') . ' (Full Backup)',
            ];
        }

        $pdo = $db->getConnection();
        $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $tableCount = 0;
        $totalRows = 0;

        foreach (self::$tenantTables as $table) {
            if (!in_array($table, $existingTables, true)) continue;
            if (!$this->tableHasColumn($pdo, $table, 'company_id')) continue;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE company_id = ?");
            $stmt->execute([$companyId]);
            $count = (int)$stmt->fetchColumn();
            if ($count > 0) {
                $tableCount++;
                $totalRows += $count;
            }
        }

        // Rough size estimate: avg 200 bytes per row
        $estimatedSize = $totalRows * 200;

        $companyName = Tenant::company()['name'] ?? 'Company #' . $companyId;

        return [
            'tableCount'    => $tableCount,
            'totalRows'     => $totalRows,
            'estimatedSize' => $estimatedSize,
            'label'         => $companyName . ' (Tenant Data)',
        ];
    }

    private function activeCompanyId(): int {
        return Session::isSuperAdmin() ? (int)(Tenant::id() ?? 0) : Tenant::require();
    }
}
