<?php
// Standalone demo + signup fixer - no dependencies on app classes
error_reporting(E_ALL); ini_set('display_errors', 1);

$pdo = new PDO("mysql:host=127.0.0.1;dbname=invenbill;charset=utf8mb4",
    'invenbill_app', 'InvenBillPass@2026',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// 1. Show companies table columns
echo "=== COMPANIES COLUMNS ===\n";
foreach ($pdo->query("SHOW COLUMNS FROM companies")->fetchAll() as $c) {
    echo $c['Field'] . " | Null=" . $c['Null'] . " | Def=" . $c['Default'] . "\n";
}

// 2. All tables
echo "\n=== ALL TABLES ===\n";
echo implode(", ", $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)) . "\n";

// 3. RateLimiter check
$rlFiles = glob('/var/www/inventory/{core,app/core,app}/*ateLimit*.php', GLOB_BRACE)
    ?: glob('/var/www/inventory/**/*ateLimit*.php');
echo "\n=== RATELIMITER FILE ===\n";
if ($rlFiles) {
    $content = file_get_contents($rlFiles[0]);
    echo "File: " . $rlFiles[0] . "\n";
    echo "Uses Redis: " . (stripos($content, 'redis') !== false ? "YES" : "NO") . "\n";
    echo "Uses DB/file: " . (stripos($content, 'rate_limits') !== false ? "YES (DB table)" : (strpos($content, 'file') !== false ? "YES (file)" : "UNKNOWN")) . "\n";
} else {
    echo "NOT FOUND\n";
}

// 4. is_demo column
$hasDemoCol = (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='invenbill' AND table_name='companies' AND column_name='is_demo'")->fetchColumn();
echo "\n=== is_demo column: " . ($hasDemoCol ? "EXISTS" : "MISSING, CREATING...") . " ===\n";
if (!$hasDemoCol) {
    $pdo->exec("ALTER TABLE companies ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0");
    echo "Created.\n";
}

// 5. Create demo company
$demo = $pdo->query("SELECT id, name, status FROM companies WHERE is_demo=1 LIMIT 1")->fetch();
if (!$demo) {
    echo "\n=== CREATING DEMO COMPANY ===\n";
    $pdo->exec("INSERT INTO companies (name, slug, saas_plan_id, subscription_status, trial_ends_at, plan, status, max_users, max_products, is_demo) 
                VALUES ('Demo Company', 'demo-company', 1, 'active', DATE_ADD(NOW(), INTERVAL 365 DAY), 'starter', 'active', 10, 1000, 1)");
    $dcId = (int)$pdo->lastInsertId();
    echo "Company ID: $dcId\n";

    // Company settings
    $pdo->prepare("INSERT IGNORE INTO company_settings 
        (company_id, company_name, company_email, company_phone, company_address, company_city, company_state, company_country, currency_symbol, currency_code, enable_gst, enable_tax, tax_rate, low_stock_threshold, invoice_prefix, purchase_prefix, payment_prefix, receipt_prefix) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$dcId,'Demo Company','demo@tsalegacy.shop','9999999999','Demo Street','Mumbai','Maharashtra','India','₹','INR',1,1,18,10,'INV-','PUR-','PAY-','REC-']);
    echo "Settings created.\n";

    // Role
    $pdo->prepare("INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system) VALUES (?,?,?,?,0,1)")
        ->execute([$dcId,'admin','Administrator','Demo admin role']);
    $rId = (int)$pdo->lastInsertId();

    // Demo user
    $hash = password_hash('Demo@2026Secure', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (company_id, username, email, password, full_name, phone, role, role_id, is_active, is_super_admin, created_at) VALUES (?,?,?,?,?,?,?,?,1,0,NOW())")
        ->execute([$dcId,'demo',"demo+{$dcId}@invenbill.com",$hash,'Demo User','9999999999','admin',$rId]);
    echo "Demo user created.\n";

    // Default data
    foreach (['General','Electronics','Groceries','Clothing'] as $c)
        $pdo->prepare("INSERT INTO categories (company_id,name) VALUES (?,?)")->execute([$dcId,$c]);
    foreach (['Generic','Unbranded'] as $b)
        $pdo->prepare("INSERT INTO brands (company_id,name) VALUES (?,?)")->execute([$dcId,$b]);
    foreach ([['Pieces','pcs'],['Kilograms','kg'],['Liters','ltr'],['Meters','mtr'],['Boxes','box']] as $u)
        $pdo->prepare("INSERT INTO units (company_id,name,short_name) VALUES (?,?,?)")->execute([$dcId,$u[0],$u[1]]);
    $pdo->prepare("INSERT INTO customers (company_id,name,phone,email,address) VALUES (?,'Walk-In Customer','','','')")->execute([$dcId]);
    echo "Demo seed data done.\n";
} else {
    echo "\nDemo company OK: " . $demo['name'] . " (ID=" . $demo['id'] . ", status=" . $demo['status'] . ")\n";
}

// 6. Check signup issue - rate_limits table
$hasRlTable = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='invenbill' AND table_name='rate_limits'")->fetchColumn();
echo "\n=== rate_limits table: " . ($hasRlTable ? "EXISTS" : "MISSING") . " ===\n";
if (!$hasRlTable) {
    // Create file-based fallback won't help, create DB table for RateLimiter
    $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `key` VARCHAR(255) NOT NULL,
        attempts INT UNSIGNED DEFAULT 0,
        reset_at INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_key (`key`)
    )");
    echo "rate_limits table created.\n";
}

echo "\n=== ALL DONE ===\n";
echo "Test Demo: https://tsalegacy.shop/index.php?page=demo_login\n";
echo "Test Signup: https://tsalegacy.shop/index.php?page=signup\n";
