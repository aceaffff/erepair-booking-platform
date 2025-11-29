<?php
/**
 * API for shop items CRUD operations
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/InputValidator.php';

// Sanitization functions
function sanitizeItemName($name) {
    // Remove dangerous characters that could be used for SQL injection
    // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, and parentheses
    $name = trim($name);
    $name = preg_replace('/[<>{}[\]();\'"`\\/|&*%$#@~^!]/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return $name;
}

function sanitizeItemDescription($description) {
    // Remove dangerous characters from description
    // Allow letters, numbers, spaces, hyphens, apostrophes, periods, commas, parentheses, and newlines
    $description = trim($description);
    $description = preg_replace('/[<>{}[\]();\'"`\\/|&*%$#@~^!]/', '', $description);
    $description = preg_replace('/\s+/', ' ', $description);
    return $description;
}

function sanitizeItemCategory($category) {
    // Remove dangerous characters from category
    // Allow letters, numbers, spaces, hyphens, apostrophes, periods, and commas
    $category = trim($category);
    $category = preg_replace('/[<>{}[\]();\'"`\\/|&*%$#@~^!]/', '', $category);
    $category = preg_replace('/\s+/', ' ', $category);
    return $category;
}

// Get authentication
function auth_shop_owner($db) {
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if($token === null){ 
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>NOW()");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if(!$u || $u['role']!=='shop_owner'){ 
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    return $u;
}

try {
    $db = (new Database())->getConnection();
    $user = auth_shop_owner($db);
    
    // Auto-create shop_items table if it doesn't exist
    try {
        $db->query("SELECT 1 FROM shop_items LIMIT 1");
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        $createTableSQL = "CREATE TABLE IF NOT EXISTS shop_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shop_owner_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            stock_quantity INT DEFAULT 0,
            category VARCHAR(100) DEFAULT 'general',
            image_url VARCHAR(500),
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (shop_owner_id) REFERENCES shop_owners(id) ON DELETE CASCADE,
            INDEX idx_shop_owner (shop_owner_id),
            INDEX idx_available (is_available),
            INDEX idx_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $db->exec($createTableSQL);
            error_log("Auto-created shop_items table");
        } catch (PDOException $createError) {
            error_log("Failed to create shop_items table: " . $createError->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database setup error. Please contact administrator.']);
            exit;
        }
    }
    
    // Get shop owner's shop_owner_id
    $stmt = $db->prepare("SELECT id FROM shop_owners WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $shopOwner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shopOwner) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Shop owner profile not found']);
        exit;
    }
    
    $shop_owner_id = $shopOwner['id'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get all items for this shop
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
                    created_at,
                    updated_at
                FROM shop_items
                WHERE shop_owner_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$shop_owner_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'items' => $items]);
            break;
            
        case 'POST':
            // Create or update item (handle multipart/form-data for file uploads)
            $action = $_POST['action'] ?? 'create'; // 'create' or 'update'
            $item_id = intval($_POST['id'] ?? 0);
            
            // Sanitize inputs
            $item_name = sanitizeItemName($_POST['item_name'] ?? '');
            $description = sanitizeItemDescription($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
            $category = sanitizeItemCategory($_POST['category'] ?? 'general');
            $is_available = isset($_POST['is_available']) ? (bool)$_POST['is_available'] : true;
            
            // Validate inputs
            if(empty($item_name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item name is required']);
                exit;
            }
            
            if($price < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Price cannot be negative']);
                exit;
            }
            
            if($stock_quantity < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Stock quantity cannot be negative']);
                exit;
            }
            
            if(strlen($item_name) > 255) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item name must be 255 characters or less']);
                exit;
            }
            
            // If action is 'update', handle as update
            if ($action === 'update') {
                if (!$item_id) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Item ID is required for update']);
                    exit;
                }
                
                // Verify ownership
                $stmt = $db->prepare("SELECT id, image_url FROM shop_items WHERE id = ? AND shop_owner_id = ?");
                $stmt->execute([$item_id, $shop_owner_id]);
                $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$existingItem) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Item not found or unauthorized']);
                    exit;
                }
                
                // Handle image upload for update
                $image_url = $existingItem['image_url']; // Keep existing image by default
                
                // If keep_image is set, keep existing image
                if (isset($_POST['keep_image']) && $_POST['keep_image'] === '1') {
                    $image_url = $existingItem['image_url'];
                } 
                // If new image is uploaded, replace existing
                elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['image'];
                    
                    // Validate file type
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    
                    if (!in_array($mimeType, $allowedTypes)) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
                        exit;
                    }
                    
                    // Validate file size (max 5MB)
                    if ($file['size'] > 5 * 1024 * 1024) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 5MB']);
                        exit;
                    }
                    
                    // Delete old image if exists
                    if ($existingItem['image_url'] && !empty($existingItem['image_url']) && !str_starts_with($existingItem['image_url'], 'http')) {
                        $oldImagePath = __DIR__ . '/../../frontend/' . $existingItem['image_url'];
                        if (file_exists($oldImagePath)) {
                            @unlink($oldImagePath);
                        }
                    }
                    
                    // Create upload directory
                    $uploadDir = __DIR__ . '/../../frontend/uploads/shop_items';
                    if (!is_dir($uploadDir)) {
                        if (!@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                            http_response_code(500);
                            echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
                            exit;
                        }
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'item_' . uniqid() . '_' . time() . '.' . $extension;
                    $destination = $uploadDir . '/' . $filename;
                    
                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
                        exit;
                    }
                    
                    @chmod($destination, 0644);
                    $image_url = 'uploads/shop_items/' . $filename;
                }
                
                // Update the item
                $stmt = $db->prepare("
                    UPDATE shop_items SET
                        item_name = ?,
                        description = ?,
                        price = ?,
                        stock_quantity = ?,
                        category = ?,
                        image_url = ?,
                        is_available = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND shop_owner_id = ?
                ");
                
                $stmt->execute([
                    $item_name,
                    $description,
                    $price,
                    $stock_quantity,
                    $category,
                    $image_url,
                    $is_available,
                    $item_id,
                    $shop_owner_id
                ]);
                
                // Fetch updated item
                $stmt = $db->prepare("SELECT * FROM shop_items WHERE id = ?");
                $stmt->execute([$item_id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'item' => $item]);
                break;
            }
            
            // Otherwise, create new item
            
            // Validate inputs
            if (empty($item_name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item name is required']);
                exit;
            }
            
            if($price <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Valid price (greater than 0) is required']);
                exit;
            }
            
            if($price < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Price cannot be negative']);
                exit;
            }
            
            if($stock_quantity < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Stock quantity cannot be negative']);
                exit;
            }
            
            if(strlen($item_name) > 255) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item name must be 255 characters or less']);
                exit;
            }
            
            // Handle image upload
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
                    exit;
                }
                
                // Validate file size (max 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 5MB']);
                    exit;
                }
                
                // Create upload directory
                $uploadDir = __DIR__ . '/../../frontend/uploads/shop_items';
                if (!is_dir($uploadDir)) {
                    if (!@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
                        exit;
                    }
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'item_' . uniqid() . '_' . time() . '.' . $extension;
                $destination = $uploadDir . '/' . $filename;
                
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
                    exit;
                }
                
                @chmod($destination, 0644);
                $image_url = 'uploads/shop_items/' . $filename;
            }
            
            $stmt = $db->prepare("
                INSERT INTO shop_items (
                    shop_owner_id,
                    item_name,
                    description,
                    price,
                    stock_quantity,
                    category,
                    image_url,
                    is_available
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $shop_owner_id,
                $item_name,
                $description,
                $price,
                $stock_quantity,
                $category,
                $image_url,
                $is_available
            ]);
            
            $itemId = $db->lastInsertId();
            
            // Fetch the created item
            $stmt = $db->prepare("SELECT * FROM shop_items WHERE id = ?");
            $stmt->execute([$itemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'item' => $item]);
            break;
            
        case 'PUT':
            // Update existing item (handle multipart/form-data for file uploads)
            // For PUT requests with multipart/form-data, PHP doesn't populate $_POST automatically
            // So we get the ID from the URL query parameter
            $item_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
            
            if (!$item_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item ID is required']);
                exit;
            }
            
            // Verify ownership
            $stmt = $db->prepare("SELECT id, image_url FROM shop_items WHERE id = ? AND shop_owner_id = ?");
            $stmt->execute([$item_id, $shop_owner_id]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existingItem) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Item not found or unauthorized']);
                exit;
            }
            
            // For PUT with multipart/form-data, PHP may not populate $_POST
            // Try to get from $_POST first, then parse manually if needed
            $item_name = '';
            $description = '';
            $price = 0;
            $stock_quantity = 0;
            $category = 'general';
            $is_available = true;
            
            // Try $_POST first (works if PHP populates it)
            if (!empty($_POST)) {
                $item_name = sanitizeItemName($_POST['item_name'] ?? '');
                $description = sanitizeItemDescription($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
                $category = sanitizeItemCategory($_POST['category'] ?? 'general');
                $is_available = isset($_POST['is_available']) ? (bool)$_POST['is_available'] : true;
            } else {
                // If $_POST is empty, manually parse multipart/form-data
                // This is a fallback - normally PHP should populate $_POST for multipart/form-data
                $rawInput = file_get_contents('php://input');
                if (!empty($rawInput)) {
                    // For multipart/form-data, we need to parse it manually
                    // This is complex, so we'll use a workaround: read from $_REQUEST as fallback
                    $item_name = sanitizeItemName($_REQUEST['item_name'] ?? '');
                    $description = sanitizeItemDescription($_REQUEST['description'] ?? '');
                    $price = floatval($_REQUEST['price'] ?? 0);
                    $stock_quantity = intval($_REQUEST['stock_quantity'] ?? 0);
                    $category = sanitizeItemCategory($_REQUEST['category'] ?? 'general');
                    $is_available = isset($_REQUEST['is_available']) ? (bool)$_REQUEST['is_available'] : true;
                }
            }
            
            // Validate inputs
            if (empty($item_name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item name is required']);
                exit;
            }
            
            if($price <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Valid price (greater than 0) is required']);
                exit;
            }
            
            if($price < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Price cannot be negative']);
                exit;
            }
            
            if($stock_quantity < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Stock quantity cannot be negative']);
                exit;
            }
            
            if(strlen($item_name) > 255) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item name must be 255 characters or less']);
                exit;
            }
            
            // Handle image upload
            $image_url = $existingItem['image_url']; // Keep existing image by default
            
            // If keep_image is set, keep existing image
            if (isset($_POST['keep_image']) && $_POST['keep_image'] === '1') {
                $image_url = $existingItem['image_url'];
            } 
            // If new image is uploaded, replace existing
            elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
                    exit;
                }
                
                // Validate file size (max 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 5MB']);
                    exit;
                }
                
                // Delete old image if exists
                if ($existingItem['image_url'] && !empty($existingItem['image_url']) && !str_starts_with($existingItem['image_url'], 'http')) {
                    $oldImagePath = __DIR__ . '/../../frontend/' . $existingItem['image_url'];
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }
                
                // Create upload directory
                $uploadDir = __DIR__ . '/../../frontend/uploads/shop_items';
                if (!is_dir($uploadDir)) {
                    if (!@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
                        exit;
                    }
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'item_' . uniqid() . '_' . time() . '.' . $extension;
                $destination = $uploadDir . '/' . $filename;
                
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
                    exit;
                }
                
                @chmod($destination, 0644);
                $image_url = 'uploads/shop_items/' . $filename;
            }
            
            $stmt = $db->prepare("
                UPDATE shop_items SET
                    item_name = ?,
                    description = ?,
                    price = ?,
                    stock_quantity = ?,
                    category = ?,
                    image_url = ?,
                    is_available = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND shop_owner_id = ?
            ");
            
            $stmt->execute([
                $item_name,
                $description,
                $price,
                $stock_quantity,
                $category,
                $image_url,
                $is_available,
                $item_id,
                $shop_owner_id
            ]);
            
            // Fetch updated item
            $stmt = $db->prepare("SELECT * FROM shop_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'item' => $item]);
            break;
            
        case 'DELETE':
            // Delete item
            $item_id = intval($_GET['id'] ?? 0);
            
            if (!$item_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Item ID is required']);
                exit;
            }
            
            // Verify ownership
            $stmt = $db->prepare("SELECT id FROM shop_items WHERE id = ? AND shop_owner_id = ?");
            $stmt->execute([$item_id, $shop_owner_id]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Item not found or unauthorized']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM shop_items WHERE id = ? AND shop_owner_id = ?");
            $stmt->execute([$item_id, $shop_owner_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>

