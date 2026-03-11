<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/core/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "--- CHECKING ISOLATION & SCHEMA ---\n";
    $tables = [
        'companies', 'users', 'products', 'invoices',
        'saas_plans', 'tenant_subscriptions', 'tenant_billing_history', 'jobs'
    ];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table $table EXISTS.\n";
            if ($table !== 'companies' && $table !== 'saas_plans') {
                $col = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'company_id'");
                if ($col && $col->rowCount() > 0) {
                    echo "  -> has company_id.\n";
                } else {
                    echo "  -> MISSING company_id!\n";
                }
            }
        } else {
            echo "Table $table DOES NOT EXIST!\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
