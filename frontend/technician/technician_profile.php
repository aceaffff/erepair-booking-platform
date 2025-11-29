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

    // Defensive: ensure tables
    $db->exec("CREATE TABLE IF NOT EXISTS technicians (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        shop_owner_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (shop_owner_id) REFERENCES shop_owners(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $db->exec("CREATE TABLE IF NOT EXISTS technician_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        technician_id INT NOT NULL,
        shop_owner_id INT NOT NULL,
        bio TEXT,
        years_experience INT DEFAULT 0,
        certifications TEXT,
        specialties TEXT,
        hourly_rate DECIMAL(10,2) DEFAULT 0,
        availability VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
        FOREIGN KEY (shop_owner_id) REFERENCES shop_owners(id) ON DELETE CASCADE,
        UNIQUE KEY uniq_profile_per_tech (technician_id)
    ) ENGINE=InnoDB");

    $method = $_SERVER['REQUEST_METHOD'];

    if($method === 'GET'){
        $techId = isset($_GET['technician_id']) ? (int)$_GET['technician_id'] : 0;
        if($techId<=0){ http_response_code(400); echo json_encode(['error'=>'Invalid technician_id']); exit; }
        $chk = $db->prepare('SELECT id FROM technicians WHERE id=? AND shop_owner_id=?');
        $chk->execute([$techId, $shop['shop_id']]);
        if(!$chk->fetch()){ http_response_code(404); echo json_encode(['error'=>'Technician not found']); exit; }
        $stmt = $db->prepare('SELECT id, technician_id, bio, years_experience, certifications, specialties, hourly_rate, availability FROM technician_profiles WHERE technician_id=? AND shop_owner_id=?');
        $stmt->execute([$techId, $shop['shop_id']]);
        $profile = $stmt->fetch();
        echo json_encode(['success'=>true, 'profile'=>$profile ?: null]);
        exit;
    }

    if($method === 'POST'){
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $techId = (int)($input['technician_id'] ?? 0);
        if($techId<=0){ http_response_code(400); echo json_encode(['error'=>'technician_id is required']); exit; }
        $chk = $db->prepare('SELECT id FROM technicians WHERE id=? AND shop_owner_id=?');
        $chk->execute([$techId, $shop['shop_id']]);
        if(!$chk->fetch()){ http_response_code(404); echo json_encode(['error'=>'Technician not found']); exit; }

        $bio = trim($input['bio'] ?? '');
        $years = (int)($input['years_experience'] ?? 0);
        $cert = trim($input['certifications'] ?? '');
        $spec = trim($input['specialties'] ?? '');
        $rate = isset($input['hourly_rate']) ? (float)$input['hourly_rate'] : 0;
        $avail = trim($input['availability'] ?? '');

        // upsert
        $stmt = $db->prepare('SELECT id FROM technician_profiles WHERE technician_id=? AND shop_owner_id=?');
        $stmt->execute([$techId, $shop['shop_id']]);
        $existing = $stmt->fetch();
        if($existing){
            $upd = $db->prepare('UPDATE technician_profiles SET bio=?, years_experience=?, certifications=?, specialties=?, hourly_rate=?, availability=? WHERE id=?');
            $upd->execute([$bio,$years,$cert,$spec,$rate,$avail,$existing['id']]);
        } else {
            $ins = $db->prepare('INSERT INTO technician_profiles (technician_id, shop_owner_id, bio, years_experience, certifications, specialties, hourly_rate, availability) VALUES (?,?,?,?,?,?,?,?)');
            $ins->execute([$techId, $shop['shop_id'], $bio, $years, $cert, $spec, $rate, $avail]);
        }
        echo json_encode(['success'=>true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);
}catch(PDOException $e){ http_response_code(500); echo json_encode(['error'=>'Server error','detail'=>$e->getMessage()]); }
?>
