<?php
/**
 * Enterprise Hardening Verification Script
 * 
 * Run via CLI: php cli/verify_hardening.php
 * Validates that all security and database hardening changes are in effect.
 */

// Minimal bootstrap
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

echo "═══════════════════════════════════════════════════════════\n";
echo "  InvenBill Pro — Enterprise Hardening Verification\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

function check($label, $result, $detail = '') {
    global $passed, $failed;
    if ($result) {
        echo "  ✅ PASS  {$label}\n";
        $passed++;
    } else {
        echo "  ❌ FAIL  {$label}" . ($detail ? " — {$detail}" : '') . "\n";
        $failed++;
    }
}

function warn($label, $detail = '') {
    global $warnings;
    echo "  ⚠️  WARN  {$label}" . ($detail ? " — {$detail}" : '') . "\n";
    $warnings++;
}

// ─── 1. Configuration Checks ───
echo "─── Configuration ───\n";

check('APP_URL does not contain hardcoded IP',
    strpos(APP_URL, '192.168.101.100') === false,
    'APP_URL still contains hardcoded IP: ' . APP_URL
);

check('PASSWORD_MIN_LENGTH is defined and >= 8',
    defined('PASSWORD_MIN_LENGTH') && PASSWORD_MIN_LENGTH >= 8,
    'Current value: ' . (defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 'NOT DEFINED')
);

check('PASSWORD_COMPLEXITY is defined',
    defined('PASSWORD_COMPLEXITY') && PASSWORD_COMPLEXITY === true
);

check('SESSION_IDLE_TIMEOUT is defined',
    defined('SESSION_IDLE_TIMEOUT') && SESSION_IDLE_TIMEOUT > 0,
    'Current value: ' . (defined('SESSION_IDLE_TIMEOUT') ? SESSION_IDLE_TIMEOUT . 's' : 'NOT DEFINED')
);

// ─── 2. Core Security Checks ───
echo "\n─── Core Security ───\n";

// Check Session.php has validateActivity
$sessionSrc = file_get_contents(BASE_PATH . '/core/Session.php');
check('Session::validateActivity() exists',
    strpos($sessionSrc, 'function validateActivity') !== false
);
check('Session::validateFingerprint() exists',
    strpos($sessionSrc, 'function validateFingerprint') !== false
);
check('Session::rotateIdIfNeeded() exists',
    strpos($sessionSrc, 'function rotateIdIfNeeded') !== false
);
check('Session::initFingerprint() exists',
    strpos($sessionSrc, 'function initFingerprint') !== false
);

// Check CSRF.php has rotateToken
$csrfSrc = file_get_contents(BASE_PATH . '/core/CSRF.php');
check('CSRF::rotateToken() exists',
    strpos($csrfSrc, 'function rotateToken') !== false
);

// Check Model.php has sanitizeOrderBy
$modelSrc = file_get_contents(BASE_PATH . '/core/Model.php');
check('Model::sanitizeOrderBy() exists',
    strpos($modelSrc, 'function sanitizeOrderBy') !== false
);
check('Model::sanitizeDirection() exists',
    strpos($modelSrc, 'function sanitizeDirection') !== false
);
check('Model::count() accepts $conditionParams',
    strpos($modelSrc, 'count($conditions = \'\', $conditionParams') !== false
);

// Check index.php has CSP nonce
$indexSrc = file_get_contents(BASE_PATH . '/index.php');
check('index.php generates CSP nonce',
    strpos($indexSrc, 'csp_nonce') !== false
);
check('index.php calls Session::validateActivity()',
    strpos($indexSrc, 'Session::validateActivity()') !== false
);
check('index.php calls Session::validateFingerprint()',
    strpos($indexSrc, 'Session::validateFingerprint()') !== false
);
check('index.php calls Session::rotateIdIfNeeded()',
    strpos($indexSrc, 'Session::rotateIdIfNeeded()') !== false
);
check('CSP does not contain unsafe-inline for scripts',
    strpos($indexSrc, "'unsafe-inline' 'unsafe-eval'") === false,
    'CSP still contains unsafe-inline/unsafe-eval'
);

// ─── 3. Auth Security ───
echo "\n─── Authentication ───\n";

$userModelSrc = file_get_contents(BASE_PATH . '/models/UserModel.php');
check('UserModel::authenticate() uses JOIN companies',
    strpos($userModelSrc, 'JOIN companies') !== false,
    'authenticate() may not check company status'
);
check('UserModel uses fetchAll() for multi-match auth',
    strpos($userModelSrc, '->fetchAll()') !== false
);
check('Password policy checks complexity',
    strpos($userModelSrc, 'PASSWORD_COMPLEXITY') !== false
);

$authSrc = file_get_contents(BASE_PATH . '/controllers/AuthController.php');
check('AuthController calls Session::initFingerprint()',
    strpos($authSrc, 'Session::initFingerprint()') !== false
);

// ─── 4. Input Validation ───
echo "\n─── Input Validation ───\n";

$salesSrc = file_get_contents(BASE_PATH . '/controllers/SalesController.php');
check('SalesController validates date format',
    strpos($salesSrc, 'preg_match') !== false && strpos($salesSrc, 'YYYY-MM-DD') !== false
);
check('SalesController enforces round-off cap',
    strpos($salesSrc, 'abs($roundOff) > 10') !== false
);
check('SalesController validates discount <= subtotal',
    strpos($salesSrc, '$discountAmount > $subtotal') !== false
);

$purchaseSrc = file_get_contents(BASE_PATH . '/controllers/PurchaseController.php');
check('PurchaseController validates date format',
    strpos($purchaseSrc, 'preg_match') !== false && strpos($purchaseSrc, 'YYYY-MM-DD') !== false
);

// ─── 5. File Upload ───
echo "\n─── File Upload Security ───\n";

$helperSrc = file_get_contents(BASE_PATH . '/core/Helper.php');
check('Helper::uploadFile() has GD image reprocessing',
    strpos($helperSrc, 'imagecreatefromjpeg') !== false
);

// ─── 6. Layout Security ───
echo "\n─── Layout/View Security ───\n";

$layoutSrc = file_get_contents(BASE_PATH . '/views/layouts/main.php');
check('main.php uses CSP nonce on inline scripts',
    strpos($layoutSrc, 'nonce=') !== false
);

// ─── 7. Database Config ───
echo "\n─── Database Config ───\n";

$dbConfigSrc = file_get_contents(BASE_PATH . '/config/database.php');
check('database.php has production guard',
    strpos($dbConfigSrc, 'APP_ENV') !== false && strpos($dbConfigSrc, 'production') !== false
);

// ─── 8. Database Migration ───
echo "\n─── Database Migration ───\n";

$migrationFile = BASE_PATH . '/database/enterprise_hardening.sql';
check('enterprise_hardening.sql exists',
    file_exists($migrationFile)
);

if (file_exists($migrationFile)) {
    $migrationSrc = file_get_contents($migrationFile);
    check('Migration contains composite indexes',
        substr_count($migrationSrc, 'CREATE INDEX') >= 10,
        'Found ' . substr_count($migrationSrc, 'CREATE INDEX') . ' CREATE INDEX statements'
    );
    check('Migration contains foreign key constraints',
        substr_count($migrationSrc, 'FOREIGN KEY') >= 10,
        'Found ' . substr_count($migrationSrc, 'FOREIGN KEY') . ' FK statements'
    );
    check('Migration contains unique constraints',
        substr_count($migrationSrc, 'UNIQUE') >= 3,
        'Found ' . substr_count($migrationSrc, 'UNIQUE') . ' UNIQUE statements'
    );
}

// ─── 9. Documentation ───
echo "\n─── Documentation ───\n";

check('Enterprise blueprint exists',
    file_exists(BASE_PATH . '/docs/enterprise_blueprint.md')
);

// ─── Summary ───
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  Results: {$passed} passed, {$failed} failed, {$warnings} warnings\n";
echo "═══════════════════════════════════════════════════════════\n";

if ($failed === 0) {
    echo "\n  🎉 All enterprise hardening checks PASSED!\n\n";
    exit(0);
} else {
    echo "\n  ⚠️  {$failed} check(s) failed. Review the output above.\n\n";
    exit(1);
}
