<?php
/**
 * Migration: Add id_file and selfie_file columns to users table for customer verification
 */
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Adding id_file and selfie_file columns to users table...\n";
    
    // Check if id_file column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'id_file'");
    $has_id_file = $stmt->fetch() !== false;
    
    if (!$has_id_file) {
        $db->exec("ALTER TABLE users ADD COLUMN id_file VARCHAR(500) NULL AFTER avatar");
        echo "✓ Added id_file column\n";
    } else {
        echo "✓ id_file column already exists\n";
    }
    
    // Check if selfie_file column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'selfie_file'");
    $has_selfie_file = $stmt->fetch() !== false;
    
    if (!$has_selfie_file) {
        $db->exec("ALTER TABLE users ADD COLUMN selfie_file VARCHAR(500) NULL AFTER id_file");
        echo "✓ Added selfie_file column\n";
    } else {
        echo "✓ selfie_file column already exists\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

