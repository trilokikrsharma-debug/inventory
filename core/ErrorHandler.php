<?php
/**
 * Error Handler Bootstrap
 *
 * Sets up environment-aware error handling:
 * - Production: hides errors, logs to file
 * - Development: shows all errors
 * - Global exception and error handlers
 *
 * Extracted from index.php lines 15-73.
 */
class ErrorHandler {
    /**
     * Initialize error handling for the current environment.
     */
    public static function register(): void {
        $logDir = BASE_PATH . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Production hardening: hide PHP version and suppress HTML error output.
        ini_set('expose_php', '0');
        ini_set('html_errors', '0');
        header_remove('X-Powered-By');

        if (APP_ENV === 'production') {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            ini_set('log_errors', '1');
            ini_set('error_log', $logDir . '/php_error.log');
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        } else {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            ini_set('log_errors', '1');
            ini_set('error_log', $logDir . '/php_error.log');
            error_reporting(E_ALL);
        }

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Handle uncaught exceptions.
     */
    public static function handleException(\Throwable $exception): void {
        if (class_exists('Logger')) {
            Logger::log(Logger::CRITICAL, 'Uncaught Exception', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ], Logger::CHANNEL_ERROR);
        }

        error_log(
            '[UNCAUGHT EXCEPTION] [' . (defined('REQUEST_ID') ? REQUEST_ID : '-') . '] ' . $exception->getMessage()
            . ' in ' . $exception->getFile() . ':' . $exception->getLine()
            . "\n" . $exception->getTraceAsString()
        );

        if (!headers_sent()) {
            http_response_code(500);
        }

        self::renderErrorPage();
        exit;
    }

    /**
     * Convert PHP errors to ErrorException (non-fatal only).
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    /**
     * Catch fatal errors that bypass the exception handler.
     */
    public static function handleShutdown(): void {
        $error = error_get_last();
        if (!$error) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if (!in_array((int)($error['type'] ?? 0), $fatalTypes, true)) {
            return;
        }

        error_log(
            '[SHUTDOWN ERROR] [' . (defined('REQUEST_ID') ? REQUEST_ID : '-') . '] '
            . ($error['message'] ?? 'Unknown fatal error')
            . ' in ' . ($error['file'] ?? '-') . ':' . ($error['line'] ?? 0)
        );

        if (headers_sent()) {
            return;
        }

        http_response_code(500);
        self::renderErrorPage();
    }

    /**
     * Render the generic production error page without leaking internals.
     */
    private static function renderErrorPage(): void {
        $errorPage = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/..') . '/views/errors/500.php';
        if (file_exists($errorPage)) {
            include $errorPage;
            return;
        }

        echo '<h1>500 - Internal Server Error</h1>';
    }
}
