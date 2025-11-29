<?php
require_once __DIR__ . '/../../backend/utils/ResponseHelper.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/email.php';
require_once __DIR__ . '/../../backend/utils/NotificationHelper.php';
require_once __DIR__ . '/../../backend/utils/DBTransaction.php';

// Initialize API environment
ResponseHelper::initApi();

function auth_technician(PDO $db){
    $token = $_COOKIE['auth_token'] ?? '';
    if(!$token){ ResponseHelper::unauthorized('Not authenticated'); }
    $stmt = $db->prepare("SELECT u.id, u.role, t.id as tech_id FROM users u INNER JOIN sessions s ON s.user_id=u.id INNER JOIN technicians t ON t.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='technician' || empty($u['tech_id'])){ ResponseHelper::unauthorized('Unauthorized - Invalid technician profile'); }
    return $u;
}

if($_SERVER['REQUEST_METHOD']!=='POST'){ ResponseHelper::methodNotAllowed(); }

try{
    $db = (new Database())->getConnection();
    $tech = auth_technician($db);
    
    // Debug logging
    error_log("Technician update request - Tech ID: " . ($tech['tech_id'] ?? 'null'));
    
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $bookingId = (int)($input['booking_id'] ?? 0);
    $status = trim($input['status'] ?? ''); // in_progress, completed
    
    error_log("Booking update request - Booking ID: $bookingId, Status: '$status'");
    
    if($bookingId<=0 || !in_array($status, ['in_progress','completed'], true)){
        error_log("Invalid request - bookingId: $bookingId, status: '$status'");
        ResponseHelper::validationError('Invalid request'); }

    // Check if technician has a valid tech_id
    if(empty($tech['tech_id'])) {
        error_log("Technician has no tech_id");
        ResponseHelper::error('Technician profile not found', 400);
    }

    // verify booking is assigned to this tech
    $chk = $db->prepare('SELECT id, status FROM bookings WHERE id=? AND technician_id=?');
    $chk->execute([$bookingId, $tech['tech_id']]);
    $booking = $chk->fetch();
    
    if(!$booking) { 
        error_log("Booking not found for technician - Booking ID: $bookingId, Tech ID: " . $tech['tech_id']);
        ResponseHelper::notFound('Booking not found or not assigned to you'); 
    }
    
    error_log("Booking found - Current status: " . $booking['status']);

    $result = $db->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$status, $bookingId]);
    error_log("Update result: " . ($result ? 'success' : 'failed'));
    
    // Send completion email if status is 'completed'
    if($status === 'completed') {
        try {
            // Get booking details for email
            $bookingStmt = $db->prepare("
                SELECT b.*, u.name as customer_name, u.email as customer_email, 
                       rs.name as shop_name, tech_user.name as technician_name
                FROM bookings b
                INNER JOIN users u ON b.customer_id = u.id
                INNER JOIN repair_shops rs ON b.shop_id = rs.id
                LEFT JOIN technicians t ON b.technician_id = t.id
                LEFT JOIN users tech_user ON tech_user.id = t.user_id
                WHERE b.id = ?
            ");
            $bookingStmt->execute([$bookingId]);
            $booking = $bookingStmt->fetch();
            
            if($booking) {
                $emailService = new EmailService();
                $completionDate = date('F j, Y \a\t g:i A');
                
                $emailSent = $emailService->sendRepairCompletionEmail(
                    $booking['customer_email'],
                    $booking['customer_name'],
                    $booking['shop_name'],
                    $booking['device_description'],
                    $completionDate,
                    $booking['technician_name']
                );
                
                if($emailSent) {
                    error_log("Repair completion email sent successfully to: " . $booking['customer_email']);
                } else {
                    error_log("Failed to send repair completion email to: " . $booking['customer_email']);
                }
                
                // Create in-app notification
                NotificationHelper::notifyBookingCompleted(
                    $db,
                    (int)$booking['customer_id'],
                    $booking['shop_name'],
                    $booking['device_description']
                );
            }
        } catch (Exception $e) {
            error_log("Error sending repair completion email: " . $e->getMessage());
        }
    }
    
    ResponseHelper::success('Job status updated successfully');
}catch(Exception $e){ 
    error_log("Booking update error: " . $e->getMessage());
    // Ensure any active transaction is rolled back
    if (isset($db)) {
        DBTransaction::cleanup($db);
    }
    ResponseHelper::serverError('Server error', $e->getMessage()); 
}
?>



