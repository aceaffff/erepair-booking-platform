<?php
/**
 * Migration: Update estimated_time_days column to support decimal values (for hours)
 * This allows storing values like 0.5 days (12 hours), 1.5 days (36 hours), etc.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Check if column exists and is INT
    $checkStmt = $db->query("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'bookings' 
        AND COLUMN_NAME = 'estimated_time_days'
    ");
    $columnInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($columnInfo) {
        // Check if it's already DECIMAL/FLOAT
        if (stripos($columnInfo['COLUMN_TYPE'], 'int') !== false) {
            // Change from INT to DECIMAL(10,2) to support decimal values
            $db->exec("
                ALTER TABLE bookings 
                MODIFY COLUMN estimated_time_days DECIMAL(10,2) NULL
            ");
            echo "✓ Successfully updated estimated_time_days column to DECIMAL(10,2)\n";
        } else {
            echo "✓ Column estimated_time_days already supports decimal values\n";
        }
    } else {
        // Column doesn't exist, create it
        $db->exec("
            ALTER TABLE bookings 
            ADD COLUMN estimated_time_days DECIMAL(10,2) NULL AFTER estimated_cost
        ");
        echo "✓ Successfully created estimated_time_days column as DECIMAL(10,2)\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

