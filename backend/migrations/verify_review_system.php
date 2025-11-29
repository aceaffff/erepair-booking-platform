<?php
/**
 * Verify Review System Installation
 * This script checks if the review system is properly installed
 */

require_once __DIR__ . '/../config/database.php';

echo "=== ERepair Review System Verification ===\n";
echo "Checking if review system is properly installed...\n\n";

try {
    $db = (new Database())->getConnection();
    
    $checks = [
        'reviews table has technician_id column' => false,
        'reviews table has unique constraint on booking_id' => false,
        'technician_ratings table exists' => false,
        'shop_ratings table exists' => false,
        'foreign key constraints exist' => false,
        'indexes are created' => false
    ];
    
    // Check 1: reviews table structure
    echo "1. Checking reviews table structure...\n";
    try {
        $stmt = $db->query("DESCRIBE reviews");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('technician_id', $columns)) {
            echo "   ✓ technician_id column exists\n";
            $checks['reviews table has technician_id column'] = true;
        } else {
            echo "   ✗ technician_id column missing\n";
        }
        
        // Check for unique constraint
        $stmt = $db->query("SHOW INDEX FROM reviews WHERE Key_name = 'unique_booking_review'");
        if ($stmt->fetch()) {
            echo "   ✓ unique constraint on booking_id exists\n";
            $checks['reviews table has unique constraint on booking_id'] = true;
        } else {
            echo "   ✗ unique constraint on booking_id missing\n";
        }
    } catch (PDOException $e) {
        echo "   ✗ Error checking reviews table: " . $e->getMessage() . "\n";
    }
    
    // Check 2: technician_ratings table
    echo "\n2. Checking technician_ratings table...\n";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'technician_ratings'");
        if ($stmt->fetch()) {
            echo "   ✓ technician_ratings table exists\n";
            $checks['technician_ratings table exists'] = true;
            
            // Check structure
            $stmt = $db->query("DESCRIBE technician_ratings");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = ['id', 'technician_id', 'total_reviews', 'average_rating', 'total_rating_sum'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                echo "   ✓ All required columns exist\n";
            } else {
                echo "   ✗ Missing columns: " . implode(', ', $missingColumns) . "\n";
            }
        } else {
            echo "   ✗ technician_ratings table missing\n";
        }
    } catch (PDOException $e) {
        echo "   ✗ Error checking technician_ratings table: " . $e->getMessage() . "\n";
    }
    
    // Check 3: shop_ratings table
    echo "\n3. Checking shop_ratings table...\n";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'shop_ratings'");
        if ($stmt->fetch()) {
            echo "   ✓ shop_ratings table exists\n";
            $checks['shop_ratings table exists'] = true;
            
            // Check structure
            $stmt = $db->query("DESCRIBE shop_ratings");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = ['id', 'shop_id', 'total_reviews', 'average_rating', 'total_rating_sum'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                echo "   ✓ All required columns exist\n";
            } else {
                echo "   ✗ Missing columns: " . implode(', ', $missingColumns) . "\n";
            }
        } else {
            echo "   ✗ shop_ratings table missing\n";
        }
    } catch (PDOException $e) {
        echo "   ✗ Error checking shop_ratings table: " . $e->getMessage() . "\n";
    }
    
    // Check 4: Foreign key constraints
    echo "\n4. Checking foreign key constraints...\n";
    try {
        $stmt = $db->query("
            SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_NAME IS NOT NULL 
            AND TABLE_SCHEMA = 'repair_booking'
            AND (TABLE_NAME = 'reviews' OR TABLE_NAME = 'technician_ratings' OR TABLE_NAME = 'shop_ratings')
        ");
        $constraints = $stmt->fetchAll();
        
        $expectedConstraints = [
            ['reviews', 'technician_id', 'technicians', 'id'],
            ['reviews', 'shop_id', 'repair_shops', 'id'],
            ['technician_ratings', 'technician_id', 'technicians', 'id'],
            ['shop_ratings', 'shop_id', 'repair_shops', 'id']
        ];
        
        $foundConstraints = 0;
        foreach ($expectedConstraints as $expected) {
            foreach ($constraints as $constraint) {
                if ($constraint['TABLE_NAME'] === $expected[0] && 
                    $constraint['COLUMN_NAME'] === $expected[1] && 
                    $constraint['REFERENCED_TABLE_NAME'] === $expected[2] && 
                    $constraint['REFERENCED_COLUMN_NAME'] === $expected[3]) {
                    $foundConstraints++;
                    break;
                }
            }
        }
        
        if ($foundConstraints === count($expectedConstraints)) {
            echo "   ✓ All foreign key constraints exist\n";
            $checks['foreign key constraints exist'] = true;
        } else {
            echo "   ✗ Some foreign key constraints missing ($foundConstraints/" . count($expectedConstraints) . ")\n";
        }
    } catch (PDOException $e) {
        echo "   ✗ Error checking foreign key constraints: " . $e->getMessage() . "\n";
    }
    
    // Check 5: Indexes
    echo "\n5. Checking indexes...\n";
    try {
        $expectedIndexes = [
            'idx_reviews_technician',
            'idx_reviews_shop', 
            'idx_reviews_booking',
            'idx_technician_ratings_tech',
            'idx_shop_ratings_shop'
        ];
        
        $foundIndexes = 0;
        foreach ($expectedIndexes as $indexName) {
            $stmt = $db->query("SHOW INDEX FROM reviews WHERE Key_name = '$indexName'");
            if ($stmt->fetch()) {
                $foundIndexes++;
            } else {
                // Check other tables
                $stmt = $db->query("SHOW INDEX FROM technician_ratings WHERE Key_name = '$indexName'");
                if ($stmt->fetch()) {
                    $foundIndexes++;
                } else {
                    $stmt = $db->query("SHOW INDEX FROM shop_ratings WHERE Key_name = '$indexName'");
                    if ($stmt->fetch()) {
                        $foundIndexes++;
                    }
                }
            }
        }
        
        if ($foundIndexes === count($expectedIndexes)) {
            echo "   ✓ All indexes exist\n";
            $checks['indexes are created'] = true;
        } else {
            echo "   ✗ Some indexes missing ($foundIndexes/" . count($expectedIndexes) . ")\n";
        }
    } catch (PDOException $e) {
        echo "   ✗ Error checking indexes: " . $e->getMessage() . "\n";
    }
    
    // Summary
    echo "\n=== Verification Summary ===\n";
    $passedChecks = 0;
    foreach ($checks as $check => $passed) {
        $status = $passed ? '✓' : '✗';
        echo "$status $check\n";
        if ($passed) $passedChecks++;
    }
    
    echo "\nPassed: $passedChecks/" . count($checks) . " checks\n";
    
    if ($passedChecks === count($checks)) {
        echo "\n🎉 Review system is properly installed!\n";
        echo "\nYou can now:\n";
        echo "1. Test the review system using tests/test-review-system.html\n";
        echo "2. Submit reviews from the customer dashboard\n";
        echo "3. View ratings using the API endpoints\n";
    } else {
        echo "\n⚠️  Review system installation is incomplete.\n";
        echo "Please run the migration script again or check the errors above.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Verification failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
