<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

// Sanitization functions
function sanitizeCustomerName($name) {
    // Remove dangerous characters that could be used for SQL injection or XSS
    // Allow letters, numbers, spaces, hyphens, apostrophes, periods, and commas
    $name = trim($name);
    $name = preg_replace('/[<>{}[\]();\'"`\\/|&*%$#@~^!]/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return $name;
}

function sanitizeCustomerPhone($phone) {
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

function sanitizeCustomerAddress($address) {
    // Remove dangerous characters from address
    // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, parentheses, and forward slashes
    $address = trim($address);
    $address = preg_replace('/[<>{}[\]();\'"`\\|&*%$#@~^!]/', '', $address);
    $address = preg_replace('/\s+/', ' ', $address);
    return $address;
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

function auth_customer_or_unauthorized(PDO $db): array {
    $token = $_COOKIE['auth_token'] ?? '';
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] !== 'customer') {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    return $user;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $user = auth_customer_or_unauthorized($db);

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    // Sanitize and validate inputs
    $name = sanitizeCustomerName($input['name'] ?? '');
    $phoneInput = trim($input['phone'] ?? '');
    $address = sanitizeCustomerAddress($input['address'] ?? '');
    $password = (string)($input['password'] ?? '');

    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        exit;
    }
    
    if(strlen($name) > 100){
        http_response_code(400);
        echo json_encode(['error' => 'Name must be 100 characters or less']);
        exit;
    }
    
    // Check for SQL injection patterns in name
    $sqlPatterns = ['/\b(union|select|insert|update|delete|drop|create|alter|exec|execute|script|javascript|onerror|onload)\b/i'];
    foreach($sqlPatterns as $pattern){
        if(preg_match($pattern, $name)){
            http_response_code(400);
            echo json_encode(['error' => 'Invalid characters in name']);
            exit;
        }
    }
    
    // Validate phone if provided
    $phone = '';
    if($phoneInput !== ''){
        $phone = sanitizeCustomerPhone($phoneInput);
        if($phone === false){
            http_response_code(400);
            echo json_encode(['error' => 'Phone number must start with 09 and be exactly 11 digits']);
            exit;
        }
    }
    
    // Validate address length
    if(strlen($address) > 500){
        http_response_code(400);
        echo json_encode(['error' => 'Address must be 500 characters or less']);
        exit;
    }
    
    // Validate password if provided
    if ($password !== '') {
        if(strlen($password) < 6 || strlen($password) > 128){
            http_response_code(400);
            echo json_encode(['error' => 'Password must be between 6 and 128 characters']);
            exit;
        }
        
        if(!validatePassword($password)){
            http_response_code(400);
            echo json_encode(['error' => 'Password contains invalid characters']);
            exit;
        }
        
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE users SET name = ?, phone = ?, address = ?, password = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $phone, $address, $hash, $user['id']]);
    } else {
        $stmt = $db->prepare('UPDATE users SET name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $phone, $address, $user['id']]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>


