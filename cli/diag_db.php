<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "--- DB VERIFICATION ---\n";
    $tables = ['saas_plans', 'tenant_subscriptions', 'tenant_billing_history', 'companies'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table $table EXISTS.\n";
        } else {
            echo "Table $table DOES NOT EXIST.\n";
        }
    }

    echo "\n--- TENANT ISOLATION CHECK ---\n";
    $checkTables = ['users', 'products', 'invoices', 'customers', 'inventory', 'sales', 'purchases'];
    foreach ($checkTables as $table) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'company_id'");
        if ($stmt && $stmt->rowCount() > 0) {
            echo "Table $table has company_id.\n";
        } else {
            echo "Table $table is MISSING company_id.\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
