<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

function auth_customer(PDO $db){
    $token = $_COOKIE['auth_token'] ?? '';
    if(!$token){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='customer'){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
    return $u;
}

if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);
    exit;
}

try{
    $db = (new Database())->getConnection();
    $u = auth_customer($db);

    if(!isset($_FILES['avatar']) || $_FILES['avatar']['error']!==UPLOAD_ERR_OK){
        http_response_code(400);
        echo json_encode(['error'=>'No file uploaded']);
        exit;
    }

    $file = $_FILES['avatar'];
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    // safer MIME detection across PHP setups
    $mime = null;
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) { $mime = finfo_file($f, $file['tmp_name']); finfo_close($f); }
    }
    if (!$mime && function_exists('getimagesize')) {
        $imgInfo = @getimagesize($file['tmp_name']);
        if ($imgInfo && isset($imgInfo['mime'])) { $mime = $imgInfo['mime']; }
    }
    if (!$mime) {
        // fallback to extension guess
        $extGuess = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $map = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'];
        $mime = $map[$extGuess] ?? '';
    }
    if(!isset($allowed[$mime])){
        http_response_code(400);
        echo json_encode(['error'=>'Invalid image type','detail'=>$mime ?: 'unknown']);
        exit;
    }
    if($file['size'] > 2*1024*1024){
        http_response_code(400);
        echo json_encode(['error'=>'Image too large (max 2MB)']);
        exit;
    }

    $ext = $allowed[$mime];
    $uploadDir = __DIR__ . '/../uploads/avatars';
    if(!is_dir($uploadDir)){
        @mkdir($uploadDir, 0775, true);
    }

    $filename = 'user_'.$u['id'].'_'.time().'.'.$ext;
    $destPath = $uploadDir . '/' . $filename;
    if(!move_uploaded_file($file['tmp_name'], $destPath)){
        http_response_code(500);
        echo json_encode(['error'=>'Failed to save image']);
        exit;
    }

    // Build public URL relative to frontend directory
    $publicUrl = 'uploads/avatars/' . $filename;

    try {
        $stmt = $db->prepare('UPDATE users SET avatar=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$publicUrl, $u['id']]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            http_response_code(500);
            echo json_encode(['error'=>'Database missing avatar_url column','detail'=>'Run backend/update_database_avatar.php then retry.']);
            exit;
        }
        throw $e;
    }

    echo json_encode(['success'=>true,'avatar_url'=>$publicUrl]);
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['error'=>'Server error','detail'=>$e->getMessage()]);
}
?>


