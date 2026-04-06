<?php
declare(strict_types=1);

/**
 * Enterprise feature smoke verifier (non-destructive, DB-backed).
 *
 * Validates that the demo tenant and key enterprise modules are wired:
 * - latest enterprise migrations are applied
 * - demo tenant resolves to an enterprise-capable plan
 * - core HR / payroll / warehouse schema pieces exist
 * - tenant feature gating resolves for premium modules
 * - reporting/service snapshots execute without fatal errors
 *
 * Usage:
 *   php cli/smoke_enterprise.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

function smokeOk(string $message): void
{
    echo "[OK] {$message}" . PHP_EOL;
}

function smokeWarn(string $message): void
{
    echo "[WARN] {$message}" . PHP_EOL;
}

function smokeFail(string $message): void
{
    fwrite(STDERR, "[FAIL] {$message}" . PHP_EOL);
}

function requireColumn(PDO $pdo, string $table, string $column): void
{
    $stmt = $pdo->prepare(
        "SELECT 1
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND column_name = ?
         LIMIT 1"
    );
    $stmt->execute([$table, $column]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException("Missing column {$table}.{$column}");
    }
}

function requireTable(PDO $pdo, string $table): void
{
    $stmt = $pdo->prepare(
        "SELECT 1
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = ?
         LIMIT 1"
    );
    $stmt->execute([$table]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException("Missing table {$table}");
    }
}

function smokeScalar(PDO $pdo, string $sql, array $params = []): mixed
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

try {
    $pdo = Database::getInstance()->getConnection();
    $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($dbName === '') {
        throw new RuntimeException('No active database selected.');
    }
    smokeOk("Connected to database: {$dbName}");

    $requiredMigrations = [
        '047_add_hr_leave_approval_chain.sql',
        '048_add_hr_payroll_approval_and_locking.sql',
        '049_create_payroll_payment_journal_entries.sql',
        '050_add_stock_transfer_approval.sql',
        '051_add_stock_transfer_rejection.sql',
    ];
    $migrationStmt = $pdo->prepare('SELECT 1 FROM migrations WHERE filename = ? LIMIT 1');
    foreach ($requiredMigrations as $filename) {
        $migrationStmt->execute([$filename]);
        if (!$migrationStmt->fetchColumn()) {
            throw new RuntimeException("Required migration not applied: {$filename}");
        }
    }
    smokeOk('Latest enterprise migrations are applied');

    $demo = $pdo->query(
        "SELECT c.*, p.slug AS saas_plan_slug, p.features
         FROM companies c
         LEFT JOIN saas_plans p ON p.id = c.saas_plan_id
         WHERE c.is_demo = 1
         ORDER BY c.id ASC
         LIMIT 1"
    )->fetch();
    if (!$demo) {
        throw new RuntimeException('Demo tenant not found.');
    }
    smokeOk('Demo tenant found: ' . ($demo['name'] ?? 'Unknown'));

    if (($demo['saas_plan_slug'] ?? '') !== 'enterprise') {
        throw new RuntimeException('Demo tenant is not mapped to enterprise SaaS plan.');
    }
    smokeOk('Demo tenant is mapped to enterprise SaaS plan');

    requireTable($pdo, 'hr_employees');
    requireTable($pdo, 'hr_leave_requests');
    requireTable($pdo, 'hr_payroll_runs');
    requireTable($pdo, 'hr_payroll_items');
    requireTable($pdo, 'stock_transfers');
    requireTable($pdo, 'stock_transfer_items');
    requireTable($pdo, 'payroll_payment_journal_entries');
    smokeOk('Required enterprise tables exist');

    $requiredColumns = [
        ['hr_employees', 'shift_id'],
        ['hr_employees', 'reporting_manager_user_id'],
        ['hr_leave_requests', 'manager_status'],
        ['hr_leave_requests', 'approver_user_id'],
        ['hr_payroll_runs', 'approved_by'],
        ['hr_payroll_runs', 'locked_at'],
        ['hr_payroll_items', 'pf_amount'],
        ['hr_payroll_items', 'statutory_deduction_amount'],
        ['stock_transfers', 'status'],
        ['stock_transfers', 'rejected_by'],
        ['stock_transfers', 'rejection_reason'],
    ];
    foreach ($requiredColumns as [$table, $column]) {
        requireColumn($pdo, $table, $column);
    }
    smokeOk('Required enterprise columns exist');

    Tenant::set((int)$demo['id'], is_array($demo) ? $demo : null);

    $featureChecks = [
        'hr',
        'multi_warehouse',
        'bulk_import',
        'custom_fields',
        'ai_insights',
        'api_access',
        'backup_restore',
    ];
    foreach ($featureChecks as $feature) {
        if (!Tenant::canUse($feature)) {
            throw new RuntimeException("Demo tenant cannot use required feature: {$feature}");
        }
    }
    smokeOk('Demo tenant feature gating resolves for premium modules');

    $warehouseCount = (int)smokeScalar($pdo, "SELECT COUNT(*) FROM warehouses WHERE company_id = ? AND deleted_at IS NULL", [(int)$demo['id']]);
    if ($warehouseCount < 1) {
        throw new RuntimeException('No warehouses found for demo tenant.');
    }
    smokeOk("Demo tenant warehouses available: {$warehouseCount}");

    $shiftCount = (int)smokeScalar($pdo, "SELECT COUNT(*) FROM hr_shifts WHERE company_id = ?", [(int)$demo['id']]);
    if ($shiftCount < 1) {
        smokeWarn('No HR shifts configured yet for demo tenant');
    } else {
        smokeOk("HR shifts available: {$shiftCount}");
    }

    $leaveSummary = (new HrLeaveRequest())->summary();
    smokeOk('HR leave summary query executed');

    $payrollSnapshot = (new HrPayroll())->dashboardSnapshot(date('Y-m'));
    smokeOk('HR payroll snapshot query executed with status: ' . ($payrollSnapshot['status'] ?? 'draft'));

    $financeReport = (new HrPayroll())->financeReport(date('Y-01'), date('Y-m'));
    smokeOk(
        'Payroll finance report query executed (' .
        count((array)($financeReport['runs'] ?? [])) . ' runs, ' .
        count((array)($financeReport['entries'] ?? [])) . ' journal rows)'
    );

    $transferSummary = (new WarehouseModel())->transferSummary();
    smokeOk(
        'Warehouse transfer summary query executed (' .
        (int)($transferSummary['total_transfers'] ?? 0) . ' transfers)'
    );

    $transferReport = (new WarehouseModel())->transferReport(date('Y-m-01'), date('Y-m-d'));
    smokeOk('Warehouse transfer report query executed (' . count($transferReport) . ' rows)');

    $stockReport = (new ProductModel())->getStockReport('', '', null, 1, 25);
    smokeOk('Stock report query executed (' . count((array)($stockReport['data'] ?? [])) . ' rows sampled)');

    echo "[DONE] Enterprise smoke verification passed" . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    smokeFail($e->getMessage());
    exit(1);
} finally {
    Tenant::reset();
}
