<?php
class BackupManagementService {
    private string $backupDir;
    private string $legacyBackupRoot;
    private string $fullBackupDir;
    private string $legacyFullBackupDir;

    /**
     * @var array<int, string>
     */
    private array $tenantTables;

    /**
     * @param array<int, string> $tenantTables
     */
    public function __construct(
        string $backupDir,
        string $legacyBackupRoot,
        string $fullBackupDir,
        string $legacyFullBackupDir,
        array $tenantTables
    ) {
        $this->backupDir = $backupDir;
        $this->legacyBackupRoot = $legacyBackupRoot;
        $this->fullBackupDir = $fullBackupDir;
        $this->legacyFullBackupDir = $legacyFullBackupDir;
        $this->tenantTables = $tenantTables;
    }

    public function resolveVisibleBackupPath(string $filename, int $companyId, bool $isSuperAdmin): ?string {
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

    public function resolveFullBackupPath(string $file): ?string {
        if (!$this->isValidBackupFilename($file)) {
            return null;
        }

        $current = $this->assertManagedBackupPath($this->fullBackupDir . '/' . $file);
        if ($current !== null) {
            return $current;
        }

        return $this->assertManagedBackupPath($this->legacyFullBackupDir . '/' . $file);
    }

    public function getBackupList(int $companyId, bool $isSuperAdmin): array {
        $backups = [];

        if ($companyId > 0) {
            $this->scanBackupDir($this->backupDir . '/company_' . $companyId, $backups, 'tenant');
        }

        if ($isSuperAdmin) {
            $this->scanBackupDir($this->fullBackupDir, $backups, 'full');

            if ($this->legacyBackupRoot !== $this->backupDir) {
                $legacyFiles = glob($this->legacyBackupRoot . '/*.sql');
                if ($legacyFiles) {
                    foreach ($legacyFiles as $file) {
                        $backups[] = [
                            'filename' => basename($file),
                            'size' => filesize($file),
                            'created' => date('Y-m-d H:i:s', filemtime($file)),
                            'path' => $file,
                            'type' => 'legacy',
                        ];
                    }
                }
            }

            if ($this->legacyFullBackupDir !== $this->fullBackupDir) {
                $this->scanBackupDir($this->legacyFullBackupDir, $backups, 'legacy_full');
            }
        }

        usort($backups, function ($a, $b) {
            return strtotime((string)$b['created']) - strtotime((string)$a['created']);
        });

        return $backups;
    }

    public function isValidBackupFilename(string $filename): bool {
        return (bool)preg_match('/\A[a-zA-Z0-9._-]+\.sql\z/', $filename);
    }

    public function assertManagedBackupPath(string $path): ?string {
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

    /**
     * @return array<string, mixed>
     */
    public function getTenantStats($db, int $companyId): array {
        if ($companyId <= 0) {
            $pdo = $db->getConnection();
            $tableCount = count($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN));
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

        foreach ($this->tenantTables as $table) {
            if (!in_array($table, $existingTables, true)) {
                continue;
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE company_id = ?");
            $stmt->execute([$companyId]);
            $count = (int)$stmt->fetchColumn();
            if ($count > 0) {
                $tableCount++;
                $totalRows += $count;
            }
        }

        return [
            'tableCount' => $tableCount,
            'totalRows' => $totalRows,
            'estimatedSize' => $totalRows * 200,
            'label' => (Tenant::company()['name'] ?? ('Company #' . $companyId)) . ' (Tenant Data)',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function allowedBackupRoots(): array {
        return array_values(array_unique([
            $this->backupDir,
            $this->fullBackupDir,
            $this->legacyBackupRoot,
            $this->legacyFullBackupDir,
        ]));
    }

    private function resolveFilePath(string $filename, int $companyId, bool $isSuperAdmin): ?string {
        if (!$this->isValidBackupFilename($filename)) {
            return null;
        }

        if ($companyId > 0) {
            $tenantPath = $this->assertManagedBackupPath($this->backupDir . '/company_' . $companyId . '/' . $filename);
            if ($tenantPath !== null) {
                return $tenantPath;
            }
        }

        $legacyPath = $this->assertManagedBackupPath($this->legacyBackupRoot . '/' . $filename);
        if ($legacyPath !== null && $isSuperAdmin) {
            return $legacyPath;
        }

        if ($isSuperAdmin) {
            $fullPath = $this->assertManagedBackupPath($this->fullBackupDir . '/' . $filename);
            if ($fullPath !== null) {
                return $fullPath;
            }

            $legacyFullPath = $this->assertManagedBackupPath($this->legacyFullBackupDir . '/' . $filename);
            if ($legacyFullPath !== null) {
                return $legacyFullPath;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $backups
     */
    private function scanBackupDir(string $dir, array &$backups, string $type): void {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*.sql');
        if (!$files) {
            return;
        }

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created' => date('Y-m-d H:i:s', filemtime($file)),
                'path' => $file,
                'type' => $type,
            ];
        }
    }
}
