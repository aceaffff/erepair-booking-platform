<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/email.php';
require_once '../utils/InputValidator.php';
require_once '../middleware/security.php';

// Apply security middleware
applySecurityMiddleware([
    'rate_limit_max' => 5, // Stricter rate limit for registration
    'rate_limit_window' => 60 // 5 attempts per hour
]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Handle file uploads directory
$upload_dir = '../uploads/customers/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Validate and sanitize form data (from FormData, not JSON)
$name = InputValidator::validateString($_POST['name'] ?? '', 2, 100);
$email = InputValidator::validateEmail($_POST['email'] ?? '');
$phone = InputValidator::validatePhilippineMobile($_POST['phone'] ?? '', false);
$password = InputValidator::validatePassword($_POST['password'] ?? '', 6);
$address = InputValidator::validateString($_POST['address'] ?? '', 0, 500, true);
$latitude = InputValidator::validateLatitude($_POST['latitude'] ?? null);
$longitude = InputValidator::validateLongitude($_POST['longitude'] ?? null);

// Check required fields
$required = [
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'password' => $password
];

foreach ($required as $field => $value) {
    if ($value === null) {
        http_response_code(400);
        echo json_encode(['error' => "Field '$field' is invalid or required"]);
        exit;
    }
}

// Handle file uploads
$id_file = null;
$selfie_file = null;

// Validate file uploads
$id_file_data = InputValidator::validateFileUpload($_FILES['id_file'] ?? null);
$selfie_file_data = InputValidator::validateFileUpload($_FILES['selfie_file'] ?? null);

if ($id_file_data === null || $selfie_file_data === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Both ID picture and selfie with ID are required']);
    exit;
}

// Validate selfie is an image (not PDF)
if (strpos($selfie_file_data['type'], 'image/') !== 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Selfie must be an image file (JPG or PNG)']);
    exit;
}

// Validate ID file type
$allowed_id_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
if (!in_array($id_file_data['type'], $allowed_id_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID file must be JPG, PNG, or PDF']);
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
    
    // Generate unique filenames
    $id_file_ext = pathinfo($id_file_data['name'], PATHINFO_EXTENSION);
    $selfie_file_ext = pathinfo($selfie_file_data['name'], PATHINFO_EXTENSION);
    $id_filename = uniqid('id_', true) . '.' . $id_file_ext;
    $selfie_filename = uniqid('selfie_', true) . '.' . $selfie_file_ext;
    
    // Move uploaded files
    $id_file_path = $upload_dir . $id_filename;
    $selfie_file_path = $upload_dir . $selfie_filename;
    
    if (!move_uploaded_file($id_file_data['tmp_name'], $id_file_path)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload ID file']);
        exit;
    }
    
    if (!move_uploaded_file($selfie_file_data['tmp_name'], $selfie_file_path)) {
        // Clean up ID file if selfie upload fails
        @unlink($id_file_path);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload selfie file']);
        exit;
    }
    
    // Hash password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate 6-digit verification code with 5-minute expiry
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Ensure id_file and selfie_file columns exist in users table
    // Use a more reliable method to check for column existence
    $stmt = $db->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $has_id_file = in_array('id_file', $columns);
    $has_selfie_file = in_array('selfie_file', $columns);
    
    // Automatically add columns if they don't exist
    if (!$has_id_file) {
        try {
            // Try to add after common columns in order of likelihood
            // Check for longitude first (most likely to exist based on schema)
            $has_longitude = in_array('longitude', $columns);
            if ($has_longitude) {
                $db->exec("ALTER TABLE users ADD COLUMN id_file VARCHAR(500) NULL AFTER longitude");
            } else {
                // Try latitude
                $has_latitude = in_array('latitude', $columns);
                if ($has_latitude) {
                    $db->exec("ALTER TABLE users ADD COLUMN id_file VARCHAR(500) NULL AFTER latitude");
                } else {
                    // Try address
                    $has_address = in_array('address', $columns);
                    if ($has_address) {
                        $db->exec("ALTER TABLE users ADD COLUMN id_file VARCHAR(500) NULL AFTER address");
                    } else {
                        // Try phone
                        $has_phone = in_array('phone', $columns);
                        if ($has_phone) {
                            $db->exec("ALTER TABLE users ADD COLUMN id_file VARCHAR(500) NULL AFTER phone");
                        } else {
                            // Add at the end if no common columns found
                            $db->exec("ALTER TABLE users ADD COLUMN id_file VARCHAR(500) NULL");
                        }
                    }
                }
            }
            error_log("Successfully added 'id_file' column to 'users' table.");
            // Refresh columns list
            $stmt = $db->query("SHOW COLUMNS FROM users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $has_id_file = in_array('id_file', $columns);
            if (!$has_id_file) {
                throw new Exception("Failed to verify id_file column was added");
            }
        } catch (PDOException $e) {
            // Check if error is because column already exists (duplicate column error)
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                error_log("Column 'id_file' already exists, continuing...");
                $has_id_file = true;
            } else {
                error_log("Error adding id_file column: " . $e->getMessage());
                // Clean up uploaded files
                @unlink($id_file_path);
                @unlink($selfie_file_path);
                http_response_code(500);
                echo json_encode(['error' => 'Database error: Could not add required column. Please contact support.']);
                exit;
            }
        } catch (Exception $e) {
            error_log("Error adding id_file column: " . $e->getMessage());
            // Clean up uploaded files
            @unlink($id_file_path);
            @unlink($selfie_file_path);
            http_response_code(500);
            echo json_encode(['error' => 'Database error: Could not add required column. Please contact support.']);
            exit;
        }
    }
    
    if (!$has_selfie_file) {
        try {
            // Refresh columns list in case id_file was just added
            $stmt = $db->query("SHOW COLUMNS FROM users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Add after id_file if it exists, otherwise try common columns
            if (in_array('id_file', $columns)) {
                $db->exec("ALTER TABLE users ADD COLUMN selfie_file VARCHAR(500) NULL AFTER id_file");
            } else {
                // Try to add after common columns in order of likelihood
                $has_longitude = in_array('longitude', $columns);
                if ($has_longitude) {
                    $db->exec("ALTER TABLE users ADD COLUMN selfie_file VARCHAR(500) NULL AFTER longitude");
                } else {
                    // Try latitude
                    $has_latitude = in_array('latitude', $columns);
                    if ($has_latitude) {
                        $db->exec("ALTER TABLE users ADD COLUMN selfie_file VARCHAR(500) NULL AFTER latitude");
                    } else {
                        // Try address
                        $has_address = in_array('address', $columns);
                        if ($has_address) {
                            $db->exec("ALTER TABLE users ADD COLUMN selfie_file VARCHAR(500) NULL AFTER address");
                        } else {
                            // Try phone
                            $has_phone = in_array('phone', $columns);
                            if ($has_phone) {
                                $db->exec("ALTER TABLE users ADD COLUMN selfie_file VARCHAR(500) NULL AFTER phone");
                            } else {
                                // Add at the end if no common columns found
                                $db->exec("ALTER TABLE users ADD COLUMN selfie_file VARCHAR(500) NULL");
                            }
                        }
                    }
                }
            }
            error_log("Successfully added 'selfie_file' column to 'users' table.");
            // Refresh columns list
            $stmt = $db->query("SHOW COLUMNS FROM users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $has_selfie_file = in_array('selfie_file', $columns);
            if (!$has_selfie_file) {
                throw new Exception("Failed to verify selfie_file column was added");
            }
        } catch (PDOException $e) {
            // Check if error is because column already exists (duplicate column error)
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                error_log("Column 'selfie_file' already exists, continuing...");
                $has_selfie_file = true;
            } else {
                error_log("Error adding selfie_file column: " . $e->getMessage());
                // Clean up uploaded files
                @unlink($id_file_path);
                @unlink($selfie_file_path);
                http_response_code(500);
                echo json_encode(['error' => 'Database error: Could not add required column. Please contact support.']);
                exit;
            }
        } catch (Exception $e) {
            error_log("Error adding selfie_file column: " . $e->getMessage());
            // Clean up uploaded files
            @unlink($id_file_path);
            @unlink($selfie_file_path);
            http_response_code(500);
            echo json_encode(['error' => 'Database error: Could not add required column. Please contact support.']);
            exit;
        }
    }
    
    // Final verification before INSERT - ensure columns exist
    $stmt = $db->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('id_file', $columns) || !in_array('selfie_file', $columns)) {
        // Clean up uploaded files
        @unlink($id_file_path);
        @unlink($selfie_file_path);
        http_response_code(500);
        echo json_encode(['error' => 'Database error: Required columns (id_file, selfie_file) are missing. Please run the database migration script or contact support.']);
        exit;
    }
    
    // Insert user with file information
    $stmt = $db->prepare("
        INSERT INTO users (name, email, phone, password, role, email_verified, status, address, latitude, longitude, id_file, selfie_file) 
        VALUES (?, ?, ?, ?, 'customer', FALSE, 'approved', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $email,
        $phone,
        $hashed_password,
        $address ?: null,
        $latitude ?: null,
        $longitude ?: null,
        $id_filename,
        $selfie_filename
    ]);
    
    $user_id = $db->lastInsertId();
    
    // Insert verification code into email_verifications table using MySQL's NOW() for timezone consistency
    $stmt = $db->prepare("
        INSERT INTO email_verifications (user_id, email, verification_code, expires_at) 
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
    ");
    $stmt->execute([$user_id, $email, $code]);
    
    // Send verification code email
    $emailService = new EmailService();
    $emailService->sendVerificationCodeEmail($email, $name, $code);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Customer registered, please verify email.',
        'user_id' => $user_id
    ]);
    
} catch (Exception $e) {
    // Clean up uploaded files on error
    if (isset($id_file_path) && file_exists($id_file_path)) {
        @unlink($id_file_path);
    }
    if (isset($selfie_file_path) && file_exists($selfie_file_path)) {
        @unlink($selfie_file_path);
    }
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
