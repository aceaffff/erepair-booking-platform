<?php
/**
 * Migration: Fix technicians table structure
 * 
 * This ensures the technicians table uses shop_owner_id (not shop_id) to match the schema.
 * Also adds shop_id column if missing (optional, for repair_shops reference).
 * 
 * Usage: php migrations/fix_technicians_table_structure.php
 * Or open in browser: http://localhost/repair-booking-platform/backend/migrations/fix_technicians_table_structure.php
 */

require_once __DIR__ . '/../config/database.php';

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Technicians Table</title>";
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

output("<h1>Fix Technicians Table Structure</h1>", 'info');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    output("✓ Connected to database", 'success');
    
    // Check if technicians table exists
    $stmt = $db->query("SHOW TABLES LIKE 'technicians'");
    if ($stmt->rowCount() == 0) {
        output("⚠ Technicians table does not exist. It will be created by the schema.", 'info');
        output("Migration not needed - run setup.php to create the table.", 'info');
        exit;
    }
    
    // Check for shop_owner_id column
    $stmt = $db->query("SHOW COLUMNS FROM technicians LIKE 'shop_owner_id'");
    $hasShopOwnerId = $stmt->rowCount() > 0;
    
    // Check for shop_id column (old structure)
    $stmt = $db->query("SHOW COLUMNS FROM technicians LIKE 'shop_id'");
    $hasShopId = $stmt->rowCount() > 0;
    
    if ($hasShopOwnerId && !$hasShopId) {
        output("✓ Technicians table already has correct structure (shop_owner_id exists, shop_id doesn't)", 'success');
        output("Migration not needed.", 'info');
    } else {
        // Need to migrate
        if (!$hasShopOwnerId && $hasShopId) {
            // Old structure: has shop_id but not shop_owner_id
            output("Migrating from shop_id to shop_owner_id...", 'info');
            
            // Add shop_owner_id column
            $db->exec("ALTER TABLE technicians ADD COLUMN shop_owner_id INT NULL AFTER user_id");
            
            // Copy data from shop_id to shop_owner_id
            $db->exec("UPDATE technicians SET shop_owner_id = shop_id WHERE shop_owner_id IS NULL");
            
            // Make shop_owner_id NOT NULL
            $db->exec("ALTER TABLE technicians MODIFY COLUMN shop_owner_id INT NOT NULL");
            
            // Add foreign key
            try {
                $db->exec("ALTER TABLE technicians ADD FOREIGN KEY (shop_owner_id) REFERENCES shop_owners(id) ON DELETE CASCADE");
            } catch (PDOException $e) {
                // Foreign key might already exist, ignore
                if (strpos($e->getMessage(), 'Duplicate key') === false) {
                    throw $e;
                }
            }
            
            // Keep shop_id as optional (for repair_shops reference) but make it nullable
            $db->exec("ALTER TABLE technicians MODIFY COLUMN shop_id INT NULL");
            
            output("✓ Migrated to shop_owner_id structure", 'success');
        } else if (!$hasShopOwnerId) {
            // No shop_owner_id, no shop_id - add shop_owner_id
            output("Adding shop_owner_id column...", 'info');
            $db->exec("ALTER TABLE technicians ADD COLUMN shop_owner_id INT NOT NULL AFTER user_id");
            
            // Try to populate from existing data if possible
            // This is a best-effort migration
            try {
                $db->exec("ALTER TABLE technicians ADD FOREIGN KEY (shop_owner_id) REFERENCES shop_owners(id) ON DELETE CASCADE");
            } catch (PDOException $e) {
                output("⚠ Could not add foreign key. You may need to populate shop_owner_id manually.", 'error');
            }
            
            output("✓ Added shop_owner_id column", 'success');
        } else {
            // Has both - ensure shop_id is nullable
            output("Ensuring shop_id is nullable (optional)...", 'info');
            $db->exec("ALTER TABLE technicians MODIFY COLUMN shop_id INT NULL");
            output("✓ Updated shop_id to be nullable", 'success');
        }
        
        // Add shop_id if missing (optional column for repair_shops reference)
        if (!$hasShopId) {
            output("Adding optional shop_id column...", 'info');
            $db->exec("ALTER TABLE technicians ADD COLUMN shop_id INT NULL AFTER shop_owner_id");
            output("✓ Added shop_id column (optional)", 'success');
        }
        
        // Ensure avatar column exists
        $stmt = $db->query("SHOW COLUMNS FROM technicians LIKE 'avatar'");
        if ($stmt->rowCount() == 0) {
            output("Adding avatar column...", 'info');
            $db->exec("ALTER TABLE technicians ADD COLUMN avatar VARCHAR(500) NULL AFTER shop_id");
            output("✓ Added avatar column", 'success');
        }
    }
    
    // Verify final structure
    $stmt = $db->query("SHOW COLUMNS FROM technicians");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['id', 'user_id', 'shop_owner_id'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columns)) {
            $missingColumns[] = $col;
        }
    }
    
    if (empty($missingColumns)) {
        output("", 'info');
        output("============================================", 'info');
        output("✓ Migration completed successfully!", 'success');
        output("✓ Technicians table has correct structure", 'success');
        output("============================================", 'info');
    } else {
        throw new Exception("Missing required columns: " . implode(', ', $missingColumns));
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

