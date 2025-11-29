<?php
/**
 * Migration Script: Fix avatar column name
 * 
 * This script ensures the users table uses 'avatar' column (not 'avatar_url')
 * and creates a migration for existing databases that might have 'avatar_url'.
 * 
 * Usage: php migrations/fix_avatar_column.php
 * Or open in browser: http://localhost/repair-booking-platform/backend/migrations/fix_avatar_column.php
 */

require_once __DIR__ . '/../config/database.php';

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Avatar Column</title>";
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

output("<h1>Fix Avatar Column Migration</h1>", 'info');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    output("✓ Connected to database", 'success');
    
    // Check if avatar column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    $avatarExists = $stmt->rowCount() > 0;
    
    // Check if avatar_url column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_url'");
    $avatarUrlExists = $stmt->rowCount() > 0;
    
    if ($avatarExists && !$avatarUrlExists) {
        output("✓ Database already uses 'avatar' column (correct)", 'success');
        output("Migration not needed.", 'info');
    } else if ($avatarUrlExists && !$avatarExists) {
        // Rename avatar_url to avatar
        output("Renaming 'avatar_url' to 'avatar'...", 'info');
        $db->exec("ALTER TABLE users CHANGE COLUMN avatar_url avatar VARCHAR(500) NULL");
        output("✓ Column renamed successfully", 'success');
    } else if ($avatarUrlExists && $avatarExists) {
        // Both exist - copy data and drop avatar_url
        output("Both columns exist. Migrating data...", 'info');
        $db->exec("UPDATE users SET avatar = avatar_url WHERE avatar IS NULL AND avatar_url IS NOT NULL");
        $db->exec("ALTER TABLE users DROP COLUMN avatar_url");
        output("✓ Data migrated and old column removed", 'success');
    } else {
        // Neither exists - add avatar column
        output("Adding 'avatar' column...", 'info');
        $db->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(500) NULL AFTER longitude");
        output("✓ Column added successfully", 'success');
    }
    
    // Verify final state
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    if ($stmt->rowCount() > 0) {
        output("", 'info');
        output("============================================", 'info');
        output("✓ Migration completed successfully!", 'success');
        output("✓ Users table now has 'avatar' column", 'success');
        output("============================================", 'info');
    } else {
        throw new Exception("Failed to verify avatar column exists");
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

