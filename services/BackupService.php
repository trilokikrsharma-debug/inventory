<?php
/**
 * Shared backup generation service for tenant and full SQL exports.
 */
class BackupService {
    private const MANIFEST_VERSION = 1;
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

    public static function resolveBackupRoot(): string {
        $candidates = [
            BASE_PATH . '/uploads/backups',
            dirname(dirname(BASE_PATH)) . '/inventory_backups',
            rtrim(sys_get_temp_dir(), '\\/') . '/invenbill_backups',
        ];

        foreach ($candidates as $candidate) {
            try {
                if (!is_dir($candidate) && !@mkdir($candidate, 0755, true) && !is_dir($candidate)) {
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

    public static function ensureDir(string $dir): void {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create backup directory: ' . $dir);
        }
        @chmod($dir, 0775);
    }

    public static function createTenantBackup(\PDO $pdo, int $companyId, string $companyName, string $filePath): void {
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
            self::writeManifest($filePath, [
                'backup_type' => 'tenant',
                'company_id' => $companyId,
                'company_name' => $companyName,
            ]);
        } catch (\Throwable $e) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            self::deleteManifest($filePath);
            throw $e;
        }
    }

    public static function createFullBackup(\PDO $pdo, string $filePath): void {
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
            self::writeManifest($filePath, [
                'backup_type' => 'full',
                'company_id' => null,
                'company_name' => null,
            ]);
        } catch (\Throwable $e) {
            if (is_resource($fp)) {
                fclose($fp);
            }
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            self::deleteManifest($filePath);
            throw $e;
        }
    }

    public static function manifestPath(string $filePath): string {
        return $filePath . '.manifest.json';
    }

    public static function deleteManifest(string $filePath): void {
        $manifestPath = self::manifestPath($filePath);
        if (is_file($manifestPath)) {
            @unlink($manifestPath);
        }
    }

    public static function readManifest(string $filePath): ?array {
        $manifestPath = self::manifestPath($filePath);
        if (!is_file($manifestPath) || !is_readable($manifestPath)) {
            return null;
        }

        $json = @file_get_contents($manifestPath);
        if ($json === false || trim($json) === '') {
            return null;
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function verifyIntegrity(string $filePath): array {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return ['ok' => false, 'reason' => 'Backup file is missing or unreadable.'];
        }

        $manifest = self::readManifest($filePath);
        if ($manifest === null) {
            return ['ok' => false, 'reason' => 'Backup manifest not found.'];
        }

        $expectedChecksum = (string)($manifest['checksum_sha256'] ?? '');
        $expectedSize = (int)($manifest['file_size_bytes'] ?? -1);
        if ($expectedChecksum === '' || $expectedSize < 0) {
            return ['ok' => false, 'reason' => 'Backup manifest is incomplete.'];
        }

        $actualSize = (int)filesize($filePath);
        if ($actualSize !== $expectedSize) {
            return ['ok' => false, 'reason' => 'Backup file size does not match manifest.'];
        }

        $actualChecksum = hash_file('sha256', $filePath);
        if (!is_string($actualChecksum) || !hash_equals($expectedChecksum, $actualChecksum)) {
            return ['ok' => false, 'reason' => 'Backup checksum verification failed.'];
        }

        return ['ok' => true, 'manifest' => $manifest];
    }

    public static function backfillManifestForExistingBackup(string $filePath, array $meta = []): array {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException('Backup file is missing or unreadable.');
        }

        self::writeManifest($filePath, [
            'backup_type' => $meta['backup_type'] ?? 'full',
            'company_id' => $meta['company_id'] ?? null,
            'company_name' => $meta['company_name'] ?? null,
        ]);

        return self::verifyIntegrity($filePath);
    }

    private static function tableHasColumn(\PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function writeManifest(string $filePath, array $meta): void {
        $manifestPath = self::manifestPath($filePath);
        $manifest = [
            'manifest_version' => self::MANIFEST_VERSION,
            'file_name' => basename($filePath),
            'file_size_bytes' => (int)filesize($filePath),
            'checksum_sha256' => hash_file('sha256', $filePath),
            'generated_at_utc' => gmdate('Y-m-d H:i:s'),
            'app_env' => defined('APP_ENV') ? APP_ENV : 'unknown',
            'backup_type' => $meta['backup_type'] ?? 'unknown',
            'company_id' => $meta['company_id'] ?? null,
            'company_name' => $meta['company_name'] ?? null,
        ];

        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded) || @file_put_contents($manifestPath, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write backup manifest.');
        }

        @chmod($manifestPath, 0664);
    }
}
