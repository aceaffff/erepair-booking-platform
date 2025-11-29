<?php
/**
 * Check if all required database columns exist
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../backend/config/database.php';
    $db = (new Database())->getConnection();
    
    echo "Checking database columns...\n\n";
    
    // Check bookings table columns
    $stmt = $db->query("SHOW COLUMNS FROM bookings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Bookings table columns:\n";
    foreach($columns as $column) {
        echo "  - $column\n";
    }
    
    // Check for required columns
    $requiredColumns = [
        'device_type',
        'device_issue_description', 
        'device_photo',
        'diagnostic_notes',
        'estimated_cost',
        'estimated_time_days'
    ];
    
    echo "\nChecking required columns:\n";
    foreach($requiredColumns as $col) {
        if(in_array($col, $columns)) {
            echo "  ✓ $col - EXISTS\n";
        } else {
            echo "  ✗ $col - MISSING\n";
        }
    }
    
    // Check booking_history table
    echo "\nChecking booking_history table:\n";
    $stmt = $db->query("SHOW TABLES LIKE 'booking_history'");
    if($stmt->fetch()) {
        echo "  ✓ booking_history table EXISTS\n";
        
        $stmt = $db->query("SHOW COLUMNS FROM booking_history");
        $historyColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "  Columns: " . implode(', ', $historyColumns) . "\n";
    } else {
        echo "  ✗ booking_history table MISSING\n";
    }
    
    // Check notifications table
    echo "\nChecking notifications table:\n";
    $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
    if($stmt->fetch()) {
        echo "  ✓ notifications table EXISTS\n";
    } else {
        echo "  ✗ notifications table MISSING\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
