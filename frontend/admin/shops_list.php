<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/InputValidator.php';

function haversine($lat1,$lon1,$lat2,$lon2){
    if($lat1===null||$lon1===null||$lat2===null||$lon2===null) return null;
    $R=6371; // km
    $dLat=deg2rad($lat2-$lat1);
    $dLon=deg2rad($lon2-$lon1);
    $a=sin($dLat/2)*sin($dLat/2)+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)*sin($dLon/2);
    $c=2*atan2(sqrt($a),sqrt(1-$a));
    return $R*$c;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->query("
        SELECT 
            so.id, 
            so.shop_name, 
            so.shop_address, 
            COALESCE(so.shop_phone, u.phone) as shop_phone,
            so.shop_latitude, 
            so.shop_longitude, 
            so.approval_status,
            COALESCE(
                CASE 
                    WHEN SUM(sr.total_reviews) > 0 THEN SUM(sr.average_rating * sr.total_reviews) / SUM(sr.total_reviews)
                    ELSE 0
                END, 
                0
            ) as average_rating,
            COALESCE(SUM(sr.total_reviews), 0) as total_reviews
        FROM shop_owners so
        LEFT JOIN users u ON u.id = so.user_id
        LEFT JOIN repair_shops rs ON rs.owner_id = so.id
        LEFT JOIN shop_ratings sr ON sr.shop_id = rs.id
        WHERE so.approval_status='approved'
        GROUP BY so.id, so.shop_name, so.shop_address, so.shop_phone, u.phone, so.shop_latitude, so.shop_longitude, so.approval_status
    ");
    $shops = $stmt->fetchAll();

    $lat = InputValidator::validateLatitude($_GET['lat'] ?? null);
    $lng = InputValidator::validateLongitude($_GET['lng'] ?? null);

    if($lat !== null && $lng !== null){
        foreach($shops as &$s){
            $s['distance_km'] = ($s['shop_latitude'] !== null && $s['shop_longitude'] !== null) ? round(haversine($lat,$lng, (float)$s['shop_latitude'], (float)$s['shop_longitude']),2) : null;
        }
        usort($shops, function($a,$b){
            if($a['distance_km']===null) return 1;
            if($b['distance_km']===null) return -1;
            return $a['distance_km'] <=> $b['distance_km'];
        });
    }

    echo json_encode(['success' => true, 'shops' => $shops]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>


