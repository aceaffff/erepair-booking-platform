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

    // Defensive: ensure tables exist (using existing structure)
    $db->exec("CREATE TABLE IF NOT EXISTS technicians (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        shop_id INT NOT NULL,
        skills TEXT,
        rating FLOAT DEFAULT 0,
        total_ratings INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (shop_id) REFERENCES shop_owners(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $stmt = $db->prepare("
        SELECT t.id, u.name, u.email, u.phone, u.status,
               COALESCE(tr.average_rating, 0) as average_rating,
               COALESCE(tr.total_reviews, 0) as total_reviews
        FROM technicians t 
        INNER JOIN users u ON u.id = t.user_id 
        LEFT JOIN technician_ratings tr ON tr.technician_id = t.id
        WHERE t.shop_id = ? 
        ORDER BY u.name
    ");
    $stmt->execute([$shop['shop_id']]);
    $rows = $stmt->fetchAll();
    echo json_encode(['success'=>true,'technicians'=>$rows]);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'Server error','detail'=>$e->getMessage()]); }
?>



