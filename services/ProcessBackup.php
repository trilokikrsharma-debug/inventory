<?php
/**
 * Background job handler for tenant/full SQL backups.
 */
class ProcessBackup {
    /**
     * Tables expected to be tenant-scoped by company_id.
     *
     * @var array<int, string>
     */
    private static array $tenantTables = [
        'products', 'categories', 'brands', 'units',
        'customers', 'suppliers',
        'sales', 'sale_items', 'sale_returns', 'sale_return_items',
        'purchases', 'purchase_items',
        'payments', 'quotations', 'quotation_items',
        'stock_history', 'activity_log',
        'users', 'company_settings',
    ];

    public static function handle(array $payload, array $job = []): void {
        $companyId = (int)($payload['company_id'] ?? $job['company_id'] ?? 0);
        $backupType = strtolower(trim((string)($payload['backup_type'] ?? 'tenant')));
        $isSuperAdmin = (bool)($payload['is_super_admin'] ?? false);
        $userId = (int)($payload['user_id'] ?? 0);

        if ($companyId <= 0) {
            throw new \RuntimeException('Invalid tenant context for backup job.');
        }

        if ($backupType === 'full' && !$isSuperAdmin) {
            throw new \RuntimeException('Full backups are restricted to super-admin users.');
        }

        $backupRoot = self::resolveBackupRoot();
        self::ensureDir($backupRoot);
        self::ensureDir($backupRoot . '/full');
        self::ensureDir($backupRoot . '/company_' . $companyId);

        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $timestamp = date('Y-m-d_H-i-s');

        if ($backupType === 'full') {
            $filePath = $backupRoot . '/full/full_backup_' . $timestamp . '.sql';
            self::createFullBackup($pdo, $filePath);
        } else {
            $companyName = self::resolveCompanyName($companyId);
            $filePath = $backupRoot . '/company_' . $companyId . '/company_' . $companyId . '_backup_' . $timestamp . '.sql';
            self::createTenantBackup($pdo, $companyId, $companyName, $filePath);
        }

        self::logBackupActivity(
            $companyId,
            $userId,
            $backupType,
            basename($filePath)
        );
    }

    private static function createTenantBackup(\PDO $pdo, int $companyId, string $companyName, string $filePath): void {
        $fp = fopen($filePath, 'wb');
        if ($fp === false) {
            throw new \RuntimeException('Unable to open backup file for writing.');
        }

        try {
            fwrite($fp, "-- ================================================\n");
            fwrite($fp, "-- InvenBill Pro - Tenant Backup\n");
            fwrite($fp, "-- Company: {$companyName} (ID: {$companyId})\n");
            fwrite($fp, "-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- ================================================\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

            $existingTables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            foreach (self::$tenantTables as $table) {
                if (!in_array($table, $existingTables, true)) {
                    continue;
                }
                if (!self::tableHasColumn($pdo, $table, 'company_id')) {
                    continue;
                }

                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE company_id = ?");
                $countStmt->execute([$companyId]);
                $totalRows = (int)$countStmt->fetchColumn();

                fwrite($fp, "-- Table: {$table} (rows: {$totalRows})\n");
                if ($totalRows === 0) {
                    fwrite($fp, "-- (no data)\n\n");
                    continue;
                }

                $colStmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE company_id = ? LIMIT 1");
                $colStmt->execute([$companyId]);
                $firstRow = $colStmt->fetch(\PDO::FETCH_ASSOC);
                if (!$firstRow) {
                    continue;
                }

                $columns = array_keys($firstRow);
                $columnList = implode('`, `', $columns);
                $chunkSize = 200;
                $offset = 0;

                while ($offset < $totalRows) {
                    $dataStmt = $pdo->prepare(
                        "SELECT * FROM `{$table}` WHERE company_id = ? ORDER BY id LIMIT ? OFFSET ?"
                    );
                    $dataStmt->bindValue(1, $companyId, \PDO::PARAM_INT);
                    $dataStmt->bindValue(2, $chunkSize, \PDO::PARAM_INT);
                    $dataStmt->bindValue(3, $offset, \PDO::PARAM_INT);
                    $dataStmt->execute();
                    $rows = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);
                    if (empty($rows)) {
                        break;
                    }

                    fwrite($fp, "INSERT INTO `{$table}` (`{$columnList}`) VALUES\n");
                    $valueRows = [];
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($row as $value) {
                            $values[] = ($value === null) ? 'NULL' : $pdo->quote((string)$value);
                        }
                        $valueRows[] = '(' . implode(', ', $values) . ')';
                    }
                    fwrite($fp, implode(",\n", $valueRows) . ";\n\n");
                    $offset += $chunkSize;
                }
            }

            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($fp, "-- End of tenant backup\n");
            fclose($fp);
        } catch (\Throwable $e) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            throw $e;
        }
    }

    private static function createFullBackup(\PDO $pdo, string $filePath): void {
        $fp = fopen($filePath, 'wb');
        if ($fp === false) {
            throw new \RuntimeException('Unable to open backup file for writing.');
        }

        try {
            $dbConfig = require CONFIG_PATH . '/database.php';

            fwrite($fp, "-- ================================================\n");
            fwrite($fp, "-- InvenBill Pro - FULL Backup\n");
            fwrite($fp, "-- Database: " . ($dbConfig['database'] ?? 'unknown') . "\n");
            fwrite($fp, "-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- ================================================\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($fp, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($fp, "SET AUTOCOMMIT = 0;\n");
            fwrite($fp, "START TRANSACTION;\n\n");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                fwrite($fp, ($createStmt['Create Table'] ?? '') . ";\n\n");

                $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                if ($count <= 0) {
                    continue;
                }

                $firstRow = $pdo->query("SELECT * FROM `{$table}` LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
                if (!$firstRow) {
                    continue;
                }

                $columns = array_keys($firstRow);
                $columnList = implode('`, `', $columns);
                $chunkSize = 100;
                $offset = 0;
                while ($offset < $count) {
                    $rows = $pdo->query("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}")
                        ->fetchAll(\PDO::FETCH_ASSOC);
                    if (empty($rows)) {
                        break;
                    }

                    fwrite($fp, "INSERT INTO `{$table}` (`{$columnList}`) VALUES\n");
                    $valueRows = [];
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($row as $value) {
                            $values[] = ($value === null) ? 'NULL' : $pdo->quote((string)$value);
                        }
                        $valueRows[] = '(' . implode(', ', $values) . ')';
                    }
                    fwrite($fp, implode(",\n", $valueRows) . ";\n\n");
                    $offset += $chunkSize;
                }
            }

            fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($fp, "COMMIT;\n");
            fwrite($fp, "-- End of full backup\n");
            fclose($fp);
        } catch (\Throwable $e) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            throw $e;
        }
    }

    private static function tableHasColumn(\PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function resolveCompanyName(int $companyId): string {
        try {
            $row = Database::getInstance()->query(
                "SELECT name FROM companies WHERE id = ? LIMIT 1",
                [$companyId]
            )->fetch();
            if (!empty($row['name'])) {
                return (string)$row['name'];
            }
        } catch (\Throwable $e) {
            // Fall through to default name.
        }
        return 'Company #' . $companyId;
    }

    private static function resolveBackupRoot(): string {
        $candidates = [
            dirname(dirname(BASE_PATH)) . '/inventory_backups',
            rtrim(sys_get_temp_dir(), '\\/') . '/invenbill_backups',
            BASE_PATH . '/uploads/backups',
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

        return BASE_PATH . '/uploads/backups';
    }

    private static function ensureDir(string $dir): void {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create backup directory: ' . $dir);
        }
    }

    private static function logBackupActivity(int $companyId, int $userId, string $backupType, string $fileName): void {
        try {
            Database::getInstance()->query(
                "INSERT INTO activity_log (company_id, user_id, action, module, details, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $companyId,
                    $userId > 0 ? $userId : null,
                    'Created backup: ' . $fileName,
                    'backup',
                    json_encode(['type' => $backupType, 'file' => $fileName]),
                    'queue-worker',
                ]
            );
        } catch (\Throwable $e) {
            error_log('[BACKUP_JOB] Failed to write activity log: ' . $e->getMessage());
        }
    }
}
