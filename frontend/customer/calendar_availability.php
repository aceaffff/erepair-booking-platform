<?php
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
    
    $shopId = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    
    if($shopId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid shop ID']);
        exit;
    }
    
    // Use existing bookings table structure
    
    // Define available time slots (8 AM to 4 PM, 1-hour slots)
    $timeSlots = [
        '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'
    ];
    
    // Get all days in the month
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $availability = [];
    $now = new DateTime('now');
    
    for($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $dayAvailability = [];
        
        foreach($timeSlots as $slot) {
            // Check if this time slot is already booked using existing table structure
            // First get the repair_shop_id from shop_owners
            $shopQuery = $db->prepare("
                SELECT rs.id as repair_shop_id 
                FROM repair_shops rs 
                WHERE rs.owner_id = ?
            ");
            $shopQuery->execute([$shopId]);
            $repairShop = $shopQuery->fetch();
            
            if($repairShop) {
                $stmt = $db->prepare("
                    SELECT COUNT(*) as count 
                    FROM bookings 
                    WHERE shop_id = ? 
                    AND scheduled_at IS NOT NULL
                    AND DATE(scheduled_at) = ? 
                    AND TIME(scheduled_at) = ?
                    AND status IN ('pending_review','awaiting_customer_confirmation','confirmed_by_customer','approved','assigned','in_progress')
                ");
                $stmt->execute([$repairShop['repair_shop_id'], $date, $slot . ':00']);
                $result = $stmt->fetch();
                
                $isAvailable = $result['count'] == 0;
                $today = $now->format('Y-m-d');
                $tomorrow = (new DateTime('+1 day'))->format('Y-m-d');
                
                // Disable same-day bookings (must be at least one day in advance)
                if ($date <= $today) { 
                    $isAvailable = false; 
                } else if ($date === $tomorrow) {
                    // For tomorrow, check if time slot has passed
                    $slotDt = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $slot . ':00');
                    if ($slotDt && $slotDt <= $now) { $isAvailable = false; }
                }
                $dayAvailability[$slot] = [
                    'available' => $isAvailable,
                    'booked_count' => (int)$result['count']
                ];
            } else {
                // If no repair_shop exists, all slots are available
                $isAvailable = true;
                $today = $now->format('Y-m-d');
                $tomorrow = (new DateTime('+1 day'))->format('Y-m-d');
                
                // Disable same-day bookings (must be at least one day in advance)
                if ($date <= $today) { 
                    $isAvailable = false; 
                } else if ($date === $tomorrow) {
                    // For tomorrow, check if time slot has passed
                    $slotDt = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $slot . ':00');
                    if ($slotDt && $slotDt <= $now) { $isAvailable = false; }
                }
                $dayAvailability[$slot] = [
                    'available' => $isAvailable,
                    'booked_count' => 0
                ];
            }
        }
        
        $availability[$date] = $dayAvailability;
    }
    
    echo json_encode([
        'success' => true,
        'shop_id' => $shopId,
        'month' => $month,
        'year' => $year,
        'time_slots' => $timeSlots,
        'availability' => $availability
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
}
?>
