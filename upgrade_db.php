<?php
define('BASE_PATH', __DIR__);
require 'core/Controller.php';
require 'core/Router.php';
require 'core/Session.php';
require 'core/Logger.php';
require 'config/config.php';
require 'core/Database.php';
require 'core/Model.php';

try {
    $db = Database::getInstance();

    $db->query("CREATE TABLE IF NOT EXISTS api_keys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT UNSIGNED NOT NULL,
        api_key VARCHAR(64) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
    )");

    $db->query("CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT UNSIGNED NOT NULL,
        name VARCHAR(100) NOT NULL,
        designation VARCHAR(100),
        status ENUM('Active', 'On Leave', 'Terminated') DEFAULT 'Active',
        joined_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
    )");

    echo "DB Migration Success\n";
} catch (Exception $e) {
    echo "DB Migration Failed: " . $e->getMessage() . "\n";
}
