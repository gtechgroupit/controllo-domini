<?php
/**
 * Database Class
 *
 * PDO wrapper for PostgreSQL database operations
 * Provides connection pooling, query building, transactions, and logging
 *
 * @package ControlloDomin
 * @version 4.2.0
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    private static $instance = null;
    private $connection = null;
    private $read_connection = null;
    private $in_transaction = false;
    private $query_log = [];
    private $slow_queries = [];

    /**
     * Singleton pattern - get database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor - use getInstance()
     */
    private function __construct() {
        $this->connect();
    }

    /**
     * Establish database connection
     */
    private function connect() {
        try {
            // Main connection (write)
            $this->connection = new PDO(
                getDatabaseDSN(false),
                DB_USER,
                DB_PASSWORD,
                getDatabaseOptions()
            );

            // Read replica connection (if different from main)
            if (DB_READ_HOST !== DB_HOST) {
                $this->read_connection = new PDO(
                    getDatabaseDSN(true),
                    DB_USER,
                    DB_PASSWORD,
                    getDatabaseOptions()
                );
            }

            // Log connection
            $this->log('Database connection established');

        } catch (PDOException $e) {
            $this->logError('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get PDO connection (write or read)
     */
    private function getConnection($read_only = false) {
        // Use write connection if in transaction or no read replica
        if ($this->in_transaction || !$this->read_connection || !$read_only) {
            return $this->connection;
        }

        return $this->read_connection;
    }

    /**
     * Execute a SELECT query
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Query parameters
     * @return array Results
     */
    public function query($sql, $params = []) {
        $start_time = microtime(true);

        try {
            $pdo = $this->getConnection(true);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();

            $this->logQuery($sql, $params, $start_time);

            return $results;

        } catch (PDOException $e) {
            $this->logError('Query error: ' . $e->getMessage(), $sql, $params);
            throw new Exception('Query error: ' . $e->getMessage());
        }
    }

    /**
     * Execute a SELECT query and return single row
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Query parameters
     * @return array|null Single row or null
     */
    public function queryOne($sql, $params = []) {
        $start_time = microtime(true);

        try {
            $pdo = $this->getConnection(true);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            $this->logQuery($sql, $params, $start_time);

            return $result ?: null;

        } catch (PDOException $e) {
            $this->logError('Query error: ' . $e->getMessage(), $sql, $params);
            throw new Exception('Query error: ' . $e->getMessage());
        }
    }

    /**
     * Execute an INSERT/UPDATE/DELETE query
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Query parameters
     * @return int Number of affected rows
     */
    public function execute($sql, $params = []) {
        $start_time = microtime(true);

        try {
            $pdo = $this->getConnection(false);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $affected = $stmt->rowCount();

            $this->logQuery($sql, $params, $start_time);

            return $affected;

        } catch (PDOException $e) {
            $this->logError('Execute error: ' . $e->getMessage(), $sql, $params);
            throw new Exception('Execute error: ' . $e->getMessage());
        }
    }

    /**
     * Insert a record and return the ID
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return string Last insert ID
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_map(function($col) { return ':' . $col; }, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s) RETURNING id',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        try {
            $result = $this->queryOne($sql, $params);
            return $result['id'];
        } catch (Exception $e) {
            throw new Exception('Insert failed: ' . $e->getMessage());
        }
    }

    /**
     * Update records
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause with placeholders
     * @param array $where_params WHERE parameters
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $where_params = []) {
        $set_clauses = [];
        $params = [];

        foreach ($data as $key => $value) {
            $set_clauses[] = $key . ' = :set_' . $key;
            $params[':set_' . $key] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $table,
            implode(', ', $set_clauses),
            $where
        );

        // Merge SET params with WHERE params
        $params = array_merge($params, $where_params);

        return $this->execute($sql, $params);
    }

    /**
     * Delete records
     *
     * @param string $table Table name
     * @param string $where WHERE clause with placeholders
     * @param array $params WHERE parameters
     * @return int Number of affected rows
     */
    public function delete($table, $where, $params = []) {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        return $this->execute($sql, $params);
    }

    /**
     * Start a transaction
     */
    public function beginTransaction() {
        if (!$this->in_transaction) {
            $this->connection->beginTransaction();
            $this->in_transaction = true;
            $this->log('Transaction started');
        }
    }

    /**
     * Commit a transaction
     */
    public function commit() {
        if ($this->in_transaction) {
            $this->connection->commit();
            $this->in_transaction = false;
            $this->log('Transaction committed');
        }
    }

    /**
     * Rollback a transaction
     */
    public function rollback() {
        if ($this->in_transaction) {
            $this->connection->rollBack();
            $this->in_transaction = false;
            $this->log('Transaction rolled back');
        }
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Check if table exists
     */
    public function tableExists($table) {
        $sql = "SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = :table
        )";

        $result = $this->queryOne($sql, [':table' => $table]);
        return $result['exists'] ?? false;
    }

    /**
     * Log query execution
     */
    private function logQuery($sql, $params, $start_time) {
        $execution_time = (microtime(true) - $start_time) * 1000; // ms

        $log_entry = [
            'sql' => $sql,
            'params' => $params,
            'time' => round($execution_time, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->query_log[] = $log_entry;

        // Log slow queries
        if (DB_SLOW_QUERY_LOG && $execution_time > DB_SLOW_QUERY_THRESHOLD) {
            $this->slow_queries[] = $log_entry;
            $this->logSlowQuery($log_entry);
        }
    }

    /**
     * Log slow query to file
     */
    private function logSlowQuery($entry) {
        $log_file = __DIR__ . '/../logs/slow-queries.log';
        $log_dir = dirname($log_file);

        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        $message = sprintf(
            "[%s] Slow query (%sms): %s | Params: %s\n",
            $entry['timestamp'],
            $entry['time'],
            $entry['sql'],
            json_encode($entry['params'])
        );

        file_put_contents($log_file, $message, FILE_APPEND);
    }

    /**
     * Log message
     */
    private function log($message) {
        if (function_exists('logMessage')) {
            logMessage($message);
        }
    }

    /**
     * Log error
     */
    private function logError($message, $sql = '', $params = []) {
        $log_file = __DIR__ . '/../logs/database-errors.log';
        $log_dir = dirname($log_file);

        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }

        $error_message = sprintf(
            "[%s] %s | SQL: %s | Params: %s\n",
            date('Y-m-d H:i:s'),
            $message,
            $sql,
            json_encode($params)
        );

        file_put_contents($log_file, $error_message, FILE_APPEND);
    }

    /**
     * Get query statistics
     */
    public function getQueryStats() {
        $total_queries = count($this->query_log);
        $slow_queries = count($this->slow_queries);

        $total_time = array_reduce($this->query_log, function($sum, $entry) {
            return $sum + $entry['time'];
        }, 0);

        $avg_time = $total_queries > 0 ? $total_time / $total_queries : 0;

        return [
            'total_queries' => $total_queries,
            'slow_queries' => $slow_queries,
            'total_time' => round($total_time, 2),
            'avg_time' => round($avg_time, 2),
            'queries' => $this->query_log
        ];
    }

    /**
     * Close database connections
     */
    public function close() {
        $this->connection = null;
        $this->read_connection = null;
        $this->log('Database connections closed');
    }

    /**
     * Destructor
     */
    public function __destruct() {
        if ($this->in_transaction) {
            $this->rollback();
        }
    }
}

/**
 * Helper function to get database instance
 */
function getDatabase() {
    return Database::getInstance();
}
