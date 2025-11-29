<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/InputValidator.php';

function auth_admin(PDO $db) {
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if ($token === null) { 
        http_response_code(401); 
        echo json_encode(['error'=>'Not authenticated']); 
        exit; 
    }
    
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if(!$user || $user['role']!=='admin'){ 
        http_response_code(401); 
        echo json_encode(['error'=>'Unauthorized']); 
        exit; 
    }
    
    return $user;
}

try {
    $db = (new Database())->getConnection();
    $admin = auth_admin($db);
    
    $customerId = InputValidator::validateId($_GET['id'] ?? 0);
    if ($customerId === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid customer ID required']);
        exit;
    }
    
    // Get customer details
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.address, u.email_verified, u.created_at, u.avatar as avatar_url
        FROM users u
        WHERE u.id = ? AND u.role = 'customer'
    ");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        http_response_code(404);
        echo json_encode(['error' => 'Customer not found']);
        exit;
    }
    
    // Get customer's bookings
    $stmt = $db->prepare("
        SELECT b.id, b.device_type, b.device_issue_description, b.status, b.scheduled_at, b.total_price, b.notes,
               rs.name as shop_name
        FROM bookings b
        LEFT JOIN repair_shops rs ON rs.id = b.shop_id
        WHERE b.customer_id = ?
        ORDER BY b.scheduled_at DESC
        LIMIT 10
    ");
    $stmt->execute([$customerId]);
    $bookings = $stmt->fetchAll();
    
    // Transform booking data
    $transformedBookings = [];
    foreach($bookings as $booking) {
        $notes = json_decode($booking['notes'] ?? '', true);
        $serviceName = is_array($notes) && isset($notes['service']) ? $notes['service'] : ($booking['notes'] ?? 'Service');
        
        $transformedBookings[] = [
            'id' => $booking['id'],
            'service' => $serviceName,
            'device_type' => $booking['device_type'],
            'device_issue_description' => $booking['device_issue_description'],
            'status' => $booking['status'],
            'scheduled_at' => $booking['scheduled_at'],
            'total_price' => $booking['total_price'],
            'shop_name' => $booking['shop_name']
        ];
    }
    
    // Transform customer data
    $avatarUrl = $customer['avatar_url'];
    if ($avatarUrl && !preg_match('/^https?:\/\//', $avatarUrl)) {
        // If it's a relative path, keep it as is for frontend processing
        $avatarUrl = $avatarUrl;
    }
    
    $transformedCustomer = [
        'id' => $customer['id'],
        'name' => $customer['name'],
        'email' => $customer['email'],
        'phone' => $customer['phone'],
        'address' => $customer['address'],
        'email_verified' => (bool)$customer['email_verified'],
        'created_at' => $customer['created_at'],
        'avatar_url' => $avatarUrl
    ];
    
    echo json_encode([
        'success' => true, 
        'customer' => $transformedCustomer,
        'bookings' => $transformedBookings
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
}
?>
