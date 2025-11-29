<?php
/**
 * Migration: Add ID type and separate front/back ID file columns
 * This migration adds:
 * - id_type: VARCHAR(50) - Type of ID (Driver's License, Passport, National ID, etc.)
 * - id_file_front: VARCHAR(500) - Front side of ID document
 * - id_file_back: VARCHAR(500) - Back side of ID document
 * 
 * Note: id_file column is kept for backward compatibility but will be deprecated
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Adding ID Type and Front/Back ID File Columns</h2>";
    
    // Check if id_type column exists
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'id_type'");
    $idTypeExists = $stmt->fetch();
    
    if (!$idTypeExists) {
        echo "<p>Adding id_type column...</p>";
        $db->exec("ALTER TABLE shop_owners ADD COLUMN id_type VARCHAR(50) AFTER id_file");
        echo "<p style='color: green;'>✓ id_type column added</p>";
    } else {
        echo "<p style='color: green;'>✓ id_type column already exists</p>";
    }
    
    // Check if id_file_front column exists
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'id_file_front'");
    $idFrontExists = $stmt->fetch();
    
    if (!$idFrontExists) {
        echo "<p>Adding id_file_front column...</p>";
        $db->exec("ALTER TABLE shop_owners ADD COLUMN id_file_front VARCHAR(500) AFTER id_type");
        echo "<p style='color: green;'>✓ id_file_front column added</p>";
    } else {
        echo "<p style='color: green;'>✓ id_file_front column already exists</p>";
    }
    
    // Check if id_file_back column exists
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'id_file_back'");
    $idBackExists = $stmt->fetch();
    
    if (!$idBackExists) {
        echo "<p>Adding id_file_back column...</p>";
        $db->exec("ALTER TABLE shop_owners ADD COLUMN id_file_back VARCHAR(500) AFTER id_file_front");
        echo "<p style='color: green;'>✓ id_file_back column added</p>";
    } else {
        echo "<p style='color: green;'>✓ id_file_back column already exists</p>";
    }
    
    // Check if id_number column exists
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'id_number'");
    $idNumberExists = $stmt->fetch();
    
    if (!$idNumberExists) {
        echo "<p>Adding id_number column...</p>";
        $db->exec("ALTER TABLE shop_owners ADD COLUMN id_number VARCHAR(100) AFTER id_type");
        echo "<p style='color: green;'>✓ id_number column added</p>";
    } else {
        echo "<p style='color: green;'>✓ id_number column already exists</p>";
    }
    
    // Check if id_expiry_date column exists
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'id_expiry_date'");
    $idExpiryExists = $stmt->fetch();
    
    if (!$idExpiryExists) {
        echo "<p>Adding id_expiry_date column...</p>";
        $db->exec("ALTER TABLE shop_owners ADD COLUMN id_expiry_date DATE AFTER id_number");
        echo "<p style='color: green;'>✓ id_expiry_date column added</p>";
    } else {
        echo "<p style='color: green;'>✓ id_expiry_date column already exists</p>";
    }
    
    echo "<h3 style='color: green;'>Migration Complete!</h3>";
    echo "<p>All columns have been added successfully.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

