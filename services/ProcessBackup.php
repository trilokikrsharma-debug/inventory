<?php
/**
 * Background job handler for tenant/full SQL backups.
 */
class ProcessBackup {
    public static function handle(array $payload, array $job = []): void {
        $companyId = (int)($payload['company_id'] ?? $job['company_id'] ?? 0);
        $backupType = strtolower(trim((string)($payload['backup_type'] ?? 'tenant')));
        $isSuperAdmin = (bool)($payload['is_super_admin'] ?? false);
        $userId = (int)($payload['user_id'] ?? 0);

        if ($backupType !== 'full' && $companyId <= 0) {
            throw new \RuntimeException('Invalid tenant context for backup job.');
        }

        if ($backupType === 'full' && !$isSuperAdmin) {
            throw new \RuntimeException('Full backups are restricted to super-admin users.');
        }

        $backupRoot = BackupService::resolveBackupRoot();
        BackupService::ensureDir($backupRoot);
        BackupService::ensureDir($backupRoot . '/full');
        if ($companyId > 0) {
            BackupService::ensureDir($backupRoot . '/company_' . $companyId);
        }

        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $timestamp = date('Y-m-d_H-i-s');

        if ($backupType === 'full') {
            $filePath = $backupRoot . '/full/full_backup_' . $timestamp . '.sql';
            BackupService::createFullBackup($pdo, $filePath);
        } else {
            $companyName = self::resolveCompanyName($companyId);
            $filePath = $backupRoot . '/company_' . $companyId . '/company_' . $companyId . '_backup_' . $timestamp . '.sql';
            BackupService::createTenantBackup($pdo, $companyId, $companyName, $filePath);
        }

        self::logBackupActivity(
            $companyId,
            $userId,
            $backupType,
            basename($filePath)
        );
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

    private static function logBackupActivity(int $companyId, int $userId, string $backupType, string $fileName): void {
        try {
            Database::getInstance()->query(
                "INSERT INTO activity_log (company_id, user_id, action, module, details, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $companyId > 0 ? $companyId : null,
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
