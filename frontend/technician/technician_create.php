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

if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

try{
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $shopUser = auth_shop($db);

    // Ensure the shop owner has a valid shop profile linked
    if(empty($shopUser['shop_id'])){
        http_response_code(400);
        echo json_encode(['error'=>'Shop profile not found. Please complete your shop profile before adding technicians.']);
        exit;
    }

    // Validate that the shop_owners record actually exists (defensive against inconsistent data)
    $chk = $db->prepare('SELECT id FROM shop_owners WHERE id=?');
    $chk->execute([$shopUser['shop_id']]);
    if(!$chk->fetch()){
        http_response_code(400);
        echo json_encode(['error'=>'Shop profile is invalid. Please refresh and try again.']);
        exit;
    }

    // Function to sanitize and validate input
    function sanitizeName($name) {
        // Remove special characters except spaces, hyphens, apostrophes, and periods
        // Allow letters, numbers, spaces, hyphens, apostrophes, and periods
        $name = trim($name);
        $name = preg_replace('/[^a-zA-Z0-9\s\-\'\.]/', '', $name);
        // Remove multiple consecutive spaces
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
    
    function sanitizeEmail($email) {
        // Email should only contain valid email characters
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return $email;
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

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    // Sanitize and validate inputs
    $name = sanitizeName($input['name'] ?? '');
    $email = sanitizeEmail($input['email'] ?? '');
    $phone = sanitizePhone($input['phone'] ?? '');
    $password = (string)($input['password'] ?? '');
    
    // Validation checks
    if($name === '' || strlen($name) < 2 || strlen($name) > 100){
        http_response_code(400); echo json_encode(['error'=>'Name must be between 2 and 100 characters']); exit;
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255){
        http_response_code(400); echo json_encode(['error'=>'Valid email is required (max 255 characters)']); exit;
    }
    
    if($phone === false || $phone === ''){
        http_response_code(400); echo json_encode(['error'=>'Phone number must start with 09 and be exactly 11 digits']); exit;
    }
    
    if($password === '' || strlen($password) < 6 || strlen($password) > 128){
        http_response_code(400); echo json_encode(['error'=>'Password must be between 6 and 128 characters']); exit;
    }
    
    if(!validatePassword($password)){
        http_response_code(400); echo json_encode(['error'=>'Password contains invalid characters']); exit;
    }
    
    // Additional security: Check for SQL injection patterns in name
    $sqlInjectionPatterns = ['/\b(union|select|insert|update|delete|drop|create|alter|exec|execute|script|javascript|onerror|onload)\b/i'];
    foreach($sqlInjectionPatterns as $pattern){
        if(preg_match($pattern, $name)){
            http_response_code(400); echo json_encode(['error'=>'Invalid characters in name']); exit;
        }
    }

    // Check duplicate email
    $exists = $db->prepare('SELECT id FROM users WHERE email=?');
    $exists->execute([$email]);
    if($exists->fetch()){ http_response_code(409); echo json_encode(['error'=>'Email already exists']); exit; }

    // Ensure technicians table exists (using existing structure)
    $db->exec("CREATE TABLE IF NOT EXISTS technicians (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        shop_id INT NOT NULL,
        skills TEXT,
        rating FLOAT DEFAULT 0,
        total_ratings INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (shop_id) REFERENCES shop_owners(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Create user and link to shop in a transaction
    $db->beginTransaction();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (name,email,phone,password,role,email_verified,status) VALUES (?,?,?,?, 'technician', FALSE, 'approved')");
    $stmt->execute([$name,$email,$phone,$hash]);
    $techUserId = (int)$db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO technicians (user_id, shop_id) VALUES (?, ?)");
    $stmt->execute([$techUserId, $shopUser['shop_id']]);

    $db->commit();
    echo json_encode(['success'=>true]);
}catch(PDOException $e){
    if(isset($db) && $db->inTransaction()){ $db->rollBack(); }
    // 23000 = integrity constraint violation (covers unique + FK). Try to refine message.
    if($e->getCode()==='23000'){
        $msg = $e->getMessage();
        if(stripos($msg, 'users') !== false && stripos($msg, 'email') !== false){
            http_response_code(409); echo json_encode(['error'=>'Email already exists']);
        } elseif (stripos($msg, 'foreign key') !== false && stripos($msg, 'shop_owners') !== false) {
            http_response_code(400); echo json_encode(['error'=>'Invalid shop profile. Please reload and try again.']);
        } else {
            http_response_code(400); echo json_encode(['error'=>'Invalid data','detail'=>$msg]);
        }
    } else {
        http_response_code(500); echo json_encode(['error'=>'Server error','detail'=>$e->getMessage()]);
    }
}catch(Exception $e){ if(isset($db) && $db->inTransaction()){ $db->rollBack(); } http_response_code(500); echo json_encode(['error'=>'Server error','detail'=>$e->getMessage()]); }
?>



