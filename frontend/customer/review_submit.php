<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../backend/utils/InputValidator.php';

function auth_customer(PDO $db){
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if($token === null){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='customer'){ http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
    return $u;
}

if($_SERVER['REQUEST_METHOD']!=='POST'){ http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

try{
    $db = (new Database())->getConnection();
    $u = auth_customer($db);
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $bookingId = InputValidator::validateId($input['booking_id'] ?? 0);
    $rating = (int)($input['rating'] ?? 0);
    $techName = InputValidator::validateString($input['tech_name'] ?? '', 0, 120) ?? '';
    $comment = InputValidator::sanitizeHtml($input['comment'] ?? '', '');
    if($bookingId===null || $rating<1 || $rating>5){ http_response_code(400); echo json_encode(['error'=>'Valid booking and rating (1-5) required']); exit; }
    $stmt = $db->prepare('SELECT id, status, notes FROM bookings WHERE id=? AND customer_id=?');
    $stmt->execute([$bookingId, $u['id']]);
    $b = $stmt->fetch();
    if(!$b){ http_response_code(404); echo json_encode(['error'=>'Booking not found']); exit; }
    if(($b['status'] ?? '')!=='completed'){ http_response_code(400); echo json_encode(['error'=>'You can only review completed bookings']); exit; }
    $notes = json_decode($b['notes'] ?? '', true); if(!is_array($notes)) $notes = [];
    $notes['review'] = [ 'rating'=>$rating, 'comment'=>$comment, 'tech_name'=>$techName, 'created_at'=>date('Y-m-d H:i:s') ];
    $upd = $db->prepare('UPDATE bookings SET notes=? WHERE id=?');
    $upd->execute([ json_encode($notes), $bookingId ]);
    echo json_encode(['success'=>true]);
}catch(Exception $e){ http_response_code(500); echo json_encode(['error'=>'Server error']); }
?>


