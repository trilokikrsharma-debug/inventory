<?php
/**
 * Database Configuration
 * 
 * This file contains the database connection settings.
 * 
 * For production, set these environment variables on your server:
 *   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
 * 
 * Local XAMPP defaults are used as fallback.
 */

// SECURITY: Database credentials are loaded from environment variables.
// Empty env values are treated as missing to avoid accidental blank overrides.
$firstEnv = static function (array $keys, $default = null, bool $allowEmpty = false) {
    foreach ($keys as $key) {
        $value = getenv($key);
        if ($value === false) {
            continue;
        }
        if ($allowEmpty || trim((string)$value) !== '') {
            return (string)$value;
        }
    }
    return $default;
};

$dbUser = $firstEnv(
    ['DB_USER', 'DB_USERNAME'],
    (defined('APP_ENV') && APP_ENV !== 'production') ? 'root' : null
);
$dbPass = $firstEnv(
    ['DB_PASS', 'DB_PASSWORD'],
    (defined('APP_ENV') && APP_ENV !== 'production') ? '' : null,
    true
);

if (defined('APP_ENV') && APP_ENV === 'production') {
    if ($dbUser === null || trim((string)$dbUser) === '' || $dbPass === null || trim((string)$dbPass) === '') {
        error_log('[FATAL] DB_USER and DB_PASS/DB_PASSWORD must be set in production.');
        http_response_code(503);
        die('Service unavailable: database configuration error. Check server environment.');
    }
}

return [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => (int)(getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: 'inventory_billing',
    'username' => $dbUser !== null ? (string)$dbUser : '',
    'password' => $dbPass !== null ? (string)$dbPass : '',
    'charset'  => 'utf8mb4',
    'collation'=> 'utf8mb4_unicode_ci',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]
];
