<?php
require_once '../../backend/config/database.php';
require_once '../../backend/utils/ResponseHelper.php';

// Initialize API environment
ResponseHelper::initApi();

function auth_customer(PDO $db) {
    $token = $_COOKIE['auth_token'] ?? '';
    if(!$token){ 
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
    $bookingId = $input['booking_id'] ?? null;
    $rating = $input['rating'] ?? null;
    $comment = trim($input['comment'] ?? '');
    
    if (!$bookingId || !$rating) {
        ResponseHelper::validationError('Booking ID and rating are required');
    }
    
    if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        ResponseHelper::validationError('Rating must be between 1 and 5');
    }
    
    // Check if booking exists and belongs to customer
    $bookingStmt = $db->prepare("
        SELECT b.id, b.technician_id, b.shop_id, b.status, b.customer_id,
               t.id as tech_id, rs.id as shop_id_check
        FROM bookings b
        LEFT JOIN technicians t ON t.id = b.technician_id
        LEFT JOIN repair_shops rs ON rs.id = b.shop_id
        WHERE b.id = ? AND b.customer_id = ? AND b.status = 'completed'
    ");
    $bookingStmt->execute([$bookingId, $user['id']]);
    $booking = $bookingStmt->fetch();
    
    if (!$booking) {
        ResponseHelper::validationError('Booking not found or not completed');
    }
    
    if (!$booking['technician_id']) {
        ResponseHelper::validationError('No technician assigned to this booking');
    }
    
    // Check if review already exists
    $existingReviewStmt = $db->prepare("SELECT id FROM reviews WHERE booking_id = ?");
    $existingReviewStmt->execute([$bookingId]);
    if ($existingReviewStmt->fetch()) {
        ResponseHelper::validationError('Review already submitted for this booking');
    }
    
    // Insert review
    $insertStmt = $db->prepare("
        INSERT INTO reviews (booking_id, technician_id, shop_id, customer_id, rating, comment)
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
    
    ResponseHelper::success('Review submitted successfully');
    
} catch (Exception $e) {
    ResponseHelper::serverError('Server error', $e->getMessage());
}
?>
