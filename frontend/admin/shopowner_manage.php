<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/NotificationHelper.php';

if($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405);
    echo json_encode(['error'=>'Method not allowed']);
    exit;
}

try{
    $db = (new Database())->getConnection();
    
    // Check if rejection_reason column exists, if not add it
    try {
        $checkColumn = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'rejection_reason'");
        if($checkColumn->rowCount() === 0) {
            // Column doesn't exist, add it
            $db->exec("ALTER TABLE shop_owners ADD COLUMN rejection_reason TEXT NULL AFTER approval_status");
        }
    } catch(Exception $e) {
        // If SHOW COLUMNS fails, try to add the column anyway (might already exist)
        try {
            $db->exec("ALTER TABLE shop_owners ADD COLUMN rejection_reason TEXT NULL");
        } catch(Exception $e2) {
            // Column might already exist, continue
        }
    }
    
    // Admin auth via session token cookie
    $token = $_COOKIE['auth_token'] ?? '';
    if($token===''){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id,u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $auth = $stmt->fetch();
    if(!$auth || $auth['role']!=='admin'){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $ownerId = (int)($input['owner_id'] ?? 0);
    $action = trim($input['action'] ?? ''); // approve | reject
    $rejectionReason = trim($input['rejection_reason'] ?? '');
    
    if($ownerId<=0 || ($action!=='approve' && $action!=='reject')){
        http_response_code(400);
        echo json_encode(['error'=>'Invalid request']);
        exit;
    }
    
    // Require rejection reason for reject action
    if($action === 'reject' && empty($rejectionReason)){
        http_response_code(400);
        echo json_encode(['error'=>'Rejection reason is required']);
        exit;
    }

    // find user_id from shop_owners
    $stmt = $db->prepare('SELECT user_id FROM shop_owners WHERE id=?');
    $stmt->execute([$ownerId]);
    $row = $stmt->fetch();
    if(!$row){ http_response_code(404); echo json_encode(['error'=>'Shop owner not found']); exit; }
    $userId = (int)$row['user_id'];

    if($action==='approve'){
        $db->prepare("UPDATE shop_owners SET approval_status='approved', rejection_reason=NULL WHERE id=?")->execute([$ownerId]);
        $db->prepare("UPDATE users SET status='approved' WHERE id=?")->execute([$userId]);
        
        // Get shop name, owner email, and owner name for notification
        $shopStmt = $db->prepare('SELECT shop_name FROM shop_owners WHERE id=?');
        $shopStmt->execute([$ownerId]);
        $shopData = $shopStmt->fetch();
        
        // Get owner email and name
        $userStmt = $db->prepare('SELECT email, name FROM users WHERE id=?');
        $userStmt->execute([$userId]);
        $userData = $userStmt->fetch();
        
        if($shopData && $userData) {
            // Send notification
            NotificationHelper::notifyShopApproval($db, $userId, $shopData['shop_name'], true);
            
            // Send email notification
            require_once __DIR__ . '/../../backend/config/email.php';
            $emailService = new EmailService();
            if($emailService->testEmailConnection()) {
                $emailService->sendShopOwnerApprovalEmail(
                    $userData['email'],
                    $userData['name'],
                    $shopData['shop_name'],
                    true
                );
            }
        }
        
        echo json_encode(['success'=>true,'message'=>'Shop owner approved']);
    } else {
        $db->prepare("UPDATE shop_owners SET approval_status='rejected', rejection_reason=? WHERE id=?")->execute([$rejectionReason, $ownerId]);
        $db->prepare("UPDATE users SET status='rejected' WHERE id=?")->execute([$userId]);
        
        // Get shop name, owner email, and owner name for notification
        $shopStmt = $db->prepare('SELECT shop_name FROM shop_owners WHERE id=?');
        $shopStmt->execute([$ownerId]);
        $shopData = $shopStmt->fetch();
        
        // Get owner email and name
        $userStmt = $db->prepare('SELECT email, name FROM users WHERE id=?');
        $userStmt->execute([$userId]);
        $userData = $userStmt->fetch();
        
        if($shopData && $userData) {
            // Send notification
            NotificationHelper::notifyShopApproval($db, $userId, $shopData['shop_name'], false, $rejectionReason);
            
            // Send email with rejection reason
            require_once __DIR__ . '/../../backend/config/email.php';
            $emailService = new EmailService();
            if($emailService->testEmailConnection()) {
                $emailService->sendShopOwnerApprovalEmail(
                    $userData['email'],
                    $userData['name'],
                    $shopData['shop_name'],
                    false,
                    $rejectionReason
                );
            }
        }
        
        echo json_encode(['success'=>true,'message'=>'Shop owner rejected']);
    }
}catch(Exception $e){
    http_response_code(500);
    error_log('Shop owner manage error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['error'=>'Server error: ' . $e->getMessage()]);
}
?>


