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
        // Ensure logs directory exists
        $logDir = BASE_PATH . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

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

        // Global exception handler
        set_exception_handler([self::class, 'handleException']);

        // Global error handler — convert PHP errors to exceptions
        set_error_handler([self::class, 'handleError']);
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
            '[UNCAUGHT EXCEPTION] ' . $exception->getMessage()
            . ' in ' . $exception->getFile() . ':' . $exception->getLine()
            . "\n" . $exception->getTraceAsString()
        );

        if (!headers_sent()) {
            http_response_code(500);
        }

        $errorDetail = '';
        if (defined('APP_ENV') && APP_ENV !== 'production') {
            $errorDetail = $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine();
        }

        $errorPage = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/..') . '/views/errors/500.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo '<h1>500 — Internal Server Error</h1>';
        }
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
}
