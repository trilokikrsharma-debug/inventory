<?php
/**
 * InvenBill Pro - CLI Migration Runner
 *
 * Executes SQL migration files from database/ in a deterministic order.
 * Tracks executed migrations in the migrations table to prevent double execution.
 *
 * Usage:
 *   php cli/migrate.php           Run all pending migrations
 *   php cli/migrate.php --status  Show migration status
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

function green(string $text): string
{
    return "\033[32m{$text}\033[0m";
}

function red(string $text): string
{
    return "\033[31m{$text}\033[0m";
}

function yellow(string $text): string
{
    return "\033[33m{$text}\033[0m";
}

function bold(string $text): string
{
    return "\033[1m{$text}\033[0m";
}

function migrationManifest(string $migrationDir): array
{
    $orderedFiles = [
        ['filename' => 'schema.sql', 'freshOnly' => true],
        ['filename' => '003_migrations_table.sql'],
        ['filename' => 'multi_tenant_migration.sql'],
        ['filename' => 'quotations.sql'],
        ['filename' => 'rbac_migration.sql'],
        ['filename' => 'super_admin_migration.sql'],
        ['filename' => '002_tenant_isolation_fix.sql'],
        ['filename' => '004_composite_indexes.sql'],
        ['filename' => '005_audit_trail.sql'],
        ['filename' => '006_financial_precision.sql'],
        ['filename' => '007_invoice_tenant_isolation.sql'],
        ['filename' => '008_job_queue.sql'],
        ['filename' => '009_saas_foundation.sql'],
        ['filename' => '009_two_factor_auth.sql'],
        ['filename' => '010_fix_tenant_role_hierarchy.sql'],
        ['filename' => '011_fix_superadmin_login.sql'],
        ['filename' => '012_roles_tenant_scoping.sql'],
        ['filename' => '013_final_security_hardening.sql'],
        ['filename' => '014_saas_billing_system.sql'],
        ['filename' => '015_sales_tax_charge_breakup.sql'],
        ['filename' => '016_company_settings_invoice_display_options.sql'],
        ['filename' => '017_gst_hsn_and_roundoff_settings.sql'],
        ['filename' => 'enterprise_hardening.sql'],
        ['filename' => 'enterprise_platform.sql'],
        ['filename' => 'performance_indexes.sql'],
    ];

    $manifest = [];
    foreach ($orderedFiles as $entry) {
        $path = $migrationDir . DIRECTORY_SEPARATOR . $entry['filename'];
        $entry['path'] = $path;
        $entry['missing'] = !is_file($path);
        $manifest[] = $entry;
    }

    return $manifest;
}

function splitSqlStatements(string $sqlContent): array
{
    $sqlContent = preg_replace('/^\xEF\xBB\xBF/', '', $sqlContent) ?? $sqlContent;
    $sqlContent = preg_replace('~/\*.*?\*/~s', '', $sqlContent) ?? $sqlContent;

    $lines = preg_split('/\R/', $sqlContent) ?: [];
    $cleanLines = [];

    foreach ($lines as $line) {
        $trimmed = ltrim($line);
        if ($trimmed === '') {
            continue;
        }
        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $cleanLines[] = $line;
    }

    $normalized = trim(implode("\n", $cleanLines));
    if ($normalized === '') {
        return [];
    }

    $parts = preg_split('/;\s*(?:\R|$)/', $normalized) ?: [];
    $statements = [];

    foreach ($parts as $part) {
        $statement = trim($part);
        if ($statement !== '') {
            $statements[] = $statement;
        }
    }

    return $statements;
}

function applicationTableCount(PDO $pdo): int
{
    $stmt = $pdo->query("\n        SELECT COUNT(*)\n        FROM information_schema.TABLES\n        WHERE TABLE_SCHEMA = DATABASE()\n          AND TABLE_NAME <> 'migrations'\n    ");

    return (int)($stmt ? $stmt->fetchColumn() : 0);
}

function runSqlFile(PDO $pdo, string $path): void
{
    $sqlContent = file_get_contents($path);
    if ($sqlContent === false) {
        throw new RuntimeException('Unable to read SQL file.');
    }

    $statements = splitSqlStatements($sqlContent);
    if (empty($statements)) {
        throw new RuntimeException('SQL file is empty or contains no executable statements.');
    }

    foreach ($statements as $statement) {
        $result = $pdo->query($statement);
        if ($result instanceof PDOStatement) {
            do {
                $result->fetchAll();
            } while ($result->nextRowset());
            $result->closeCursor();
        }
    }
}

echo bold('InvenBill Pro - Migration Runner') . PHP_EOL;
echo str_repeat('-', 50) . PHP_EOL;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    echo red('Database connection failed: ' . $e->getMessage()) . PHP_EOL;
    exit(1);
}

try {
    $pdo->exec("\n        CREATE TABLE IF NOT EXISTS `migrations` (\n            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n            `filename` VARCHAR(255) NOT NULL,\n            `batch` INT UNSIGNED NOT NULL DEFAULT 1,\n            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n            UNIQUE KEY `uq_migration_filename` (`filename`)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci\n    ");
} catch (Exception $e) {
    // The table may already exist. Keep going.
}

$migrationDir = BASE_PATH . '/database';
$manifest = migrationManifest($migrationDir);
$mode = $argv[1] ?? '--run';
$hasExistingApplicationTables = applicationTableCount($pdo) > 0;

$executed = [];
try {
    $stmt = $pdo->query('SELECT filename FROM migrations');
    $executed = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
} catch (Exception $e) {
    echo yellow('Could not read migrations table yet; continuing with an empty execution cache.') . PHP_EOL;
}
$executedMap = array_fill_keys($executed, true);

if ($mode === '--status') {
    echo PHP_EOL . bold('Migration Status:') . PHP_EOL;
    foreach ($manifest as $entry) {
        $basename = $entry['filename'];
        if (!empty($entry['missing'])) {
            echo '  ' . yellow("! {$basename}") . ' (missing)' . PHP_EOL;
            continue;
        }

        if ($basename === 'schema.sql' && $hasExistingApplicationTables && empty($executedMap[$basename])) {
            echo '  ' . yellow("SKIP {$basename}") . ' (skipped on non-empty database)' . PHP_EOL;
            continue;
        }

        if (!empty($executedMap[$basename])) {
            echo '  ' . green("OK {$basename}") . ' (executed)' . PHP_EOL;
            continue;
        }

        echo '  ' . yellow("PENDING {$basename}") . ' (pending)' . PHP_EOL;
    }

    $pendingCount = 0;
    $executedCount = count($executedMap);
    $availableCount = 0;
    foreach ($manifest as $entry) {
        if (!empty($entry['missing'])) {
            continue;
        }
        $availableCount++;
        if ($entry['filename'] === 'schema.sql' && $hasExistingApplicationTables) {
            continue;
        }
        if (empty($executedMap[$entry['filename']])) {
            $pendingCount++;
        }
    }

    echo PHP_EOL . 'Total: ' . $availableCount . ' | Executed: ' . $executedCount . ' | Pending: ' . $pendingCount . PHP_EOL;
    exit(0);
}

$pending = [];
foreach ($manifest as $entry) {
    if (!empty($entry['missing'])) {
        continue;
    }

    if ($entry['filename'] === 'schema.sql' && $hasExistingApplicationTables) {
        continue;
    }

    if (empty($executedMap[$entry['filename']])) {
        $pending[] = $entry;
    }
}

if (empty($pending)) {
    echo green('All migrations are up to date.') . PHP_EOL;
    exit(0);
}

$currentBatch = 1;
try {
    $maxBatch = $pdo->query('SELECT MAX(batch) FROM migrations')->fetchColumn();
    $currentBatch = ((int)($maxBatch ?? 0)) + 1;
} catch (Exception $e) {
    // Keep default batch number.
}

echo 'Pending: ' . yellow((string)count($pending) . ' migration(s)') . PHP_EOL;
echo 'Batch:   ' . $currentBatch . PHP_EOL . PHP_EOL;

$success = 0;
$failed = 0;

foreach ($pending as $entry) {
    $basename = $entry['filename'];
    $path = $entry['path'];
    echo "  Running: {$basename} ... ";

    try {
        if ($basename === 'schema.sql' && $hasExistingApplicationTables) {
            echo yellow('SKIP (database already contains application tables)') . PHP_EOL;
            continue;
        }

        runSqlFile($pdo, $path);

        $pdo->prepare('INSERT INTO migrations (filename, batch) VALUES (?, ?)')
            ->execute([$basename, $currentBatch]);

        echo green('OK') . PHP_EOL;
        $success++;
    } catch (Exception $e) {
        echo red('FAILED') . PHP_EOL;
        echo '    ' . red('Error: ' . $e->getMessage()) . PHP_EOL;
        $failed++;
        echo PHP_EOL . red('Migration halted. Fix the error and re-run.') . PHP_EOL;
        break;
    }
}

echo PHP_EOL . str_repeat('-', 50) . PHP_EOL;
echo 'Results: ' . green("{$success} succeeded") . ' | ' . ($failed > 0 ? red("{$failed} failed") : "{$failed} failed") . PHP_EOL;
exit($failed > 0 ? 1 : 0);
