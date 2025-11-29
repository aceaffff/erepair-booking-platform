<?php
/**
 * Migration Runner for Enhanced Booking Workflow
 * Run this file once to update the database schema
 */

require_once __DIR__ . '/../config/database.php';

echo "=== Enhanced Booking Workflow Migration ===\n";
echo "Starting migration...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        die("ERROR: Could not connect to database\n");
    }
    
    echo "✓ Database connection successful\n";
    
    // Step 1: Add new columns
    echo "\n[Step 1] Adding new columns to bookings table...\n";
    
    $columns = [
        "ADD COLUMN IF NOT EXISTS device_type VARCHAR(100) AFTER service_id",
        "ADD COLUMN IF NOT EXISTS device_issue_description TEXT AFTER device_type",
        "ADD COLUMN IF NOT EXISTS device_photo VARCHAR(255) AFTER device_issue_description",
        "ADD COLUMN IF NOT EXISTS diagnostic_notes TEXT AFTER notes",
        "ADD COLUMN IF NOT EXISTS estimated_cost DECIMAL(10,2) AFTER diagnostic_notes",
        "ADD COLUMN IF NOT EXISTS estimated_time_days INT AFTER estimated_cost"
    ];
    
    foreach ($columns as $col) {
        try {
            $db->exec("ALTER TABLE bookings $col");
            echo "  ✓ Added column\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "  - Column already exists, skipping\n";
            } else {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Step 2: Modify status enum
    echo "\n[Step 2] Updating status enum...\n";
    try {
        $db->exec("ALTER TABLE bookings MODIFY COLUMN status ENUM(
            'pending_review',
            'awaiting_customer_confirmation', 
            'confirmed_by_customer',
            'approved',
            'assigned',
            'in_progress',
            'completed',
            'cancelled_by_customer',
            'rejected',
            'cancelled'
        ) DEFAULT 'pending_review'");
        echo "  ✓ Status enum updated\n";
    } catch (PDOException $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
    
    // Step 3: Update existing bookings
    echo "\n[Step 3] Updating existing bookings...\n";
    try {
        $stmt = $db->exec("UPDATE bookings SET status = 'pending_review' WHERE status = 'pending'");
        echo "  ✓ Updated $stmt existing booking(s)\n";
    } catch (PDOException $e) {
        echo "  - No bookings to update or error: " . $e->getMessage() . "\n";
    }
    
    // Step 4: Create indexes
    echo "\n[Step 4] Creating indexes...\n";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings(status)",
        "CREATE INDEX IF NOT EXISTS idx_bookings_shop_scheduled ON bookings(shop_id, scheduled_at)",
        "CREATE INDEX IF NOT EXISTS idx_bookings_customer ON bookings(customer_id, status)"
    ];
    
    foreach ($indexes as $idx) {
        try {
            $db->exec($idx);
            echo "  ✓ Index created\n";
        } catch (PDOException $e) {
            echo "  - Index might already exist\n";
        }
    }
    
    // Step 5: Create booking_history table
    echo "\n[Step 5] Creating booking_history table...\n";
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS booking_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            old_status VARCHAR(50),
            new_status VARCHAR(50) NOT NULL,
            changed_by INT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_booking (booking_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "  ✓ booking_history table created\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "  - Table already exists\n";
        } else {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // Step 6: Ensure notifications table exists
    echo "\n[Step 6] Ensuring notifications table exists...\n";
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        echo "  ✓ notifications table ready\n";
    } catch (PDOException $e) {
        echo "  - Table already exists or error: " . $e->getMessage() . "\n";
    }
    
    // Create upload directory
    echo "\n[Step 7] Creating upload directory...\n";
    $uploadDir = __DIR__ . '/../../frontend/uploads/device_photos';
    if (!is_dir($uploadDir)) {
        if (@mkdir($uploadDir, 0775, true)) {
            echo "  ✓ Upload directory created: $uploadDir\n";
        } else {
            echo "  ✗ Failed to create upload directory\n";
        }
    } else {
        echo "  - Upload directory already exists\n";
    }
    
    echo "\n=== Migration Completed Successfully ===\n";
    echo "\nNext steps:\n";
    echo "1. Test booking creation with device photo\n";
    echo "2. Test diagnosis workflow\n";
    echo "3. Test customer confirmation\n";
    echo "4. Review frontend integration guide\n";
    
} catch (Exception $e) {
    echo "\n✗ MIGRATION FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
?>

