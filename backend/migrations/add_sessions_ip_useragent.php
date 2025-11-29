<?php
/**
 * Migration Script: Add ip_address and user_agent to sessions table
 * 
 * Run this script if you have an existing database and need to add
 * the missing columns to the sessions table.
 * 
 * Usage: php migrations/add_sessions_ip_useragent.php
 * Or open in browser: http://localhost/repair-booking-platform/backend/migrations/add_sessions_ip_useragent.php
 */

require_once __DIR__ . '/../config/database.php';

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migration: Add IP & User Agent</title>";
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

output("<h1>Migration: Add IP Address & User Agent to Sessions</h1>", 'info');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    output("✓ Connected to database", 'success');
    
    // Check if columns already exist
    $stmt = $db->query("SHOW COLUMNS FROM sessions LIKE 'ip_address'");
    $ipExists = $stmt->rowCount() > 0;
    
    $stmt = $db->query("SHOW COLUMNS FROM sessions LIKE 'user_agent'");
    $uaExists = $stmt->rowCount() > 0;
    
    if ($ipExists && $uaExists) {
        output("✓ Columns 'ip_address' and 'user_agent' already exist in sessions table", 'success');
        output("Migration not needed.", 'info');
    } else {
        // Add ip_address column if it doesn't exist
        if (!$ipExists) {
            output("Adding ip_address column...", 'info');
            $db->exec("ALTER TABLE sessions ADD COLUMN ip_address VARCHAR(45) NULL AFTER expires_at");
            output("✓ Added ip_address column", 'success');
        } else {
            output("✓ ip_address column already exists", 'success');
        }
        
        // Add user_agent column if it doesn't exist
        if (!$uaExists) {
            output("Adding user_agent column...", 'info');
            $db->exec("ALTER TABLE sessions ADD COLUMN user_agent VARCHAR(500) NULL AFTER ip_address");
            output("✓ Added user_agent column", 'success');
        } else {
            output("✓ user_agent column already exists", 'success');
        }
        
        // Add index for ip_address if it doesn't exist
        $stmt = $db->query("SHOW INDEXES FROM sessions WHERE Key_name = 'idx_ip_address'");
        if ($stmt->rowCount() == 0) {
            output("Adding index for ip_address...", 'info');
            $db->exec("ALTER TABLE sessions ADD INDEX idx_ip_address (ip_address)");
            output("✓ Added index for ip_address", 'success');
        } else {
            output("✓ Index idx_ip_address already exists", 'success');
        }
        
        output("", 'info');
        output("============================================", 'info');
        output("✓ Migration completed successfully!", 'success');
        output("============================================", 'info');
    }
    
} catch (PDOException $e) {
    output("✗ Database Error: " . $e->getMessage(), 'error');
} catch (Exception $e) {
    output("✗ Error: " . $e->getMessage(), 'error');
}

if (!$isCli) {
    echo "</body></html>";
}
?>

