<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Update the bookings table to include 'rejected' in the status ENUM
    $sql = "ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','approved','assigned','in_progress','completed','cancelled','rejected') DEFAULT 'pending'";
    $db->exec($sql);
    
    echo "Successfully updated bookings table to include 'rejected' status.\n";
    
    // Also update any existing 'cancelled' bookings that should be 'rejected'
    // (This is optional - you might want to keep cancelled separate from rejected)
    // $updateSql = "UPDATE bookings SET status='rejected' WHERE status='cancelled'";
    // $db->exec($updateSql);
    // echo "Updated existing cancelled bookings to rejected status.\n";
    
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
