#!/bin/bash
# =============================================================
# Production Setup Script: Fix Demo + Deploy Git Changes
# =============================================================
set -e

APP_DIR="/var/www/inventory"
DB_USER="invenbill_app"
DB_PASS="InvenBillPass@2026"
DB_NAME="invenbill"
MYSQL_CMD="mysql -u$DB_USER -p$DB_PASS $DB_NAME"

echo "========================================"
echo "  InvenBill Pro — Production Setup"
echo "========================================"

# ── 1. Git Pull ──────────────────────────────────────────────
echo ""
echo "[1/5] Pulling latest code from GitHub..."
cd $APP_DIR
git pull origin main 2>&1 || git pull origin master 2>&1 || echo "Git pull done (or already up to date)"

# ── 2. Fix Demo Company ───────────────────────────────────────
echo ""
echo "[2/5] Setting up demo company..."
php8.2 - <<'PHPEOF'
<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=invenbill;charset=utf8mb4",
    'invenbill_app', 'InvenBillPass@2026',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// Add is_demo column if missing
$hasDemoCol = (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='invenbill' AND table_name='companies' AND column_name='is_demo'")->fetchColumn();
if (!$hasDemoCol) {
    $pdo->exec("ALTER TABLE companies ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0");
    echo "  ✓ Added is_demo column\n";
}

// Check/create saas_plan id=1
$hasPlan = (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='invenbill' AND table_name='saas_plans'")->fetchColumn();
if ($hasPlan) {
    $planExists = (bool) $pdo->query("SELECT COUNT(*) FROM saas_plans WHERE id=1")->fetchColumn();
    if (!$planExists) {
        $pdo->exec("INSERT INTO saas_plans (id, name, display_name, price, billing_cycle, max_users, max_products, is_active) VALUES (1, 'starter', 'Starter', 0, 'monthly', 3, 500, 1)");
        echo "  ✓ Created starter saas_plan (id=1)\n";
    } else {
        echo "  ✓ saas_plan id=1 exists\n";
    }
}

// Get companies columns
$companyCols = $pdo->query("SHOW COLUMNS FROM companies")->fetchAll(PDO::FETCH_COLUMN);

// Check demo company
$demo = $pdo->query("SELECT * FROM companies WHERE is_demo=1 LIMIT 1")->fetch();
if (!$demo) {
    echo "  Creating demo company...\n";
    
    // Build column list dynamically based on what exists
    $cols = ['name', 'slug', 'status', 'is_demo'];
    $vals = ['Demo Company', 'demo-company', 'active', 1];
    
    if (in_array('saas_plan_id', $companyCols)) { $cols[] = 'saas_plan_id'; $vals[] = 1; }
    if (in_array('subscription_status', $companyCols)) { $cols[] = 'subscription_status'; $vals[] = 'active'; }
    if (in_array('trial_ends_at', $companyCols)) { $cols[] = 'trial_ends_at'; $vals[] = date('Y-m-d H:i:s', strtotime('+1 year')); }
    if (in_array('plan', $companyCols)) { $cols[] = 'plan'; $vals[] = 'starter'; }
    if (in_array('max_users', $companyCols)) { $cols[] = 'max_users'; $vals[] = 10; }
    if (in_array('max_products', $companyCols)) { $cols[] = 'max_products'; $vals[] = 1000; }
    
    $sql = "INSERT INTO companies (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($vals), '?')) . ")";
    $pdo->prepare($sql)->execute($vals);
    $dcId = (int)$pdo->lastInsertId();
    echo "  ✓ Demo company created: ID=$dcId\n";
    
    // Company settings
    $hasCsTable = (bool) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='invenbill' AND table_name='company_settings'")->fetchColumn();
    if ($hasCsTable) {
        $csCols = $pdo->query("SHOW COLUMNS FROM company_settings")->fetchAll(PDO::FETCH_COLUMN);
        $csInsertCols = ['company_id','company_name','company_email'];
        $csInsertVals = [$dcId,'Demo Company','demo@tsalegacy.shop'];
        foreach (['company_phone'=>'9999999999','company_address'=>'Demo Street','company_city'=>'Mumbai','company_state'=>'Maharashtra','company_country'=>'India','currency_symbol'=>'₹','currency_code'=>'INR','enable_gst'=>1,'enable_tax'=>1,'tax_rate'=>18,'low_stock_threshold'=>10,'invoice_prefix'=>'INV-','purchase_prefix'=>'PUR-','payment_prefix'=>'PAY-','receipt_prefix'=>'REC-'] as $c=>$v) {
            if (in_array($c, $csCols)) { $csInsertCols[] = $c; $csInsertVals[] = $v; }
        }
        $pdo->prepare("INSERT IGNORE INTO company_settings (" . implode(',', $csInsertCols) . ") VALUES (" . implode(',', array_fill(0, count($csInsertVals), '?')) . ")")->execute($csInsertVals);
        echo "  ✓ Company settings created\n";
    }
    
    // Demo role
    $pdo->prepare("INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system) VALUES (?,?,?,?,0,1)")
        ->execute([$dcId,'admin','Administrator','Demo company admin role']);
    $roleId = (int)$pdo->lastInsertId();
    echo "  ✓ Demo role created\n";
    
    // Demo user
    $hash = password_hash('Demo@2026Secure', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (company_id, username, email, password, full_name, phone, role, role_id, is_active, is_super_admin, created_at) VALUES (?,?,?,?,?,?,?,?,1,0,NOW())")
        ->execute([$dcId,'demo',"demo+{$dcId}@invenbill.com",$hash,'Demo User','9999999999','admin',$roleId]);
    echo "  ✓ Demo user created\n";
    
    // Seed data
    foreach (['General','Electronics','Groceries','Clothing'] as $c)
        $pdo->prepare("INSERT INTO categories (company_id,name) VALUES (?,?)")->execute([$dcId,$c]);
    foreach (['Generic','Unbranded'] as $b)
        $pdo->prepare("INSERT INTO brands (company_id,name) VALUES (?,?)")->execute([$dcId,$b]);
    foreach ([['Pieces','pcs'],['Kilograms','kg'],['Liters','ltr'],['Meters','mtr'],['Boxes','box']] as $u)
        $pdo->prepare("INSERT INTO units (company_id,name,short_name) VALUES (?,?,?)")->execute([$dcId,$u[0],$u[1]]);
    $pdo->prepare("INSERT INTO customers (company_id,name,phone,email,address) VALUES (?,'Walk-In Customer','','','')")->execute([$dcId]);
    echo "  ✓ Demo seed data done\n";
} else {
    echo "  ✓ Demo company already exists (ID=" . $demo['id'] . ")\n";
}

// Check rate_limits table
$hasRlTable = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='invenbill' AND table_name='rate_limits'")->fetchColumn();
if (!$hasRlTable) {
    $pdo->exec("CREATE TABLE rate_limits (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        \`key\` VARCHAR(255) NOT NULL,
        attempts INT UNSIGNED DEFAULT 0,
        reset_at INT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_key (\`key\`)
    )");
    echo "  ✓ rate_limits table created\n";
} else {
    echo "  ✓ rate_limits table OK\n";
}

echo "  ✓ Database setup complete\n";
PHPEOF

# ── 3. Permissions ───────────────────────────────────────────
echo ""
echo "[3/5] Fixing file permissions..."
chown -R www-data:www-data $APP_DIR/storage $APP_DIR/uploads $APP_DIR/logs 2>/dev/null || true
chmod -R 755 $APP_DIR/storage $APP_DIR/uploads $APP_DIR/logs 2>/dev/null || true
echo "  ✓ Permissions OK"

# ── 4. Remove temp files ─────────────────────────────────────
echo ""
echo "[4/5] Cleaning up temp scripts..."
rm -f $APP_DIR/fix_demo*.php $APP_DIR/make_admin.php $APP_DIR/check_schema*.php $APP_DIR/mk_admin.sh
rm -f /home/KARSO/fix_demo*.php /home/KARSO/make_admin.php /home/KARSO/check_schema*.php /home/KARSO/mk_admin.sh /home/KARSO/fix_demo3.php
echo "  ✓ Cleanup done"

# ── 5. Restart services ──────────────────────────────────────
echo ""
echo "[5/5] Restarting services..."
systemctl reload nginx 2>/dev/null || true
systemctl reload php8.2-fpm 2>/dev/null || true
echo "  ✓ Services restarted"

echo ""
echo "========================================"
echo "  ✅ Setup Complete!"
echo "  🌐 https://tsalegacy.shop"
echo "  👑 superadmin / admin123"
echo "  🎮 Demo: https://tsalegacy.shop/index.php?page=demo_login"
echo "========================================"
