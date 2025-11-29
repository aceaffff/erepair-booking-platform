<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

// Sanitization functions
function sanitizeTechnicianName($name) {
    // Remove dangerous characters that could be used for SQL injection or XSS
    // Allow letters, numbers, spaces, hyphens, apostrophes, periods, and commas
    $name = trim($name);
    $name = preg_replace('/[<>{}[\]();\'"`\\/|&*%$#@~^!]/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return $name;
}

function sanitizeTechnicianPhone($phone) {
    // Only allow numbers, must start with 09, exactly 11 digits
    $phone = trim($phone);
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    // Validate: must start with 09 and be exactly 11 digits
    if($phone !== '' && !preg_match('/^09[0-9]{9}$/', $phone)){
        return false; // Invalid format
    }
    return $phone;
}

function validatePassword($password) {
    // Check for dangerous characters that could be used in SQL injection
    $dangerousChars = ['<', '>', '{', '}', '[', ']', '(', ')', ';', "'", '"', '`', '\\', '/', '|', '&', '*', '%', '$', '#', '@', '~', '^'];
    foreach ($dangerousChars as $char) {
        if (strpos($password, $char) !== false) {
            return false;
        }
    }
    return true;
}

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
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tech = auth_technician($db);

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    // Sanitize and validate inputs
    $name = sanitizeTechnicianName($input['name'] ?? '');
    $phoneInput = trim($input['phone'] ?? '');
    $password = (string)($input['password'] ?? '');

    if($name===''){
        http_response_code(400); 
        echo json_encode(['error'=>'Name is required']); 
        exit;
    }
    
    if(strlen($name) > 100){
        http_response_code(400);
        echo json_encode(['error'=>'Name must be 100 characters or less']);
        exit;
    }
    
    // Check for SQL injection patterns in name
    $sqlPatterns = ['/\b(union|select|insert|update|delete|drop|create|alter|exec|execute|script|javascript|onerror|onload)\b/i'];
    foreach($sqlPatterns as $pattern){
        if(preg_match($pattern, $name)){
            http_response_code(400);
            echo json_encode(['error'=>'Invalid characters in name']);
            exit;
        }
    }
    
    // Validate phone if provided
    $phone = '';
    if($phoneInput !== ''){
        $phone = sanitizeTechnicianPhone($phoneInput);
        if($phone === false){
            http_response_code(400);
            echo json_encode(['error'=>'Phone number must start with 09 and be exactly 11 digits']);
            exit;
        }
    }

    // Validate password if provided
    if($password !== ''){
        if(strlen($password) < 6 || strlen($password) > 128){
            http_response_code(400);
            echo json_encode(['error'=>'Password must be between 6 and 128 characters']);
            exit;
        }
        
        if(!validatePassword($password)){
            http_response_code(400);
            echo json_encode(['error'=>'Password contains invalid characters']);
            exit;
        }
        
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE users SET name=?, phone=?, password=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$name, $phone, $hash, $tech['id']]);
    } else {
        $stmt = $db->prepare('UPDATE users SET name=?, phone=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$name, $phone, $tech['id']]);
    }

    echo json_encode(['success'=>true]);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


