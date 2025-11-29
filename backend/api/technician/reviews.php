<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/ResponseHelper.php';

// Initialize API environment
ResponseHelper::initApi();

function auth_technician(PDO $db) {
    $token = $_COOKIE['auth_token'] ?? '';
    if(!$token){ 
        ResponseHelper::unauthorized('Not authenticated'); 
    }
    $stmt = $db->prepare("SELECT u.id, u.role, u.name, t.id as tech_id FROM users u INNER JOIN sessions s ON s.user_id=u.id INNER JOIN technicians t ON t.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='technician' || empty($u['tech_id'])){ 
        ResponseHelper::unauthorized('Unauthorized - Invalid technician profile'); 
    }
    return $u;
}

try {
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tech = auth_technician($db);

    // Get technician rating summary
    $ratingStmt = $db->prepare("
        SELECT 
            tr.technician_id,
            tr.total_reviews,
            tr.average_rating,
            tr.total_rating_sum
        FROM technician_ratings tr
        WHERE tr.technician_id = ?
    ");
    $ratingStmt->execute([$tech['tech_id']]);
    $rating = $ratingStmt->fetch();
    
    if (!$rating) {
        $rating = [
            'technician_id' => $tech['tech_id'],
            'total_reviews' => 0,
            'average_rating' => 0.0,
            'total_rating_sum' => 0
        ];
    }

    // Get all reviews for this technician
    $reviewsStmt = $db->prepare("
        SELECT 
            r.id,
            r.rating,
            r.comment,
            r.created_at,
            u.name as customer_name,
            u.avatar as customer_avatar,
            b.device_description,
            rs.name as shop_name
        FROM reviews r
        JOIN users u ON u.id = r.customer_id
        LEFT JOIN bookings b ON b.id = r.booking_id
        LEFT JOIN repair_shops rs ON rs.id = r.shop_id
        WHERE r.technician_id = ?
        ORDER BY r.created_at DESC
    ");
    $reviewsStmt->execute([$tech['tech_id']]);
    $reviews = $reviewsStmt->fetchAll();

    ResponseHelper::success('Reviews retrieved successfully', [
        'rating' => [
            'total_reviews' => (int)$rating['total_reviews'],
            'average_rating' => round((float)$rating['average_rating'], 2),
            'total_rating_sum' => (int)$rating['total_rating_sum']
        ],
        'reviews' => $reviews
    ]);

} catch (Exception $e) {
    error_log('Technician reviews error: ' . $e->getMessage());
    ResponseHelper::serverError('Server error', $e->getMessage());
}
?>

