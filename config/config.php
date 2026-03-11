<?php
/**
 * Application Configuration
 * 
 * Central configuration file for the Inventory & Billing system.
 * 
 * Environment variables take precedence over defaults.
 * For production, set these in your server environment or a .env loader:
 *   APP_URL, APP_ENV, SESSION_LIFETIME
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

// Application Info
define('APP_NAME', 'KARSO');
define('APP_VERSION', '2.0.0');

// APP_URL: Use environment variable if set, otherwise auto-detect from request.
// For production, ALWAYS set APP_URL in your environment (Apache SetEnv, .env file, etc.)
$_autoProtocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_autoHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$_autoBasePath = str_replace('\\', '/', dirname($_scriptName));
if ($_autoBasePath === '/' || $_autoBasePath === '.') {
    $_autoBasePath = '';
}
define('APP_URL', getenv('APP_URL') ?: "{$_autoProtocol}://{$_autoHost}{$_autoBasePath}");
unset($_autoProtocol, $_autoHost, $_scriptName, $_autoBasePath);

// Environment hint (development / production)
// Set APP_ENV=production on your production server to enable stricter defaults.
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Timezone (used by PHP date functions globally)
date_default_timezone_set('Asia/Kolkata');

// Paths
define('CONFIG_PATH', BASE_PATH . '/config');
define('CORE_PATH', BASE_PATH . '/core');
define('CONTROLLER_PATH', BASE_PATH . '/controllers');
define('MODEL_PATH', BASE_PATH . '/models');
define('VIEW_PATH', BASE_PATH . '/views');
define('ASSET_PATH', BASE_PATH . '/assets');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('LOG_PATH', BASE_PATH . '/logs');
define('CACHE_PATH', BASE_PATH . '/cache');
define('CLI_PATH', BASE_PATH . '/cli');

// Session Configuration
define('SESSION_NAME', 'invenbill_session');
define('SESSION_LIFETIME', (int)(getenv('SESSION_LIFETIME') ?: 7200)); // 2 hours default

// CSRF Token
define('CSRF_TOKEN_NAME', '_csrf_token');

// Upload Settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Pagination
define('RECORDS_PER_PAGE', 15);

// Password Policy
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_COMPLEXITY', true); // Require 1 uppercase + 1 digit

// Session Idle Timeout (seconds) Ã¢â‚¬â€ server-side enforcement
define('SESSION_IDLE_TIMEOUT', (int)(getenv('SESSION_IDLE_TIMEOUT') ?: 1800)); // 30 min default

// Date formats
define('DATE_FORMAT_DB', 'Y-m-d');
define('DATETIME_FORMAT_DB', 'Y-m-d H:i:s');

// Report guardrail: maximum rows fetched in a single report query
define('REPORT_MAX_ROWS', 2000);

// Redis Configuration
$redisEnabledEnv = getenv('REDIS_ENABLED');
define(
    'REDIS_ENABLED',
    $redisEnabledEnv === false
        ? extension_loaded('redis')
        : in_array(strtolower((string)$redisEnabledEnv), ['1', 'true', 'yes', 'on'], true)
);
define('REDIS_HOST', getenv('REDIS_HOST') ?: '127.0.0.1');
define('REDIS_PORT', (int)(getenv('REDIS_PORT') ?: 6379));
define('REDIS_PASSWORD', getenv('REDIS_PASSWORD') ?: null);
define('REDIS_DB', (int)(getenv('REDIS_DB') ?: 0));
define('REDIS_PREFIX', getenv('REDIS_PREFIX') ?: 'invenbill:');

// Cache TTL Defaults (seconds)
define('CACHE_TTL_DASHBOARD', 300);   // 5 minutes
define('CACHE_TTL_SETTINGS', 3600);   // 1 hour
define('CACHE_TTL_QUERY', 60);        // 1 minute

// Razorpay Configuration
define('RAZORPAY_KEY', getenv('RAZORPAY_KEY') ?: '');
define('RAZORPAY_SECRET', getenv('RAZORPAY_SECRET') ?: '');

// IMPORTANT: Set this to the Webhook Secret from Razorpay Dashboard -> Webhooks -> Secret
// This is DIFFERENT from the API secret and must be set separately.
define('RAZORPAY_WEBHOOK_SECRET', getenv('RAZORPAY_WEBHOOK_SECRET') ?: '');

// Asset Versioning
define('ASSET_VERSION', APP_VERSION . '.' . (getenv('ASSET_BUILD') ?: '1'));
