<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/InputValidator.php';

function auth_admin(PDO $db) {
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if ($token === null) { 
        http_response_code(401); 
        echo json_encode(['error'=>'Not authenticated']); 
        exit; 
    }
    
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if(!$user || $user['role']!=='admin'){ 
        http_response_code(401); 
        echo json_encode(['error'=>'Unauthorized']); 
        exit; 
    }
    
    return $user;
}

try {
    $db = (new Database())->getConnection();
    $admin = auth_admin($db);
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    $customerId = InputValidator::validateId($input['customer_id'] ?? 0);
    $action = $input['action'] ?? '';
    
    if ($customerId === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid customer ID required']);
        exit;
    }
    
    if (!in_array($action, ['verify', 'unverify'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Must be "verify" or "unverify"']);
        exit;
    }
    
    // Check if customer exists
    $stmt = $db->prepare("SELECT id, name, email_verified FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        http_response_code(404);
        echo json_encode(['error' => 'Customer not found']);
        exit;
    }
    
    // Update email verification status
    $newStatus = ($action === 'verify') ? 1 : 0;
    
    $stmt = $db->prepare("UPDATE users SET email_verified = ? WHERE id = ?");
    $result = $stmt->execute([$newStatus, $customerId]);
    
    if ($result) {
        $actionText = ($action === 'verify') ? 'verified' : 'unverified';
        echo json_encode([
            'success' => true, 
            'message' => "Customer {$customer['name']} has been {$actionText} successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update customer status']);
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
}
?>
