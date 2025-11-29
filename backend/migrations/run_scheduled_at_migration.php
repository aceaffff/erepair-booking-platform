<?php
/**
 * Migration: Make scheduled_at nullable in bookings table
 * Run this file once to update the database schema
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Scheduled_at Nullable Migration ===\n";
echo "Starting migration...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("ERROR: Could not connect to database\n");
    }
    
    echo "✓ Database connection successful\n\n";
    
    // Execute migration
    echo "[Step 1] Making scheduled_at nullable in bookings table...\n";
    
    try {
        $db->exec("ALTER TABLE bookings MODIFY scheduled_at DATETIME NULL");
        echo "✓ Migration completed successfully!\n";
        echo "✓ scheduled_at column is now nullable\n\n";
    } catch (PDOException $e) {
        // Check if column is already nullable
        $checkStmt = $db->query("SHOW COLUMNS FROM bookings WHERE Field = 'scheduled_at'");
        $column = $checkStmt->fetch();
        
        if ($column && $column['Null'] === 'YES') {
            echo "✓ Column is already nullable, no changes needed\n\n";
        } else {
            echo "✗ Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    echo "Migration completed successfully!\n";
    echo "\nThe booking system now supports:\n";
    echo "- Submitting bookings for diagnosis without selecting a schedule\n";
    echo "- Selecting schedule when confirming booking after diagnosis\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>

