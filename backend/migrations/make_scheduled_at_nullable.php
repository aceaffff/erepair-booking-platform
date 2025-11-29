<?php
/**
 * Migration: Make scheduled_at nullable in bookings table
 * 
 * This allows bookings to be created without a schedule initially,
 * as the schedule is selected during customer confirmation.
 * 
 * Usage: php migrations/make_scheduled_at_nullable.php
 * Or open in browser: http://localhost/repair-booking-platform/backend/migrations/make_scheduled_at_nullable.php
 */

require_once __DIR__ . '/../config/database.php';

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Make scheduled_at Nullable</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
    echo ".success{color:#28a745;}.error{color:#dc3545;}.info{color:#17a2b8;}</style></head><body>";
}

function output($message, $type = 'info') {
    global $isCli;
    if ($isCli) {
        echo $message . "\n";
    } else {
        $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : 'info');
        echo "<p class='$class'>$message</p>";
    }
}

output("<h1>Make scheduled_at Nullable Migration</h1>", 'info');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    output("✓ Connected to database", 'success');
    
    // Check current column definition
    $stmt = $db->query("SHOW COLUMNS FROM bookings WHERE Field = 'scheduled_at'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column) {
        throw new Exception("scheduled_at column not found in bookings table");
    }
    
    $isNullable = $column['Null'] === 'YES';
    
    if ($isNullable) {
        output("✓ Column 'scheduled_at' is already nullable", 'success');
        output("Migration not needed.", 'info');
    } else {
        // Check if there are any NULL values that need to be handled
        $stmt = $db->query("SELECT COUNT(*) as count FROM bookings WHERE scheduled_at IS NULL");
        $nullCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($nullCount > 0) {
            output("Found $nullCount bookings with NULL scheduled_at. Setting default values...", 'info');
            // Set a default date for existing NULL values (tomorrow at 9 AM)
            $defaultDate = date('Y-m-d', strtotime('+1 day')) . ' 09:00:00';
            $db->exec("UPDATE bookings SET scheduled_at = '$defaultDate' WHERE scheduled_at IS NULL");
            output("✓ Updated $nullCount bookings with default scheduled_at", 'success');
        }
        
        output("Making scheduled_at column nullable...", 'info');
        $db->exec("ALTER TABLE bookings MODIFY COLUMN scheduled_at DATETIME NULL");
        output("✓ Column 'scheduled_at' is now nullable", 'success');
    }
    
    output("", 'info');
    output("============================================", 'info');
    output("✓ Migration completed successfully!", 'success');
    output("============================================", 'info');
    
} catch (PDOException $e) {
    output("✗ Database Error: " . $e->getMessage(), 'error');
} catch (Exception $e) {
    output("✗ Error: " . $e->getMessage(), 'error');
}

if (!$isCli) {
    echo "</body></html>";
}
?>

