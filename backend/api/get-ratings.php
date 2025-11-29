<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/ResponseHelper.php';
require_once __DIR__ . '/../utils/InputValidator.php';

// Initialize API environment
ResponseHelper::initApi();

function auth_user(PDO $db) {
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if($token === null){ 
        ResponseHelper::unauthorized('Not authenticated'); 
    }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u){ 
        ResponseHelper::unauthorized('Unauthorized'); 
    }
    return $u;
}

try {
    $db = (new Database())->getConnection();
    $user = auth_user($db);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        ResponseHelper::methodNotAllowed();
    }
    
    $type = $_GET['type'] ?? '';
    $id = InputValidator::validateId($_GET['id'] ?? 0);
    
    if (!$id) {
        ResponseHelper::validationError('Valid ID is required');
    }
    
    if ($type === 'technician') {
        // Get technician ratings
        $stmt = $db->prepare("
            SELECT 
                tr.technician_id,
                tr.total_reviews,
                tr.average_rating,
                u.name as technician_name,
                t.shop_id,
                rs.name as shop_name
            FROM technician_ratings tr
            JOIN technicians t ON t.id = tr.technician_id
            JOIN users u ON u.id = t.user_id
            JOIN repair_shops rs ON rs.id = t.shop_id
            WHERE tr.technician_id = ?
        ");
        $stmt->execute([$id]);
        $rating = $stmt->fetch();
        
        if (!$rating) {
            ResponseHelper::notFound('Technician not found');
        }
        
        // Get recent reviews for this technician
        $reviewsStmt = $db->prepare("
            SELECT 
                r.technician_rating as rating,
                r.technician_comment as comment,
                r.created_at,
                u.name as customer_name
            FROM reviews r
            JOIN users u ON u.id = r.customer_id
            WHERE r.technician_id = ? AND r.technician_rating IS NOT NULL
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        $reviewsStmt->execute([$id]);
        $reviews = $reviewsStmt->fetchAll();
        
        ResponseHelper::success('Technician ratings retrieved', [
            'technician' => [
                'id' => $rating['technician_id'],
                'name' => $rating['technician_name'],
                'shop_name' => $rating['shop_name']
            ],
            'rating' => [
                'total_reviews' => (int)$rating['total_reviews'],
                'average_rating' => round((float)$rating['average_rating'], 2)
            ],
            'recent_reviews' => $reviews
        ]);
        
    } elseif ($type === 'shop') {
        // Get shop ratings
        $stmt = $db->prepare("
            SELECT 
                sr.shop_id,
                sr.total_reviews,
                sr.average_rating,
                rs.name as shop_name,
                rs.address as shop_address
            FROM shop_ratings sr
            JOIN repair_shops rs ON rs.id = sr.shop_id
            WHERE sr.shop_id = ?
        ");
        $stmt->execute([$id]);
        $rating = $stmt->fetch();
        
        if (!$rating) {
            ResponseHelper::notFound('Shop not found');
        }
        
        // Get recent reviews for this shop
        $reviewsStmt = $db->prepare("
            SELECT 
                r.technician_rating as rating,
                r.technician_comment as comment,
                r.created_at,
                u.name as customer_name,
                tech_user.name as technician_name
            FROM reviews r
            JOIN users u ON u.id = r.customer_id
            LEFT JOIN technicians t ON t.id = r.technician_id
            LEFT JOIN users tech_user ON tech_user.id = t.user_id
            WHERE r.shop_id = ? AND r.technician_rating IS NOT NULL
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        $reviewsStmt->execute([$id]);
        $reviews = $reviewsStmt->fetchAll();
        
        ResponseHelper::success('Shop ratings retrieved', [
            'shop' => [
                'id' => $rating['shop_id'],
                'name' => $rating['shop_name'],
                'address' => $rating['shop_address']
            ],
            'rating' => [
                'total_reviews' => (int)$rating['total_reviews'],
                'average_rating' => round((float)$rating['average_rating'], 2)
            ],
            'recent_reviews' => $reviews
        ]);
        
    } else {
        ResponseHelper::validationError('Invalid type. Use "technician" or "shop"');
    }
    
} catch (Exception $e) {
    ResponseHelper::serverError('Server error', $e->getMessage());
}
?>
