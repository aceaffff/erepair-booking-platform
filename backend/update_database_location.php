<?php
require_once 'config/database.php';

echo "<h1>ERepair Database Location Update</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Check if address column exists in users table
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'address'");
    $addressExists = $stmt->fetch();
    
    if (!$addressExists) {
        echo "<p>Adding address column to users table...</p>";
        $db->exec("ALTER TABLE users ADD COLUMN address TEXT AFTER verification_code");
        echo "<p style='color: green;'>✓ address column added to users table</p>";
    } else {
        echo "<p style='color: green;'>✓ address column already exists in users table</p>";
    }
    
    // Check if latitude column exists in users table
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'latitude'");
    $latitudeExists = $stmt->fetch();
    
    if (!$latitudeExists) {
        echo "<p>Adding latitude column to users table...</p>";
        $db->exec("ALTER TABLE users ADD COLUMN latitude DECIMAL(10, 8) AFTER address");
        echo "<p style='color: green;'>✓ latitude column added to users table</p>";
    } else {
        echo "<p style='color: green;'>✓ latitude column already exists in users table</p>";
    }
    
    // Check if longitude column exists in users table
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'longitude'");
    $longitudeExists = $stmt->fetch();
    
    if (!$longitudeExists) {
        echo "<p>Adding longitude column to users table...</p>";
        $db->exec("ALTER TABLE users ADD COLUMN longitude DECIMAL(11, 8) AFTER latitude");
        echo "<p style='color: green;'>✓ longitude column added to users table</p>";
    } else {
        echo "<p style='color: green;'>✓ longitude column already exists in users table</p>";
    }
    
    // Check if shop_latitude column exists in shop_owners table
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'shop_latitude'");
    $shopLatitudeExists = $stmt->fetch();
    
    if (!$shopLatitudeExists) {
        echo "<p>Adding shop_latitude column to shop_owners table...</p>";
        $db->exec("ALTER TABLE shop_owners ADD COLUMN shop_latitude DECIMAL(10, 8) AFTER shop_phone");
        echo "<p style='color: green;'>✓ shop_latitude column added to shop_owners table</p>";
    } else {
        echo "<p style='color: green;'>✓ shop_latitude column already exists in shop_owners table</p>";
    }
    
    // Check if shop_longitude column exists in shop_owners table
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'shop_longitude'");
    $shopLongitudeExists = $stmt->fetch();
    
    if (!$shopLongitudeExists) {
        echo "<p>Adding shop_longitude column to shop_owners table...</p>";
        $db->exec("ALTER TABLE shop_owners ADD COLUMN shop_longitude DECIMAL(11, 8) AFTER shop_latitude");
        echo "<p style='color: green;'>✓ shop_longitude column added to shop_owners table</p>";
    } else {
        echo "<p style='color: green;'>✓ shop_longitude column already exists in shop_owners table</p>";
    }
    
    echo "<h2>Database Location Update Complete!</h2>";
    echo "<p>All location-related columns have been added successfully.</p>";
    
    echo "<h3>New Features Available:</h3>";
    echo "<ul>";
    echo "<li>✅ Step-by-step registration process</li>";
    echo "<li>✅ Interactive map with location picker</li>";
    echo "<li>✅ Current location detection</li>";
    echo "<li>✅ Address auto-fill from coordinates</li>";
    echo "<li>✅ Location search functionality</li>";
    echo "<li>✅ Address and coordinates storage</li>";
    echo "</ul>";
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test the new step-by-step registration at <a href='../frontend/register-step.php'>register-step.php</a></li>";
    echo "<li>Try the location features (current location, map click, search)</li>";
    echo "<li>Verify that addresses are auto-filled correctly</li>";
    echo "</ol>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error updating database: " . $e->getMessage() . "</p>";
}
?>
