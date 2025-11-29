<?php
// Prevent any HTML/PHP errors from breaking JSON response
error_reporting(0);
ini_set('display_errors', '0');

// Ensure we output JSON even if there are errors
header('Content-Type: application/json');

// Catch any errors and return JSON
try {
    require_once __DIR__ . '/../../backend/config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load database configuration', 'detail' => $e->getMessage()]);
    exit;
}

function auth_technician(PDO $db){
    $token = $_COOKIE['auth_token'] ?? '';
    if(!$token){ http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
    
    $stmt = $db->prepare("SELECT u.id, u.role, u.name, t.id as tech_id FROM users u INNER JOIN sessions s ON s.user_id=u.id INNER JOIN technicians t ON t.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    
    if(!$u || $u['role']!=='technician' || empty($u['tech_id'])){ 
        http_response_code(401); 
        echo json_encode(['error'=>'Unauthorized - Invalid technician profile']); 
        exit; 
    }
    return $u;
}

try{
    $db = (new Database())->getConnection();
    $tech = auth_technician($db);

    // Ensure we have a valid technician ID
    if(empty($tech['tech_id']) || !is_numeric($tech['tech_id'])){
        http_response_code(400); 
        echo json_encode(['error'=>'Invalid technician profile']); 
        exit;
    }

    // Query bookings using the actual table structure, scoped strictly to the authenticated technician
    $stmt = $db->prepare("SELECT b.id,
                                 b.device_description as description,
                                 b.status,
                                 b.scheduled_at,
                                 b.total_price,
                                 b.notes,
                                 b.device_type,
                                 b.device_issue_description,
                                 b.device_photo,
                                 rs.name as shop_name,
                                 rs.address as shop_address,
                                 u.name AS customer_name,
                                 u.email AS customer_email,
                                 u.phone AS customer_phone,
                                 b.technician_id,
                                 tech_user.name AS technician_name
                          FROM bookings b 
                          INNER JOIN repair_shops rs ON rs.id=b.shop_id
                          INNER JOIN users u ON u.id=b.customer_id
                          INNER JOIN technicians t ON t.id = b.technician_id
                          INNER JOIN users tech_user ON tech_user.id = t.user_id
                          WHERE t.id = ? AND tech_user.status = 'approved'
                          ORDER BY b.scheduled_at ASC");
    $stmt->execute([$tech['tech_id']]);
    $bookings = $stmt->fetchAll();
    
    // Transform the data to match expected format
    $transformedJobs = [];
    foreach($bookings as $booking) {
        $scheduledAt = new DateTime($booking['scheduled_at']);
        
        // Parse service from notes JSON
        $serviceName = 'Service';
        if (!empty($booking['notes'])) {
            $notesData = json_decode($booking['notes'], true);
            if (is_array($notesData) && isset($notesData['service'])) {
                $serviceName = $notesData['service'];
            }
        }
        
        $transformedJobs[] = [
            'id' => $booking['id'],
            'service' => $serviceName,
            'date' => $scheduledAt->format('Y-m-d'),
            'time_slot' => $scheduledAt->format('H:i'),
            'description' => $booking['description'],
            'device_type' => $booking['device_type'] ?? null,
            'device_issue_description' => $booking['device_issue_description'] ?? null,
            'device_photo' => $booking['device_photo'] ?? null,
            'status' => $booking['status'],
            'shop_name' => $booking['shop_name'],
            'shop_address' => $booking['shop_address'] ?? null,
            'customer_name' => $booking['customer_name'],
            'customer_email' => $booking['customer_email'] ?? null,
            'customer_phone' => $booking['customer_phone'] ?? null,
            'price' => $booking['total_price'],
            'technician_id' => $booking['technician_id'],
            'technician_name' => $booking['technician_name']
        ];
    }
    
    echo json_encode(['success'=>true, 'jobs'=>$transformedJobs]);
}catch(Exception $e){ 
    http_response_code(500); 
    echo json_encode(['error'=>'Server error', 'detail'=>$e->getMessage()]); 
}
?>


