<?php
/**
 * API to get shop homepage details including items and ratings
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $shop_owner_id = $_GET['shop_id'] ?? null;
    
    if (!$shop_owner_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Shop ID is required']);
        exit;
    }
    
    $db = (new Database())->getConnection();
    
    // Get shop basic info - try with logo first, fallback without it
    try {
        $stmt = $db->prepare("
            SELECT 
                so.id,
                so.shop_name,
                so.shop_address,
                so.shop_phone,
                so.shop_latitude,
                so.shop_longitude,
                rs.logo,
                u.name as owner_name,
                u.email as owner_email,
                u.phone as owner_phone,
                u.avatar as owner_avatar_url,
                so.approval_status
            FROM shop_owners so
            LEFT JOIN repair_shops rs ON rs.owner_id = so.id
            LEFT JOIN users u ON u.id = so.user_id
            WHERE so.id = ? AND so.approval_status = 'approved'
        ");
        $stmt->execute([$shop_owner_id]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback if repair_shops table or logo column doesn't exist
        $stmt = $db->prepare("
            SELECT 
                so.id,
                so.shop_name,
                so.shop_address,
                so.shop_phone,
                so.shop_latitude,
                so.shop_longitude,
                u.name as owner_name,
                u.email as owner_email,
                u.phone as owner_phone,
                u.avatar as owner_avatar_url,
                so.approval_status
            FROM shop_owners so
            LEFT JOIN users u ON u.id = so.user_id
            WHERE so.id = ? AND so.approval_status = 'approved'
        ");
        $stmt->execute([$shop_owner_id]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($shop) {
            $shop['logo'] = null;
            if (!isset($shop['owner_avatar_url'])) {
                $shop['owner_avatar_url'] = null;
            }
        }
    }
    
    if (!$shop) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Shop not found']);
        exit;
    }
    
    // Get shop ratings
    $stmt = $db->prepare("
        SELECT 
            COALESCE(
                CASE 
                    WHEN SUM(sr.total_reviews) > 0 THEN SUM(sr.average_rating * sr.total_reviews) / SUM(sr.total_reviews)
                    ELSE 0
                END, 
                0
            ) as average_rating,
            COALESCE(SUM(sr.total_reviews), 0) as total_reviews
        FROM shop_owners so
        LEFT JOIN repair_shops rs ON rs.owner_id = so.id
        LEFT JOIN shop_ratings sr ON sr.shop_id = rs.id
        WHERE so.id = ?
        GROUP BY so.id
    ");
    $stmt->execute([$shop_owner_id]);
    $ratings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get shop services - handle missing columns gracefully
    try {
        // First try with all columns
        $stmt = $db->prepare("
            SELECT 
                id,
                service_name,
                price,
                description,
                is_active
            FROM shop_services
            WHERE shop_owner_id = ? AND is_active = TRUE
            ORDER BY id DESC
        ");
        $stmt->execute([$shop_owner_id]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback if is_active column doesn't exist
        try {
            $stmt = $db->prepare("
                SELECT 
                    id,
                    service_name,
                    price,
                    description
                FROM shop_services
                WHERE shop_owner_id = ?
                ORDER BY id DESC
            ");
            $stmt->execute([$shop_owner_id]);
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            // Last fallback - basic columns only
            try {
                $stmt = $db->prepare("
                    SELECT 
                        id,
                        service_name,
                        price
                    FROM shop_services
                    WHERE shop_owner_id = ?
                    ORDER BY id DESC
                ");
                $stmt->execute([$shop_owner_id]);
                $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e3) {
                $services = [];
            }
        }
    }
    
    // Ensure all services have required fields
    foreach ($services as &$service) {
        if (!isset($service['description'])) {
            $service['description'] = '';
        }
        if (!isset($service['is_active'])) {
            $service['is_active'] = true;
        }
    }
    unset($service); // Break reference
    
    // Get shop items - handle potential missing columns
    try {
        $stmt = $db->prepare("
            SELECT 
                id,
                item_name,
                description,
                price,
                stock_quantity,
                category,
                image_url,
                is_available,
                created_at
            FROM shop_items
            WHERE shop_owner_id = ?
            ORDER BY id DESC
        ");
        $stmt->execute([$shop_owner_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback if table or columns don't exist
        $items = [];
    }
    
    // Combine results
    $result = [
        'success' => true,
        'shop' => [
            'id' => $shop['id'],
            'shop_name' => $shop['shop_name'],
            'shop_address' => $shop['shop_address'],
            'shop_phone' => $shop['shop_phone'] ?? null,
            'owner_phone' => $shop['owner_phone'] ?? null,
            'latitude' => $shop['shop_latitude'],
            'longitude' => $shop['shop_longitude'],
            'logo' => $shop['logo'],
            'owner_name' => $shop['owner_name'],
            'owner_email' => $shop['owner_email'],
            'owner_avatar_url' => $shop['owner_avatar_url'] ?? null
        ],
        'ratings' => [
            'average_rating' => (float)($ratings['average_rating'] ?? 0),
            'total_reviews' => (int)($ratings['total_reviews'] ?? 0)
        ],
        'services' => $services,
        'items' => array_map(function($item) {
            return [
                'id' => $item['id'] ?? 0,
                'item_name' => $item['item_name'] ?? '',
                'description' => $item['description'] ?? '',
                'price' => (float)($item['price'] ?? 0),
                'stock_quantity' => (int)($item['stock_quantity'] ?? 0),
                'category' => $item['category'] ?? 'general',
                'image_url' => $item['image_url'] ?? '',
                'is_available' => (bool)($item['is_available'] ?? false),
                'created_at' => $item['created_at'] ?? ''
            ];
        }, $items)
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>

