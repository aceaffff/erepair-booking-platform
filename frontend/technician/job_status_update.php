<?php
require_once __DIR__ . '/../../backend/utils/ResponseHelper.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/DBTransaction.php';
require_once __DIR__ . '/../../backend/utils/NotificationHelper.php';

// Initialize API environment
ResponseHelper::initApi();

function auth_technician(PDO $db){
    $token = $_COOKIE['auth_token'] ?? '';
    if(!$token){ ResponseHelper::unauthorized('Not authenticated'); }
    
    $stmt = $db->prepare("SELECT u.id, u.role, u.name, t.id as tech_id FROM users u INNER JOIN sessions s ON s.user_id=u.id INNER JOIN technicians t ON t.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    
    if(!$u || $u['role']!=='technician' || empty($u['tech_id'])){ 
        ResponseHelper::unauthorized('Unauthorized - Invalid technician profile'); 
    }
    return $u;
}

if($_SERVER['REQUEST_METHOD']!=='POST'){ 
    ResponseHelper::methodNotAllowed(); 
}

try{
    $db = (new Database())->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tech = auth_technician($db);

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $bookingId = (int)($input['booking_id'] ?? 0);
    $newStatus = trim($input['status'] ?? '');

    if($bookingId <= 0 || $newStatus === ''){
        ResponseHelper::validationError('booking_id and status are required'); 
    }

    // Validate status
    $allowedStatuses = ['in_progress', 'completed'];
    if(!in_array($newStatus, $allowedStatuses)){
        ResponseHelper::error('Technicians can only update status to in_progress or completed', 400); 
    }

    // Verify the booking is assigned to this technician and get full details
    $stmt = $db->prepare("SELECT b.id, b.status, b.customer_id, b.device_description, b.notes, 
                                 u.email as customer_email, u.name as customer_name,
                                 rs.name as shop_name,
                                 tech_user.name as technician_name
                          FROM bookings b 
                          INNER JOIN users u ON u.id = b.customer_id
                          INNER JOIN repair_shops rs ON rs.id = b.shop_id
                          INNER JOIN technicians t ON t.id = b.technician_id
                          INNER JOIN users tech_user ON tech_user.id = t.user_id
                          WHERE b.id=? AND b.technician_id=?");
    $stmt->execute([$bookingId, $tech['tech_id']]);
    $booking = $stmt->fetch();
    
    if(!$booking){
        ResponseHelper::notFound('Booking not found or not assigned to you'); 
    }
    
    // Get service name for notifications
    $serviceName = $booking['device_description'];
    if (!empty($booking['notes'])) {
        $notesData = json_decode($booking['notes'], true);
        if (is_array($notesData) && isset($notesData['service'])) {
            $serviceName = $notesData['service'];
        }
    }

    // Validate status transitions
    $currentStatus = $booking['status'];
    if($newStatus === 'in_progress' && $currentStatus !== 'assigned'){
        ResponseHelper::error('Job must be "assigned" to start work', 400);
    }
    if($newStatus === 'completed' && $currentStatus !== 'in_progress'){
        ResponseHelper::error('Job must be "in_progress" to mark as complete', 400);
    }

    // Execute transaction with safe handling
    DBTransaction::execute($db, function($pdo) use ($newStatus, $bookingId, $booking, $serviceName) {
        // Update the booking status
        $updateStmt = $pdo->prepare("UPDATE bookings SET status=?, updated_at=NOW() WHERE id=?");
        $updateStmt->execute([$newStatus, $bookingId]);

        // Create notification for customer
        try {
            if($newStatus === 'completed'){
                NotificationHelper::notifyBookingCompleted(
                    $pdo,
                    $booking['customer_id'],
                    $booking['shop_name'],
                    $serviceName,
                    $bookingId
                );
            } elseif($newStatus === 'in_progress'){
                NotificationHelper::notifyBookingInProgress(
                    $pdo,
                    $booking['customer_id'],
                    $booking['shop_name'],
                    $serviceName,
                    $bookingId
                );
            }
        } catch(Exception $e) {
            // Log notification error but don't fail the request
            error_log("Notification creation failed for booking " . $bookingId . ": " . $e->getMessage());
        }

        // Send email notification to customer
        try {
            require_once __DIR__ . '/../../backend/config/email.php';
            $emailService = new EmailService();
            
            // Test email service connection first
            if (!$emailService->testEmailConnection()) {
                error_log("Email service connection failed, skipping email notification for booking: " . $bookingId);
            } else {
                $currentDate = date('F j, Y \a\t g:i A');
                
                if($newStatus === 'completed'){
                    $emailSent = $emailService->sendRepairCompletionEmail(
                        $booking['customer_email'],
                        $booking['customer_name'],
                        $booking['shop_name'],
                        $serviceName,
                        $currentDate,
                        $booking['technician_name']
                    );
                    
                    if($emailSent) {
                        error_log("Repair completion email sent successfully to: " . $booking['customer_email']);
                    } else {
                        error_log("Failed to send repair completion email to: " . $booking['customer_email']);
                    }
                } elseif($newStatus === 'in_progress'){
                    $emailSent = $emailService->sendRepairStartedEmail(
                        $booking['customer_email'],
                        $booking['customer_name'],
                        $booking['shop_name'],
                        $serviceName,
                        $currentDate,
                        $booking['technician_name']
                    );
                    
                    if($emailSent) {
                        error_log("Repair started email sent successfully to: " . $booking['customer_email']);
                    } else {
                        error_log("Failed to send repair started email to: " . $booking['customer_email']);
                    }
                }
            }
        } catch(Exception $e) {
            // Log email error but don't fail the request
            error_log("Email notification failed for booking " . $bookingId . ": " . $e->getMessage());
        }
    });

    ResponseHelper::success('Job status updated successfully');
}catch(Exception $e){ 
    // Ensure any active transaction is rolled back
    if (isset($db)) {
        DBTransaction::cleanup($db);
    }
    ResponseHelper::serverError('Server error', $e->getMessage()); 
}
?>

