<?php
/**
 * Get available time slots for a specific date and shop
 * Used when customer confirms booking to prevent double bookings
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

function auth_customer(PDO $db) {
    $token = $_COOKIE['auth_token'] ?? '';
    if (!$token) { http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='customer'){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
    return $u;
}

try {
    $db = (new Database())->getConnection();
    $user = auth_customer($db);
    
    $shopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0; // This is repair_shop.id
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0; // Exclude current booking from check
    
    if($shopId <= 0 || empty($date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid shop ID or date']);
        exit;
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format']);
        exit;
    }
    
    // Define available time slots (8 AM to 4 PM, 1-hour slots)
    $timeSlots = [
        '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'
    ];
    
    $now = new DateTime('now');
    $today = $now->format('Y-m-d');
    $tomorrow = (new DateTime('+1 day'))->format('Y-m-d');
    
    // Check if date is valid (at least one day in advance)
    $selectedDate = new DateTime($date);
    $minDate = new DateTime('+1 day');
    $minDate->setTime(0, 0, 0);
    $selectedDate->setTime(0, 0, 0);
    
    if ($selectedDate < $minDate) {
        http_response_code(400);
        echo json_encode(['error' => 'Date must be at least one day in advance']);
        exit;
    }
    
    $allSlots = [];
    
    foreach($timeSlots as $slot) {
        // Check if this time slot is already booked
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE shop_id = ? 
            AND scheduled_at IS NOT NULL
            AND DATE(scheduled_at) = ? 
            AND TIME(scheduled_at) = ?
            AND status IN ('pending_review','awaiting_customer_confirmation','confirmed_by_customer','approved','assigned','in_progress')
            AND id != ?
        ");
        $stmt->execute([$shopId, $date, $slot . ':00', $bookingId]);
        $result = $stmt->fetch();
        
        $isAvailable = $result['count'] == 0;
        
        // Additional validation: check if time slot has passed for tomorrow
        if ($date === $tomorrow) {
            $slotDt = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $slot . ':00');
            if ($slotDt && $slotDt <= $now) { 
                $isAvailable = false; 
            }
        }
        
        $allSlots[] = [
            'time' => $slot,
            'available' => $isAvailable
        ];
    }
    
    echo json_encode([
        'success' => true,
        'shop_id' => $shopId,
        'date' => $date,
        'available_slots' => $allSlots
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
}
?>

