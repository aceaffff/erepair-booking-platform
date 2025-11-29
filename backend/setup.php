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
    
    // Remove comments and split by semicolon, but handle DELIMITER statements
    $statements = [];
    $currentStatement = '';
    $delimiter = ';';
    $inDelimiterBlock = false;
    
    $lines = explode("\n", $schema);
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || preg_match('/^--/', $line) || preg_match('/^\/\*/', $line)) {
            continue;
        }
        
        // Handle DELIMITER statements
        if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
            if ($inDelimiterBlock && !empty($currentStatement)) {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            }
            $delimiter = trim($matches[1]);
            $inDelimiterBlock = ($delimiter !== ';');
            continue;
        }
        
        // Check if line ends with current delimiter
        if (substr(rtrim($line), -strlen($delimiter)) === $delimiter) {
            $currentStatement .= "\n" . substr($line, 0, -strlen($delimiter));
            if (!empty(trim($currentStatement))) {
                $statements[] = trim($currentStatement);
            }
            $currentStatement = '';
            if ($inDelimiterBlock && $delimiter !== ';') {
                $delimiter = ';';
                $inDelimiterBlock = false;
            }
        } else {
            $currentStatement .= "\n" . $line;
        }
    }
    
    // Execute each statement
    $executed = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Some errors are expected (like DROP TABLE IF EXISTS on non-existent tables)
            // Only report actual errors
            if (strpos($e->getMessage(), "doesn't exist") === false && 
                strpos($e->getMessage(), "Unknown database") === false) {
                $errors++;
                if ($errors <= 5) { // Only show first 5 errors
                    output("Warning: " . $e->getMessage(), 'error');
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
