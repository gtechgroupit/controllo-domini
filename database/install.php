<?php
/**
 * Database Installation Script
 *
 * Installs the PostgreSQL database schema
 * Usage: php database/install.php
 *
 * @package ControlloDomin
 * @version 4.2.0
 */

require_once __DIR__ . '/../config/database.php';

echo "==============================================\n";
echo "Controllo Domini - Database Installation\n";
echo "==============================================\n\n";

// Test connection
echo "Testing database connection...\n";
$test = testDatabaseConnection();

if (!$test['success']) {
    echo "ERROR: " . $test['error'] . "\n";
    echo "\nPlease check your database configuration in config/database.php\n";
    echo "Make sure PostgreSQL is running and credentials are correct.\n";
    exit(1);
}

echo "✓ Connected to PostgreSQL: " . $test['version'] . "\n\n";

// Read schema file
$schema_file = __DIR__ . '/schema.sql';
if (!file_exists($schema_file)) {
    echo "ERROR: Schema file not found: $schema_file\n";
    exit(1);
}

echo "Reading schema file...\n";
$schema = file_get_contents($schema_file);

// Connect to database
try {
    $pdo = new PDO(
        getDatabaseDSN(),
        DB_USER,
        DB_PASSWORD,
        getDatabaseOptions()
    );

    echo "✓ Schema file loaded\n\n";
    echo "Installing database schema...\n";
    echo "This may take a few moments...\n\n";

    // Execute schema
    $pdo->exec($schema);

    echo "✓ Database schema installed successfully!\n\n";

    // Verify tables
    echo "Verifying installation...\n";
    $tables = $pdo->query("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        AND table_type = 'BASE TABLE'
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);

    echo "\nInstalled tables (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  ✓ $table\n";
    }

    // Count indexes
    $indexes = $pdo->query("
        SELECT COUNT(*)
        FROM pg_indexes
        WHERE schemaname = 'public'
    ")->fetchColumn();

    echo "\n✓ Created $indexes indexes\n";

    // Count views
    $views = $pdo->query("
        SELECT COUNT(*)
        FROM information_schema.views
        WHERE table_schema = 'public'
    ")->fetchColumn();

    echo "✓ Created $views views\n";

    // Count triggers
    $triggers = $pdo->query("
        SELECT COUNT(*)
        FROM information_schema.triggers
        WHERE trigger_schema = 'public'
    ")->fetchColumn();

    echo "✓ Created $triggers triggers\n";

    echo "\n==============================================\n";
    echo "Installation completed successfully!\n";
    echo "==============================================\n\n";

    echo "Next steps:\n";
    echo "1. Update your environment variables or config/database.php\n";
    echo "2. Run php database/seed.php to add sample data (optional)\n";
    echo "3. Start using the application\n\n";

} catch (PDOException $e) {
    echo "\nERROR: Installation failed!\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
