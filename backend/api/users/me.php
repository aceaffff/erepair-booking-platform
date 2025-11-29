<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../utils/InputValidator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get token from Authorization header
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
    }
} elseif (isset($_GET['token'])) {
    $token = $_GET['token'];
}

// Validate token
$token = InputValidator::validateToken($token);
if ($token === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Valid token required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify token and get user
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.role, u.email_verified, u.status, u.created_at,
               so.shop_name, so.shop_address, so.shop_phone, so.approval_status as shop_approval_status
        FROM users u
        LEFT JOIN shop_owners so ON u.id = so.user_id
        INNER JOIN sessions s ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }
    
    // Update session expiry (extend by 24 hours)
    $new_expires = date('Y-m-d H:i:s', time() + (24 * 60 * 60));
    $stmt = $db->prepare("UPDATE sessions SET expires_at = ? WHERE token = ?");
    $stmt->execute([$new_expires, $token]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
