<?php
/**
 * CLI Database Setup Script
 * 
 * Simple command-line version of the setup script
 * Usage: php setup_database.php
 */

require_once __DIR__ . '/config/database.php';

try {
    // Connect to MySQL without specifying database
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connecting to MySQL server...\n";
    echo "✓ Connected\n\n";
    
    // Read and execute schema
    $schemaFile = __DIR__ . '/schema_complete.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    echo "Reading schema file...\n";
    $schema = file_get_contents($schemaFile);
    
    if ($schema === false) {
        throw new Exception("Failed to read schema file");
    }
    
    echo "✓ Schema file loaded\n\n";
    echo "Executing SQL statements...\n";
    
    // Simple execution - split by semicolon
    // Note: This may have issues with stored procedures, but works for basic setup
    $statements = explode(';', $schema);
    $executed = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || preg_match('/^--/', $statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Ignore "doesn't exist" errors from DROP TABLE IF EXISTS
            if (strpos($e->getMessage(), "doesn't exist") === false) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "✓ Executed $executed statements\n\n";
    
    // Verify connection
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✓ Database setup completed!\n";
        echo "✓ Found " . count($tables) . " tables\n\n";
        
        echo "Default admin credentials:\n";
        echo "Email: admin@repair.com\n";
        echo "Password: admin123\n";
        echo "\n⚠ IMPORTANT: Change the admin password immediately!\n";
    } else {
        throw new Exception("Failed to connect to database after setup");
    }
    
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    echo "Please check:\n";
    echo "- MySQL server is running\n";
    echo "- Database credentials in config/database.php\n";
    echo "- User has CREATE DATABASE privileges\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
