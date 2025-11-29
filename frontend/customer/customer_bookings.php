<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/InputValidator.php';

function auth_customer(PDO $db) {
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if ($token === null) { http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='customer'){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
    return $u;
}

try {
    $db = (new Database())->getConnection();
    $u = auth_customer($db);
    
    // Query bookings with technician details and diagnostic info
    $stmt = $db->prepare("SELECT b.id, b.device_type, b.device_issue_description, b.device_photo, b.device_description as description, 
                                 b.status, b.scheduled_at, b.total_price, b.notes,
                                 b.diagnostic_notes, b.estimated_cost, b.estimated_time_days,
                                 COALESCE(so.shop_name, rs.name) as shop_name, b.shop_id as shop_id,
                                 t.id AS technician_id, tech_user.name AS technician_name,
                                 CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as reviewed,
                                 (SELECT bh.notes FROM booking_history bh 
                                  WHERE bh.booking_id = b.id 
                                  AND (bh.new_status = 'cancelled_by_customer' OR bh.new_status = 'cancelled')
                                  ORDER BY bh.created_at DESC LIMIT 1) as cancellation_reason
                          FROM bookings b 
                          INNER JOIN repair_shops rs ON rs.id=b.shop_id
                          LEFT JOIN shop_owners so ON so.id=rs.owner_id
                          LEFT JOIN technicians t ON t.id=b.technician_id
                          LEFT JOIN users tech_user ON tech_user.id=t.user_id
                          LEFT JOIN reviews r ON r.booking_id = b.id
                          WHERE b.customer_id=? 
                          ORDER BY b.created_at DESC LIMIT 200");
    $stmt->execute([$u['id']]);
    $bookings = $stmt->fetchAll();
    
    // Transform the data to match expected format
    $transformedBookings = [];
    foreach($bookings as $booking) {
        // Handle NULL scheduled_at (for bookings awaiting schedule selection)
        $scheduledAt = null;
        $date = null;
        $timeSlot = null;
        
        if ($booking['scheduled_at']) {
            try {
                $scheduledAt = new DateTime($booking['scheduled_at']);
                $date = $scheduledAt->format('Y-m-d');
                $timeSlot = $scheduledAt->format('H:i');
            } catch (Exception $e) {
                // If date parsing fails, keep as null
                $scheduledAt = null;
            }
        }
        
        $notes = json_decode($booking['notes'] ?? '', true);
        $serviceName = is_array($notes) && isset($notes['service']) ? $notes['service'] : ($booking['notes'] ?? 'Service');
        $rebookOf = is_array($notes) && isset($notes['rebook_of']) ? $notes['rebook_of'] : null;
        $rescheduleStatus = is_array($notes) && isset($notes['reschedule_status']) ? $notes['reschedule_status'] : null;
        $rescheduleNewAt = is_array($notes) && isset($notes['reschedule_new_at']) ? $notes['reschedule_new_at'] : null;

        // Get rejection reason for rejected bookings
        $rejectionReason = null;
        if ($booking['status'] === 'rejected') {
            $rejectionStmt = $db->prepare("
                SELECT notes FROM booking_history 
                WHERE booking_id = ? AND new_status = 'rejected' 
                ORDER BY created_at DESC LIMIT 1
            ");
            $rejectionStmt->execute([$booking['id']]);
            $rejectionData = $rejectionStmt->fetch();
            if ($rejectionData && $rejectionData['notes']) {
                // Extract reason from "Booking rejected by shop. Reason: [reason]"
                if (preg_match('/Reason:\s*(.+)/', $rejectionData['notes'], $matches)) {
                    $rejectionReason = trim($matches[1]);
                }
            }
        }

        $transformedBookings[] = [
            'id' => $booking['id'],
            'service' => $serviceName ?: 'Service',
            'device_type' => $booking['device_type'] ?? '',
            'device_issue_description' => $booking['device_issue_description'] ?? '',
            'device_photo' => $booking['device_photo'] ?? null,
            'date' => $date,
            'time_slot' => $timeSlot,
            'description' => $booking['description'],
            'status' => $booking['status'],
            'shop_name' => $booking['shop_name'],
            'shop_id' => $booking['shop_id'],
            'price' => $booking['total_price'],
            'diagnostic_notes' => $booking['diagnostic_notes'] ?? null,
            'estimated_cost' => $booking['estimated_cost'] ?? null,
            'estimated_time_days' => $booking['estimated_time_days'] ?? null,
            'rebook_of' => $rebookOf,
            'reschedule_status' => $rescheduleStatus,
            'reschedule_new_at' => $rescheduleNewAt,
            'technician_id' => $booking['technician_id'] ?? null,
            'technician_name' => $booking['technician_name'] ?? null,
            'rejection_reason' => $rejectionReason,
            'cancellation_reason' => $booking['cancellation_reason'] ?? null,
            'reviewed' => (bool) $booking['reviewed']
        ];
    }
    
    echo json_encode(['success'=>true,'bookings'=>$transformedBookings]);
} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['error'=>'Server error', 'detail'=>$e->getMessage()]);
}
?>


