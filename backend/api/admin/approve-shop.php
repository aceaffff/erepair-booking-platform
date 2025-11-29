<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/database.php';
require_once '../../config/email.php';
require_once '../../utils/DBTransaction.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get token from Authorization header
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Token required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$shopId = $input['shop_id'] ?? null;
$action = $input['action'] ?? null; // 'approve' or 'reject'
$rejectionReason = trim($input['rejection_reason'] ?? '');

if (!$shopId || !$action || !in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request. shop_id and action (approve/reject) are required']);
    exit;
}

// Require rejection reason for reject action
if ($action === 'reject' && empty($rejectionReason)) {
    http_response_code(400);
    echo json_encode(['error' => 'Rejection reason is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify admin token
    $stmt = $db->prepare("
        SELECT u.id, u.role FROM users u
        INNER JOIN sessions s ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW() AND u.role = 'admin'
    ");
    $stmt->execute([$token]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
    
    // Get shop owner details
    $stmt = $db->prepare("
        SELECT so.id, so.user_id, so.shop_name, u.name, u.email
        FROM shop_owners so
        INNER JOIN users u ON so.user_id = u.id
        WHERE so.id = ? AND so.approval_status = 'pending'
    ");
    $stmt->execute([$shopId]);
    $shopOwner = $stmt->fetch();
    
    if (!$shopOwner) {
        http_response_code(404);
        echo json_encode(['error' => 'Shop owner not found or already processed']);
        exit;
    }
    
    DBTransaction::execute($db, function($pdo) use ($action, $shopId, $shopOwner) {
        if ($action === 'approve') {
            // Update shop owner approval status and clear rejection reason
            $stmt = $pdo->prepare("UPDATE shop_owners SET approval_status = 'approved', rejection_reason = NULL WHERE id = ?");
            $stmt->execute([$shopId]);
            
            // Update user status to approved
            $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
            $stmt->execute([$shopOwner['user_id']]);
        } else {
            // Update shop owner approval status and save rejection reason
            $stmt = $pdo->prepare("UPDATE shop_owners SET approval_status = 'rejected', rejection_reason = ? WHERE id = ?");
            $stmt->execute([$rejectionReason, $shopId]);
            
            // Update user status to rejected
            $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$shopOwner['user_id']]);
        }
    });
    
    $message = $action === 'approve' ? 'Shop owner approved successfully' : 'Shop owner rejected successfully';
    
    // Send email notification
    $emailService = new EmailService();
    $emailSent = $emailService->sendShopOwnerApprovalEmail(
        $shopOwner['email'], 
        $shopOwner['name'], 
        $shopOwner['shop_name'], 
        $action === 'approve',
        $rejectionReason
    );
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'email_sent' => $emailSent
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        DBTransaction::cleanup($db);
    }
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
