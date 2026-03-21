<?php
// All-in-one: check schema + setup demo + fix signup

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '/var/www/inventory/config/Database.php';
require_once '/var/www/inventory/config/RateLimiter.php';

$pdo = new PDO(
    "mysql:host=127.0.0.1;dbname=invenbill;charset=utf8mb4",
    'invenbill_app',
    'InvenBillPass@2026',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ============================================================
// 1. Show companies table columns
// ============================================================
echo "=== COMPANIES COLUMNS ===\n";
foreach ($pdo->query("SHOW COLUMNS FROM companies")->fetchAll() as $c) {
    echo $c['Field'] . " | Null=" . $c['Null'] . " | Default=" . $c['Default'] . "\n";
}

// ============================================================
// 2. Show all tables
// ============================================================
echo "\n=== ALL TABLES ===\n";
foreach ($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $t) echo $t . "\n";

// ============================================================
// 3. Check is_demo column
// ============================================================
$hasDemoCol = (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='invenbill' AND table_name='companies' AND column_name='is_demo'")->fetchColumn();
echo "\nis_demo column exists: " . ($hasDemoCol ? "YES" : "NO") . "\n";

if (!$hasDemoCol) {
    $pdo->exec("ALTER TABLE companies ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0");
    echo "Created is_demo column.\n";
}

// ============================================================
// 4. Check/Create demo company
// ============================================================
$demo = $pdo->query("SELECT * FROM companies WHERE is_demo=1 LIMIT 1")->fetch();
if (!$demo) {
    echo "\nCreating demo company...\n";
    $pdo->exec("INSERT INTO companies (name, slug, saas_plan_id, subscription_status, trial_ends_at, plan, status, max_users, max_products, is_demo) 
                VALUES ('Demo Company', 'demo-company', 1, 'active', DATE_ADD(NOW(), INTERVAL 365 DAY), 'starter', 'active', 10, 1000, 1)");
    $demoCompanyId = (int)$pdo->lastInsertId();
    echo "Demo company created: ID=$demoCompanyId\n";

    // Settings
    $pdo->prepare("INSERT IGNORE INTO company_settings (company_id, company_name, company_email, company_phone, company_address, company_city, company_state, company_country, currency_symbol, currency_code, enable_gst, enable_tax, tax_rate, low_stock_threshold, invoice_prefix, purchase_prefix, payment_prefix, receipt_prefix) 
                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$demoCompanyId,'Demo Company','demo@tsalegacy.shop','9999999999','Demo Street','Mumbai','Maharashtra','India','₹','INR',1,1,18,10,'INV-','PUR-','PAY-','REC-']);

    // Role
    $pdo->prepare("INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system) VALUES (?,?,?,?,0,1)")
        ->execute([$demoCompanyId,'admin','Administrator','Demo company full access']);
    $demoRoleId = (int)$pdo->lastInsertId();

    // User
    $hash = password_hash('Demo@2026Secure', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (company_id, username, email, password, full_name, phone, role, role_id, is_active, is_super_admin, created_at) VALUES (?,?,?,?,?,?,?,?,1,0,NOW())")
        ->execute([$demoCompanyId, 'demo', "demo+{$demoCompanyId}@invenbill.com", $hash, 'Demo User', '9999999999', 'admin', $demoRoleId]);
    echo "Demo user created.\n";

    // Seed default data
    foreach (['General','Electronics','Groceries','Clothing'] as $cat)
        $pdo->prepare("INSERT INTO categories (company_id, name) VALUES (?,?)")->execute([$demoCompanyId, $cat]);
    foreach (['Generic','Unbranded'] as $b)
        $pdo->prepare("INSERT INTO brands (company_id, name) VALUES (?,?)")->execute([$demoCompanyId, $b]);
    foreach ([['Pieces','pcs'],['Kilograms','kg'],['Liters','ltr'],['Meters','mtr'],['Boxes','box']] as $u)
        $pdo->prepare("INSERT INTO units (company_id, name, short_name) VALUES (?,?,?)")->execute([$demoCompanyId, $u[0], $u[1]]);
    $pdo->prepare("INSERT INTO customers (company_id, name, phone, email, address) VALUES (?,'Walk-In Customer','','','')")->execute([$demoCompanyId]);
    echo "Demo data seeded.\n";
} else {
    echo "\nDemo company already exists: " . $demo['name'] . " (ID=" . $demo['id'] . ")\n";
}

// ============================================================
// 5. Check RateLimiter
// ============================================================
echo "\n=== RATELIMITER CHECK ===\n";
$rlFile = '/var/www/inventory/core/RateLimiter.php';
if (!file_exists($rlFile)) {
    echo "RateLimiter.php NOT FOUND in /var/www/inventory/core/\n";
    // Search for it
    $found = glob('/var/www/inventory/**/*.php');
    foreach ($found as $f) {
        if (stripos($f, 'RateLimiter') !== false) echo "Found at: $f\n";
    }
} else {
    $content = file_get_contents($rlFile);
    echo "RateLimiter found. Uses Redis: " . (stripos($content, 'redis') !== false ? "YES" : "NO") . "\n";
    echo "First 300 chars:\n" . substr($content, 0, 300) . "\n";
}

echo "\n=== DONE ===\n";
