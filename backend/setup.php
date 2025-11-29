<?php
/**
 * ERepair Platform Database Setup Script
 * 
 * This script initializes the database, creates all tables, views, 
 * stored procedures, and sets up default admin user.
 * 
 * Usage: Open in browser: http://localhost/repair-booking-platform/backend/setup.php
 * Or run via CLI: php setup.php
 */

// Check if running from CLI or web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>ERepair Setup</title>";
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

output("<h1>ERepair Platform Setup</h1>", 'info');

// Include database configuration
require_once __DIR__ . '/config/database.php';

try {
    // Step 1: Connect to MySQL server (without database)
    output("Step 1: Connecting to MySQL server...", 'info');
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); // Enable buffered queries
    output("✓ Connected to MySQL server", 'success');
    
    // Step 2: Read schema file
    output("Step 2: Reading schema file...", 'info');
    $schemaFile = __DIR__ . '/schema_complete.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        throw new Exception("Failed to read schema file");
    }
    output("✓ Schema file loaded", 'success');
    
    // Step 3: Execute SQL statements
    output("Step 3: Executing SQL statements...", 'info');
    
    // Better SQL parsing that handles DELIMITER statements properly
    $statements = [];
    $currentStatement = '';
    $delimiter = ';';
    
    // Remove single-line comments first
    $schema = preg_replace('/^--.*$/m', '', $schema);
    
    $lines = explode("\n", $schema);
    foreach ($lines as $lineNum => $line) {
        $originalLine = $line;
        $line = rtrim($line);
        
        // Skip empty lines
        if (empty($line)) {
            continue;
        }
        
        // Handle DELIMITER statements
        if (preg_match('/^\s*DELIMITER\s+(.+)$/i', $line, $matches)) {
            // Save current statement if any
            if (!empty(trim($currentStatement))) {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            }
            // Change delimiter
            $delimiter = trim($matches[1]);
            continue;
        }
        
        // Check if line ends with current delimiter
        $delimiterLen = strlen($delimiter);
        if (strlen($line) >= $delimiterLen && substr($line, -$delimiterLen) === $delimiter) {
            // Add line without delimiter
            $currentStatement .= "\n" . substr($line, 0, -$delimiterLen);
            if (!empty(trim($currentStatement))) {
                $statements[] = trim($currentStatement);
            }
            $currentStatement = '';
            // Reset delimiter to semicolon after stored procedure
            if ($delimiter !== ';') {
                $delimiter = ';';
            }
        } else {
            // Add line to current statement
            $currentStatement .= "\n" . $line;
        }
    }
    
    // Add any remaining statement
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }
    
    // Execute each statement
    $executed = 0;
    $errors = 0;
    $errorMessages = [];
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) {
            continue;
        }
        
        // Skip SELECT statements (they're just informational)
        if (preg_match('/^\s*SELECT\s+/i', $statement)) {
            continue;
        }
        
        // Skip SET GLOBAL (may require privileges)
        if (preg_match('/^\s*SET\s+GLOBAL/i', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore privilege errors for SET GLOBAL
            }
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            $errorCode = $e->getCode();
            $errorMsg = $e->getMessage();
            
            // Ignore expected errors
            $ignoreErrors = [
                "doesn't exist",
                "Unknown database",
                "already exists",
                "Duplicate key",
                "Duplicate entry"
            ];
            
            $shouldIgnore = false;
            foreach ($ignoreErrors as $ignore) {
                if (stripos($errorMsg, $ignore) !== false) {
                    $shouldIgnore = true;
                    break;
                }
            }
            
            if (!$shouldIgnore) {
                $errors++;
                $errorMessages[] = "Statement " . ($index + 1) . ": " . substr($errorMsg, 0, 100);
                if ($errors <= 10) { // Show first 10 errors
                    output("Warning: " . substr($errorMsg, 0, 150), 'error');
                }
            }
        }
    }
    
    output("✓ Executed $executed SQL statements", 'success');
    if ($errors > 0) {
        output("⚠ Encountered $errors warnings (some may be expected)", 'error');
    }
    
    // Step 4: Create upload directories
    output("Step 4: Creating upload directories...", 'info');
    $uploadDirs = [
        __DIR__ . '/../uploads',
        __DIR__ . '/../uploads/avatars',
        __DIR__ . '/../uploads/id_files',
        __DIR__ . '/../uploads/business_permit',
        __DIR__ . '/../uploads/device_photos',
        __DIR__ . '/../uploads/shop_logos'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                output("✓ Created directory: " . basename($dir), 'success');
            } else {
                output("⚠ Failed to create directory: $dir", 'error');
            }
        } else {
            output("✓ Directory exists: " . basename($dir), 'success');
        }
    }
    
    // Step 5: Verify database connection
    output("Step 5: Verifying database setup...", 'info');
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        // Check if tables exist
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        output("✓ Database connection successful", 'success');
        output("✓ Found " . count($tables) . " tables in database", 'success');
        
        // Verify admin user exists
        $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            output("✓ Admin user verified", 'success');
        } else {
            output("⚠ Admin user not found - please check schema", 'error');
        }
        
        // Verify avatar column exists (not avatar_url)
        $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'avatar'");
        $avatarExists = $stmt->rowCount() > 0;
        
        if ($avatarExists) {
            output("✓ Avatar column verified (using 'avatar' not 'avatar_url')", 'success');
        } else {
            output("⚠ Avatar column not found - database may need migration", 'error');
            output("Run: migrations/fix_avatar_column.php to fix this", 'info');
        }
        
        // Verify technicians table structure
        $stmt = $conn->query("SHOW TABLES LIKE 'technicians'");
        if ($stmt->rowCount() > 0) {
            $stmt = $conn->query("SHOW COLUMNS FROM technicians LIKE 'shop_owner_id'");
            $hasShopOwnerId = $stmt->rowCount() > 0;
            
            if ($hasShopOwnerId) {
                output("✓ Technicians table structure verified (using 'shop_owner_id')", 'success');
            } else {
                output("⚠ Technicians table may need migration", 'error');
                output("Run: migrations/fix_technicians_table_structure.php to fix this", 'info');
            }
        }
        
        // Verify scheduled_at is nullable in bookings
        $stmt = $conn->query("SHOW COLUMNS FROM bookings WHERE Field = 'scheduled_at'");
        $scheduledAtCol = $stmt->fetch();
        if ($scheduledAtCol && $scheduledAtCol['Null'] === 'YES') {
            output("✓ Bookings.scheduled_at is nullable (correct)", 'success');
        } else if ($scheduledAtCol) {
            output("⚠ Bookings.scheduled_at is NOT NULL - may need migration", 'error');
            output("Run: migrations/make_scheduled_at_nullable.php to fix this", 'info');
        }
    } else {
        throw new Exception("Failed to connect to database after setup");
    }
    
    // Success message
    output("", 'info');
    output("============================================", 'info');
    output("✓ Setup Complete!", 'success');
    output("============================================", 'info');
    output("", 'info');
    output("<strong>Default Admin Credentials:</strong>", 'info');
    output("Email: admin@repair.com", 'info');
    output("Password: admin123", 'info');
    output("", 'info');
    output("<strong>⚠ IMPORTANT:</strong> Change the admin password immediately after first login!", 'error');
    output("", 'info');
    output("<strong>Next Steps:</strong>", 'info');
    output("1. Configure email settings in backend/config/email.php", 'info');
    output("2. Test the API endpoints", 'info');
    output("3. Open frontend at: http://localhost/repair-booking-platform/frontend/", 'info');
    output("4. Login as admin to approve shop owners", 'info');
    
} catch (PDOException $e) {
    output("✗ Database Error: " . $e->getMessage(), 'error');
    output("Please check:", 'error');
    output("- MySQL server is running", 'error');
    output("- Database credentials in config/database.php", 'error');
    output("- User has CREATE DATABASE privileges", 'error');
} catch (Exception $e) {
    output("✗ Error: " . $e->getMessage(), 'error');
}

if (!$isCli) {
    echo "</body></html>";
}
?>
