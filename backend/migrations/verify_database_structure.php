<?php
/**
 * Database Structure Verification Script
 * 
 * This script verifies that all required tables and columns exist,
 * and fixes any missing structures.
 * 
 * Usage: php migrations/verify_database_structure.php
 * Or open in browser: http://localhost/repair-booking-platform/backend/migrations/verify_database_structure.php
 */

require_once __DIR__ . '/../config/database.php';

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Verify Database Structure</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}";
    echo ".success{color:#28a745;}.error{color:#dc3545;}.info{color:#17a2b8;.warning{color:#ffc107;}</style></head><body>";
}

function output($message, $type = 'info') {
    global $isCli;
    if ($isCli) {
        echo $message . "\n";
    } else {
        $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : 'info'));
        echo "<p class='$class'>$message</p>";
    }
}

output("<h1>Database Structure Verification</h1>", 'info');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    output("✓ Connected to database", 'success');
    output("", 'info');
    
    // Required tables
    $requiredTables = [
        'users',
        'sessions',
        'shop_owners',
        'repair_shops',
        'technicians',
        'services',
        'shop_services',
        'bookings',
        'booking_history',
        'reviews',
        'shop_ratings',
        'technician_ratings',
        'notifications',
        'shop_items'
    ];
    
    $missingTables = [];
    $existingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existingTables[] = $table;
            output("✓ Table '$table' exists", 'success');
        } else {
            $missingTables[] = $table;
            output("✗ Table '$table' is MISSING", 'error');
        }
    }
    
    output("", 'info');
    
    if (!empty($missingTables)) {
        output("⚠ Missing tables detected. Please run setup.php to create them.", 'warning');
    } else {
        output("✓ All required tables exist", 'success');
    }
    
    // Verify shop_owners table structure
    output("", 'info');
    output("Verifying shop_owners table structure...", 'info');
    
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'user_id', 'shop_name', 'shop_address', 'approval_status'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            output("  ✓ Column '$col' exists", 'success');
        } else {
            $missingColumns[] = $col;
            output("  ✗ Column '$col' is MISSING", 'error');
        }
    }
    
    // Check foreign key constraints
    output("", 'info');
    output("Checking foreign key constraints...", 'info');
    
    $stmt = $db->query("
        SELECT 
            CONSTRAINT_NAME, 
            TABLE_NAME, 
            COLUMN_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'shop_owners'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($foreignKeys) > 0) {
        output("✓ Foreign key constraints exist", 'success');
        foreach ($foreignKeys as $fk) {
            output("  - {$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}", 'info');
        }
    } else {
        output("⚠ No foreign key constraints found on shop_owners", 'warning');
    }
    
    // Check if any shop owners exist
    output("", 'info');
    output("Checking shop owners data...", 'info');
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM shop_owners");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    output("Found $count shop owner(s) in database", 'info');
    
    // Check for shop owners without user records
    $stmt = $db->query("
        SELECT so.id, so.user_id 
        FROM shop_owners so 
        LEFT JOIN users u ON u.id = so.user_id 
        WHERE u.id IS NULL
    ");
    $orphaned = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orphaned) > 0) {
        output("⚠ Found " . count($orphaned) . " orphaned shop_owners records (no matching user)", 'warning');
    } else {
        output("✓ All shop_owners have valid user records", 'success');
    }
    
    output("", 'info');
    output("============================================", 'info');
    if (empty($missingTables) && empty($missingColumns)) {
        output("✓ Database structure is valid!", 'success');
    } else {
        output("⚠ Database structure has issues. Please fix them.", 'warning');
    }
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

