<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

try{
    $db = (new Database())->getConnection();
    // Admin auth via cookie token
    $token = $_COOKIE['auth_token'] ?? '';
    if($token===''){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id,u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $auth = $stmt->fetch();
    if(!$auth || $auth['role']!=='admin'){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

    $ownerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if($ownerId<=0){ http_response_code(400); echo json_encode(['error'=>'Invalid id']); exit; }

    // Check if new columns exist
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'id_type'");
    $hasNewColumns = $stmt->fetch() !== false;
    
    // Check if selfie_file column exists
    $stmt = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'selfie_file'");
    $hasSelfieFile = $stmt->fetch() !== false;
    
    if($hasNewColumns){
        if($hasSelfieFile) {
            $stmt = $db->prepare('SELECT id_type, id_number, id_expiry_date, id_file_front, id_file_back, selfie_file, business_permit_file FROM shop_owners WHERE id=?');
        } else {
            $stmt = $db->prepare('SELECT id_type, id_number, id_expiry_date, id_file_front, id_file_back, business_permit_file FROM shop_owners WHERE id=?');
        }
    } else {
        $stmt = $db->prepare('SELECT id_file, business_permit_file FROM shop_owners WHERE id=?');
    }
    $stmt->execute([$ownerId]);
    $row = $stmt->fetch();
    if(!$row){ http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }

    // Use absolute path from document root to avoid path issues
    $base = '../../backend/uploads/shop_owners/';
    
    if($hasNewColumns){
        $result = [
            'success' => true,
            'id_type' => $row['id_type'] ?? null,
            'id_number' => $row['id_number'] ?? null,
            'id_expiry_date' => $row['id_expiry_date'] ?? null,
            'id_front_url' => $row['id_file_front'] ? $base . $row['id_file_front'] : null,
            'id_back_url' => $row['id_file_back'] ? $base . $row['id_file_back'] : null,
            'selfie_url' => ($hasSelfieFile && !empty($row['selfie_file'])) ? $base . $row['selfie_file'] : null,
            'permit_url' => $row['business_permit_file'] ? $base . $row['business_permit_file'] : null
        ];
        // For backward compatibility
        $result['id_url'] = $result['id_front_url'];
    } else {
        $result = [
            'success' => true,
            'id_url' => $row['id_file'] ? $base . $row['id_file'] : null,
            'permit_url' => $row['business_permit_file'] ? $base . $row['business_permit_file'] : null
        ];
    }
    
    echo json_encode($result);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


