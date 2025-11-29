<?php
/**
 * Enhanced Booking Creation with Device Photo Upload
 * Supports the new workflow: pending_review → diagnosis → customer confirmation → approval
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
    $stmt = $db->prepare("SELECT u.id, u.role, u.email_verified FROM users u INNER JOIN sessions s ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] !== 'customer') {
        ResponseHelper::unauthorized('Unauthorized');
    }
    return $user;
}

function validate_and_upload_device_photo(): ?string {
    if (!isset($_FILES['device_photo']) || $_FILES['device_photo']['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // Photo is optional
    }
    
    $file = $_FILES['device_photo'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        ResponseHelper::error('Photo upload failed', 400);
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        ResponseHelper::error('Invalid file type. Only JPG, PNG, and WEBP are allowed', 400);
    }
    
    // Validate file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        ResponseHelper::error('File too large. Maximum size is 2MB', 400);
    }
    
    // Create upload directory
    $uploadDir = __DIR__ . '/../uploads/device_photos';
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            ResponseHelper::serverError('Failed to create upload directory');
        }
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'device_' . uniqid() . '_' . time() . '.' . $extension;
    $destination = $uploadDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        ResponseHelper::serverError('Failed to save uploaded file');
    }
    
    @chmod($destination, 0644);
    return 'uploads/device_photos/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ResponseHelper::methodNotAllowed();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $user = auth_customer($db);

    if (!$user['email_verified']) {
        ResponseHelper::forbidden('Please verify your email to make a booking');
    }

    // Parse JSON input (for non-file fields)
    $inputJson = $_POST['booking_data'] ?? null;
    if ($inputJson) {
        $input = json_decode($inputJson, true);
    } else {
        // Fallback to regular POST data
        $input = $_POST;
    }

    // Validate required fields (date and time_slot are no longer required - schedule will be selected during confirmation)
    $service = InputValidator::validateString($input['service'] ?? '', 1, 255);
    $deviceType = InputValidator::validateString($input['device_type'] ?? '', 1, 100);
    $issueDescription = InputValidator::validateString($input['issue_description'] ?? '', 1, 1000);
    $shopOwnerId = InputValidator::validateId($input['shop_owner_id'] ?? 0);
    $description = trim($input['description'] ?? ''); // Optional additional notes
    $rebookOf = InputValidator::validateId($input['rebook_of'] ?? 0);
    
    if ($service === null || $deviceType === null || $issueDescription === null || $shopOwnerId === null) {
        ResponseHelper::validationError('All required fields must be valid: shop, service, device type, and issue description');
    }
    
    // Additional security: Remove dangerous characters that could be used for SQL injection or XSS
    // Block: < > { } [ ] ( ) ; ' " ` \ / | & * % $ # @ ~ ^
    $dangerousChars = ['<', '>', '{', '}', '[', ']', '(', ')', ';', "'", '"', '`', '\\', '/', '|', '&', '*', '%', '$', '#', '@', '~', '^'];
    
    // Sanitize device type (allow only letters, numbers, spaces, and safe punctuation)
    $deviceType = preg_replace('/[^a-zA-Z0-9\s.\-_]/', '', $deviceType);
    
    // Sanitize text fields (allow letters, numbers, spaces, and safe punctuation)
    $issueDescription = preg_replace('/[<>{}[\]();\'"`\\\\\/|&*%$#@~^]/', '', $issueDescription);
    if ($description) {
        $description = preg_replace('/[<>{}[\]();\'"`\\\\\/|&*%$#@~^]/', '', $description);
    }
    
    // Re-validate after sanitization
    if (strlen($deviceType) < 1 || strlen($deviceType) > 100) {
        ResponseHelper::validationError('Device type must be between 1 and 100 characters');
    }
    if (strlen($issueDescription) < 1 || strlen($issueDescription) > 1000) {
        ResponseHelper::validationError('Issue description must be between 1 and 1000 characters');
    }
    
    // Handle device photo upload
    $devicePhoto = validate_and_upload_device_photo();
    
    // scheduled_at will be NULL initially - will be set when customer confirms booking
    $scheduledAt = null;
    
    // Validate shop owner exists and is approved
    $shopStmt = $db->prepare('SELECT id, shop_name, approval_status FROM shop_owners WHERE id = ?');
    $shopStmt->execute([$shopOwnerId]);
    $shop = $shopStmt->fetch();
    
    if (!$shop || ($shop['approval_status'] ?? 'pending') !== 'approved') {
        ResponseHelper::error('Selected shop is not available', 400);
    }
    
    // Get or create repair_shop entry
    $repairShopChk = $db->prepare('SELECT id FROM repair_shops WHERE owner_id = ?');
    $repairShopChk->execute([$shopOwnerId]);
    $repairShop = $repairShopChk->fetch();
    
    if (!$repairShop) {
        // Auto-create repair_shop if it doesn't exist
        $createShop = $db->prepare('INSERT INTO repair_shops (name, address, phone, owner_id) VALUES (?, ?, ?, ?)');
        $createShop->execute([
            $shop['shop_name'] ?? 'Shop',
            '',
            '',
            $shopOwnerId
        ]);
        $repairShopId = $db->lastInsertId();
    } else {
        $repairShopId = $repairShop['id'];
    }
    
    // No availability check needed here - schedule will be selected during confirmation
    
    // Get service details for pricing from shop_services
    $serviceStmt = $db->prepare('SELECT id, service_name, price FROM shop_services WHERE shop_owner_id = ? AND service_name = ?');
    $serviceStmt->execute([$shopOwnerId, $service]);
    $shopServiceData = $serviceStmt->fetch();
    
    if (!$shopServiceData) {
        ResponseHelper::error('Service not found for this shop', 400);
    }
    
    // Find or create corresponding entry in services table (for foreign key)
    $servicesTableStmt = $db->prepare('SELECT id FROM services WHERE shop_id = ? AND name = ?');
    $servicesTableStmt->execute([$repairShopId, $service]);
    $serviceRow = $servicesTableStmt->fetch();
    
    if (!$serviceRow) {
        // Create service entry in services table if it doesn't exist
        $createServiceStmt = $db->prepare('
            INSERT INTO services (shop_id, name, duration_minutes, price, description, is_active)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $createServiceStmt->execute([
            $repairShopId,
            $service,
            60, // Default duration
            $shopServiceData['price'],
            '', // No description
            1 // Active
        ]);
        $serviceId = $db->lastInsertId();
    } else {
        $serviceId = $serviceRow['id'];
    }
    
    // Prepare notes JSON with service info and rebook reference
    $notesData = [
        'service' => $service,
        'device_type' => $deviceType,
        'issue_description' => $issueDescription
    ];
    
    if ($rebookOf !== null) {
        $notesData['rebook_of'] = $rebookOf;
    }
    
    $notesJson = json_encode($notesData);

    // Insert booking with pending_review status
    $stmt = $db->prepare('
        INSERT INTO bookings (
            customer_id, shop_id, service_id, 
            device_type, device_issue_description, device_photo,
            device_description, status, scheduled_at, 
            duration_minutes, total_price, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->execute([
        $user['id'], 
        $repairShopId,
        $serviceId,
        $deviceType,
        $issueDescription,
        $devicePhoto,
        $description ?: $issueDescription, // Fallback to issue description
        'pending_review', 
        $scheduledAt, 
        60, // Default 1 hour duration
        $shopServiceData['price'], // Initial estimate, will be updated after diagnosis
        $notesJson
    ]);
    
    $bookingId = $db->lastInsertId();
    
    // Notify shop owner about new booking requiring diagnosis
    try {
        $customerStmt = $db->prepare("SELECT name FROM users WHERE id = ?");
        $customerStmt->execute([$user['id']]);
        $customer = $customerStmt->fetch();
        
        $ownerStmt = $db->prepare("SELECT user_id FROM shop_owners WHERE id = ?");
        $ownerStmt->execute([$shopOwnerId]);
        $owner = $ownerStmt->fetch();
        
        if ($customer && $owner) {
            NotificationHelper::notifyDiagnosisRequired(
                $db,
                (int)$owner['user_id'],
                $customer['name'],
                $deviceType
            );
        }
    } catch (Exception $e) {
        error_log("Error creating diagnosis notification: " . $e->getMessage());
    }

    ResponseHelper::success(
        'Booking submitted for diagnosis. You will be notified when the shop provides a quotation.',
        ['booking_id' => $bookingId]
    );
} catch (Exception $e) {
    ResponseHelper::serverError('Server error', $e->getMessage());
}
?>
