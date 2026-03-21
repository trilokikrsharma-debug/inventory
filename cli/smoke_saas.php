<?php
declare(strict_types=1);

/**
 * Production smoke flow (DB-backed, non-destructive).
 *
 * Validates core SaaS paths in one transaction:
 * - required tables exist
 * - signup/login primitives (user + password verify)
 * - product + invoice creation
 * - tenant-scoped invoice uniqueness
 * - subscription/payment mock record
 *
 * Usage:
 *   php cli/smoke_saas.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/vendor/autoload.php';

function ok(string $message): void
{
    echo "[OK] {$message}" . PHP_EOL;
}

function fail(string $message): void
{
    fwrite(STDERR, "[FAIL] {$message}" . PHP_EOL);
}

try {
    $pdo = Database::getInstance()->getConnection();
    $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($dbName === '') {
        throw new RuntimeException('No active database selected.');
    }
    ok("Connected to database: {$dbName}");

    $required = ['users', 'companies', 'products', 'sales', 'sale_items', 'tenant_subscriptions'];
    $missing = [];
    $checkTableStmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    foreach ($required as $table) {
        $checkTableStmt->execute([$table]);
        if (!$checkTableStmt->fetchColumn()) {
            $missing[] = $table;
        }
    }
    if (!empty($missing)) {
        throw new RuntimeException('Missing required table(s): ' . implode(', ', $missing));
    }
    ok('Required core tables are present');

    $pdo->beginTransaction();

    $stamp = (string)time();
    $companySlugA = 'smoke-a-' . $stamp;
    $companySlugB = 'smoke-b-' . $stamp;

    $insCompany = $pdo->prepare('INSERT INTO companies (name, slug, plan, status, is_demo, max_users, max_products) VALUES (?, ?, ?, ?, 0, 10, 1000)');
    $insCompany->execute(['Smoke Company A', $companySlugA, 'pro', 'active']);
    $companyA = (int)$pdo->lastInsertId();
    $insCompany->execute(['Smoke Company B', $companySlugB, 'pro', 'active']);
    $companyB = (int)$pdo->lastInsertId();
    ok('Created two tenant records');

    $password = 'SmokePass#123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if (!is_string($hash) || $hash === '') {
        throw new RuntimeException('password_hash() failed.');
    }
    if (!password_verify($password, $hash)) {
        throw new RuntimeException('password_verify() failed.');
    }
    ok('Password hashing/verification works');

    $insUser = $pdo->prepare('INSERT INTO users (company_id, username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
    $insUser->execute([$companyA, 'smoke_admin_a_' . $stamp, 'smoke-a-' . $stamp . '@example.test', $hash, 'Smoke Admin A', 'admin']);
    $userA = (int)$pdo->lastInsertId();
    $insUser->execute([$companyB, 'smoke_admin_b_' . $stamp, 'smoke-b-' . $stamp . '@example.test', $hash, 'Smoke Admin B', 'admin']);
    $userB = (int)$pdo->lastInsertId();
    ok('Created multi-tenant admin users');

    $insCategory = $pdo->prepare('INSERT INTO categories (company_id, name) VALUES (?, ?)');
    $insBrand = $pdo->prepare('INSERT INTO brands (company_id, name) VALUES (?, ?)');
    $insUnit = $pdo->prepare('INSERT INTO units (company_id, name, short_name) VALUES (?, ?, ?)');
    $insCategory->execute([$companyA, 'Smoke Category']);
    $categoryId = (int)$pdo->lastInsertId();
    $insBrand->execute([$companyA, 'Smoke Brand']);
    $brandId = (int)$pdo->lastInsertId();
    $insUnit->execute([$companyA, 'Pieces', 'pcs']);
    $unitId = (int)$pdo->lastInsertId();

    $insProduct = $pdo->prepare("
        INSERT INTO products
            (company_id, name, sku, category_id, brand_id, unit_id, purchase_price, selling_price, opening_stock, current_stock, is_active)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $insProduct->execute([$companyA, 'Smoke Product', 'SMK-' . $stamp, $categoryId, $brandId, $unitId, 100.00, 150.00, 10, 10]);
    $productId = (int)$pdo->lastInsertId();
    ok('Created product under tenant A');

    $insCustomer = $pdo->prepare('INSERT INTO customers (company_id, name, email, phone, is_active) VALUES (?, ?, ?, ?, 1)');
    $insCustomer->execute([$companyA, 'Smoke Customer A', 'cust-a-' . $stamp . '@example.test', '9000000001']);
    $customerA = (int)$pdo->lastInsertId();
    $insCustomer->execute([$companyB, 'Smoke Customer B', 'cust-b-' . $stamp . '@example.test', '9000000002']);
    $customerB = (int)$pdo->lastInsertId();

    $invoiceNo = 'INV-SMOKE-001';
    $insSale = $pdo->prepare("
        INSERT INTO sales
            (company_id, invoice_number, customer_id, sale_date, subtotal, discount_amount, tax_amount, shipping_cost, grand_total, paid_amount, due_amount, payment_status, status, created_by)
        VALUES
            (?, ?, ?, CURDATE(), ?, 0, 0, 0, ?, ?, ?, ?, 'completed', ?)
    ");
    $insSale->execute([$companyA, $invoiceNo, $customerA, 150.00, 150.00, 0.00, 150.00, 'unpaid', $userA]);
    $saleA = (int)$pdo->lastInsertId();

    $insSaleItem = $pdo->prepare("
        INSERT INTO sale_items
            (company_id, sale_id, product_id, quantity, unit_price, discount, tax_rate, tax_amount, subtotal, total)
        VALUES
            (?, ?, ?, 1, 150.00, 0, 0, 0, 150.00, 150.00)
    ");
    $insSaleItem->execute([$companyA, $saleA, $productId]);
    ok('Created invoice + line item in tenant A');

    $insSale->execute([$companyB, $invoiceNo, $customerB, 200.00, 200.00, 0.00, 200.00, 'unpaid', $userB]);
    ok('Same invoice number accepted in tenant B (tenant isolation verified)');

    $sameTenantConflict = false;
    try {
        $insSale->execute([$companyA, $invoiceNo, $customerA, 100.00, 100.00, 0.00, 100.00, 'unpaid', $userA]);
    } catch (Throwable $e) {
        $sameTenantConflict = true;
    }
    if (!$sameTenantConflict) {
        throw new RuntimeException('Expected duplicate invoice conflict in same tenant did not occur.');
    }
    ok('Same-tenant duplicate invoice rejected by unique constraint');

    $planId = (int)($pdo->query("SELECT id FROM saas_plans WHERE IFNULL(status, 'active') = 'active' ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 0);
    if ($planId <= 0) {
        $insPlan = $pdo->prepare("
            INSERT INTO saas_plans (name, slug, price, billing_cycle, max_users, features, is_active, status, billing_type, duration_days)
            VALUES (?, ?, ?, ?, ?, ?, 1, 'active', 'monthly', 30)
        ");
        $insPlan->execute(['Smoke Plan', 'smoke-plan-' . $stamp, 499.00, 'monthly', 10, json_encode(['reports' => true], JSON_THROW_ON_ERROR)]);
        $planId = (int)$pdo->lastInsertId();
    }

    $insSub = $pdo->prepare("
        INSERT INTO tenant_subscriptions
            (company_id, plan_id, status, subscription_type, change_type, amount, original_amount, discount_amount, payment_status, duration_days, current_start, current_end, started_at, expires_at, last_payment_at)
        VALUES
            (?, ?, 'active', 'recurring', 'new', 499.00, 499.00, 0.00, 'paid', 30, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())
    ");
    $insSub->execute([$companyA, $planId]);
    ok('Inserted mock paid subscription/payment lifecycle row');

    $pdo->rollBack();
    ok('Smoke transaction rolled back (non-destructive)');
    echo "[DONE] SaaS smoke flow passed" . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail($e->getMessage());
    exit(1);
}
