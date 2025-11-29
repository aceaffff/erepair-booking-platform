<?php
/**
 * Migration: Add selfie_file column to shop_owners table
 * 
 * This adds the selfie_file column that was added for shop owner registration
 * but might be missing in existing databases.
 */

require_once __DIR__ . '/../config/database.php';

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Add Selfie Column</title>";
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

output("<h1>Add Selfie File Column to shop_owners</h1>", 'info');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    output("✓ Connected to database", 'success');
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'selfie_file'");
    if ($stmt->rowCount() > 0) {
        output("✓ Column 'selfie_file' already exists in shop_owners table", 'success');
        output("Migration not needed.", 'info');
    } else {
        output("Adding selfie_file column...", 'info');
        $db->exec("ALTER TABLE shop_owners ADD COLUMN selfie_file VARCHAR(500) NULL AFTER business_permit_file");
        output("✓ Added selfie_file column", 'success');
    }
    
    // Also check for id_file_front if missing
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'id_file_front'");
    if ($stmt->rowCount() == 0) {
        output("Adding id_file_front column...", 'info');
        $db->exec("ALTER TABLE shop_owners ADD COLUMN id_file_front VARCHAR(500) NULL AFTER id_expiry_date");
        output("✓ Added id_file_front column", 'success');
    } else {
        output("✓ Column 'id_file_front' already exists", 'success');
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

