<?php
// Direct superadmin creation script
$host = '127.0.0.1';
$db   = 'invenbill';
$user = 'invenbill_app';
$pass = 'InvenBillPass@2026';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "DB Connected OK\n";
    
    // Ensure superadmin role exists
    $pdo->exec("INSERT IGNORE INTO roles (name, display_name, is_super_admin) VALUES ('superadmin', 'Super Admin', 1)");
    $roleId = $pdo->query("SELECT id FROM roles WHERE is_super_admin=1 LIMIT 1")->fetchColumn();
    echo "Role ID: $roleId\n";
    
    // Check table structure for users
    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    echo "User columns: " . implode(', ', $cols) . "\n";
    
    // Hash password
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    echo "Hash: $hash\n";
    
    // Remove old
    $pdo->exec("DELETE FROM users WHERE username='superadmin' OR email='superadmin@tsalegacy.shop'");
    
    // Insert superadmin (check which column for name/username)
    $hasUsername = in_array('username', $cols);
    $hasCompanyId = in_array('company_id', $cols);
    
    // Build safe insert with all required fields
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role_id, is_super_admin, is_active, company_id, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, 1, NULL, NOW())");
    $stmt->execute(['superadmin', 'superadmin@tsalegacy.shop', $hash, 'Super Admin', '0000000000', $roleId]);
    
    echo "Superadmin inserted. ID: " . $pdo->lastInsertId() . "\n";
    
    $user = $pdo->query("SELECT id, username, email, is_super_admin, is_active FROM users WHERE username='superadmin'")->fetch(PDO::FETCH_ASSOC);
    echo "Verified: " . json_encode($user) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
