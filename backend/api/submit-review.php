<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/ResponseHelper.php';
require_once __DIR__ . '/../utils/InputValidator.php';

// Initialize API environment
ResponseHelper::initApi();

function auth_customer(PDO $db) {
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if($token === null){ 
        ResponseHelper::unauthorized('Not authenticated'); 
    }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='customer'){ 
        ResponseHelper::unauthorized('Unauthorized'); 
    }
    return $u;
}

try {
    $db = (new Database())->getConnection();
    $user = auth_customer($db);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ResponseHelper::methodNotAllowed();
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ResponseHelper::validationError('Invalid JSON input');
    }
    
    // Validate required fields
    $bookingId = InputValidator::validateId($input['booking_id'] ?? 0);
    $rating = (int)($input['rating'] ?? 0);
    $comment = InputValidator::sanitizeHtml($input['comment'] ?? '', '');
    
    if ($bookingId === null || $rating < 1 || $rating > 5) {
        ResponseHelper::validationError('Valid booking ID and rating (1-5) are required');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Check if booking exists, is completed, and belongs to customer
        $bookingStmt = $db->prepare("
            SELECT b.id, b.technician_id, b.shop_id, b.status, b.customer_id,
                   u.name as technician_name
            FROM bookings b
            LEFT JOIN technicians t ON t.id = b.technician_id
            LEFT JOIN users u ON u.id = t.user_id
            WHERE b.id = ? AND b.customer_id = ? AND b.status = 'completed'
        ");
        $bookingStmt->execute([$bookingId, $user['id']]);
        $booking = $bookingStmt->fetch();
        
        if (!$booking) {
            throw new Exception('Booking not found or not completed');
        }
        
        if (!$booking['technician_id']) {
            throw new Exception('No technician assigned to this booking');
        }
        
        // Check if review already exists (one review per booking)
        $existingReviewStmt = $db->prepare("SELECT id FROM reviews WHERE booking_id = ?");
        $existingReviewStmt->execute([$bookingId]);
        if ($existingReviewStmt->fetch()) {
            throw new Exception('Review already submitted for this booking');
        }
        
        // Insert review
        $insertStmt = $db->prepare("
            INSERT INTO reviews (booking_id, technician_id, shop_id, customer_id, technician_rating, technician_comment)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $insertStmt->execute([
            $bookingId,
            $booking['technician_id'],
            $booking['shop_id'],
            $user['id'],
            $rating,
            $comment
        ]);
        
        // Update technician rating
        $updateTechRatingStmt = $db->prepare("
            INSERT INTO technician_ratings (technician_id, total_reviews, average_rating, total_rating_sum)
            SELECT 
                technician_id,
                COUNT(*) as total_reviews,
                AVG(technician_rating) as average_rating,
                SUM(technician_rating) as total_rating_sum
            FROM reviews 
            WHERE technician_id = ? AND technician_rating IS NOT NULL
            GROUP BY technician_id
            ON DUPLICATE KEY UPDATE
                total_reviews = VALUES(total_reviews),
                average_rating = VALUES(average_rating),
                total_rating_sum = VALUES(total_rating_sum),
                updated_at = CURRENT_TIMESTAMP
        ");
        $updateTechRatingStmt->execute([$booking['technician_id']]);
        
        // Update shop rating (using technician ratings for shop overall rating)
        $updateShopRatingStmt = $db->prepare("
            INSERT INTO shop_ratings (shop_id, total_reviews, average_rating, total_rating_sum)
            SELECT 
                shop_id,
                COUNT(*) as total_reviews,
                AVG(technician_rating) as average_rating,
                SUM(technician_rating) as total_rating_sum
            FROM reviews 
            WHERE shop_id = ? AND technician_rating IS NOT NULL
            GROUP BY shop_id
            ON DUPLICATE KEY UPDATE
                total_reviews = VALUES(total_reviews),
                average_rating = VALUES(average_rating),
                total_rating_sum = VALUES(total_rating_sum),
                updated_at = CURRENT_TIMESTAMP
        ");
        $updateShopRatingStmt->execute([$booking['shop_id']]);
        
        // Update booking to mark as reviewed
        $updateBookingStmt = $db->prepare("
            UPDATE bookings 
            SET notes = JSON_SET(COALESCE(notes, '{}'), '$.reviewed', true, '$.reviewed_at', NOW())
            WHERE id = ?
        ");
        $updateBookingStmt->execute([$bookingId]);
        
        // Commit transaction
        $db->commit();
        
        ResponseHelper::success('Review submitted successfully', [
            'booking_id' => $bookingId,
            'rating' => $rating,
            'technician_name' => $booking['technician_name']
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Review submission error: ' . $e->getMessage());
    ResponseHelper::serverError('Server error', $e->getMessage());
}
?>
