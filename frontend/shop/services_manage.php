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
    $shop = auth_shop($db);
    $method = $_SERVER['REQUEST_METHOD'];

    // ensure table
    $db->exec("CREATE TABLE IF NOT EXISTS shop_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shop_owner_id INT NOT NULL,
        service_name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) DEFAULT 0,
        FOREIGN KEY (shop_owner_id) REFERENCES shop_owners(id) ON DELETE CASCADE
    )");

    if($method==='GET'){
        $stmt = $db->prepare('SELECT id, service_name, price FROM shop_services WHERE shop_owner_id=? ORDER BY service_name');
        $stmt->execute([$shop['shop_id']]);
        echo json_encode(['success'=>true,'services'=>$stmt->fetchAll()]);
        exit;
    }

    // Function to sanitize service name
    function sanitizeServiceName($name) {
        // Remove dangerous characters that could be used for SQL injection
        // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, and parentheses
        $name = trim($name);
        $name = preg_replace('/[<>{}[\]();\'"`\\/|&*%$#@~^!]/', '', $name);
        // Remove multiple consecutive spaces
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    if($method==='POST'){
        $name = sanitizeServiceName($input['service_name'] ?? '');
        $price = (float)($input['price'] ?? 0);
        
        if($name===''){ 
            http_response_code(400); 
            echo json_encode(['error'=>'Service name is required']); 
            exit; 
        }
        
        if($price < 0){
            http_response_code(400); 
            echo json_encode(['error'=>'Price cannot be negative']); 
            exit; 
        }
        
        if(strlen($name) > 255){
            http_response_code(400); 
            echo json_encode(['error'=>'Service name must be 255 characters or less']); 
            exit; 
        }
        
        $stmt = $db->prepare('INSERT INTO shop_services (shop_owner_id, service_name, price) VALUES (?,?,?)');
        $stmt->execute([$shop['shop_id'], $name, $price]);
        echo json_encode(['success'=>true]);
        exit;
    }

    if($method==='PUT'){
        $id = (int)($input['id'] ?? 0);
        $name = sanitizeServiceName($input['service_name'] ?? '');
        $price = (float)($input['price'] ?? 0);
        
        if($id<=0 || $name===''){ 
            http_response_code(400); 
            echo json_encode(['error'=>'Invalid input']); 
            exit; 
        }
        
        if($price < 0){
            http_response_code(400); 
            echo json_encode(['error'=>'Price cannot be negative']); 
            exit; 
        }
        
        if(strlen($name) > 255){
            http_response_code(400); 
            echo json_encode(['error'=>'Service name must be 255 characters or less']); 
            exit; 
        }
        // ownership
        $chk = $db->prepare('SELECT id FROM shop_services WHERE id=? AND shop_owner_id=?');
        $chk->execute([$id, $shop['shop_id']]);
        if(!$chk->fetch()){ http_response_code(404); echo json_encode(['error'=>'Not found']); exit; }
        $db->prepare('UPDATE shop_services SET service_name=?, price=? WHERE id=?')->execute([$name,$price,$id]);
        echo json_encode(['success'=>true]);
        exit;
    }

    if($method==='DELETE'){
        $id = (int)($_GET['id'] ?? 0);
        if($id<=0){ http_response_code(400); echo json_encode(['error'=>'Invalid']); exit; }
        $db->prepare('DELETE FROM shop_services WHERE id=? AND shop_owner_id=?')->execute([$id,$shop['shop_id']]);
        echo json_encode(['success'=>true]);
        exit;
    }

    http_response_code(405); echo json_encode(['error'=>'Method not allowed']);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


