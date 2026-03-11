<?php
/**
 * InvenBill Pro — CLI Migration Runner
 * 
 * Executes numbered SQL migration files from database/ directory.
 * Tracks executed migrations in the `migrations` table to prevent double execution.
 * 
 * Usage:
 *   php cli/migrate.php              Run all pending migrations
 *   php cli/migrate.php --status     Show migration status
 *   php cli/migrate.php --rollback   Show rollback info (manual)
 * 
 * Migration file naming convention:
 *   NNN_description.sql   (e.g., 003_migrations_table.sql)
 *   Files are executed in alphabetical order.
 *   Only files matching /^\d{3}_/ pattern are processed.
 */

// ─── Bootstrap ───────────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

// ─── Colors (ANSI) ──────────────────────────────────────
function green(string $text): string  { return "\033[32m{$text}\033[0m"; }
function red(string $text): string    { return "\033[31m{$text}\033[0m"; }
function yellow(string $text): string { return "\033[33m{$text}\033[0m"; }
function bold(string $text): string   { return "\033[1m{$text}\033[0m"; }

// ─── Main ────────────────────────────────────────────────
echo bold("InvenBill Pro — Migration Runner") . PHP_EOL;
echo str_repeat('─', 50) . PHP_EOL;

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    echo red("✗ Database connection failed: " . $e->getMessage()) . PHP_EOL;
    exit(1);
}

// Ensure migrations table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `filename`    VARCHAR(255) NOT NULL,
            `batch`       INT UNSIGNED NOT NULL DEFAULT 1,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_migration_filename` (`filename`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    // Table may already exist, that's fine
}

// Get already-executed migrations
$executed = [];
try {
    $stmt = $pdo->query("SELECT filename FROM migrations ORDER BY filename");
    $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    echo yellow("⚠ Could not read migrations table, it may not exist yet.") . PHP_EOL;
}

// Discover migration files (NNN_*.sql pattern)
$migrationDir = BASE_PATH . '/database';
$files = glob($migrationDir . '/[0-9][0-9][0-9]_*.sql');
sort($files);

if (empty($files)) {
    echo yellow("No numbered migration files found in database/.") . PHP_EOL;
    exit(0);
}

$mode = $argv[1] ?? '--run';

// ─── Status Mode ─────────────────────────────────────────
if ($mode === '--status') {
    echo PHP_EOL . bold("Migration Status:") . PHP_EOL;
    foreach ($files as $file) {
        $basename = basename($file);
        if (in_array($basename, $executed, true)) {
            echo "  " . green("✓ {$basename}") . " (executed)" . PHP_EOL;
        } else {
            echo "  " . yellow("○ {$basename}") . " (pending)" . PHP_EOL;
        }
    }
    echo PHP_EOL . "Total: " . count($files) . " | Executed: " . count($executed) . " | Pending: " . (count($files) - count(array_intersect(array_map('basename', $files), $executed))) . PHP_EOL;
    exit(0);
}

// ─── Run Migrations ──────────────────────────────────────
$pending = array_filter($files, function ($file) use ($executed) {
    return !in_array(basename($file), $executed, true);
});

if (empty($pending)) {
    echo green("✓ All migrations are up to date.") . PHP_EOL;
    exit(0);
}

// Get next batch number
$currentBatch = 1;
try {
    $maxBatch = $pdo->query("SELECT MAX(batch) FROM migrations")->fetchColumn();
    $currentBatch = ($maxBatch ?? 0) + 1;
} catch (Exception $e) {
    // Default to batch 1
}

echo "Pending: " . yellow(count($pending) . " migration(s)") . PHP_EOL;
echo "Batch:   " . $currentBatch . PHP_EOL . PHP_EOL;

$success = 0;
$failed = 0;

foreach ($pending as $file) {
    $basename = basename($file);
    echo "  Running: {$basename} ... ";

    $sqlContent = file_get_contents($file);
    if (empty(trim($sqlContent))) {
        echo yellow("SKIP (empty)") . PHP_EOL;
        continue;
    }

    try {
        // Split by semicolons (preserving multi-line statements)
        $statements = preg_split('/;\s*\n/', $sqlContent);

        $pdo->beginTransaction();

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (empty($stmt)) continue;
            if (str_starts_with($stmt, '--')) continue;

            $pdo->exec($stmt);
        }

        // Record this migration
        $pdo->prepare("INSERT INTO migrations (filename, batch) VALUES (?, ?)")
            ->execute([$basename, $currentBatch]);

        $pdo->commit();
        echo green("OK") . PHP_EOL;
        $success++;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo red("FAILED") . PHP_EOL;
        echo "    " . red("Error: " . $e->getMessage()) . PHP_EOL;
        $failed++;

        // Stop on first failure to prevent cascading errors
        echo PHP_EOL . red("Migration halted. Fix the error and re-run.") . PHP_EOL;
        break;
    }
}

echo PHP_EOL . str_repeat('─', 50) . PHP_EOL;
echo "Results: " . green("{$success} succeeded") . " | " . ($failed > 0 ? red("{$failed} failed") : "{$failed} failed") . PHP_EOL;
exit($failed > 0 ? 1 : 0);
