<?php
/**
 * Quick script to create shop_items table if it doesn't exist
 * Run this file once: http://localhost/ERepair/repair-booking-platform/backend/create_shop_items_table.php
 */
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Creating Shop Items Table</h2>";
    echo "<p>Connecting to database...</p>";
    
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'shop_items'");
    if ($checkTable->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠ Table 'shop_items' already exists. No action needed.</p>";
        echo "<p><a href='../frontend/shop/shop_dashboard.php'>Go to Shop Dashboard</a></p>";
        exit;
    }
    
    // Create the table
    $sql = "CREATE TABLE IF NOT EXISTS shop_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shop_owner_id INT NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        stock_quantity INT DEFAULT 0,
        category VARCHAR(100) DEFAULT 'general',
        image_url VARCHAR(500),
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (shop_owner_id) REFERENCES shop_owners(id) ON DELETE CASCADE,
        INDEX idx_shop_owner (shop_owner_id),
        INDEX idx_available (is_available),
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    echo "<p>Creating table...</p>";
    $db->exec($sql);
    
    echo "<p style='color: green;'>✓ Table 'shop_items' created successfully!</p>";
    echo "<hr>";
    echo "<h3>Setup Complete!</h3>";
    echo "<p><a href='../frontend/shop/shop_dashboard.php'>Go to Shop Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

