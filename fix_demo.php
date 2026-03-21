<?php
// Check and fix Demo + Signup on production

$host = '127.0.0.1';
$db   = 'invenbill';
$user = 'invenbill_app';
$pass = 'InvenBillPass@2026';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "=== CHECKING TABLES ===\n";

    // Check companies table
    $cols = $pdo->query("SHOW COLUMNS FROM companies")->fetchAll(PDO::FETCH_COLUMN);
    echo "Companies columns: " . implode(', ', $cols) . "\n";

    // Check if demo company exists
    $hasIsDemo = in_array('is_demo', $cols);
    echo "Has is_demo column: " . ($hasIsDemo ? "YES" : "NO") . "\n";

    if ($hasIsDemo) {
        $demo = $pdo->query("SELECT * FROM companies WHERE is_demo=1 LIMIT 1")->fetch();
        echo "Demo company: " . ($demo ? json_encode($demo) : "NONE") . "\n";
    }

    // Check all companies
    $companies = $pdo->query("SELECT id, name, status FROM companies LIMIT 5")->fetchAll();
    echo "All companies: " . json_encode($companies) . "\n";

    // Check RateLimiter class - does it use Redis or file-based?
    echo "\n=== CREATING DEMO COMPANY ===\n";

    if ($hasIsDemo) {
        // Create demo company if not exists
        $demoExists = $pdo->query("SELECT COUNT(*) FROM companies WHERE is_demo=1")->fetchColumn();
        if ($demoExists == 0) {
            // Check for is_demo column type
            $pdo->exec("INSERT INTO companies (name, slug, saas_plan_id, subscription_status, trial_ends_at, plan, status, max_users, max_products, is_demo) 
                        VALUES ('Demo Company', 'demo-company', 1, 'active', DATE_ADD(NOW(), INTERVAL 365 DAY), 'starter', 'active', 10, 1000, 1)");
            $demoCompanyId = $pdo->lastInsertId();
            echo "Demo company created: ID=$demoCompanyId\n";

            // Create settings for demo company
            $pdo->exec("INSERT INTO company_settings (company_id, company_name, company_email, company_phone, company_address, company_city, company_state, company_country, currency_symbol, currency_code, enable_gst, enable_tax, tax_rate, low_stock_threshold, invoice_prefix, purchase_prefix, payment_prefix, receipt_prefix) 
                        VALUES ($demoCompanyId, 'Demo Company', 'demo@tsalegacy.shop', '9999999999', 'Demo Address', 'Mumbai', 'Maharashtra', 'India', '₹', 'INR', 1, 1, 18, 10, 'INV-', 'PUR-', 'PAY-', 'REC-')");
            echo "Demo settings created.\n";

            // Create demo admin role
            $pdo->exec("INSERT INTO roles (company_id, name, display_name, description, is_super_admin, is_system)
                        VALUES ($demoCompanyId, 'admin', 'Administrator', 'Full tenant-level access', 0, 1)");
            $demoRoleId = $pdo->lastInsertId();
            echo "Demo role created: ID=$demoRoleId\n";

            // Create demo user
            $hash = password_hash('Demo@2026Secure', PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO users (company_id, username, email, password, full_name, phone, role, role_id, is_active, is_super_admin, created_at) 
                           VALUES (?, 'demo', ?, ?, 'Demo User', '9999999999', 'admin', ?, 1, 0, NOW())")
                ->execute([$demoCompanyId, "demo+$demoCompanyId@invenbill.com", $hash, $demoRoleId]);
            echo "Demo user created.\n";

            // Seed categories, brands, units for demo
            foreach (['General', 'Electronics', 'Groceries', 'Clothing'] as $cat) {
                $pdo->prepare("INSERT INTO categories (company_id, name) VALUES (?,?)")->execute([$demoCompanyId, $cat]);
            }
            foreach (['Generic', 'Unbranded'] as $brand) {
                $pdo->prepare("INSERT INTO brands (company_id, name) VALUES (?,?)")->execute([$demoCompanyId, $brand]);
            }
            foreach ([['Pieces','pcs'],['Kilograms','kg'],['Liters','ltr'],['Meters','mtr'],['Boxes','box']] as $u) {
                $pdo->prepare("INSERT INTO units (company_id, name, short_name) VALUES (?,?,?)")->execute([$demoCompanyId, $u[0], $u[1]]);
            }
            $pdo->exec("INSERT INTO customers (company_id, name, phone, email, address) VALUES ($demoCompanyId, 'Walk-In Customer', '', '', '')");
            echo "Demo data seeded.\n";
        } else {
            echo "Demo company already exists! OK\n";
        }
    } else {
        echo "ERROR: is_demo column doesn't exist in companies table!\n";
        // Need to add it
        $pdo->exec("ALTER TABLE companies ADD COLUMN is_demo TINYINT(1) DEFAULT 0 AFTER status");
        echo "Added is_demo column. Rerun this script.\n";
    }

    echo "\n=== CHECK RATE LIMITER ===\n";
    // Check if rate_limits table exists (file-based fallback)
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in DB: " . implode(', ', $tables) . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
