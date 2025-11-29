<?php
require_once __DIR__ . '/../../backend/utils/ResponseHelper.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/InputValidator.php';

// Initialize API environment
ResponseHelper::initApi();

function auth_customer(PDO $db){
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if($token === null){ ResponseHelper::unauthorized('Not authenticated'); }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='customer'){ ResponseHelper::unauthorized('Unauthorized'); }
    return $u;
}

if($_SERVER['REQUEST_METHOD']!=='POST'){ ResponseHelper::methodNotAllowed(); }

try{
    $db = (new Database())->getConnection();
    $u = auth_customer($db);
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $bookingId = InputValidator::validateId($input['booking_id'] ?? 0);
    $newDate = InputValidator::validateDate($input['date'] ?? '');
    $newTime = InputValidator::validateTime($input['time_slot'] ?? '');
    if($bookingId===null || $newDate===null || $newTime===null){ ResponseHelper::validationError('Valid booking, date and time are required'); }

    // verify booking belongs to this customer and is approvable state
    $stmt = $db->prepare('SELECT id, status, notes, shop_id FROM bookings WHERE id=? AND customer_id=?');
    $stmt->execute([$bookingId, $u['id']]);
    $b = $stmt->fetch();
    if(!$b){ ResponseHelper::notFound('Booking not found'); }
    if(!in_array($b['status'], ['approved','assigned','in_progress'], true)){
        ResponseHelper::error('Only approved/assigned bookings can be rescheduled', 400);
    }

    $scheduledAtCandidate = $newDate.' '.$newTime.':00';
    try{ $dt = new DateTime($scheduledAtCandidate); } catch(Exception $e){ ResponseHelper::validationError('Invalid date/time'); }

    // Check not in the past and availability for that shop/time
    $now = new DateTime('now');
    if($dt < $now){ ResponseHelper::validationError('You cannot reschedule to a past time'); }

    $availabilityCheck = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE shop_id = ? AND DATE(scheduled_at) = ? AND TIME(scheduled_at) = ? AND status IN ('pending','approved','assigned','in_progress')");
    $availabilityCheck->execute([$b['shop_id'], $newDate, $newTime . ':00']);
    $result = $availabilityCheck->fetch();
    if(($result['count'] ?? 0) > 0){ ResponseHelper::conflict('This time slot is no longer available'); }

    // Merge notes JSON
    $notes = json_decode($b['notes'] ?? '', true); if(!is_array($notes)) $notes = [];
    $notes['reschedule_request'] = true;
    $notes['reschedule_new_at'] = $scheduledAtCandidate;
    unset($notes['reschedule_status']); // clear any prior status

    $upd = $db->prepare('UPDATE bookings SET notes=? WHERE id=?');
    $upd->execute([ json_encode($notes), $bookingId ]);

    ResponseHelper::success('Reschedule request submitted successfully');
}catch(Exception $e){ ResponseHelper::serverError('Server error', $e->getMessage()); }
?>


