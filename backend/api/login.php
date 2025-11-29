<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

require_once '../config/database.php';
require_once '../utils/InputValidator.php';
require_once '../middleware/security.php';

// Apply security middleware (relax/disable rate limiting on localhost for development)
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost','127.0.0.1'])
    || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1']);
applySecurityMiddleware([
    'rate_limit' => $isLocal ? false : true,
    'rate_limit_max' => $isLocal ? 1000 : 10,
    'rate_limit_window' => $isLocal ? 1 : 15
]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Validate JSON input
$input = InputValidator::validateJsonInput(file_get_contents('php://input'));
if ($input === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate and sanitize inputs with enhanced SQL injection prevention
$email = InputValidator::validateSecureEmail($input['email'] ?? '');
$password = InputValidator::validateSecurePassword($input['password'] ?? '');

if ($email === null || $password === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email or password format. Only letters, numbers, dots, hyphens, underscores, and @ symbol are allowed.']);
    exit;
}

// Additional security checks
if (strlen($input['email'] ?? '') > 254 || strlen($input['password'] ?? '') > 128) {
    http_response_code(400);
    echo json_encode(['error' => 'Input too long']);
    exit;
}

// Check for suspicious patterns in input
if (InputValidator::detectSqlInjection($input['email'] ?? '') || 
    InputValidator::detectSqlInjection($input['password'] ?? '')) {
    http_response_code(400);
    echo json_encode(['error' => 'Special characters that could be used for security attacks are not allowed. Please use only letters, numbers, dots, hyphens, underscores, and @ symbol for email.']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Log login attempt for security monitoring
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    error_log("Login attempt from IP: $clientIP, Email: $email, User-Agent: $userAgent");
    
    // Get user by email using prepared statement (already SQL injection safe)
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.password, u.role, u.email_verified, u.status,
               so.approval_status as shop_approval_status
        FROM users u
        LEFT JOIN shop_owners so ON u.id = so.user_id
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Log failed login attempt
        error_log("Failed login attempt - User not found: $email from IP: $clientIP");
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Log failed login attempt
        error_log("Failed login attempt - Invalid password for user: $email from IP: $clientIP");
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }
    
    // Check account status
    if ($user['status'] === 'rejected' || $user['status'] === 'deactivated') {
        http_response_code(403);
        echo json_encode(['error' => 'Your account is not active. Please contact the shop owner or support.']);
        exit;
    }
    
    if ($user['status'] === 'pending') {
        http_response_code(403);
        echo json_encode(['error' => 'Account pending admin approval']);
        exit;
    }
    
    // Check email verification for roles that require it (customers, shop owners)
    if (!$user['email_verified'] && in_array($user['role'], ['customer','shop_owner'], true)) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Please verify your email',
            'email_verified' => false,
            'email' => $user['email'],
            'redirect_to_verification' => true
        ]);
        exit;
    }
    
    // For shop owners, check shop approval status
    if ($user['role'] === 'shop_owner') {
        $shopApprovalStatus = $user['shop_approval_status'] ?? null;
        
        if ($shopApprovalStatus === 'pending' || $shopApprovalStatus === null) {
            http_response_code(403);
            echo json_encode(['error' => 'Your shop is pending admin approval. Please wait for admin approval before logging in.']);
            exit;
        }
        
        if ($shopApprovalStatus === 'rejected') {
            http_response_code(403);
            echo json_encode(['error' => 'Your shop application has been rejected. Please contact support for assistance.']);
            exit;
        }
    }
    
    // Generate secure random token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours from now
    
    // Clean up old sessions for this user
    $stmt = $db->prepare("DELETE FROM sessions WHERE user_id = ? OR expires_at < NOW()");
    $stmt->execute([$user['id']]);
    
    // Insert new session with additional security info
    $stmt = $db->prepare("INSERT INTO sessions (user_id, token, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user['id'], $token, $expires_at, $clientIP, $userAgent]);
    
    // Log successful login
    error_log("Successful login for user: $email (ID: {$user['id']}) from IP: $clientIP");
    
    // Remove password from response
    unset($user['password']);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'token' => $token,
        'role' => $user['role'],
        'user' => $user
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
