<?php
/**
 * InvenBill Pro - CLI Migration Runner
 *
 * Usage:
 *   php cli/migrate.php          Run pending migrations
 *   php cli/migrate.php --status Show migration and schema health status
 */

declare(strict_types=1);

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

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
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
        ['filename' => '018_deduplicate_indexes.sql'],
        ['filename' => '019_tenant_integrity_hardening.sql'],
        ['filename' => '020_tenant_subscription_nullable_ids.sql'],
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

function getMissingMigrationFilenames(array $manifest): array
{
    $missing = [];
    foreach ($manifest as $entry) {
        if (!empty($entry['missing'])) {
            $missing[] = (string)$entry['filename'];
        }
    }
    return $missing;
}

function filterSqlComments(string $sqlContent): string
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

    return trim(implode("\n", $cleanLines));
}

function sanitizeSqlForCurrentDatabase(string $sqlContent, string $databaseName): string
{
    $sanitized = preg_replace('/^\s*CREATE\s+DATABASE\b[\s\S]*?;\s*/im', '', $sqlContent) ?? $sqlContent;
    $sanitized = preg_replace('/^\s*USE\s+`?[a-zA-Z0-9_\-]+`?\s*;\s*$/im', '', $sanitized) ?? $sanitized;

    return 'USE ' . quoteIdentifier($databaseName) . ';' . "\n\n" . ltrim($sanitized);
}

function splitSqlStatements(string $sqlContent): array
{
    $normalized = filterSqlComments($sqlContent);
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

function normalizeMigrationStatement(string $statement): string
{
    $statement = trim($statement);
    if ($statement === '') {
        return '';
    }

    $statement = preg_replace('/\bCREATE\s+UNIQUE\s+INDEX\s+IF\s+NOT\s+EXISTS\b/i', 'CREATE UNIQUE INDEX', $statement) ?? $statement;
    $statement = preg_replace('/\bCREATE\s+INDEX\s+IF\s+NOT\s+EXISTS\b/i', 'CREATE INDEX', $statement) ?? $statement;
    $statement = preg_replace('/\bADD\s+UNIQUE\s+INDEX\s+IF\s+NOT\s+EXISTS\b/i', 'ADD UNIQUE INDEX', $statement) ?? $statement;
    $statement = preg_replace('/\bADD\s+INDEX\s+IF\s+NOT\s+EXISTS\b/i', 'ADD INDEX', $statement) ?? $statement;
    $statement = preg_replace('/\bDROP\s+INDEX\s+IF\s+EXISTS\b/i', 'DROP INDEX', $statement) ?? $statement;
    $statement = preg_replace('/\bADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\b/i', 'ADD COLUMN', $statement) ?? $statement;
    $statement = preg_replace('/\bDROP\s+COLUMN\s+IF\s+EXISTS\b/i', 'DROP COLUMN', $statement) ?? $statement;

    return trim($statement);
}

function splitTopLevelCsv(string $input): array
{
    $parts = [];
    $buffer = '';
    $depth = 0;
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $escaped = false;
    $length = strlen($input);

    for ($i = 0; $i < $length; $i++) {
        $char = $input[$i];

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

        if ($char === '(') {
            $depth++;
            $buffer .= $char;
            continue;
        }

        if ($char === ')') {
            $depth = max(0, $depth - 1);
            $buffer .= $char;
            continue;
        }

        if ($char === ',' && $depth === 0) {
            $part = trim($buffer);
            if ($part !== '') {
                $parts[] = $part;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $parts[] = $tail;
    }

    return $parts;
}

function isSplitSafeAlterOperation(string $operation): bool
{
    $normalized = strtoupper(trim((string)(preg_replace('/\s+/', ' ', $operation) ?? $operation)));
    if ($normalized === '') {
        return false;
    }

    $allowedPrefixes = [
        'ADD COLUMN',
        'DROP COLUMN',
        'MODIFY COLUMN',
        'CHANGE COLUMN',
        'ADD INDEX',
        'ADD UNIQUE INDEX',
        'ADD KEY',
        'ADD UNIQUE KEY',
        'DROP INDEX',
        'DROP KEY',
        'ADD CONSTRAINT',
        'DROP FOREIGN KEY',
        'ADD PRIMARY KEY',
        'DROP PRIMARY KEY',
    ];

    foreach ($allowedPrefixes as $prefix) {
        if (str_starts_with($normalized, $prefix)) {
            return true;
        }
    }

    return false;
}

function expandAlterTableStatements(string $statement): array
{
    $statement = trim($statement);
    if ($statement === '') {
        return [];
    }

    if (!preg_match('/^ALTER\s+TABLE\s+(`?[a-zA-Z0-9_]+`?)\s+(.+)$/is', $statement, $matches)) {
        return [$statement];
    }

    $tableIdentifier = trim((string)$matches[1]);
    $operationsRaw = trim((string)$matches[2]);
    if ($operationsRaw === '' || strpos($operationsRaw, ',') === false) {
        return [$statement];
    }

    $operations = splitTopLevelCsv($operationsRaw);
    if (count($operations) <= 1) {
        return [$statement];
    }

    foreach ($operations as $operation) {
        if (!isSplitSafeAlterOperation($operation)) {
            return [$statement];
        }
    }

    $expanded = [];
    foreach ($operations as $operation) {
        $expanded[] = 'ALTER TABLE ' . $tableIdentifier . ' ' . trim($operation);
    }

    return $expanded;
}

function extractIndexOperationMetadata(string $statement): ?array
{
    $statement = trim($statement);
    if ($statement === '') {
        return null;
    }

    $patterns = [
        '/^CREATE\s+(?:UNIQUE\s+)?INDEX\s+`?([a-zA-Z0-9_]+)`?\s+ON\s+`?([a-zA-Z0-9_]+)`?/i' => 'create',
        '/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+ADD\s+(?:UNIQUE\s+)?INDEX\s+`?([a-zA-Z0-9_]+)`?/i' => 'create',
        '/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+DROP\s+INDEX\s+`?([a-zA-Z0-9_]+)`?/i' => 'drop',
        '/^DROP\s+INDEX\s+`?([a-zA-Z0-9_]+)`?\s+ON\s+`?([a-zA-Z0-9_]+)`?/i' => 'drop',
    ];

    foreach ($patterns as $pattern => $type) {
        if (preg_match($pattern, $statement, $matches)) {
            if ($pattern === '/^DROP\s+INDEX\s+`?([a-zA-Z0-9_]+)`?\s+ON\s+`?([a-zA-Z0-9_]+)`?/i') {
                return [
                    'type' => $type,
                    'table' => strtolower((string)$matches[2]),
                    'index' => strtolower((string)$matches[1]),
                ];
            }

            if ($pattern === '/^CREATE\s+(?:UNIQUE\s+)?INDEX\s+`?([a-zA-Z0-9_]+)`?\s+ON\s+`?([a-zA-Z0-9_]+)`?/i') {
                return [
                    'type' => $type,
                    'table' => strtolower((string)$matches[2]),
                    'index' => strtolower((string)$matches[1]),
                ];
            }

            return [
                'type' => $type,
                'table' => strtolower((string)$matches[1]),
                'index' => strtolower((string)$matches[2]),
            ];
        }
    }

    return null;
}

function indexExists(PDO $pdo, string $tableName, string $indexName): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND INDEX_NAME = ?
        LIMIT 1
    ");
    $stmt->execute([$tableName, $indexName]);
    return (bool)$stmt->fetchColumn();
}

function shouldSkipIndexStatement(PDO $pdo, string $statement): bool
{
    $meta = extractIndexOperationMetadata($statement);
    if ($meta === null) {
        return false;
    }

    $exists = indexExists($pdo, $meta['table'], $meta['index']);
    return $meta['type'] === 'create' ? $exists : !$exists;
}

function extractColumnOperationMetadata(string $statement): ?array
{
    $statement = trim($statement);
    if ($statement === '') {
        return null;
    }

    if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+ADD\s+COLUMN\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches)) {
        return [
            'type' => 'create',
            'table' => strtolower((string)$matches[1]),
            'column' => strtolower((string)$matches[2]),
        ];
    }

    if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+DROP\s+COLUMN\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches)) {
        return [
            'type' => 'drop',
            'table' => strtolower((string)$matches[1]),
            'column' => strtolower((string)$matches[2]),
        ];
    }

    return null;
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");
    $stmt->execute([$tableName, $columnName]);
    return (bool)$stmt->fetchColumn();
}

function shouldSkipColumnStatement(PDO $pdo, string $statement): bool
{
    $meta = extractColumnOperationMetadata($statement);
    if ($meta === null) {
        return false;
    }

    $exists = columnExists($pdo, $meta['table'], $meta['column']);
    return $meta['type'] === 'create' ? $exists : !$exists;
}

function extractConstraintOperationMetadata(string $statement): ?array
{
    $statement = trim($statement);
    if ($statement === '') {
        return null;
    }

    if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+ADD\s+CONSTRAINT\s+`?([a-zA-Z0-9_]+)`?\s+FOREIGN\s+KEY/i', $statement, $matches)) {
        return [
            'type' => 'create',
            'table' => strtolower((string)$matches[1]),
            'constraint' => strtolower((string)$matches[2]),
            'constraint_type' => 'FOREIGN KEY',
        ];
    }

    if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+DROP\s+FOREIGN\s+KEY\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches)) {
        return [
            'type' => 'drop',
            'table' => strtolower((string)$matches[1]),
            'constraint' => strtolower((string)$matches[2]),
            'constraint_type' => 'FOREIGN KEY',
        ];
    }

    if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+ADD\s+CONSTRAINT\s+`?([a-zA-Z0-9_]+)`?\s+CHECK\b/i', $statement, $matches)) {
        return [
            'type' => 'create',
            'table' => strtolower((string)$matches[1]),
            'constraint' => strtolower((string)$matches[2]),
            'constraint_type' => 'CHECK',
        ];
    }

    if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+DROP\s+CHECK\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches)) {
        return [
            'type' => 'drop',
            'table' => strtolower((string)$matches[1]),
            'constraint' => strtolower((string)$matches[2]),
            'constraint_type' => 'CHECK',
        ];
    }

    return null;
}

function tableConstraintExists(PDO $pdo, string $tableName, string $constraintName, ?string $constraintType = null): bool
{
    $sql = "
        SELECT 1
        FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND CONSTRAINT_NAME = ?
        LIMIT 1
    ";

    $params = [$tableName, $constraintName];
    if ($constraintType !== null) {
        $sql = str_replace('LIMIT 1', 'AND CONSTRAINT_TYPE = ? LIMIT 1', $sql);
        $params[] = $constraintType;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool)$stmt->fetchColumn();
}

function shouldSkipConstraintStatement(PDO $pdo, string $statement): bool
{
    $meta = extractConstraintOperationMetadata($statement);
    if ($meta === null) {
        return false;
    }

    $exists = tableConstraintExists(
        $pdo,
        $meta['table'],
        $meta['constraint'],
        $meta['constraint_type'] ?? null
    );
    return $meta['type'] === 'create' ? $exists : !$exists;
}

function extractCreateTableMetadata(string $statement): ?array
{
    $statement = trim($statement);
    if ($statement === '') {
        return null;
    }

    if (!preg_match('/^CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches)) {
        return null;
    }

    return ['table' => strtolower((string)$matches[1])];
}

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
        LIMIT 1
    ");
    $stmt->execute([$tableName]);
    return (bool)$stmt->fetchColumn();
}

function shouldSkipCreateTableStatement(PDO $pdo, string $statement): bool
{
    $meta = extractCreateTableMetadata($statement);
    if ($meta === null) {
        return false;
    }

    return tableExists($pdo, $meta['table']);
}

function extractCreatedTablesFromSql(string $sqlContent): array
{
    $normalized = filterSqlComments($sqlContent);
    if ($normalized === '') {
        return [];
    }

    $matches = [];
    preg_match_all(
        '/\bCREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i',
        $normalized,
        $matches
    );

    $tables = [];
    foreach (($matches[1] ?? []) as $table) {
        $name = strtolower((string)$table);
        if ($name !== '') {
            $tables[$name] = true;
        }
    }

    return array_keys($tables);
}

function runSqlFile(PDO $pdo, string $path, string $databaseName): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Migration SQL file is missing: ' . $path);
    }

    $rawSql = file_get_contents($path);
    if ($rawSql === false) {
        throw new RuntimeException('Unable to read SQL file: ' . $path);
    }

    $sanitizedSql = sanitizeSqlForCurrentDatabase($rawSql, $databaseName);
    $statements = splitSqlStatements($sanitizedSql);
    if (empty($statements)) {
        throw new RuntimeException('SQL file is empty or contains no executable statements: ' . basename($path));
    }

    $expandedStatements = [];
    foreach ($statements as $statement) {
        foreach (expandAlterTableStatements($statement) as $expanded) {
            $expanded = trim($expanded);
            if ($expanded !== '') {
                $expandedStatements[] = $expanded;
            }
        }
    }

    foreach ($expandedStatements as $index => $statement) {
        try {
            $statement = normalizeMigrationStatement($statement);
            if ($statement === '') {
                continue;
            }

            if (
                shouldSkipCreateTableStatement($pdo, $statement) ||
                shouldSkipColumnStatement($pdo, $statement) ||
                shouldSkipIndexStatement($pdo, $statement) ||
                shouldSkipConstraintStatement($pdo, $statement)
            ) {
                continue;
            }

            $result = $pdo->query($statement);
            if ($result instanceof PDOStatement) {
                do {
                    $result->fetchAll();
                } while ($result->nextRowset());
                $result->closeCursor();
            }
        } catch (Throwable $e) {
            $preview = preg_replace('/\s+/', ' ', trim($statement)) ?? trim($statement);
            $preview = substr($preview, 0, 180);
            throw new RuntimeException(
                sprintf(
                    'Statement #%d failed in %s: %s | SQL: %s',
                    $index + 1,
                    basename($path),
                    $e->getMessage(),
                    $preview
                ),
                0,
                $e
            );
        }
    }

    return extractCreatedTablesFromSql($sanitizedSql);
}

function applicationTableCount(PDO $pdo): int
{
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME <> 'migrations'
    ");

    return (int)($stmt ? $stmt->fetchColumn() : 0);
}

function ensureMigrationsTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `filename` VARCHAR(255) NOT NULL,
            `batch` INT UNSIGNED NOT NULL DEFAULT 1,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_migration_filename` (`filename`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function loadExecutedMap(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT filename FROM migrations');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    return array_fill_keys(array_map('strval', $rows), true);
}

function fetchExistingTables(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
    ");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

    $tables = [];
    foreach ($rows as $row) {
        $table = strtolower((string)$row);
        if ($table !== '') {
            $tables[$table] = true;
        }
    }

    return $tables;
}

function getMissingTables(array $expectedTables, array $existingTableMap): array
{
    $missing = [];
    foreach ($expectedTables as $table) {
        $normalized = strtolower((string)$table);
        if ($normalized === '') {
            continue;
        }
        if (!isset($existingTableMap[$normalized])) {
            $missing[] = $normalized;
        }
    }

    $missing = array_values(array_unique($missing));
    sort($missing);
    return $missing;
}

function expectedTablesFromManifest(array $manifest, string $databaseName): array
{
    $tables = [];

    foreach ($manifest as $entry) {
        if (!empty($entry['missing'])) {
            continue;
        }

        $path = (string)$entry['path'];
        $rawSql = file_get_contents($path);
        if ($rawSql === false) {
            throw new RuntimeException('Unable to read migration file while building expected schema map: ' . $path);
        }

        $sanitizedSql = sanitizeSqlForCurrentDatabase($rawSql, $databaseName);
        foreach (extractCreatedTablesFromSql($sanitizedSql) as $table) {
            $tables[strtolower($table)] = true;
        }
    }

    $expected = array_keys($tables);
    sort($expected);
    return $expected;
}

function requiredCoreTableGroups(): array
{
    return [
        ['label' => 'users', 'tables' => ['users']],
        ['label' => 'companies', 'tables' => ['companies']],
        ['label' => 'subscriptions', 'tables' => ['subscriptions', 'tenant_subscriptions']],
    ];
}

function dataIntegrityChecks(PDO $pdo): array
{
    $checks = [];

    $checks['tenant_users_without_company'] = (int)$pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE deleted_at IS NULL
          AND IFNULL(is_super_admin, 0) = 0
          AND company_id IS NULL
    ")->fetchColumn();

    $checks['super_admins_without_company'] = (int)$pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE deleted_at IS NULL
          AND IFNULL(is_super_admin, 0) = 1
          AND company_id IS NULL
    ")->fetchColumn();

    $checks['companies_without_settings'] = (int)$pdo->query("
        SELECT COUNT(*)
        FROM companies c
        LEFT JOIN company_settings cs ON cs.company_id = c.id
        WHERE cs.id IS NULL
    ")->fetchColumn();

    $checks['companies_without_owner'] = (int)$pdo->query("
        SELECT COUNT(*)
        FROM companies
        WHERE owner_user_id IS NULL
          AND IFNULL(is_demo, 0) = 0
    ")->fetchColumn();

    $checks['companies_with_invalid_plan'] = (int)$pdo->query("
        SELECT COUNT(*)
        FROM companies c
        LEFT JOIN saas_plans sp ON sp.id = c.saas_plan_id
        WHERE c.saas_plan_id IS NULL OR sp.id IS NULL
    ")->fetchColumn();

    $checks['activity_log_null_company_allowed'] = columnExists($pdo, 'activity_log', 'company_id')
        ? (int)$pdo->query("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'activity_log'
              AND COLUMN_NAME = 'company_id'
              AND IS_NULLABLE = 'YES'
        ")->fetchColumn()
        : 0;

    return $checks;
}

function failingIntegrityChecks(array $checks): array
{
    $failures = [];

    foreach ($checks as $label => $value) {
        switch ($label) {
            case 'tenant_users_without_company':
            case 'companies_without_settings':
            case 'companies_without_owner':
            case 'companies_with_invalid_plan':
                if ($value > 0) {
                    $failures[$label] = $value;
                }
                break;
            case 'activity_log_null_company_allowed':
            default:
                break;
        }
    }

    return $failures;
}

function missingCoreGroups(array $existingTableMap): array
{
    $missing = [];
    foreach (requiredCoreTableGroups() as $group) {
        $found = false;
        foreach ($group['tables'] as $table) {
            if (isset($existingTableMap[strtolower((string)$table)])) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missing[] = (string)$group['label'];
        }
    }
    return $missing;
}

function currentDatabaseName(PDO $pdo): string
{
    $stmt = $pdo->query('SELECT DATABASE()');
    $name = $stmt ? (string)$stmt->fetchColumn() : '';
    if (trim($name) === '') {
        throw new RuntimeException('Could not determine active database name from current connection.');
    }
    return $name;
}

function usage(): string
{
    return "Usage:\n  php cli/migrate.php\n  php cli/migrate.php --status";
}

echo bold('InvenBill Pro - Migration Runner') . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

$mode = $argv[1] ?? '--run';
if (!in_array($mode, ['--run', '--status'], true)) {
    echo red('Invalid option: ' . $mode) . PHP_EOL;
    echo usage() . PHP_EOL;
    exit(1);
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $databaseName = currentDatabaseName($pdo);
} catch (Throwable $e) {
    echo red('Database connection failed: ' . $e->getMessage()) . PHP_EOL;
    exit(1);
}

$migrationDir = BASE_PATH . '/database';
$manifest = migrationManifest($migrationDir);
$missingFiles = getMissingMigrationFilenames($manifest);

if (!empty($missingFiles)) {
    echo red('Migration validation failed. Required SQL file(s) are missing:') . PHP_EOL;
    foreach ($missingFiles as $file) {
        echo '  - ' . $file . PHP_EOL;
    }
    echo PHP_EOL . red('Fix: commit/push database/*.sql to Git, deploy again, then re-run migrations.') . PHP_EOL;
    exit(1);
}

try {
    $expectedTables = expectedTablesFromManifest($manifest, $databaseName);
    ensureMigrationsTable($pdo);
    $executedMap = loadExecutedMap($pdo);
} catch (Throwable $e) {
    echo red('Migration bootstrap failed: ' . $e->getMessage()) . PHP_EOL;
    exit(1);
}

$hasExistingApplicationTables = applicationTableCount($pdo) > 0;
$schemaEntry = null;
foreach ($manifest as $entry) {
    if ((string)$entry['filename'] === 'schema.sql') {
        $schemaEntry = $entry;
        break;
    }
}

if ($schemaEntry !== null && $hasExistingApplicationTables && empty($executedMap['schema.sql'])) {
    try {
        $schemaPath = (string)$schemaEntry['path'];
        $schemaRaw = @file_get_contents($schemaPath);
        if ($schemaRaw === false) {
            throw new RuntimeException('Unable to read schema.sql while validating bootstrap state.');
        }

        $existingTablesForSchemaCheck = fetchExistingTables($pdo);
        $expectedSchemaTables = extractCreatedTablesFromSql($schemaRaw);
        $missingSchemaTables = getMissingTables($expectedSchemaTables, $existingTablesForSchemaCheck);

        if (!empty($missingSchemaTables)) {
            echo red('Migration bootstrap is inconsistent: schema.sql is not recorded and base schema tables are missing.') . PHP_EOL;
            echo red('Missing base tables: ' . implode(', ', $missingSchemaTables)) . PHP_EOL;
            echo red('Run migration repair on a clean snapshot before continuing deployment.') . PHP_EOL;
            exit(1);
        }
    } catch (Throwable $e) {
        echo red('Migration bootstrap validation failed: ' . $e->getMessage()) . PHP_EOL;
        exit(1);
    }
}

$pending = [];
foreach ($manifest as $entry) {
    $filename = (string)$entry['filename'];
    if ($filename === 'schema.sql' && $hasExistingApplicationTables && empty($executedMap[$filename])) {
        continue;
    }
    if (empty($executedMap[$filename])) {
        $pending[] = $entry;
    }
}

if ($mode === '--status') {
    echo PHP_EOL . bold('Migration Status:') . PHP_EOL;
    foreach ($manifest as $entry) {
        $filename = (string)$entry['filename'];
        if ($filename === 'schema.sql' && $hasExistingApplicationTables && empty($executedMap[$filename])) {
            echo '  ' . yellow("SKIP {$filename}") . ' (non-empty database)' . PHP_EOL;
            continue;
        }

        if (!empty($executedMap[$filename])) {
            echo '  ' . green("OK {$filename}") . ' (executed)' . PHP_EOL;
        } else {
            echo '  ' . yellow("PENDING {$filename}") . ' (pending)' . PHP_EOL;
        }
    }

    $existingTables = fetchExistingTables($pdo);
    $missingExpectedTables = getMissingTables($expectedTables, $existingTables);
    $missingCore = missingCoreGroups($existingTables);
    $integrityChecks = dataIntegrityChecks($pdo);
    $integrityFailures = failingIntegrityChecks($integrityChecks);

    echo PHP_EOL . bold('Schema Health:') . PHP_EOL;
    echo '  Current DB: ' . $databaseName . PHP_EOL;
    echo '  Expected tables from migrations: ' . count($expectedTables) . PHP_EOL;
    echo '  Existing tables in DB: ' . count($existingTables) . PHP_EOL;

    if (empty($missingExpectedTables)) {
        echo '  ' . green('OK expected schema tables are present') . PHP_EOL;
    } else {
        echo '  ' . red('Missing expected tables: ' . implode(', ', $missingExpectedTables)) . PHP_EOL;
    }

    if (empty($missingCore)) {
        echo '  ' . green('OK core signup tables are present') . PHP_EOL;
    } else {
        echo '  ' . red('Missing core groups: ' . implode(', ', $missingCore)) . PHP_EOL;
    }

    echo PHP_EOL . bold('Data Integrity:') . PHP_EOL;
    foreach ($integrityChecks as $label => $value) {
        $prettyLabel = str_replace('_', ' ', $label);
        $isFailure = array_key_exists($label, $integrityFailures);
        $status = $isFailure ? red('FAIL') : green('OK');
        echo '  ' . $status . ' ' . $prettyLabel . ': ' . $value . PHP_EOL;
    }

    echo PHP_EOL . 'Total: ' . count($manifest)
        . ' | Executed: ' . count($executedMap)
        . ' | Pending: ' . count($pending) . PHP_EOL;

    $isInconsistent = empty($pending)
        && (!empty($missingExpectedTables) || !empty($missingCore) || !empty($integrityFailures));
    if ($isInconsistent) {
        echo red('Status FAILED: migrations appear complete but schema/data integrity is incomplete.') . PHP_EOL;
        exit(1);
    }

    exit(0);
}

if (empty($pending)) {
    $existingTables = fetchExistingTables($pdo);
    $missingExpectedTables = getMissingTables($expectedTables, $existingTables);
    $missingCore = missingCoreGroups($existingTables);
    $integrityChecks = dataIntegrityChecks($pdo);
    $integrityFailures = failingIntegrityChecks($integrityChecks);

    if (!empty($missingExpectedTables) || !empty($missingCore) || !empty($integrityFailures)) {
        echo red('Migration state is inconsistent. No pending migrations, but schema/data integrity is incomplete.') . PHP_EOL;
        if (!empty($missingExpectedTables)) {
            echo red('Missing expected tables: ' . implode(', ', $missingExpectedTables)) . PHP_EOL;
        }
        if (!empty($missingCore)) {
            echo red('Missing core groups: ' . implode(', ', $missingCore)) . PHP_EOL;
        }
        if (!empty($integrityFailures)) {
            foreach ($integrityFailures as $label => $value) {
                echo red('Integrity failure - ' . str_replace('_', ' ', $label) . ': ' . $value) . PHP_EOL;
            }
        }
        echo red('Aborting with failure so this cannot be treated as production-ready.') . PHP_EOL;
        exit(1);
    }

    echo green('All migrations are up to date and schema integrity checks passed.') . PHP_EOL;
    exit(0);
}

try {
    $maxBatch = $pdo->query('SELECT MAX(batch) FROM migrations')->fetchColumn();
    $currentBatch = ((int)($maxBatch ?? 0)) + 1;
} catch (Throwable $e) {
    $currentBatch = 1;
}

echo 'Current DB: ' . $databaseName . PHP_EOL;
echo 'Pending:    ' . yellow((string)count($pending) . ' migration(s)') . PHP_EOL;
echo 'Batch:      ' . $currentBatch . PHP_EOL . PHP_EOL;

$success = 0;
$failed = 0;

foreach ($pending as $entry) {
    $filename = (string)$entry['filename'];
    $path = (string)$entry['path'];

    echo "  Running: {$filename} ... ";

    try {
        if (!is_file($path)) {
            throw new RuntimeException('Missing migration file during execution: ' . $path);
        }

        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }

        $createdTables = runSqlFile($pdo, $path, $databaseName);
        $existingTables = fetchExistingTables($pdo);
        $missingFromThisFile = getMissingTables($createdTables, $existingTables);
        if (!empty($missingFromThisFile)) {
            throw new RuntimeException(
                'Migration executed but did not create expected table(s): ' . implode(', ', $missingFromThisFile)
            );
        }

        $insert = $pdo->prepare('INSERT INTO migrations (filename, batch) VALUES (?, ?)');
        $insert->execute([$filename, $currentBatch]);

        if ($pdo->inTransaction()) {
            $pdo->commit();
        }

        echo green('OK') . PHP_EOL;
        $success++;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo red('FAILED') . PHP_EOL;
        echo '    ' . red('Error: ' . $e->getMessage()) . PHP_EOL;
        $failed++;
        echo PHP_EOL . red('Migration halted. Fix the error and re-run.') . PHP_EOL;
        break;
    }
}

if ($failed === 0) {
    $existingTables = fetchExistingTables($pdo);
    $missingExpectedTables = getMissingTables($expectedTables, $existingTables);
    $missingCore = missingCoreGroups($existingTables);
    $integrityChecks = dataIntegrityChecks($pdo);
    $integrityFailures = failingIntegrityChecks($integrityChecks);
    if (!empty($missingExpectedTables) || !empty($missingCore) || !empty($integrityFailures)) {
        echo PHP_EOL . red('Post-migration integrity check failed.') . PHP_EOL;
        if (!empty($missingExpectedTables)) {
            echo red('Missing expected tables: ' . implode(', ', $missingExpectedTables)) . PHP_EOL;
        }
        if (!empty($missingCore)) {
            echo red('Missing core groups: ' . implode(', ', $missingCore)) . PHP_EOL;
        }
        if (!empty($integrityFailures)) {
            foreach ($integrityFailures as $label => $value) {
                echo red('Integrity failure - ' . str_replace('_', ' ', $label) . ': ' . $value) . PHP_EOL;
            }
        }
        $failed++;
    }
}

echo PHP_EOL . str_repeat('-', 60) . PHP_EOL;
echo 'Results: '
    . green("{$success} succeeded")
    . ' | '
    . ($failed > 0 ? red("{$failed} failed") : "{$failed} failed")
    . PHP_EOL;

exit($failed > 0 ? 1 : 0);
