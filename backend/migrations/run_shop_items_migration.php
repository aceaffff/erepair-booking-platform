<?php
/**
 * Run migration to add shop_items table
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Running Shop Items Migration</h2>";
    echo "<p>Connecting to database...</p>";
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/add_shop_items.sql';
    if (!file_exists($sqlFile)) {
        die("<p style='color: red;'>SQL file not found: $sqlFile</p>");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt) && !preg_match('/^ALTER TABLE.*COMMENT/', $stmt);
        }
    );
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        echo "<p>Executing statement " . ($index + 1) . "...</p>";
        try {
            $db->exec($statement);
            echo "<p style='color: green;'>✓ Statement " . ($index + 1) . " executed successfully</p>";
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<p style='color: orange;'>⚠ Table already exists, skipping...</p>";
            } else {
                echo "<p style='color: red;'>✗ Error executing statement: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>Migration Complete!</h3>";
    echo "<p><a href='../../frontend/customer/customer_dashboard.php'>Go to Customer Dashboard</a></p>";
    echo "<p><a href='../../frontend/shop/shop_dashboard.php'>Go to Shop Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

