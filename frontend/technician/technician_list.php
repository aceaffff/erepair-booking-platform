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
    
    // Fallback: get shop_id directly if missing
    if(empty($u['shop_id'])){
        $fallback = $db->prepare('SELECT id FROM shop_owners WHERE user_id=? LIMIT 1');
        $fallback->execute([$u['id']]);
        $shopOwnerRecord = $fallback->fetch();
        if($shopOwnerRecord){
            $u['shop_id'] = $shopOwnerRecord['id'];
        }
    }
    
    return $u;
}

try{
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $shop = auth_shop($db);

    if(empty($shop['shop_id'])){ http_response_code(400); echo json_encode(['error'=>'Shop profile not found']); exit; }

    // Check if technicians table exists and has correct structure
    $tableExists = false;
    $hasShopOwnerId = false;
    try {
        $checkTable = $db->query("SHOW TABLES LIKE 'technicians'");
        $tableExists = $checkTable->rowCount() > 0;
        
        if ($tableExists) {
            $checkColumn = $db->query("SHOW COLUMNS FROM technicians LIKE 'shop_owner_id'");
            $hasShopOwnerId = $checkColumn->rowCount() > 0;
        }
    } catch (PDOException $e) {
        // Table doesn't exist
    }
    
    // Build query - use shop_owner_id if it exists, otherwise fallback to shop_id
    $useShopOwnerId = $hasShopOwnerId;

    $stmt = $db->prepare("
        SELECT t.id, u.name, u.email, u.phone, u.status,
               COALESCE(tr.average_rating, 0) as average_rating,
               COALESCE(tr.total_reviews, 0) as total_reviews
        FROM technicians t 
        INNER JOIN users u ON u.id = t.user_id 
        LEFT JOIN technician_ratings tr ON tr.technician_id = t.id
        WHERE " . ($useShopOwnerId ? "t.shop_owner_id" : "t.shop_id") . " = ? 
        ORDER BY u.name
    ");
    $stmt->execute([$shop['shop_id']]);
    $rows = $stmt->fetchAll();
    echo json_encode(['success'=>true,'technicians'=>$rows]);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'Server error','detail'=>$e->getMessage()]); }
?>



