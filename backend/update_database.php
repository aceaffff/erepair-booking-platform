<?php
require_once 'config/database.php';

echo "<h1>ERepair Database Update</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✓ Connected to MySQL server</p>";
    
    // Check if verification_code column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'verification_code'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "<p>Adding verification_code column to users table...</p>";
        $db->exec("ALTER TABLE users ADD COLUMN verification_code VARCHAR(255) AFTER email_verified");
        echo "<p style='color: green;'>✓ verification_code column added</p>";
    } else {
        echo "<p style='color: green;'>✓ verification_code column already exists</p>";
    }
    
    // Check if phone column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'phone'");
    $phoneExists = $stmt->fetch();
    
    if (!$phoneExists) {
        echo "<p>Adding phone column to users table...</p>";
        $db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email");
        echo "<p style='color: green;'>✓ phone column added</p>";
    } else {
        echo "<p style='color: green;'>✓ phone column already exists</p>";
    }
    
    // Check if status column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    $statusExists = $stmt->fetch();
    
    if (!$statusExists) {
        echo "<p>Adding status column to users table...</p>";
        $db->exec("ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER email_verified");
        echo "<p style='color: green;'>✓ status column added</p>";
    } else {
        echo "<p style='color: green;'>✓ status column already exists</p>";
    }
    
    // Check if sessions table exists
    $stmt = $db->query("SHOW TABLES LIKE 'sessions'");
    $sessionsExists = $stmt->fetch();
    
    if (!$sessionsExists) {
        echo "<p>Creating sessions table...</p>";
        $db->exec("
            CREATE TABLE sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) UNIQUE NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_expires_at (expires_at)
            )
        ");
        echo "<p style='color: green;'>✓ sessions table created</p>";
    } else {
        echo "<p style='color: green;'>✓ sessions table already exists</p>";
    }
    
    // Check if shop_owners table exists
    $stmt = $db->query("SHOW TABLES LIKE 'shop_owners'");
    $shopOwnersExists = $stmt->fetch();
    
    if (!$shopOwnersExists) {
        echo "<p>Creating shop_owners table...</p>";
        $db->exec("
            CREATE TABLE shop_owners (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                shop_name VARCHAR(255) NOT NULL,
                shop_address TEXT NOT NULL,
                shop_phone VARCHAR(20),
                id_file VARCHAR(500),
                business_permit_file VARCHAR(500),
                approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        echo "<p style='color: green;'>✓ shop_owners table created</p>";
    } else {
        echo "<p style='color: green;'>✓ shop_owners table already exists</p>";
    }
    
    // Check if admin user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = 'admin@repair.com'");
    $stmt->execute();
    $adminExists = $stmt->fetch();
    
    if (!$adminExists) {
        echo "<p>Creating default admin user...</p>";
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (name, email, phone, password, role, email_verified, status) 
            VALUES ('Admin User', 'admin@repair.com', '1234567890', ?, 'admin', TRUE, 'approved')
        ");
        $stmt->execute([$hashedPassword]);
        echo "<p style='color: green;'>✓ Default admin user created</p>";
    } else {
        echo "<p style='color: green;'>✓ Admin user already exists</p>";
    }
    
    echo "<h2>Database Update Complete!</h2>";
    echo "<p><strong>Default Admin Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Email: admin@repair.com</li>";
    echo "<li>Password: admin123</li>";
    echo "</ul>";
    
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>✓ Email configuration is ready</li>";
    echo "<li>Open <a href='../frontend/auth/index.php'>frontend/auth/index.php</a> to view the landing page</li>";
    echo "<li>Test registration and login functionality</li>";
    echo "<li>Login as admin to approve shop owners</li>";
    echo "<li>Test API endpoints at <a href='test_api.php'>test_api.php</a></li>";
    echo "</ol>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error updating database: " . $e->getMessage() . "</p>";
}
?>
