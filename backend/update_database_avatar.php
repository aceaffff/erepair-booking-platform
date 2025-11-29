<?php
require_once 'config/database.php';

echo "<h1>ERepair Database Avatar Update</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "<p>✓ Connected to MySQL server</p>";

    // Add avatar_url column to users if it doesn't exist
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_url'");
    $exists = $stmt->fetch();
    if (!$exists) {
        echo "<p>Adding avatar_url column to users table...</p>";
        $db->exec("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500) NULL AFTER phone");
        echo "<p style='color: green;'>✓ avatar_url column added</p>";
    } else {
        echo "<p style='color: green;'>✓ avatar_url column already exists</p>";
    }

    echo "<h2>Done</h2>";
    echo "<p>You can now upload profile photos.</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>


