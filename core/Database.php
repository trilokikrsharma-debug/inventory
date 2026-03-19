<?php
/**
 * Database Class - PDO Wrapper
 * 
 * Provides a singleton database connection using PDO
 * with prepared statement support for security.
 * 
 * Includes connection timeout and single-retry for transient failures.
 */
class Database {
    private static $instance = null;
    private $pdo;
    private $statement;

    /**
     * Private constructor - Singleton pattern
     * 
     * Adds connection timeout (5s) and retry-once on transient failure.
     */
    private function __construct() {
        $this->connect();
    }

    /**
     * Establish a PDO connection with retry support.
     */
    private function connect(int $maxRetries = 2): void {
        $config = require CONFIG_PATH . '/database.php';
        
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        // Merge connection timeout into PDO options
        $options = $config['options'];
        $options[PDO::ATTR_TIMEOUT] = 5; // 5-second connection timeout

        // Ensure critical options are always set (defensive — don't trust config alone)
        $options[PDO::ATTR_ERRMODE]            = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        $options[PDO::ATTR_EMULATE_PREPARES]   = false;

        // Retry-once on transient connection failure (e.g., MySQL restart, network blip)
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
                return; // Success — exit constructor
            } catch (PDOException $e) {
                if ($attempt < $maxRetries) {
                    error_log("[DB] Connection attempt {$attempt} failed: " . $e->getMessage() . " — retrying in 500ms...");
                    usleep(500000); // 500ms backoff before retry
                    continue;
                }
                // Final attempt failed — log and throw (let global handler catch it)
                if (class_exists('Logger')) {
                    Logger::log(Logger::CRITICAL, 'Database Connection Failed', ['error' => $e->getMessage()], Logger::CHANNEL_ERROR);
                }
                error_log("[DB] All connection attempts failed: " . $e->getMessage());
                throw new \RuntimeException('Database connection unavailable. Please try again later.', 0, $e);
            }
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get raw PDO connection
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Prepare and execute a query
     */
    public function query($sql, $params = []) {
        $attempt = 0;
        while (true) {
            try {
                $this->statement = $this->pdo->prepare($sql);
                $this->statement->execute($params);
                return $this;
            } catch (PDOException $e) {
                $canRetry = $attempt === 0
                    && $this->isTransientDisconnect($e)
                    && !$this->isInTransaction();

                if ($canRetry) {
                    $attempt++;
                    if (class_exists('Logger')) {
                        Logger::warning('Transient DB disconnect detected, reconnecting query once', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                    $this->connect();
                    continue;
                }

                if (class_exists('Logger')) {
                    Logger::log(Logger::ERROR, 'Database Exception', [
                        'error' => $e->getMessage(),
                        'sql' => $sql,
                        'params_count' => is_countable($params) ? count($params) : 0,
                    ], Logger::CHANNEL_ERROR);
                }
                throw $e;
            }
        }
    }

    private function isInTransaction(): bool {
        try {
            return $this->pdo instanceof PDO && $this->pdo->inTransaction();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isTransientDisconnect(PDOException $e): bool {
        $message = strtolower((string)$e->getMessage());
        $driverCode = (int)($e->errorInfo[1] ?? 0);

        if (in_array($driverCode, [2006, 2013], true)) {
            return true;
        }

        return str_contains($message, 'server has gone away')
            || str_contains($message, 'lost connection');
    }

    /**
     * Fetch all results
     */
    public function fetchAll() {
        return $this->statement->fetchAll();
    }

    /**
     * Fetch single result
     */
    public function fetch() {
        return $this->statement->fetch();
    }

    /**
     * Fetch single column value
     */
    public function fetchColumn() {
        return $this->statement->fetchColumn();
    }

    /**
     * Get row count
     */
    public function rowCount() {
        return $this->statement->rowCount();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}
}
