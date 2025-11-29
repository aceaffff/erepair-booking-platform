<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try{
    $db = (new Database())->getConnection();

    // Authenticate any logged-in user (customer, shop_owner, technician)
    $token = $_COOKIE['auth_token'] ?? '';
    if(empty($token)){
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    $stmt = $db->prepare("SELECT u.id, u.password FROM users u INNER JOIN sessions s ON s.user_id = u.id WHERE s.token = ? AND s.expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if(!$user){
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $old = (string)($input['old_password'] ?? '');
    $new = (string)($input['new_password'] ?? '');

    if($old === '' || $new === ''){
        http_response_code(400);
        echo json_encode(['error' => 'Both current and new password are required']);
        exit;
    }
    if(strlen($new) < 6){
        http_response_code(400);
        echo json_encode(['error' => 'New password must be at least 6 characters']);
        exit;
    }

    if(!password_verify($old, $user['password'] ?? '')){
        http_response_code(400);
        echo json_encode(['error' => 'Current password is incorrect']);
        exit;
    }

    // Check if new password is the same as current password
    if(password_verify($new, $user['password'] ?? '')){
        http_response_code(400);
        echo json_encode(['error' => 'New password must be different from current password']);
        exit;
    }

    $hash = password_hash($new, PASSWORD_BCRYPT);
    $upd = $db->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
    $upd->execute([$hash, $user['id']]);

    echo json_encode(['success' => true]);
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>


