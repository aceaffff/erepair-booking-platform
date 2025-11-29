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
    if(empty($u['shop_id'])){ http_response_code(400); echo json_encode(['error'=>'Shop profile not found']); exit; }
    return $u;
}

try{
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $shop = auth_shop($db);

    // Defensive: create tables if missing
    $db->exec("CREATE TABLE IF NOT EXISTS technicians (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        shop_owner_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (shop_owner_id) REFERENCES shop_owners(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS technician_skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        technician_id INT NOT NULL,
        shop_owner_id INT NOT NULL,
        skill_name VARCHAR(255) NOT NULL,
        skill_level ENUM('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
        FOREIGN KEY (shop_owner_id) REFERENCES shop_owners(id) ON DELETE CASCADE,
        INDEX idx_tech (technician_id),
        INDEX idx_shop (shop_owner_id)
    ) ENGINE=InnoDB");

    $method = $_SERVER['REQUEST_METHOD'];

    if($method === 'GET'){
        $techId = isset($_GET['technician_id']) ? (int)$_GET['technician_id'] : 0;
        if($techId <= 0){ http_response_code(400); echo json_encode(['error'=>'Invalid technician_id']); exit; }
        // ensure tech belongs to shop
        $chk = $db->prepare('SELECT id FROM technicians WHERE id=? AND shop_owner_id=?');
        $chk->execute([$techId, $shop['shop_id']]);
        if(!$chk->fetch()){ http_response_code(404); echo json_encode(['error'=>'Technician not found']); exit; }
        $stmt = $db->prepare('SELECT id, skill_name, skill_level FROM technician_skills WHERE technician_id=? AND shop_owner_id=? ORDER BY skill_name');
        $stmt->execute([$techId, $shop['shop_id']]);
        echo json_encode(['success'=>true, 'skills'=>$stmt->fetchAll()]);
        exit;
    }

    if($method === 'POST'){
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $techId = (int)($input['technician_id'] ?? 0);
        $skillName = trim($input['skill_name'] ?? '');
        $skillLevel = strtolower(trim($input['skill_level'] ?? 'beginner'));
        if($techId<=0 || $skillName===''){ http_response_code(400); echo json_encode(['error'=>'technician_id and skill_name are required']); exit; }
        if(!in_array($skillLevel, ['beginner','intermediate','advanced','expert'], true)) $skillLevel='beginner';
        // ensure tech belongs to shop
        $chk = $db->prepare('SELECT id FROM technicians WHERE id=? AND shop_owner_id=?');
        $chk->execute([$techId, $shop['shop_id']]);
        if(!$chk->fetch()){ http_response_code(404); echo json_encode(['error'=>'Technician not found']); exit; }
        $ins = $db->prepare('INSERT INTO technician_skills (technician_id, shop_owner_id, skill_name, skill_level) VALUES (?,?,?,?)');
        $ins->execute([$techId, $shop['shop_id'], $skillName, $skillLevel]);
        echo json_encode(['success'=>true]);
        exit;
    }

    if($method === 'DELETE'){
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if($id<=0){ http_response_code(400); echo json_encode(['error'=>'Invalid id']); exit; }
        // ensure skill belongs to shop
        $chk = $db->prepare('SELECT s.id FROM technician_skills s INNER JOIN technicians t ON t.id=s.technician_id WHERE s.id=? AND s.shop_owner_id=? AND t.shop_owner_id=?');
        $chk->execute([$id, $shop['shop_id'], $shop['shop_id']]);
        if(!$chk->fetch()){ http_response_code(404); echo json_encode(['error'=>'Skill not found']); exit; }
        $db->prepare('DELETE FROM technician_skills WHERE id=?')->execute([$id]);
        echo json_encode(['success'=>true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);
}catch(PDOException $e){ http_response_code(500); echo json_encode(['error'=>'Server error','detail'=>$e->getMessage()]); }
?>
