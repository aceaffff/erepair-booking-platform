<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

function auth_technician(PDO $db){
    $token = $_COOKIE['auth_token'] ?? '';
    if(!$token){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id, u.role, t.id as tech_id FROM users u INNER JOIN sessions s ON s.user_id=u.id INNER JOIN technicians t ON t.user_id=u.id WHERE s.token=? AND s.expires_at>NOW() ");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='technician' || empty($u['tech_id'])){ http_response_code(401); echo json_encode(['error'=>'Unauthorized - Invalid technician profile']); exit; }
    return $u;
}

if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

try{
    $db = (new Database())->getConnection();
    $tech = auth_technician($db);

    if(!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK){
        http_response_code(400); echo json_encode(['error'=>'No file uploaded or upload error']); exit;
    }

    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if(!in_array($file['type'], $allowedTypes)){
        http_response_code(400); echo json_encode(['error'=>'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.']); exit;
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if($file['size'] > $maxSize){
        http_response_code(400); echo json_encode(['error'=>'File too large. Maximum size is 5MB.']); exit;
    }

    $dir = __DIR__ . '/../uploads/avatars';
    if(!is_dir($dir)){ mkdir($dir, 0755, true); }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'tech_' . $tech['id'] . '_' . time() . '_' . uniqid() . '.' . $ext;
    $path = $dir . '/' . $filename;

    if(!move_uploaded_file($file['tmp_name'], $path)){
        http_response_code(500); echo json_encode(['error'=>'Failed to save file']); exit;
    }

    // Update user avatar_url in database
    $avatarUrl = 'uploads/avatars/' . $filename;
    $stmt = $db->prepare('UPDATE users SET avatar=?, updated_at=NOW() WHERE id=?');
    $stmt->execute([$avatarUrl, $tech['id']]);

    echo json_encode(['success'=>true, 'avatar_url'=>$avatarUrl]);
}catch(Exception $e){ 
    http_response_code(500); 
    echo json_encode(['error'=>'Server error', 'detail'=>$e->getMessage()]); 
}
?>
