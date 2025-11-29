<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

// Sanitization functions
function sanitizeName($name) {
    // Remove dangerous characters that could be used for SQL injection or XSS
    // Allow letters, numbers, spaces, hyphens, apostrophes, periods, and commas
    $name = trim($name);
    $name = preg_replace('/[<>{}[\]();\'"`\\/|&*%$#@~^!]/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return $name;
}

function sanitizePhone($phone) {
    // Only allow numbers, must start with 09, exactly 11 digits
    $phone = trim($phone);
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Validate: must start with 09 and be exactly 11 digits
    if(!preg_match('/^09[0-9]{9}$/', $phone)){
        return false; // Invalid format
    }
    return $phone;
}

function sanitizeShopName($name) {
    // Remove dangerous characters from shop name
    // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, and parentheses
    $name = trim($name);
    $name = preg_replace('/[<>{}[\]();\'"`\\/|&*%$#@~^!]/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return $name;
}

function sanitizeAddress($address) {
    // Remove dangerous characters from address
    // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, parentheses, and forward slashes
    $address = trim($address);
    $address = preg_replace('/[<>{}[\]();\'"`\\|&*%$#@~^!]/', '', $address);
    $address = preg_replace('/\s+/', ' ', $address);
    return $address;
}

if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);
    exit;
}

try{
    $db = (new Database())->getConnection();

    // Auth any logged-in user via cookie token
    $token = $_COOKIE['auth_token'] ?? '';
    if($token===''){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id, so.id AS shop_id FROM users u INNER JOIN sessions s ON s.user_id=u.id LEFT JOIN shop_owners so ON so.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $auth = $stmt->fetch();
    if(!$auth){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $shop = $input['shop'] ?? [];
    $usr  = $input['user'] ?? [];

    // Sanitize and validate user basic info
    $name  = sanitizeName($usr['name'] ?? '');
    $phoneInput = trim($usr['phone'] ?? '');
    
    if($name===''){ 
        http_response_code(400); 
        echo json_encode(['error'=>'Name is required']); 
        exit; 
    }
    
    // Validate phone number if provided
    $phone = '';
    if($phoneInput !== ''){
        $phone = sanitizePhone($phoneInput);
        if($phone === false){
            http_response_code(400);
            echo json_encode(['error'=>'Phone number must start with 09 and be exactly 11 digits']);
            exit;
        }
    }
    
    if(strlen($name) > 100){
        http_response_code(400);
        echo json_encode(['error'=>'Name must be 100 characters or less']);
        exit;
    }
    
    // Check for SQL injection patterns
    $sqlPatterns = ['/\b(union|select|insert|update|delete|drop|create|alter|exec|execute|script|javascript|onerror|onload)\b/i'];
    foreach($sqlPatterns as $pattern){
        if(preg_match($pattern, $name)){
            http_response_code(400);
            echo json_encode(['error'=>'Invalid characters in name']);
            exit;
        }
    }
    
    $updUser = $db->prepare('UPDATE users SET name=?, phone=?, updated_at=NOW() WHERE id=?');
    $updUser->execute([$name, $phone, $auth['id']]);

    // Update shop info if this user has a shop
    if(!empty($auth['shop_id'])){
        $shopName  = sanitizeShopName($shop['shop_name'] ?? '');
        $shopAddr  = sanitizeAddress($shop['shop_address'] ?? '');
        $shopLat   = !empty($shop['shop_latitude']) ? floatval($shop['shop_latitude']) : null;
        $shopLng   = !empty($shop['shop_longitude']) ? floatval($shop['shop_longitude']) : null;
        
        // Validate shop name length
        if(strlen($shopName) > 255){
            http_response_code(400);
            echo json_encode(['error'=>'Shop name must be 255 characters or less']);
            exit;
        }
        
        // Validate address length
        if(strlen($shopAddr) > 500){
            http_response_code(400);
            echo json_encode(['error'=>'Address must be 500 characters or less']);
            exit;
        }
        
        // Validate latitude and longitude if provided
        if($shopLat !== null && ($shopLat < -90 || $shopLat > 90)){
            http_response_code(400);
            echo json_encode(['error'=>'Invalid latitude']);
            exit;
        }
        if($shopLng !== null && ($shopLng < -180 || $shopLng > 180)){
            http_response_code(400);
            echo json_encode(['error'=>'Invalid longitude']);
            exit;
        }
        
        $updShop = $db->prepare('UPDATE shop_owners SET shop_name=?, shop_address=?, shop_latitude=?, shop_longitude=? WHERE id=?');
        $updShop->execute([$shopName, $shopAddr, $shopLat, $shopLng, $auth['shop_id']]);
    }

    echo json_encode(['success'=>true]);
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['error'=>'Server error']);
}
?>


