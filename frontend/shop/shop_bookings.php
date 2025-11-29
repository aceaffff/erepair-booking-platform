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
    if(empty($u['shop_id'])){ http_response_code(400); echo json_encode(['error'=>'Shop profile not found']); exit; }
    return $u;
}

try{
    $db = (new Database())->getConnection();
    $shop = auth_shop($db);

    // Get the repair_shop_id that corresponds to this shop_owner
    // $shop['shop_id'] is the shop_owners table ID, which should match repair_shops.owner_id
    $repairShopStmt = $db->prepare('SELECT id FROM repair_shops WHERE owner_id = ?');
    $repairShopStmt->execute([$shop['shop_id']]);
    $repairShops = $repairShopStmt->fetchAll();
    
    if(empty($repairShops)) {
        // No repair_shop exists yet, create one automatically
        $shopOwnerStmt = $db->prepare('SELECT shop_name, shop_address, shop_phone FROM shop_owners WHERE user_id = ?');
        $shopOwnerStmt->execute([$shop['id']]);
        $shopOwnerData = $shopOwnerStmt->fetch();
        
        if ($shopOwnerData) {
            $createShop = $db->prepare('INSERT INTO repair_shops (name, address, phone, owner_id) VALUES (?, ?, ?, ?)');
            $createShop->execute([
                $shopOwnerData['shop_name'] ?? 'Shop',
                $shopOwnerData['shop_address'] ?? '',
                $shopOwnerData['shop_phone'] ?? '',
                $shop['shop_id']
            ]);
            $repairShopIds = [$db->lastInsertId()];
        } else {
            echo json_encode(['success'=>true, 'bookings'=>[]]);
            exit;
        }
    } else {
        $repairShopIds = array_column($repairShops, 'id');
    }

    // Query bookings with diagnostic info and cancellation reason
    $placeholders = str_repeat('?,', count($repairShopIds) - 1) . '?';
    $stmt = $db->prepare("SELECT b.id, b.device_type, b.device_issue_description, b.device_photo,
                                 b.device_description as description, b.status, b.scheduled_at, b.total_price, b.notes,
                                 b.diagnostic_notes, b.estimated_cost, b.estimated_time_days,
                                 u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                                 t.id AS technician_id, tech_user.name AS technician_name,
                                 (SELECT bh.notes FROM booking_history bh 
                                  WHERE bh.booking_id = b.id 
                                  AND (bh.new_status = 'cancelled_by_customer' OR bh.new_status = 'cancelled')
                                  ORDER BY bh.created_at DESC LIMIT 1) as cancellation_reason
                          FROM bookings b 
                          INNER JOIN users u ON u.id=b.customer_id
                          LEFT JOIN technicians t ON t.id=b.technician_id
                          LEFT JOIN users tech_user ON tech_user.id=t.user_id
                          WHERE b.shop_id IN ($placeholders)
                          ORDER BY b.scheduled_at ASC, b.created_at DESC");
    $stmt->execute($repairShopIds);
    $bookings = $stmt->fetchAll();

    // Transform the data to match expected format
    $transformedBookings = [];
    foreach($bookings as $booking) {
        $scheduledAt = new DateTime($booking['scheduled_at']);
        $notes = json_decode($booking['notes'] ?? '', true);
        $serviceName = is_array($notes) && isset($notes['service']) ? $notes['service'] : ($booking['notes'] ?? 'Service');
        $rebookOf = is_array($notes) && isset($notes['rebook_of']) ? $notes['rebook_of'] : null;
        $rescheduleRequest = is_array($notes) && !empty($notes['reschedule_request']);
        $rescheduleNewAt = is_array($notes) && !empty($notes['reschedule_new_at']) ? $notes['reschedule_new_at'] : null;

        $transformedBookings[] = [
            'id' => $booking['id'],
            'service' => $serviceName ?: 'Service',
            'device_type' => $booking['device_type'] ?? '',
            'device_issue_description' => $booking['device_issue_description'] ?? '',
            'device_photo' => $booking['device_photo'] ?? null,
            'date' => $scheduledAt->format('Y-m-d'),
            'time_slot' => $scheduledAt->format('H:i'),
            'description' => $booking['description'],
            'status' => $booking['status'],
            'created_at' => $booking['scheduled_at'],
            'customer_name' => $booking['customer_name'],
            'customer_email' => $booking['customer_email'],
            'customer_phone' => $booking['customer_phone'],
            'technician_id' => $booking['technician_id'],
            'technician_name' => $booking['technician_name'],
            'price' => $booking['total_price'],
            'diagnostic_notes' => $booking['diagnostic_notes'] ?? null,
            'estimated_cost' => $booking['estimated_cost'] ?? null,
            'estimated_time_days' => $booking['estimated_time_days'] ?? null,
            'rebook_of' => $rebookOf,
            'reschedule_request' => $rescheduleRequest,
            'reschedule_new_at' => $rescheduleNewAt,
            'cancellation_reason' => $booking['cancellation_reason'] ?? null
        ];
    }

    echo json_encode(['success'=>true, 'bookings'=>$transformedBookings]);
}catch(Exception $e){ 
    http_response_code(500); 
    echo json_encode(['error'=>'Server error', 'detail'=>$e->getMessage()]); 
}
?>
