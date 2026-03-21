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
        $this->backupDir = $this->resolveBackupRoot();
        $this->ensureDir($this->backupDir);
        $this->ensureDir($this->getFullBackupDir());
    }

    // =========================================================
    // INDEX — Show backup page
    // =========================================================

    public function index() {
        $this->requireFeature('backup');
        $this->requirePermission('backup.manage');

        $companyId = Tenant::require();
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
        $this->requireFeature('backup');
        $this->requirePermission('backup.manage');

        if (!$this->isPost()) {
            $this->redirect('index.php?page=backup');
            return;
        }

        $this->validateCSRF();

        $companyId = Tenant::require();
        $isSuperAdmin = Session::isSuperAdmin();
        $backupType = $this->post('backup_type', 'tenant'); // 'tenant' or 'full'

        // SECURITY: Only super-admin can create full backups
        if ($backupType === 'full') {
            $this->requireSuperAdmin();
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
                $this->ensureDir(dirname($filepath));
                $this->createFullBackup($pdo, $filepath);
                $displayName = basename($filepath);
            } else {
                $filepath = $this->getTenantBackupDir($companyId) . '/company_' . $companyId . '_backup_' . $timestamp . '.sql';
                $this->ensureDir(dirname($filepath));
                $this->createTenantBackup($pdo, $companyId, $filepath);
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
        $this->requirePermission('backup.manage');

        $file = $this->get('file');
        if (!$file) {
            $this->setFlash('error', 'No file specified.');
            $this->redirect('index.php?page=backup');
            return;
        }

        // Sanitize filename — prevent directory traversal
        $file = basename($file);
        $filepath = $this->resolveFilePath($file, Tenant::require(), Session::isSuperAdmin());

        if (!$filepath || !file_exists($filepath)) {
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
        $filepath = $this->resolveFilePath($file, Tenant::require(), Session::isSuperAdmin());

        if (!$filepath || !file_exists($filepath)) {
            $this->setFlash('error', 'Backup file not found or access denied.');
            $this->redirect('index.php?page=backup');
            return;
        }

        if (unlink($filepath)) {
            $this->logActivity('Deleted backup: ' . $file, 'backup', null, $file);
            $this->setFlash('success', 'Backup file deleted successfully.');
        } else {
            $this->setFlash('error', 'Failed to delete backup file.');
        }

        $this->redirect('index.php?page=backup');
    }

    // =========================================================
    // RESTORE — Super-admin only (full DB restore)
    // =========================================================

    public function restore() {
        $this->requirePermission('backup.manage');

        // SECURITY: Restore is super-admin ONLY — it affects all tenants
        $this->requireSuperAdmin();

        if (!$this->isPost()) {
            $this->redirect('index.php?page=backup');
            return;
        }

        $this->validateCSRF();

        try {
            $source = $this->post('restore_source'); // 'upload' or 'existing'
            $sqlContent = '';

            if ($source === 'existing') {
                $file = basename($this->post('backup_file'));
                // Only allow restoring from known full backup directories
                $filepath = $this->resolveFullBackupPath($file);

                if (!$filepath || !file_exists($filepath)) {
                    throw new Exception("Backup file not found in full backup directory.");
                }

                $sqlContent = file_get_contents($filepath);
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
                if ($uploadedFile['size'] > 50 * 1024 * 1024) {
                    throw new Exception("File too large. Maximum size is 50MB.");
                }

                $sqlContent = file_get_contents($uploadedFile['tmp_name']);
            }

            if (empty(trim($sqlContent))) {
                throw new Exception("The backup file is empty.");
            }

            $db = Database::getInstance();
            $pdo = $db->getConnection();

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            }

            // SECURITY: Execute SQL safely — block dangerous patterns, run statement-by-statement
            $executed = $this->executeSafeRestore($pdo, $sqlContent);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            $this->logActivity('Restored full database from backup', 'backup', null, $source === 'existing' ? $file : ($_FILES['backup_file']['name'] ?? 'uploaded file'));
            $this->setFlash('success', 'Database restored successfully! You may need to re-login.');

        } catch (Exception $e) {
            try {
                $pdo = Database::getInstance()->getConnection();
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            } catch (Exception $ex) {}

            error_log('[Backup] Restore failed: ' . $e->getMessage());
            $this->setFlash('error', 'Restore failed: ' . $e->getMessage());
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
    private function executeSafeRestore(\PDO $pdo, string $sqlContent): int {
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
        ];

        foreach ($blocked as $pattern) {
            if (preg_match($pattern, $sqlContent)) {
                Helper::securityLog('RESTORE_BLOCKED', 'Prohibited SQL pattern detected: ' . $pattern);
                throw new \RuntimeException('Restore blocked: SQL file contains prohibited statements.');
            }
        }

        // Split by semicolons followed by newlines (preserves multi-line CREATE TABLEs)
        $statements = preg_split('/;\s*\n/', $sqlContent);
        $executed = 0;

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (empty($stmt)) continue;

            // Skip pure comment lines
            // SECURITY: Only allow safe SQL statement types
            $upperStmt = strtoupper(ltrim($stmt));
            $allowedPrefixes = ['CREATE ', 'INSERT ', 'DROP TABLE', 'SET ', 'START ', 'COMMIT', 'ALTER TABLE', 'LOCK ', 'UNLOCK '];
            $isAllowed = false;
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($upperStmt, $prefix)) { $isAllowed = true; break; }
            }
            if (!$isAllowed) {
                error_log('[Backup] Skipped non-allowlisted SQL: ' . substr($stmt, 0, 80));
                continue;
            }

            if (str_starts_with($stmt, '--') || str_starts_with($stmt, '/*')) continue;

            $pdo->exec($stmt);
            $executed++;
        }

        return $executed;
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
    private function createTenantBackup($pdo, $companyId, $filepath) {
        $fp = fopen($filepath, 'w');
        if ($fp === false) {
            throw new Exception("Failed to open backup file for writing.");
        }

        try {
            // Header
            $companyName = Tenant::company()['name'] ?? 'Unknown';
            fwrite($fp, "-- ================================================\n");
            fwrite($fp, "-- InvenBill Pro — Tenant Backup\n");
            fwrite($fp, "-- Company: " . $companyName . " (ID: {$companyId})\n");
            fwrite($fp, "-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- Generated by: InvenBill Pro v" . APP_VERSION . "\n");
            fwrite($fp, "-- Type: Per-Company Logical Export\n");
            fwrite($fp, "-- ================================================\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

            // Get actual tables in the database
            $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach (self::$tenantTables as $table) {
                // Skip tables that don't exist (forward compatibility)
                if (!in_array($table, $existingTables, true)) {
                    continue;
                }

                // Check if table has company_id column
                $hasCompanyId = $this->tableHasColumn($pdo, $table, 'company_id');

                if (!$hasCompanyId) {
                    // Table exists but has no company_id — skip (shouldn't happen for tenant tables)
                    fwrite($fp, "-- Skipped `{$table}` (no company_id column)\n\n");
                    continue;
                }

                fwrite($fp, "-- -------------------------------------------\n");
                fwrite($fp, "-- Table: `{$table}` (company_id = {$companyId})\n");
                fwrite($fp, "-- -------------------------------------------\n\n");

                // Count rows for this tenant (prepared statement)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE company_id = ?");
                $stmt->execute([$companyId]);
                $totalRows = (int)$stmt->fetchColumn();

                if ($totalRows === 0) {
                    fwrite($fp, "-- (no data)\n\n");
                    continue;
                }

                // Get column names
                $colStmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE company_id = ? LIMIT 1");
                $colStmt->execute([$companyId]);
                $firstRow = $colStmt->fetch(PDO::FETCH_ASSOC);
                $columns = array_keys($firstRow);
                $columnList = implode('`, `', $columns);

                // Stream data in chunks of 200 rows
                $chunkSize = 200;
                $offset = 0;

                while ($offset < $totalRows) {
                    $dataStmt = $pdo->prepare(
                        "SELECT * FROM `{$table}` WHERE company_id = ? ORDER BY id LIMIT ? OFFSET ?"
                    );
                    $dataStmt->execute([$companyId, $chunkSize, $offset]);
                    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($rows)) break;

                    fwrite($fp, "INSERT INTO `{$table}` (`{$columnList}`) VALUES\n");
                    $values = [];
                    foreach ($rows as $row) {
                        $rowValues = [];
                        foreach ($row as $value) {
                            $rowValues[] = ($value === null) ? "NULL" : $pdo->quote($value);
                        }
                        $values[] = "(" . implode(", ", $rowValues) . ")";
                    }
                    fwrite($fp, implode(",\n", $values) . ";\n\n");

                    $offset += $chunkSize;
                    unset($rows, $values, $dataStmt);
                }
            }

            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($fp, "\n-- End of tenant backup (Company ID: {$companyId})\n");
            fclose($fp);

        } catch (Exception $e) {
            if (is_resource($fp)) fclose($fp);
            if (file_exists($filepath)) @unlink($filepath);
            throw $e;
        }
    }

    // =========================================================
    // PRIVATE: Full Database Backup (Super-Admin Only)
    // =========================================================

    /**
     * Create a full database backup (all tables, all tenants).
     * Only callable by super-admin.
     */
    private function createFullBackup($pdo, $filepath) {
        $fp = fopen($filepath, 'w');
        if ($fp === false) {
            throw new Exception("Failed to open backup file for writing.");
        }

        try {
            $dbConfig = require CONFIG_PATH . '/database.php';

            fwrite($fp, "-- ================================================\n");
            fwrite($fp, "-- InvenBill Pro — FULL Database Backup\n");
            fwrite($fp, "-- Database: " . $dbConfig['database'] . "\n");
            fwrite($fp, "-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- Generated by: InvenBill Pro v" . APP_VERSION . "\n");
            fwrite($fp, "-- Type: Full Platform Backup (Super Admin)\n");
            fwrite($fp, "-- ================================================\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($fp, "SET AUTOCOMMIT = 0;\n");
            fwrite($fp, "START TRANSACTION;\n\n");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                fwrite($fp, "-- -------------------------------------------\n");
                fwrite($fp, "-- Table: `{$table}`\n");
                fwrite($fp, "-- -------------------------------------------\n");
                fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n\n");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
                fwrite($fp, $createStmt['Create Table'] . ";\n\n");

                $countResult = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                if ($countResult > 0) {
                    $firstRow = $pdo->query("SELECT * FROM `{$table}` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    $columns = array_keys($firstRow);
                    $columnList = implode('`, `', $columns);

                    $chunkSize = 100;
                    $offset = 0;

                    while ($offset < $countResult) {
                        $rows = $pdo->query("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
                        if (empty($rows)) break;

                        fwrite($fp, "INSERT INTO `{$table}` (`{$columnList}`) VALUES\n");
                        $values = [];
                        foreach ($rows as $row) {
                            $rowValues = [];
                            foreach ($row as $value) {
                                $rowValues[] = ($value === null) ? "NULL" : $pdo->quote($value);
                            }
                            $values[] = "(" . implode(", ", $rowValues) . ")";
                        }
                        fwrite($fp, implode(",\n", $values) . ";\n\n");

                        $offset += $chunkSize;
                        unset($rows, $values);
                    }
                }
            }

            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($fp, "COMMIT;\n");
            fwrite($fp, "\n-- End of full backup\n");
            fclose($fp);

        } catch (Exception $e) {
            if (is_resource($fp)) fclose($fp);
            if (file_exists($filepath)) @unlink($filepath);
            throw $e;
        }
    }

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
    private function ensureDir($dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new \RuntimeException('Unable to create backup directory: ' . $dir);
            }
        }
    }

    /**
     * Resolve the safest writable backup root.
     *
     * Preference order:
     *  1. Outside the web root
     *  2. System temp directory
     *  3. Legacy uploads path for compatibility
     */
    private function resolveBackupRoot(): string {
        $candidates = [
            dirname(dirname(BASE_PATH)) . '/inventory_backups',
            rtrim(sys_get_temp_dir(), '\\/') . '/invenbill_backups',
            $this->legacyBackupRoot(),
        ];

        foreach ($candidates as $candidate) {
            try {
                if (!is_dir($candidate) && !mkdir($candidate, 0755, true) && !is_dir($candidate)) {
                    continue;
                }
                if (is_writable($candidate)) {
                    return $candidate;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        // Final fallback keeps the app functional even in constrained environments.
        return $this->legacyBackupRoot();
    }

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
        // Check tenant backup directory first
        $tenantPath = $this->getTenantBackupDir($companyId) . '/' . $filename;
        if (file_exists($tenantPath)) {
            return $tenantPath;
        }

        // Check legacy root backup directory (pre-migration backups)
        $legacyRoot = $this->legacyBackupRoot();
        $legacyPath = $legacyRoot . '/' . $filename;
        if (file_exists($legacyPath) && $isSuperAdmin) {
            return $legacyPath;
        }

        // Check full backup directory (super-admin only)
        if ($isSuperAdmin) {
            $fullPath = $this->getFullBackupDir() . '/' . $filename;
            if (file_exists($fullPath)) {
                return $fullPath;
            }

            $legacyFullPath = $this->getLegacyFullBackupDir() . '/' . $filename;
            if (file_exists($legacyFullPath)) {
                return $legacyFullPath;
            }
        }

        return null;
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
        $tenantDir = $this->getTenantBackupDir($companyId);
        $this->scanBackupDir($tenantDir, $backups, 'tenant');

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
        $current = $this->getFullBackupDir() . '/' . $file;
        if (file_exists($current)) {
            return $current;
        }

        $legacy = $this->getLegacyFullBackupDir() . '/' . $file;
        if (file_exists($legacy)) {
            return $legacy;
        }

        return null;
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
}
