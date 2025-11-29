<?php
/**
 * Step-by-Step Review System Migration
 * This script runs the review system migration step by step
 */

require_once __DIR__ . '/../config/database.php';

echo "=== ERepair Step-by-Step Review System Migration ===\n";
echo "Adding technician and shop rating functionality...\n\n";

try {
    $db = (new Database())->getConnection();
    
    $successCount = 0;
    $errorCount = 0;
    
    // Step 1: Check if reviews table has technician_id column
    echo "Step 1: Checking reviews table structure...\n";
    try {
        $stmt = $db->query("DESCRIBE reviews");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('technician_id', $columns)) {
            echo "Adding technician_id column to reviews table...\n";
            $db->exec("ALTER TABLE reviews ADD COLUMN technician_id INT NULL AFTER shop_id");
            echo "  ✓ technician_id column added\n";
            $successCount++;
        } else {
            echo "  - technician_id column already exists\n";
            $successCount++;
        }
    } catch (PDOException $e) {
        echo "  ✗ Error adding technician_id: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    // Step 2: Add foreign key constraint for technician_id
    echo "\nStep 2: Adding foreign key constraint...\n";
    try {
        $db->exec("ALTER TABLE reviews ADD CONSTRAINT fk_reviews_technician FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE");
        echo "  ✓ Foreign key constraint added\n";
        $successCount++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "  - Foreign key constraint already exists\n";
            $successCount++;
        } else {
            echo "  ✗ Error adding foreign key: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    // Step 3: Add unique constraint for booking_id
    echo "\nStep 3: Adding unique constraint for booking_id...\n";
    try {
        $db->exec("ALTER TABLE reviews ADD UNIQUE KEY unique_booking_review (booking_id)");
        echo "  ✓ Unique constraint added\n";
        $successCount++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "  - Unique constraint already exists\n";
            $successCount++;
        } else {
            echo "  ✗ Error adding unique constraint: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    // Step 4: Create technician_ratings table
    echo "\nStep 4: Creating technician_ratings table...\n";
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS technician_ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            technician_id INT NOT NULL,
            total_reviews INT DEFAULT 0,
            average_rating DECIMAL(3,2) DEFAULT 0.00,
            total_rating_sum INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
            UNIQUE KEY unique_technician_rating (technician_id)
        )");
        echo "  ✓ technician_ratings table created\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "  ✗ Error creating technician_ratings table: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    // Step 5: Create shop_ratings table
    echo "\nStep 5: Creating shop_ratings table...\n";
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS shop_ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_id INT NOT NULL,
            total_reviews INT DEFAULT 0,
            average_rating DECIMAL(3,2) DEFAULT 0.00,
            total_rating_sum INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (shop_id) REFERENCES repair_shops(id) ON DELETE CASCADE,
            UNIQUE KEY unique_shop_rating (shop_id)
        )");
        echo "  ✓ shop_ratings table created\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "  ✗ Error creating shop_ratings table: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    // Step 6: Create indexes
    echo "\nStep 6: Creating indexes...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_reviews_technician ON reviews(technician_id)",
        "CREATE INDEX IF NOT EXISTS idx_reviews_shop ON reviews(shop_id)",
        "CREATE INDEX IF NOT EXISTS idx_reviews_booking ON reviews(booking_id)",
        "CREATE INDEX IF NOT EXISTS idx_technician_ratings_tech ON technician_ratings(technician_id)",
        "CREATE INDEX IF NOT EXISTS idx_shop_ratings_shop ON shop_ratings(shop_id)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $db->exec($index);
            echo "  ✓ Index created\n";
            $successCount++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "  - Index already exists\n";
                $successCount++;
            } else {
                echo "  ✗ Error creating index: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    // Step 7: Initialize existing ratings
    echo "\nStep 7: Initializing existing ratings...\n";
    try {
        // Initialize technician ratings
        $db->exec("INSERT IGNORE INTO technician_ratings (technician_id, total_reviews, average_rating, total_rating_sum)
                   SELECT 
                       technician_id,
                       COUNT(*) as total_reviews,
                       AVG(rating) as average_rating,
                       SUM(rating) as total_rating_sum
                   FROM reviews 
                   WHERE technician_id IS NOT NULL
                   GROUP BY technician_id");
        echo "  ✓ Technician ratings initialized\n";
        $successCount++;
        
        // Initialize shop ratings
        $db->exec("INSERT IGNORE INTO shop_ratings (shop_id, total_reviews, average_rating, total_rating_sum)
                   SELECT 
                       shop_id,
                       COUNT(*) as total_reviews,
                       AVG(rating) as average_rating,
                       SUM(rating) as total_rating_sum
                   FROM reviews 
                   GROUP BY shop_id");
        echo "  ✓ Shop ratings initialized\n";
        $successCount++;
    } catch (PDOException $e) {
        echo "  ✗ Error initializing ratings: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "Successful operations: $successCount\n";
    echo "Failed operations: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n🎉 Review system migration completed successfully!\n";
        echo "\nFeatures added:\n";
        echo "- One review per booking constraint\n";
        echo "- Technician rating aggregation tables\n";
        echo "- Shop rating aggregation tables\n";
        echo "- Review submission API\n";
        echo "- Rating retrieval API\n";
        echo "\nNext steps:\n";
        echo "1. Test the review system using tests/test-review-system.html\n";
        echo "2. Verify that customers can submit reviews\n";
        echo "3. Check that ratings are properly calculated\n";
    } else {
        echo "\n⚠️  Migration completed with some errors. Please check the output above.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
