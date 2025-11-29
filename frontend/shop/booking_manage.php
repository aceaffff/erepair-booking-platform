<?php
/**
 * Enhanced Shop Booking Management
 * Handles: diagnosis, quotation, approval, assignment, status updates, reschedule
 */
require_once __DIR__ . '/../../backend/utils/ResponseHelper.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/DBTransaction.php';
require_once __DIR__ . '/../../backend/utils/NotificationHelper.php';

// Initialize API environment
ResponseHelper::initApi();

// EmailService - optional, will be created if needed
if(file_exists(__DIR__ . '/../../backend/services/EmailService.php')) {
    require_once __DIR__ . '/../../backend/services/EmailService.php';
}

function auth_shop(PDO $db){
    $token = $_COOKIE['auth_token'] ?? '';
    if(!$token){ ResponseHelper::unauthorized('Not authenticated'); }
    $stmt = $db->prepare("SELECT u.id as user_id, u.role, so.id as shop_id FROM users u INNER JOIN sessions s ON s.user_id=u.id LEFT JOIN shop_owners so ON so.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='shop_owner'){ ResponseHelper::unauthorized('Unauthorized'); }
    if(empty($u['shop_id'])){ ResponseHelper::error('Shop profile not found', 400); }
    return $u;
}

if($_SERVER['REQUEST_METHOD']!=='POST'){
    ResponseHelper::methodNotAllowed();
}

try{
    $db = (new Database())->getConnection();
    
    // Ensure we start with a clean transaction state
    if ($db->inTransaction()) {
        error_log("Warning: Starting with active transaction, rolling back");
        DBTransaction::cleanup($db);
    }
    
    $shop = auth_shop($db);
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $bookingId = (int)($input['booking_id'] ?? 0);
    $action = trim($input['action'] ?? ''); 
    // Actions: diagnose, approve, reject, cancel, assign, status, reschedule_accept, reschedule_decline
    
    error_log("Booking manage request: " . json_encode($input));
    
    if($bookingId<=0 || $action===''){ 
        ResponseHelper::error('Invalid request', 400); 
    }

    // Get the repair_shop_id
    $repairShopStmt = $db->prepare('SELECT id FROM repair_shops WHERE owner_id = ?');
    $repairShopStmt->execute([$shop['shop_id']]);
    $repairShop = $repairShopStmt->fetch();
    
    if(!$repairShop) {
        ResponseHelper::notFound('Shop not found'); 
    }

    // Verify booking belongs to this shop
    $chk = $db->prepare('SELECT id FROM bookings WHERE id=? AND shop_id=?');
    $chk->execute([$bookingId, $repairShop['id']]);
    if(!$chk->fetch()){ 
        ResponseHelper::notFound('Booking not found'); 
    }

    // Handle different actions
    if($action === 'diagnose'){
        // Shop provides diagnosis and quotation
        $diagnosticNotes = trim($input['diagnostic_notes'] ?? '');
        $estimatedCost = floatval($input['estimated_cost'] ?? 0);
        $estimatedTimeDays = floatval($input['estimated_time_days'] ?? 0);
        
        if($diagnosticNotes === '' || $estimatedCost <= 0 || $estimatedTimeDays <= 0){
            ResponseHelper::validationError('Diagnostic notes, estimated cost, and time are required');
        }
        
        // Additional security: Remove dangerous characters that could be used for SQL injection or XSS
        // Block: < > { } [ ] ( ) ; ' " ` \ / | & * % $ # @ ~ ^
        $diagnosticNotes = preg_replace('/[<>{}[\]();\'"`\\\\\/|&*%$#@~^]/', '', $diagnosticNotes);
        
        // Re-validate after sanitization
        if(trim($diagnosticNotes) === ''){
            ResponseHelper::validationError('Diagnostic notes cannot be empty after sanitization');
        }
        
        // Get booking details
        $bookingStmt = $db->prepare("
            SELECT b.*, u.name as customer_name, u.id as customer_id,
                   rs.name as shop_name
            FROM bookings b
            INNER JOIN users u ON u.id = b.customer_id
            INNER JOIN repair_shops rs ON rs.id = b.shop_id
            WHERE b.id = ?
        ");
        $bookingStmt->execute([$bookingId]);
        $booking = $bookingStmt->fetch();
        
        if(!$booking){
            ResponseHelper::notFound('Booking not found');
        }
        
        if($booking['status'] !== 'pending_review'){
            ResponseHelper::error('Only pending_review bookings can be diagnosed', 400);
        }
        
        DBTransaction::execute($db, function($pdo) use ($diagnosticNotes, $estimatedCost, $estimatedTimeDays, $bookingId, $shop, $booking) {
            // Update booking with diagnosis
            $updateStmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'awaiting_customer_confirmation',
                    diagnostic_notes = ?,
                    estimated_cost = ?,
                    estimated_time_days = ?,
                    total_price = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([
                $diagnosticNotes,
                $estimatedCost,
                $estimatedTimeDays,
                $estimatedCost, // Update total_price with estimated cost
                $bookingId
            ]);
            
            // Log to history
            try {
                // Ensure booking_history table exists
                $pdo->exec("CREATE TABLE IF NOT EXISTS booking_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    booking_id INT NOT NULL,
                    old_status VARCHAR(50),
                    new_status VARCHAR(50) NOT NULL,
                    changed_by INT,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
                    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB");
                
                $historyStmt = $pdo->prepare("
                    INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $historyStmt->execute([
                    $bookingId,
                    'pending_review',
                    'awaiting_customer_confirmation',
                    $shop['user_id'], // Use user_id instead of shop_id
                    "Diagnosis completed: " . $diagnosticNotes
                ]);
            } catch (Exception $e) {
                error_log("Failed to log booking history: " . $e->getMessage());
                // Don't fail the transaction for history logging errors
            }
            
            // Notify customer
            try {
                NotificationHelper::notifyBookingDiagnosed(
                    $pdo,
                    (int)$booking['customer_id'],
                    $booking['shop_name'],
                    $estimatedCost,
                    $estimatedTimeDays
                );
            } catch (Exception $e) {
                error_log("Failed to send diagnosis notification: " . $e->getMessage());
                // Don't fail the transaction for notification errors
            }
            
            // Send email to customer about diagnosis
            try {
                require_once __DIR__ . '/../../backend/config/email.php';
                $emailService = new EmailService();
                
                // Test email service first
                if (!$emailService->testEmailConnection()) {
                    error_log("Email service connection failed, skipping diagnosis email notification");
                } else {
                    // Get customer email
                    $customerStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                    $customerStmt->execute([$booking['customer_id']]);
                    $customer = $customerStmt->fetch();
                    
                    if ($customer) {
                        $scheduledAt = new DateTime($booking['scheduled_at']);
                        $bookingDate = $scheduledAt->format('F j, Y');
                        $bookingTime = $scheduledAt->format('g:i A');
                        
                        $notes = json_decode($booking['notes'] ?? '', true);
                        $serviceName = is_array($notes) && isset($notes['service']) ? $notes['service'] : $booking['device_description'];
                        
                        $emailSent = $emailService->sendBookingDiagnosisEmail(
                            $customer['email'],
                            $booking['customer_name'],
                            $booking['shop_name'],
                            $serviceName,
                            $bookingDate,
                            $bookingTime,
                            $diagnosticNotes,
                            $estimatedCost,
                            $estimatedTimeDays
                        );
                        
                        if ($emailSent) {
                            error_log("Booking diagnosis email sent successfully to: " . $customer['email']);
                        } else {
                            error_log("Failed to send booking diagnosis email to: " . $customer['email']);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error sending diagnosis email: " . $e->getMessage());
                // Don't fail the transaction for email errors
            }
        });
        
        error_log("Diagnosis completed successfully for booking ID: " . $bookingId);
        ResponseHelper::success('Diagnosis sent to customer for confirmation');
        
    } elseif($action === 'approve'){
        // Approve confirmed_by_customer booking
        error_log("Approving booking ID: $bookingId");
        
        // Get booking details for notification
        $bookingStmt = $db->prepare("
            SELECT b.*, u.name as customer_name, u.email as customer_email, u.id as customer_id,
                   rs.name as shop_name, b.notes
            FROM bookings b
            INNER JOIN users u ON u.id = b.customer_id
            INNER JOIN repair_shops rs ON rs.id = b.shop_id
            WHERE b.id = ?
        ");
        $bookingStmt->execute([$bookingId]);
        $booking = $bookingStmt->fetch();
        
        if(!$booking){
            ResponseHelper::notFound('Booking not found');
        }
        
        if($booking['status'] !== 'confirmed_by_customer'){
            ResponseHelper::error('Only confirmed_by_customer bookings can be approved', 400);
        }
        
        DBTransaction::execute($db, function($pdo) use ($bookingId, $shop, $booking) {
            $stmt = $pdo->prepare("UPDATE bookings SET status='approved', updated_at=NOW() WHERE id=?");
            $stmt->execute([$bookingId]);
            
            // Log to history
            $historyStmt = $pdo->prepare("
                INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $bookingId,
                'confirmed_by_customer',
                'approved',
                $shop['user_id'],
                'Booking approved by shop'
            ]);
            
            // Create in-app notification
            $notes = json_decode($booking['notes'] ?? '', true);
            $serviceName = is_array($notes) && isset($notes['service']) ? $notes['service'] : 'Service';
            
            NotificationHelper::notifyBookingApproved(
                $pdo,
                (int)$booking['customer_id'],
                $booking['shop_name'],
                $serviceName,
                (new DateTime($booking['scheduled_at']))->format('F j, Y')
            );
        });
        
        // Send email notification to customer after successful approval
        try {
            require_once __DIR__ . '/../../backend/config/email.php';
            $emailService = new EmailService();
            
            // Test email service first
            if ($emailService->testEmailConnection()) {
                // Format booking date and time
                $scheduledAt = new DateTime($booking['scheduled_at']);
                $bookingDate = $scheduledAt->format('F j, Y');
                $bookingTime = $scheduledAt->format('g:i A');
                
                // Get service name from notes
                $notes = json_decode($booking['notes'] ?? '', true);
                $serviceName = is_array($notes) && isset($notes['service']) ? $notes['service'] : 'Service';
                
                // Send approval email
                $emailSent = $emailService->sendBookingApprovalEmail(
                    $booking['customer_email'],
                    $booking['customer_name'],
                    $booking['shop_name'],
                    $serviceName,
                    $bookingDate,
                    $bookingTime
                );
                
                if ($emailSent) {
                    error_log("Booking approval email sent successfully to: " . $booking['customer_email']);
                } else {
                    error_log("Failed to send booking approval email to: " . $booking['customer_email']);
                }
            } else {
                error_log("Email service connection failed, skipping approval email notification");
            }
        } catch (Exception $e) {
            error_log("Error sending approval email: " . $e->getMessage());
            // Don't fail the transaction for email errors
        }
        
        ResponseHelper::success('Booking approved successfully');
        
    } elseif($action === 'reject'){
        error_log("Rejecting booking ID: $bookingId");
        
        $rejectionReason = trim($input['rejection_reason'] ?? '');
        
        // Require rejection reason
        if(empty($rejectionReason)) {
            ResponseHelper::validationError('Rejection reason is required');
        }
        
        DBTransaction::execute($db, function($pdo) use ($bookingId, $shop, $rejectionReason) {
            // Get current status first
            $statusStmt = $pdo->prepare("SELECT status FROM bookings WHERE id=?");
            $statusStmt->execute([$bookingId]);
            $currentStatus = $statusStmt->fetchColumn();
            
            $stmt = $pdo->prepare("UPDATE bookings SET status='rejected', updated_at=NOW() WHERE id=?");
            $stmt->execute([$bookingId]);
            
            // Log to history with rejection reason
            $historyStmt = $pdo->prepare("
                INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $bookingId,
                $currentStatus,
                'rejected',
                $shop['user_id'],
                'Booking rejected by shop. Reason: ' . $rejectionReason
            ]);
        });
        
        // Get customer details for notification
        $customerStmt = $db->prepare("
            SELECT b.customer_id, u.name as customer_name, u.id as user_id
            FROM bookings b
            INNER JOIN users u ON b.customer_id = u.id
            WHERE b.id = ?
        ");
        $customerStmt->execute([$bookingId]);
        $customer = $customerStmt->fetch();
        
        if ($customer) {
            // Get service name from notes
            $bookingStmt = $db->prepare("SELECT notes FROM bookings WHERE id = ?");
            $bookingStmt->execute([$bookingId]);
            $bookingData = $bookingStmt->fetch();
            $notes = json_decode($bookingData['notes'] ?? '{}', true);
            $serviceName = $notes['service'] ?? 'Service';
            
            // Get shop name
            $shopStmt = $db->prepare("SELECT rs.name FROM repair_shops rs WHERE rs.owner_id = ?");
            $shopStmt->execute([$shop['shop_id']]);
            $shopData = $shopStmt->fetch();
            $shopName = $shopData['name'] ?? 'Shop';
            
            // Notify customer about rejection with reason
            NotificationHelper::notifyBookingRejected(
                $db,
                (int)$customer['user_id'],
                $shopName,
                $serviceName,
                $bookingId,
                $rejectionReason
            );
        }
        
        ResponseHelper::success('Booking rejected successfully');
        
    } elseif($action === 'cancel'){
        error_log("Cancelling booking ID: $bookingId");
        
        $cancellationReason = trim($input['cancellation_reason'] ?? '');
        if(empty($cancellationReason)){
            ResponseHelper::error('Cancellation reason is required', 400);
        }
        
        DBTransaction::execute($db, function($pdo) use ($bookingId, $shop, $cancellationReason) {
            $statusStmt = $pdo->prepare("SELECT status FROM bookings WHERE id=?");
            $statusStmt->execute([$bookingId]);
            $currentStatus = $statusStmt->fetchColumn();
            
            $stmt = $pdo->prepare("UPDATE bookings SET status='cancelled', updated_at=NOW() WHERE id=?");
            $stmt->execute([$bookingId]);
            
            $historyStmt = $pdo->prepare("
                INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $bookingId,
                $currentStatus,
                'cancelled',
                $shop['user_id'],
                $cancellationReason
            ]);
        });
        
        ResponseHelper::success('Booking cancelled successfully');
        
    } elseif($action === 'assign'){
        $techId = (int)($input['technician_id'] ?? 0);
        error_log("Assigning technician ID: $techId to booking ID: $bookingId");
        
        // Verify technician belongs to this shop AND is active
        $t = $db->prepare('SELECT t.id, u.status FROM technicians t INNER JOIN users u ON u.id=t.user_id WHERE t.id=? AND t.shop_id=?');
        $t->execute([$techId, $shop['shop_id']]);
        $techRow = $t->fetch();
        
        if(!$techRow){ 
            ResponseHelper::error('Technician not found', 400); 
        }
        
        if(in_array(($techRow['status'] ?? ''), ['deactivated','rejected'], true)){
            ResponseHelper::error('Technician is deactivated', 400);
        }
        
        DBTransaction::execute($db, function($pdo) use ($bookingId, $techId, $shop) {
            $statusStmt = $pdo->prepare("SELECT status FROM bookings WHERE id=?");
            $statusStmt->execute([$bookingId]);
            $currentStatus = $statusStmt->fetchColumn();
            
            $stmt = $pdo->prepare("UPDATE bookings SET technician_id=?, status='assigned', updated_at=NOW() WHERE id=?");
            $stmt->execute([$techId, $bookingId]);
            
            $historyStmt = $pdo->prepare("
                INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $bookingId,
                $currentStatus,
                'assigned',
                $shop['user_id'],
                "Technician assigned (ID: $techId)"
            ]);
            
            // Notify customer and technician
            try {
                $bookingStmt = $pdo->prepare("
                    SELECT b.*, u.id as customer_id, u.name as customer_name, u.email as customer_email, rs.name as shop_name,
                           tech_user.id as tech_user_id, tech_user.name as technician_name
                    FROM bookings b
                    INNER JOIN users u ON u.id = b.customer_id
                    INNER JOIN repair_shops rs ON rs.id = b.shop_id
                    LEFT JOIN technicians t ON t.id = b.technician_id
                    LEFT JOIN users tech_user ON tech_user.id = t.user_id
                    WHERE b.id = ?
                ");
                $bookingStmt->execute([$bookingId]);
                $booking = $bookingStmt->fetch();
                
                if($booking) {
                    $notes = json_decode($booking['notes'] ?? '', true);
                    $serviceName = is_array($notes) && isset($notes['service']) ? $notes['service'] : 'Service';
                    
                    // Notify customer
                    NotificationHelper::notifyTechnicianAssigned(
                        $pdo,
                        (int)$booking['customer_id'],
                        $booking['technician_name'] ?? 'Technician',
                        $serviceName
                    );
                    
                    // Notify technician
                    if($booking['tech_user_id']) {
                        $scheduledAt = new DateTime($booking['scheduled_at']);
                        NotificationHelper::notifyNewJob(
                            $pdo,
                            (int)$booking['tech_user_id'],
                            $serviceName,
                            $booking['customer_name'] ?? 'Customer',
                            $scheduledAt->format('F j, Y \a\t g:i A')
                        );
                    }
                }
            } catch (Exception $e) {
                error_log("Error sending assignment notifications: " . $e->getMessage());
            }
        });
        
        // Send email notification to customer after successful assignment
        try {
            // Get booking details with customer email for email notification
            $bookingStmt = $db->prepare("
                SELECT b.*, u.name as customer_name, u.email as customer_email, rs.name as shop_name,
                       tech_user.name as technician_name
                FROM bookings b
                INNER JOIN users u ON u.id = b.customer_id
                INNER JOIN repair_shops rs ON rs.id = b.shop_id
                LEFT JOIN technicians t ON t.id = b.technician_id
                LEFT JOIN users tech_user ON tech_user.id = t.user_id
                WHERE b.id = ?
            ");
            $bookingStmt->execute([$bookingId]);
            $booking = $bookingStmt->fetch();
            
            if ($booking && !empty($booking['customer_email'])) {
                require_once __DIR__ . '/../../backend/config/email.php';
                $emailService = new EmailService();
                
                // Test email service first
                if ($emailService->testEmailConnection()) {
                    // Format booking date and time
                    $scheduledAt = new DateTime($booking['scheduled_at']);
                    $bookingDate = $scheduledAt->format('F j, Y');
                    $bookingTime = $scheduledAt->format('g:i A');
                    
                    // Get service name from notes
                    $notes = json_decode($booking['notes'] ?? '', true);
                    $serviceName = is_array($notes) && isset($notes['service']) ? $notes['service'] : 'Service';
                    
                    // Get technician name
                    $technicianName = $booking['technician_name'] ?? 'Technician';
                    
                    // Send technician assignment email
                    $emailSent = $emailService->sendTechnicianAssignedEmail(
                        $booking['customer_email'],
                        $booking['customer_name'],
                        $booking['shop_name'],
                        $serviceName,
                        $bookingDate,
                        $bookingTime,
                        $technicianName
                    );
                    
                    if ($emailSent) {
                        error_log("Technician assignment email sent successfully to: " . $booking['customer_email']);
                    } else {
                        error_log("Failed to send technician assignment email to: " . $booking['customer_email']);
                    }
                } else {
                    error_log("Email service connection failed, skipping technician assignment email notification");
                }
            }
        } catch (Exception $e) {
            error_log("Error sending technician assignment email: " . $e->getMessage());
            // Don't fail the transaction for email errors
        }
        
        ResponseHelper::success('Technician assigned successfully');
        
    } elseif($action === 'status'){
        $status = $input['status'] ?? '';
        $allowed = ['pending_review','awaiting_customer_confirmation','confirmed_by_customer','approved','assigned','in_progress','completed','cancelled'];
        
        if(!in_array($status, $allowed, true)){ 
            ResponseHelper::error('Invalid status', 400); 
        }
        
        DBTransaction::execute($db, function($pdo) use ($bookingId, $status, $shop) {
            $statusStmt = $pdo->prepare("SELECT status FROM bookings WHERE id=?");
            $statusStmt->execute([$bookingId]);
            $currentStatus = $statusStmt->fetchColumn();
            
            $updateStmt = $pdo->prepare('UPDATE bookings SET status=?, updated_at=NOW() WHERE id=?');
            $updateStmt->execute([$status, $bookingId]);
            
            $historyStmt = $pdo->prepare("
                INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $bookingId,
                $currentStatus,
                $status,
                $shop['user_id'],
                "Status changed to: $status"
            ]);
            
            // Send notifications for status changes
            if($status === 'completed' || $status === 'in_progress') {
                try {
                    $bookingStmt = $pdo->prepare("
                        SELECT b.*, u.name as customer_name, u.email as customer_email, u.id as customer_id,
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
                        $notes = json_decode($booking['notes'] ?? '', true);
                        $serviceName = is_array($notes) && isset($notes['service']) ? $notes['service'] : 'Service';
                        
                        if($status === 'completed') {
                            NotificationHelper::notifyBookingCompleted(
                                $pdo,
                                (int)$booking['customer_id'],
                                $booking['shop_name'],
                                $serviceName
                            );
                        } elseif($status === 'in_progress') {
                            NotificationHelper::notifyBookingInProgress(
                                $pdo,
                                (int)$booking['customer_id'],
                                $booking['shop_name'],
                                $serviceName
                            );
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error sending status change notification: " . $e->getMessage());
                }
            }
        });
        
        // Send email notification if status is completed (outside transaction)
        if($status === 'completed') {
            try {
                // Get booking details for email
                $bookingStmt = $db->prepare("
                    SELECT b.*, u.name as customer_name, u.email as customer_email, u.id as customer_id,
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
                
                if ($booking && !empty($booking['customer_email'])) {
                    require_once __DIR__ . '/../../backend/config/email.php';
                    $emailService = new EmailService();
                    
                    // Test email service first
                    if ($emailService->testEmailConnection()) {
                        // Format completion date
                        $completionDate = date('F j, Y \a\t g:i A');
                        
                        // Get service name from notes
                        $notes = json_decode($booking['notes'] ?? '', true);
                        $serviceName = is_array($notes) && isset($notes['service']) ? $notes['service'] : 'Service';
                        
                        // Get technician name
                        $technicianName = $booking['technician_name'] ?? null;
                        
                        // Send completion email
                        $emailSent = $emailService->sendRepairCompletionEmail(
                            $booking['customer_email'],
                            $booking['customer_name'],
                            $booking['shop_name'],
                            $serviceName,
                            $completionDate,
                            $technicianName
                        );
                        
                        if ($emailSent) {
                            error_log("Repair completion email sent successfully to: " . $booking['customer_email']);
                        } else {
                            error_log("Failed to send repair completion email to: " . $booking['customer_email']);
                        }
                    } else {
                        error_log("Email service connection failed, skipping completion email notification");
                    }
                }
            } catch (Exception $e) {
                error_log("Error sending completion email: " . $e->getMessage());
                // Don't fail the request for email errors
            }
        }
        
        ResponseHelper::success('Status updated successfully');
        
    } elseif($action === 'reschedule_accept' || $action === 'reschedule_decline'){
        // Load notes and parse reschedule info
        $get = $db->prepare('SELECT notes, status, customer_id FROM bookings WHERE id=?');
        $get->execute([$bookingId]);
        $row = $get->fetch();
        
        if(!$row){ 
            http_response_code(404); 
            echo json_encode(['error'=>'Booking not found']); 
            exit; 
        }
        
        $notes = json_decode($row['notes'] ?? '', true); 
        if(!is_array($notes)) $notes = [];
        
        $hasReq = !empty($notes['reschedule_request']) && !empty($notes['reschedule_new_at']);
        
        if(!$hasReq){ 
            ResponseHelper::error('No reschedule request to process', 400); 
        }
        
        DBTransaction::execute($db, function($pdo) use ($action, $notes, $repairShop, $bookingId, $shop, $row) {
            if($action === 'reschedule_accept'){
                $newAt = $notes['reschedule_new_at'];
                $date = substr($newAt,0,10); 
                $time = substr($newAt,11,8);
                
                // Re-validate availability
                $chk = $pdo->prepare("
                    SELECT COUNT(*) as count FROM bookings 
                    WHERE shop_id=? AND DATE(scheduled_at)=? AND TIME(scheduled_at)=? 
                    AND status IN ('pending_review','awaiting_customer_confirmation','confirmed_by_customer','approved','assigned','in_progress') 
                    AND id<>?
                ");
                $chk->execute([$repairShop['id'], $date, $time, $bookingId]);
                $r = $chk->fetch();
                
                if(($r['count'] ?? 0) > 0){ 
                    ResponseHelper::conflict('Selected time is no longer available'); 
                }
                
                $pdo->prepare('UPDATE bookings SET scheduled_at=?, status=\'approved\', updated_at=NOW() WHERE id=?')->execute([$newAt, $bookingId]);
                
                unset($notes['reschedule_request']); 
                unset($notes['reschedule_new_at']);
                $notes['reschedule_status'] = 'accepted';
                
                $pdo->prepare('UPDATE bookings SET notes=? WHERE id=?')->execute([json_encode($notes), $bookingId]);
                
                $historyStmt = $pdo->prepare("
                    INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $historyStmt->execute([
                    $bookingId,
                    $row['status'],
                    'approved',
                    $shop['user_id'],
                    "Reschedule accepted. New time: $newAt"
                ]);
                
                // Notify customer
                $newDate = new DateTime($newAt);
                NotificationHelper::notifyRescheduleAccepted(
                    $pdo,
                    (int)$row['customer_id'],
                    $newDate->format('F j, Y \a\t g:i A')
                );
                
            } else {
                unset($notes['reschedule_request']); 
                unset($notes['reschedule_new_at']);
                $notes['reschedule_status'] = 'declined';
                
                $pdo->prepare('UPDATE bookings SET notes=?, updated_at=NOW() WHERE id=?')->execute([json_encode($notes), $bookingId]);
                
                $historyStmt = $pdo->prepare("
                    INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $historyStmt->execute([
                    $bookingId,
                    $row['status'],
                    $row['status'],
                    $shop['user_id'],
                    "Reschedule request declined"
                ]);
                
                NotificationHelper::notifyRescheduleDeclined($pdo, (int)$row['customer_id']);
            }
        });
        
        ResponseHelper::success('Reschedule request processed successfully');
        
    } else {
        ResponseHelper::error('Unknown action', 400); 
    }

} catch(Exception $e){ 
    error_log("Booking management error: " . $e->getMessage());
    // Ensure any active transaction is rolled back
    if (isset($db)) {
        DBTransaction::cleanup($db);
    }
    ResponseHelper::serverError('Server error', $e->getMessage()); 
}
?>
