<?php
require_once 'config/database.php';

try {
    // Connect to MySQL without specifying database
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Database setup completed successfully!\n";
    echo "Default admin credentials:\n";
    echo "Email: admin@repair.com\n";
    echo "Password: admin123\n";
    
} catch(PDOException $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
}
?>
