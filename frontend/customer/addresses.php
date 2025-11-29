<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../backend/utils/InputValidator.php';

function auth_customer(PDO $db){
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if($token === null){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='customer'){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
    return $u;
}

function path_for_user($userId){
    $dir = __DIR__ . '/uploads/addresses';
    if(!is_dir($dir)) @mkdir($dir, 0775, true);
    return $dir . '/addr_' . intval($userId) . '.json';
}

$method = $_SERVER['REQUEST_METHOD'];

try{
    $db = (new Database())->getConnection();
    $u = auth_customer($db);
    $path = path_for_user($u['id']);
    $list = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    if(!is_array($list)) $list = [];

    if($method==='GET'){
        echo json_encode(['success'=>true,'addresses'=>$list]);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if($method==='POST'){
        $addr = [
            'id' => time(),
            'label' => InputValidator::validateString($input['label'] ?? '', 1, 100) ?? 'Address',
            'line1' => InputValidator::validateString($input['line1'] ?? '', 1, 255) ?? '',
            'line2' => InputValidator::validateString($input['line2'] ?? '', 0, 255) ?? '',
            'city' => InputValidator::validateString($input['city'] ?? '', 1, 100) ?? '',
            'phone' => InputValidator::validateString($input['phone'] ?? '', 0, 50) ?? ''
        ];
        $list[] = $addr;
        file_put_contents($path, json_encode($list));
        echo json_encode(['success'=>true,'address'=>$addr]);
        exit;
    }
    if($method==='DELETE'){
        $id = intval($_GET['id'] ?? 0);
        $list = array_values(array_filter($list, fn($a)=> intval($a['id']) !== $id));
        file_put_contents($path, json_encode($list));
        echo json_encode(['success'=>true]);
        exit;
    }
    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


