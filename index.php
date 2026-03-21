<?php
/**
 * InvenBill Pro — Enterprise Inventory & Billing SaaS
 * 
 * Front Controller / Entry Point
 * 
 * Architecture:
 *   1. Load config + autoloader
 *   2. Register error handlers
 *   3. Wire DI container
 *   4. Build middleware pipeline
 *   5. Capture request → run pipeline → dispatch router
 *   6. Auto-inject CSRF tokens into output
 */

// ─── Bootstrap ───────────────────────────────────────────────
header('Content-Type: text/html; charset=UTF-8');
define('BASE_PATH', __DIR__);

// ─── Maintenance Mode Check ─────────────────────────────────
$maintenanceFile = __DIR__ . '/storage/maintenance.lock';
if (file_exists($maintenanceFile)) {
    http_response_code(503);
    header('Retry-After: 3600');
    $maintenancePage = __DIR__ . '/views/errors/maintenance.php';
    if (file_exists($maintenancePage)) {
        include $maintenancePage;
    } else {
        echo '<h1>503 — Service Temporarily Unavailable</h1><p>We are performing scheduled maintenance. Please check back shortly.</p>';
    }
    exit;
}

// ─── Request Tracing ─────────────────────────────────────────
define('REQUEST_ID', bin2hex(random_bytes(8)));
header('X-Request-ID: ' . REQUEST_ID);

// Load configuration (defines paths, constants, APP_ENV, etc.)
require_once BASE_PATH . '/config/config.php';

// Composer autoloader — replaces all manual require_once calls
require_once BASE_PATH . '/vendor/autoload.php';

// Register error/exception handlers (environment-aware)
ErrorHandler::register();

// Wire DI container bindings
require_once BASE_PATH . '/bootstrap/container.php';
require_once BASE_PATH . '/middleware/RbacMiddleware.php';

// ─── Capture Request ─────────────────────────────────────────
$request = Request::capture();

// Store in container for global access
Container::instance('Request', $request);

// ─── Middleware Pipeline ─────────────────────────────────────
$pipeline = new Pipeline();
$pipeline->pipe(new SecurityHeadersMiddleware());
$pipeline->pipe(new RequestGuardMiddleware());
$pipeline->pipe(new SessionMiddleware());
$pipeline->pipe(new RateLimitMiddleware());
$pipeline->pipe(new CsrfMiddleware());
$pipeline->pipe(new AuthMiddleware());
$pipeline->pipe(new TenantMiddleware());
$pipeline->pipe(new SubscriptionGuardMiddleware());
$pipeline->pipe(new RbacMiddleware());
$pipeline->pipe(new DemoGuardMiddleware());

// ─── Execute ─────────────────────────────────────────────────
ob_start();

// Run all middleware (security headers, session, auth, rate limiting, etc.)
$pipeline->run($request);

// Dispatch to controller
$router = Container::make('Router');
$router->dispatch($request);

// ─── Output Processing ──────────────────────────────────────
$output = ob_get_clean();

// Auto-inject CSRF tokens into forms and <head>
$output = CsrfInjector::inject($output);

echo $output;
