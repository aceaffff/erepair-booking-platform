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
    
    // Get all customers with their booking counts
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.address, u.email_verified, u.created_at, u.avatar_url,
               COUNT(b.id) as total_bookings
        FROM users u
        LEFT JOIN bookings b ON u.id = b.customer_id
        WHERE u.role = 'customer'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $customers = $stmt->fetchAll();
    
    // Transform the data
    $transformedCustomers = [];
    foreach($customers as $customer) {
        // Normalize avatar URL
        $avatarUrl = $customer['avatar_url'];
        if ($avatarUrl && !preg_match('/^https?:\/\//', $avatarUrl)) {
            // If it's a relative path, keep it as is for frontend processing
            $avatarUrl = $avatarUrl;
        }
        
        $transformedCustomers[] = [
            'id' => $customer['id'],
            'name' => $customer['name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'address' => $customer['address'],
            'email_verified' => (bool)$customer['email_verified'],
            'created_at' => $customer['created_at'],
            'avatar_url' => $avatarUrl,
            'total_bookings' => (int)$customer['total_bookings']
        ];
    }
    
    echo json_encode(['success' => true, 'customers' => $transformedCustomers]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
}
?>
