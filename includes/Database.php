<?php
/**
 * Database Connection Class
 * Provides a singleton database connection with PDO
 */

// Only include config if not already included
if (!defined('DB_HOST') && file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
}

// Check if class already exists
if (!class_exists('Database')) {

    class Database {
        private static $instance = null;
        private $conn = null;

        private const DB_PING_TIMEOUT_SECONDS = 0.25;
        private const DB_DOWN_COOLDOWN_SECONDS = 30;
        private const DB_DOWN_SESSION_KEY = '__auctionnova_db_down_until';

        private static function isInDbCooldown(): bool {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                return false;
            }

            if (!isset($_SESSION[self::DB_DOWN_SESSION_KEY])) {
                return false;
            }

            return (int)$_SESSION[self::DB_DOWN_SESSION_KEY] > time();
        }

        private static function markDbDown(): void {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                return;
            }
            $_SESSION[self::DB_DOWN_SESSION_KEY] = time() + self::DB_DOWN_COOLDOWN_SECONDS;
        }

        private static function clearDbDown(): void {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                return;
            }
            unset($_SESSION[self::DB_DOWN_SESSION_KEY]);
        }

        private static function canReachMysqlHost(): bool {
            $host = DB_HOST;
            $port = defined('DB_PORT') ? (int)DB_PORT : 3306;
            $timeout = self::DB_PING_TIMEOUT_SECONDS;

            $errno = 0;
            $errstr = '';
            $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
            if ($fp) {
                fclose($fp);
                return true;
            }

            return false;
        }

        private static function isConnectivityError(string $errorMsg): bool {
            return (strpos($errorMsg, 'SQLSTATE[HY000] [2002]') !== false)
                || (strpos($errorMsg, "Can't connect") !== false)
                || (strpos($errorMsg, 'Connection refused') !== false)
                || (strpos($errorMsg, 'No such file or directory') !== false)
                || (strpos($errorMsg, 'php_network_getaddresses') !== false);
        }

        // Private constructor to prevent direct instantiation
        private function __construct() {
            try {
                // Fail fast if DB is known-down for this session
                if (self::isInDbCooldown()) {
                    throw new Exception('Cannot connect to MySQL server. Is XAMPP MySQL running?');
                }

                // Quick TCP reachability check so pages don't hang on connect()
                if (!self::canReachMysqlHost()) {
                    self::markDbDown();
                    throw new Exception('Cannot connect to MySQL server. Is XAMPP MySQL running?');
                }

                // Test basic connection first
                $port = defined('DB_PORT') ? (int)DB_PORT : 3306;
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4;connect_timeout=2';
                
                $this->conn = new PDO(
                    $dsn,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_TIMEOUT => 5, // 5 second timeout
                    ]
                );
                
                // Test a simple query
                $this->conn->query("SELECT 1");

                // Connection OK, remove cooldown flag
                self::clearDbDown();
                
            } catch (PDOException $e) {
                $error_msg = $e->getMessage();
                error_log('Database Connection Error: ' . $error_msg);

                if (self::isConnectivityError($error_msg)) {
                    self::markDbDown();
                }
                
                // Provide helpful error messages
                if (strpos($error_msg, 'Access denied') !== false) {
                    throw new Exception('Database access denied. Check username/password.');
                } elseif (strpos($error_msg, 'Unknown database') !== false) {
                    throw new Exception("Database '" . DB_NAME . "' not found. Please create the database.");
                } elseif (strpos($error_msg, "Can't connect") !== false) {
                    throw new Exception('Cannot connect to MySQL server. Is XAMPP MySQL running?');
                } else {
                    throw new Exception('Database connection failed: ' . $error_msg);
                }
            } catch (Exception $e) {
                // For explicit fast-fail errors above
                self::markDbDown();
                throw $e;
            }
        }

        // Get the database connection instance
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        // Get the PDO connection
        public function getConnection() {
            return $this->conn;
        }

        // Execute a query with parameters
        public function query($sql, $params = []) {
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            } catch (PDOException $e) {
                error_log('Query Error: ' . $e->getMessage() . '\nSQL: ' . $sql . '\nParams: ' . json_encode($params));
                throw new Exception('Database error occurred. Please try again.');
            }
        }

        // Get single row
        public function fetch($sql, $params = []) {
            return $this->query($sql, $params)->fetch();
        }

        // Get all rows
        public function fetchAll($sql, $params = []) {
            return $this->query($sql, $params)->fetchAll();
        }

        // Get single value
        public function fetchColumn($sql, $params = []) {
            return $this->query($sql, $params)->fetchColumn();
        }

        // Get last insert ID
        public function lastInsertId() {
            return $this->conn->lastInsertId();
        }

        // Begin transaction
        public function beginTransaction() {
            return $this->conn->beginTransaction();
        }

        // Commit transaction
        public function commit() {
            return $this->conn->commit();
        }

        // Rollback transaction
        public function rollBack() {
            return $this->conn->rollBack();
        }

        // Prevent cloning of the instance
        private function __clone() {}

        // Prevent unserialization of the instance
        public function __wakeup() {
            throw new Exception("Cannot unserialize a singleton.");
        }
    }
}
