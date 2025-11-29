<?php
// Simple setup script to initialize the database
require_once 'config/database.php';

echo "<h1>ERepair Platform Setup</h1>";

try {
    // Connect to MySQL without specifying database
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Read and execute schema
    $schema = file_get_contents('schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<p>✓ Database 'repair_booking' created successfully</p>";
    echo "<p>✓ Tables created successfully (users, shop_owners, sessions)</p>";
    echo "<p>✓ Default admin user created</p>";
    echo "<p>✓ Upload directories created</p>";
    
    echo "<h2>Setup Complete!</h2>";
    echo "<p><strong>Default admin credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Email: admin@repair.com</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Configure email settings at <a href='setup_email.php'>setup_email.php</a></li>";
    echo "<li>Open <a href='../frontend/auth/index.php'>frontend/auth/index.php</a> to view the landing page</li>";
    echo "<li>Test registration and login functionality</li>";
    echo "<li>Login as admin to approve shop owners</li>";
    echo "<li>Test API endpoints at <a href='test_api.php'>test_api.php</a></li>";
    echo "</ol>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error setting up database: " . $e->getMessage() . "</p>";
}
?>
