<?php
define('BASE_PATH', '/var/www/inventory');
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/core/Database.php';

$db = Database::getInstance();

$starterFeatures = json_encode(['inventory' => true, 'invoicing' => true, 'api' => false, 'crm' => false, 'hr' => false, 'multi_user' => true, 'quotations' => true, 'advanced_reports' => true]);
$proFeatures = json_encode(['inventory' => true, 'invoicing' => true, 'api' => true, 'crm' => true, 'hr' => true, 'multi_user' => true, 'advanced_reports' => true, 'backup' => true, 'backup_restore' => true]);
$freeFeatures = json_encode(['inventory' => true, 'invoicing' => true, 'multi_user' => false]);

// Update Starter Plan
$db->query("UPDATE saas_plans SET price = 99.00, offer_price = 0, name = 'Starter', description = 'For growing businesses', features = ? WHERE (name LIKE '%Starter%' OR name LIKE '%Basic%') AND is_featured = 1", [$starterFeatures]);
if ($db->rowCount() === 0) {
    // try any matching if nothing was updated
    $db->query("UPDATE saas_plans SET price = 99.00, is_featured = 1, description = 'For growing businesses', name = 'Starter', features = ? WHERE price < 1500 AND price > 0 LIMIT 1", [$starterFeatures]);
}

// Update Pro Plan
$db->query("UPDATE saas_plans SET price = 299.00, offer_price = 0, name = 'Pro', description = 'Full power for enterprises', features = ? WHERE (name LIKE '%Pro%' OR name LIKE '%Premium%') AND price >= 1500", [$proFeatures]);
if ($db->rowCount() === 0) {
    $db->query("UPDATE saas_plans SET price = 299.00, description = 'Full power for enterprises', name = 'Pro', features = ? WHERE price >= 99 LIMIT 1", [$proFeatures]);
}

// Update Free Plan
$db->query("UPDATE saas_plans SET price = 0, offer_price = 0, name = 'Free', description = 'Perfect to get started', features = ? WHERE price = 0", [$freeFeatures]);

echo "PLANS UPDATED SUCCESSFULLY.\n";
