<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

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
    
    $token = $_COOKIE['auth_token'] ?? ($_GET['token'] ?? '');
    if($token===''){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='admin'){ http_response_code(403); echo json_encode(['error'=>'Unauthorized']); exit; }

    // Dashboard aggregates
    $stats = [
        'totalCustomers' => (int)$db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),
        'totalShopOwners' => (int)$db->query("SELECT COUNT(*) FROM shop_owners")->fetchColumn(),
        'shopOwnersPending' => (int)$db->query("SELECT COUNT(*) FROM shop_owners WHERE approval_status='pending'")->fetchColumn(),
        'shopOwnersApproved' => (int)$db->query("SELECT COUNT(*) FROM shop_owners WHERE approval_status='approved'")->fetchColumn(),
        'shopOwnersRejected' => (int)$db->query("SELECT COUNT(*) FROM shop_owners WHERE approval_status='rejected'")->fetchColumn(),
        'totalTechnicians' => (int)$db->query("SELECT COUNT(*) FROM technicians")->fetchColumn(),
        'totalBookings' => (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn()
    ];

    $reports = [
        'bookings' => [
            'pending' => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),
            'approved' => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status IN ('approved','assigned','in_progress')")->fetchColumn(),
            'completed' => (int)$db->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn()
        ],
        'techsPerShop' => [],
        'monthlyTrends' => [],
        'totalRevenue' => 0,
        'topShops' => [],
        'revenueTrends' => []
    ];

    // Owners list for table (basic):
    $ownersStmt = $db->query("SELECT so.id, so.shop_name, so.shop_address, u.name AS owner_name, u.email, so.approval_status, so.rejection_reason FROM shop_owners so INNER JOIN users u ON u.id=so.user_id ORDER BY so.created_at DESC LIMIT 100");
    $owners = $ownersStmt->fetchAll();

    echo json_encode(['success'=>true,'stats'=>$stats,'reports'=>$reports,'owners'=>$owners]);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'Server error','detail'=>$e->getMessage()]); }
