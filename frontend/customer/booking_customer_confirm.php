<?php
/**
 * Customer Booking Confirmation
 * Allows customers to confirm or cancel bookings after receiving diagnosis/quotation
 */
require_once __DIR__ . '/../../backend/utils/ResponseHelper.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/InputValidator.php';
require_once __DIR__ . '/../../backend/utils/NotificationHelper.php';
require_once __DIR__ . '/../../backend/utils/DBTransaction.php';

// Initialize API environment
ResponseHelper::initApi();

function auth_customer(PDO $db): array {
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if ($token === null) {
        ResponseHelper::unauthorized('Not authenticated');
    }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] !== 'customer') {
        ResponseHelper::unauthorized('Unauthorized');
    }
    return $user;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ResponseHelper::methodNotAllowed();
}

try {
    $db = (new Database())->getConnection();
    $user = auth_customer($db);
    
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $bookingId = InputValidator::validateId($input['booking_id'] ?? 0);
    $action = trim($input['action'] ?? ''); // 'confirm' or 'cancel'
    $cancellationReason = trim($input['cancellation_reason'] ?? '');
    $date = InputValidator::validateDate($input['date'] ?? '');
    $timeSlot = InputValidator::validateTime($input['time_slot'] ?? '');
    
    if ($bookingId === null || !in_array($action, ['confirm', 'cancel'], true)) {
        ResponseHelper::validationError('Valid booking ID and action (confirm/cancel) required');
    }
    
    // If confirming, date and time_slot are required
    if ($action === 'confirm' && ($date === null || $timeSlot === null)) {
        ResponseHelper::validationError('Date and time slot are required to confirm booking');
    }
    
    // Verify booking belongs to this customer and is awaiting confirmation
    $stmt = $db->prepare('
        SELECT b.id, b.status, b.shop_id, b.diagnostic_notes, b.estimated_cost, b.estimated_time_days, b.notes, b.scheduled_at,
               rs.owner_id as shop_owner_id, rs.name as shop_name,
               u.name as customer_name
        FROM bookings b
        INNER JOIN repair_shops rs ON rs.id = b.shop_id
        INNER JOIN users u ON u.id = b.customer_id
        WHERE b.id = ? AND b.customer_id = ?
    ');
    $stmt->execute([$bookingId, $user['id']]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        ResponseHelper::notFound('Booking not found');
    }
    
    if ($booking['status'] !== 'awaiting_customer_confirmation') {
        ResponseHelper::error('This booking is not awaiting customer confirmation', 400);
    }
    
    // Get service name from notes
    $notes = json_decode($booking['notes'] ?? '{}', true);
    $serviceName = $notes['service'] ?? 'Service';
    
    // Initialize scheduledAt (will be set during confirmation)
    $scheduledAt = null;
    
    // If confirming, validate and set schedule
    if ($action === 'confirm') {
        // Validate booking date (must be at least one day in advance)
        $today = new DateTime();
        $tomorrow = new DateTime('+1 day');
        $bookingDate = new DateTime($date);
        
        // Set time to start of day for accurate comparison
        $today->setTime(0, 0, 0);
        $tomorrow->setTime(0, 0, 0);
        $bookingDate->setTime(0, 0, 0);
        
        if ($bookingDate < $tomorrow) {
            ResponseHelper::validationError('Bookings must be made at least one day in advance. Please select a future date.');
        }
        
        // Build scheduled datetime
        $scheduledAt = $date . ' ' . $timeSlot . ':00';
        
        // Validate scheduled datetime is not in the past
        try {
            $scheduledDt = new DateTime($scheduledAt);
            $now = new DateTime('now');
            if ($scheduledDt < $now) {
                ResponseHelper::validationError('You cannot book a date/time in the past');
            }
        } catch (Exception $e) {
            ResponseHelper::validationError('Invalid date/time format');
        }
        
        // Check availability - prevent double booking
        $availabilityCheck = $db->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE shop_id = ? 
            AND DATE(scheduled_at) = ? 
            AND TIME(scheduled_at) = ?
            AND status IN ('pending_review', 'awaiting_customer_confirmation', 'confirmed_by_customer', 'approved', 'assigned', 'in_progress')
            AND id != ?
        ");
        $availabilityCheck->execute([$booking['shop_id'], $date, $timeSlot . ':00', $bookingId]);
        $result = $availabilityCheck->fetch();
        
        if ($result['count'] > 0) {
            ResponseHelper::conflict('This time slot is no longer available. Please select another date/time.');
        }
    }
    
    DBTransaction::execute($db, function($pdo) use ($action, $bookingId, $user, $booking, $serviceName, $cancellationReason, $scheduledAt) {
        if ($action === 'confirm') {
            // Customer confirms the quotation and sets schedule
            $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed_by_customer', scheduled_at = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$scheduledAt, $bookingId]);
            
            // Log to booking history
            $historyStmt = $pdo->prepare("
                INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $bookingId,
                'awaiting_customer_confirmation',
                'confirmed_by_customer',
                $user['id'],
                'Customer confirmed quotation and selected schedule: ' . $scheduledAt
            ]);
            
            // Notify shop owner
            $ownerStmt = $pdo->prepare("SELECT user_id FROM shop_owners WHERE id = ?");
            $ownerStmt->execute([$booking['shop_owner_id']]);
            $owner = $ownerStmt->fetch();
            
            if ($owner) {
                NotificationHelper::notifyCustomerConfirmed(
                    $pdo,
                    (int)$owner['user_id'],
                    $booking['customer_name'],
                    $serviceName
                );
            }
            
        } else if ($action === 'cancel') {
            // Customer cancels the booking
            $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled_by_customer', updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$bookingId]);
            
            // Log to booking history
            $historyStmt = $pdo->prepare("
                INSERT INTO booking_history (booking_id, old_status, new_status, changed_by, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $bookingId,
                'awaiting_customer_confirmation',
                'cancelled_by_customer',
                $user['id'],
                $cancellationReason ?: 'Customer cancelled after receiving quotation'
            ]);
            
            // Notify shop owner
            $ownerStmt = $pdo->prepare("SELECT user_id FROM shop_owners WHERE id = ?");
            $ownerStmt->execute([$booking['shop_owner_id']]);
            $owner = $ownerStmt->fetch();
            
            if ($owner) {
                NotificationHelper::notifyCustomerCancelled(
                    $pdo,
                    (int)$owner['user_id'],
                    $booking['customer_name'],
                    $serviceName,
                    $cancellationReason
                );
            }
        }
    });
    
    if ($action === 'confirm') {
        ResponseHelper::success('Booking confirmed! The shop will review and approve your booking.');
    } else {
        ResponseHelper::success('Booking cancelled successfully.');
    }
    
} catch (Exception $e) {
    // Ensure any active transaction is rolled back
    if (isset($db)) {
        DBTransaction::cleanup($db);
    }
    ResponseHelper::serverError('Server error', $e->getMessage());
}
?>

