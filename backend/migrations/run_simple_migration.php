<?php
/**
 * Simple Review System Migration Runner
 * This script runs the simple review system migration
 */

require_once __DIR__ . '/../config/database.php';

echo "=== ERepair Simple Review System Migration ===\n";
echo "Adding technician and shop rating functionality...\n\n";

try {
    $db = (new Database())->getConnection();
    
    // Read the migration SQL file
    $migrationFile = __DIR__ . '/simple_review_migration.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    if (!$sql) {
        throw new Exception("Could not read migration file");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt) && !preg_match('/^USE/', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $db->exec($statement);
            $successCount++;
            echo "  ✓ Success\n";
        } catch (PDOException $e) {
            // Some statements might fail if they already exist, which is okay
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "  - Already exists (skipped)\n";
                $successCount++;
            } else {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "Successful statements: $successCount\n";
    echo "Failed statements: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n🎉 Review system migration completed successfully!\n";
        echo "\nFeatures added:\n";
        echo "- One review per booking constraint\n";
        echo "- Technician rating aggregation tables\n";
        echo "- Shop rating aggregation tables\n";
        echo "- Review submission API\n";
        echo "- Rating retrieval API\n";
        echo "\nNote: Rating updates will be handled by the application layer\n";
        echo "until database triggers are manually added.\n";
    } else {
        echo "\n⚠️  Migration completed with some errors. Please check the output above.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
