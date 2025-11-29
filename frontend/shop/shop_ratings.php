<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

function auth_shop(PDO $db){
    $token = $_COOKIE['auth_token'] ?? '';
    if(!$token){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id, u.role, so.id as shop_id FROM users u INNER JOIN sessions s ON s.user_id=u.id LEFT JOIN shop_owners so ON so.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='shop_owner'){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
    return $u;
}

try{
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $shop = auth_shop($db);

    if(empty($shop['shop_id'])){ http_response_code(400); echo json_encode(['error'=>'Shop profile not found']); exit; }

    $type = $_GET['type'] ?? '';
    $id = (int)($_GET['id'] ?? 0);
    
    if($type === 'technician'){
        // First check if technician exists and belongs to this shop
        $stmt = $db->prepare("
            SELECT 
                t.id as technician_id,
                u.name as technician_name
            FROM technicians t
            JOIN users u ON u.id = t.user_id
            WHERE t.id = ? AND t.shop_id = ?
        ");
        $stmt->execute([$id, $shop['shop_id']]);
        $technician = $stmt->fetch();
        
        if(!$technician){
            // Debug: Log the query parameters
            error_log("Technician not found - ID: $id, Shop ID: {$shop['shop_id']}");
            echo json_encode(['success'=>false, 'error'=>'Technician not found']);
            exit;
        }
        
        // Get technician ratings (if any)
        $stmt = $db->prepare("
            SELECT 
                tr.technician_id,
                tr.total_reviews,
                tr.average_rating
            FROM technician_ratings tr
            WHERE tr.technician_id = ?
        ");
        $stmt->execute([$id]);
        $rating = $stmt->fetch();
        
        // If no ratings exist, create default values
        if(!$rating){
            $rating = [
                'technician_id' => $id,
                'total_reviews' => 0,
                'average_rating' => 0.0
            ];
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
        
        echo json_encode([
            'success' => true,
            'rating' => [
                'total_reviews' => (int)$rating['total_reviews'],
                'average_rating' => round((float)$rating['average_rating'], 2)
            ],
            'recent_reviews' => $reviews
        ]);
        
    } elseif($type === 'shop'){
        // Get shop ratings - handle missing table or missing ratings gracefully
        $rating = null;
        try {
            $stmt = $db->prepare("
                SELECT 
                    sr.shop_id,
                    sr.total_reviews,
                    sr.average_rating
                FROM shop_ratings sr
                WHERE sr.shop_id = ?
            ");
            $stmt->execute([$shop['shop_id']]);
            $rating = $stmt->fetch();
        } catch (PDOException $e) {
            // Table doesn't exist or other error - use default values
            error_log("Shop ratings table error: " . $e->getMessage());
        }
        
        // If no ratings exist, create default values
        if(!$rating){
            $rating = [
                'shop_id' => $shop['shop_id'],
                'total_reviews' => 0,
                'average_rating' => 0.0
            ];
        }
        
        // Get recent reviews for this shop
        $reviews = [];
        try {
            $reviewsStmt = $db->prepare("
                SELECT 
                    r.rating as rating,
                    r.comment,
                    r.created_at,
                    u.name as customer_name
                FROM reviews r
                JOIN users u ON u.id = r.customer_id
                WHERE r.shop_id = ? AND r.rating IS NOT NULL
                ORDER BY r.created_at DESC
                LIMIT 10
            ");
            $reviewsStmt->execute([$shop['shop_id']]);
            $reviews = $reviewsStmt->fetchAll();
        } catch (PDOException $e) {
            // Handle missing columns gracefully
            error_log("Shop reviews query error: " . $e->getMessage());
            $reviews = [];
        }
        
        echo json_encode([
            'success' => true,
            'rating' => [
                'total_reviews' => (int)$rating['total_reviews'],
                'average_rating' => round((float)$rating['average_rating'], 2)
            ],
            'recent_reviews' => $reviews
        ]);
        
    } else {
        echo json_encode(['success'=>false, 'error'=>'Invalid type']);
    }
    
}catch(Exception $e){ 
    http_response_code(500); 
    echo json_encode(['error'=>'Server error','detail'=>$e->getMessage()]); 
}
?>
