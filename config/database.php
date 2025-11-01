<?php
/**
 * Database Configuration
 *
 * PostgreSQL database connection settings for Controllo Domini
 * Supports connection pooling, SSL, and read replicas
 *
 * @package ControlloDomin
 * @version 4.2.0
 */

// Database connection settings
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'pgsql');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'controllo_domini');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_CHARSET', 'utf8');

// Connection options
define('DB_PERSISTENT', false); // Persistent connections
define('DB_SSL_MODE', getenv('DB_SSL_MODE') ?: 'prefer'); // disable, allow, prefer, require, verify-ca, verify-full
define('DB_TIMEOUT', 10); // Connection timeout in seconds

// Read replica settings (optional)
define('DB_READ_HOST', getenv('DB_READ_HOST') ?: DB_HOST);
define('DB_READ_PORT', getenv('DB_READ_PORT') ?: DB_PORT);

// Connection pooling
define('DB_POOL_SIZE', 10);
define('DB_MAX_CONNECTIONS', 100);

// Query settings
define('DB_QUERY_TIMEOUT', 30); // Query timeout in seconds
define('DB_SLOW_QUERY_LOG', true); // Log slow queries
define('DB_SLOW_QUERY_THRESHOLD', 1000); // Log queries slower than 1 second (ms)

// Backup settings
define('DB_BACKUP_ENABLED', true);
define('DB_BACKUP_PATH', __DIR__ . '/../backups/database');
define('DB_BACKUP_RETENTION_DAYS', 30);

// Migration settings
define('DB_MIGRATIONS_TABLE', 'migrations');
define('DB_MIGRATIONS_PATH', __DIR__ . '/../database/migrations');

// Connection string builder
function getDatabaseDSN($read_only = false) {
    $host = $read_only ? DB_READ_HOST : DB_HOST;
    $port = $read_only ? DB_READ_PORT : DB_PORT;

    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;sslmode=%s',
        DB_DRIVER,
        $host,
        $port,
        DB_NAME,
        DB_SSL_MODE
    );

    if (DB_TIMEOUT > 0) {
        $dsn .= ';connect_timeout=' . DB_TIMEOUT;
    }

    return $dsn;
}

// PDO options
function getDatabaseOptions() {
    return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => DB_PERSISTENT,
        PDO::ATTR_TIMEOUT => DB_QUERY_TIMEOUT,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
    ];
}

// Test database connection
function testDatabaseConnection() {
    try {
        $pdo = new PDO(
            getDatabaseDSN(),
            DB_USER,
            DB_PASSWORD,
            getDatabaseOptions()
        );

        // Test query
        $result = $pdo->query('SELECT version()')->fetchColumn();

        return [
            'success' => true,
            'version' => $result,
            'message' => 'Database connection successful'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'message' => 'Database connection failed'
        ];
    }
}
