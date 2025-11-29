<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/email.php';
require_once '../utils/DBTransaction.php';
require_once '../utils/InputValidator.php';
require_once '../utils/DocumentAPIValidator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Handle file uploads
$upload_dir = '../uploads/shop_owners/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Validate and sanitize form data
$name = InputValidator::validateString($_POST['name'] ?? '', 2, 100);
$email = InputValidator::validateEmail($_POST['email'] ?? '');
$phone = InputValidator::validatePhilippineMobile($_POST['phone'] ?? '', false);
$password = InputValidator::validatePassword($_POST['password'] ?? '', 6);
$shop_name = InputValidator::validateString($_POST['shop_name'] ?? '', 2, 100);
$shop_address = InputValidator::validateString($_POST['shop_address'] ?? '', 5, 500);
$shop_latitude = InputValidator::validateLatitude($_POST['latitude'] ?? null);
$shop_longitude = InputValidator::validateLongitude($_POST['longitude'] ?? null);

// Check required fields
$required = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'password' => $password,
    'shop_name' => $shop_name,
    'shop_address' => $shop_address
];

foreach ($required as $field => $value) {
    if ($value === null) {
        http_response_code(400);
        echo json_encode(['error' => "Field '$field' is invalid or required"]);
        exit;
    }
}

// Handle ID type and details
$id_type = InputValidator::validateString($_POST['id_type'] ?? '', 1, 50);
$id_number = InputValidator::validateString($_POST['id_number'] ?? '', 1, 100);
$id_expiry_date = !empty($_POST['id_expiry_date']) ? $_POST['id_expiry_date'] : null;

if ($id_type === null || empty($id_type)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID type is required']);
    exit;
}

if ($id_number === null || empty($id_number)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID number is required']);
    exit;
}

// Validate expiry date format if provided
if ($id_expiry_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $id_expiry_date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID expiry date format']);
    exit;
}

// Handle file uploads
$id_file_front = null;
$id_file_back = null;
$business_permit_file = null;
$selfie_file = null;

// Validate file uploads
$id_file_front_data = InputValidator::validateFileUpload($_FILES['id_file_front'] ?? null);
$id_file_back_data = InputValidator::validateFileUpload($_FILES['id_file_back'] ?? null);
$permit_file_data = InputValidator::validateFileUpload($_FILES['business_permit_file'] ?? null);
$selfie_file_data = InputValidator::validateFileUpload($_FILES['selfie_file'] ?? null);

if ($id_file_front_data === null || $id_file_back_data === null || $permit_file_data === null || $selfie_file_data === null) {
    http_response_code(400);
    echo json_encode(['error' => 'All required documents are needed: ID front, ID back, business permit, and selfie with ID']);
    exit;
}

// Validate selfie is an image (not PDF)
if (strpos($selfie_file_data['type'], 'image/') !== 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Selfie must be an image file (JPG, PNG)']);
    exit;
}

// Validate documents before moving files
// Check if files are images (PDFs are allowed but won't be validated by image analysis)
$isIdFrontImage = strpos($id_file_front_data['type'], 'image/') === 0;
$isIdBackImage = strpos($id_file_back_data['type'], 'image/') === 0;
$isPermitImage = strpos($permit_file_data['type'], 'image/') === 0;

// Validate ID front (if image)
if ($isIdFrontImage) {
    $idFrontValidation = DocumentAPIValidator::validateIdDocument($id_file_front_data['tmp_name']);
    if (!$idFrontValidation['valid']) {
        http_response_code(400);
        echo json_encode(['error' => 'ID Front: ' . $idFrontValidation['error']]);
        exit;
    }
}

// Validate ID back (if image)
if ($isIdBackImage) {
    $idBackValidation = DocumentAPIValidator::validateIdDocument($id_file_back_data['tmp_name']);
    if (!$idBackValidation['valid']) {
        http_response_code(400);
        echo json_encode(['error' => 'ID Back: ' . $idBackValidation['error']]);
        exit;
    }
}

// Validate business permit (if image)
if ($isPermitImage) {
    $permitValidation = DocumentAPIValidator::validateBusinessPermit($permit_file_data['tmp_name']);
    if (!$permitValidation['valid']) {
        http_response_code(400);
        echo json_encode(['error' => 'Business Permit: ' . $permitValidation['error']]);
        exit;
    }
}

// Generate secure filenames and move files
$id_file_front = InputValidator::generateSecureFilename($id_file_front_data['name'], 'id_front_');
$id_file_back = InputValidator::generateSecureFilename($id_file_back_data['name'], 'id_back_');
$business_permit_file = InputValidator::generateSecureFilename($permit_file_data['name'], 'permit_');
$selfie_file = InputValidator::generateSecureFilename($selfie_file_data['name'], 'selfie_');

if (!move_uploaded_file($id_file_front_data['tmp_name'], $upload_dir . $id_file_front) ||
    !move_uploaded_file($id_file_back_data['tmp_name'], $upload_dir . $id_file_back) ||
    !move_uploaded_file($permit_file_data['tmp_name'], $upload_dir . $business_permit_file) ||
    !move_uploaded_file($selfie_file_data['tmp_name'], $upload_dir . $selfie_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload files']);
    exit;
}


try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }
    
    // Hash password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate 6-digit verification code with 5-minute expiry
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Check if selfie_file column exists, if not add it
    try {
        $checkColumn = $db->query("SHOW COLUMNS FROM shop_owners LIKE 'selfie_file'");
        if($checkColumn->rowCount() === 0) {
            // Column doesn't exist, add it
            $db->exec("ALTER TABLE shop_owners ADD COLUMN selfie_file VARCHAR(500) NULL AFTER id_file_back");
        }
    } catch(Exception $e) {
        // If SHOW COLUMNS fails, try to add the column anyway (might already exist)
        try {
            $db->exec("ALTER TABLE shop_owners ADD COLUMN selfie_file VARCHAR(500) NULL");
        } catch(Exception $e2) {
            // Column might already exist, continue
        }
    }
    
    $user_id = DBTransaction::execute($db, function($pdo) use ($name, $email, $phone, $hashed_password, $code, $shop_name, $shop_address, $shop_latitude, $shop_longitude, $id_type, $id_number, $id_expiry_date, $id_file_front, $id_file_back, $business_permit_file, $selfie_file) {
        // Insert user (without verification_code in users table)
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, phone, password, role, email_verified, status) 
            VALUES (?, ?, ?, ?, 'shop_owner', FALSE, 'pending')
        ");
        
        $stmt->execute([
            $name,
            $email,
            $phone,
            $hashed_password
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Insert verification code into email_verifications table using MySQL's NOW() for timezone consistency
        $stmt = $pdo->prepare("
            INSERT INTO email_verifications (user_id, email, verification_code, expires_at) 
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
        ");
        $stmt->execute([$user_id, $email, $code]);
        
        // Insert shop owner details (shop_phone set to NULL since we're using phone field instead)
        // Check if new columns exist, otherwise use old id_file column for backward compatibility
        $stmt = $pdo->query("SHOW COLUMNS FROM shop_owners LIKE 'id_type'");
        $hasNewColumns = $stmt->fetch() !== false;
        
        if ($hasNewColumns) {
            // Check if id_number column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM shop_owners LIKE 'id_number'");
            $hasIdNumber = $stmt->fetch() !== false;
            
            if ($hasIdNumber) {
                // Check if selfie_file column exists
                $stmt = $pdo->query("SHOW COLUMNS FROM shop_owners LIKE 'selfie_file'");
                $hasSelfieFile = $stmt->fetch() !== false;
                
                if ($hasSelfieFile) {
                    $stmt = $pdo->prepare("
                        INSERT INTO shop_owners (user_id, shop_name, shop_address, shop_phone, shop_latitude, shop_longitude, id_type, id_number, id_expiry_date, id_file_front, id_file_back, selfie_file, business_permit_file, approval_status) 
                        VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    
                    $stmt->execute([
                        $user_id,
                        $shop_name,
                        $shop_address,
                        $shop_latitude,
                        $shop_longitude,
                        $id_type,
                        $id_number,
                        $id_expiry_date,
                        $id_file_front,
                        $id_file_back,
                        $selfie_file,
                        $business_permit_file
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO shop_owners (user_id, shop_name, shop_address, shop_phone, shop_latitude, shop_longitude, id_type, id_number, id_expiry_date, id_file_front, id_file_back, business_permit_file, approval_status) 
                        VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    
                    $stmt->execute([
                        $user_id,
                        $shop_name,
                        $shop_address,
                        $shop_latitude,
                        $shop_longitude,
                        $id_type,
                        $id_number,
                        $id_expiry_date,
                        $id_file_front,
                        $id_file_back,
                        $business_permit_file
                    ]);
                }
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO shop_owners (user_id, shop_name, shop_address, shop_phone, shop_latitude, shop_longitude, id_type, id_file_front, id_file_back, business_permit_file, approval_status) 
                    VALUES (?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                
                $stmt->execute([
                    $user_id,
                    $shop_name,
                    $shop_address,
                    $shop_latitude,
                    $shop_longitude,
                    $id_type,
                    $id_file_front,
                    $id_file_back,
                    $business_permit_file
                ]);
            }
        } else {
            // Fallback for old database structure - use id_file_front as id_file
            $stmt = $pdo->prepare("
                INSERT INTO shop_owners (user_id, shop_name, shop_address, shop_phone, shop_latitude, shop_longitude, id_file, business_permit_file, approval_status) 
                VALUES (?, ?, ?, NULL, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $user_id,
                $shop_name,
                $shop_address,
                $shop_latitude,
                $shop_longitude,
                $id_file_front, // Use front as main ID file for backward compatibility
                $business_permit_file
            ]);
        }
        
        return $user_id;
    });
    
    // Send verification code email
    $emailService = new EmailService();
    $emailService->sendVerificationCodeEmail($email, $name, $code);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Shop Owner registered, pending admin approval and email verification.',
        'user_id' => $user_id
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        DBTransaction::cleanup($db);
    }
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>

