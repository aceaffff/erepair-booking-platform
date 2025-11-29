<?php
require_once __DIR__ . '/../../config/database.php';

function sendResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is authenticated and is admin
$token = $_COOKIE['auth_token'] ?? '';
if (empty($token)) {
    sendResponse(false, 'Authentication required');
}

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    sendResponse(false, 'Database connection failed');
}

try {
    // Verify token and admin role
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, u.role 
        FROM users u
        INNER JOIN sessions s ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        sendResponse(false, 'Admin access required');
    }
} catch (PDOException $e) {
    sendResponse(false, 'Authentication failed');
}

// Check if file was uploaded
if (!isset($_FILES['logo'])) {
    sendResponse(false, 'No file uploaded');
}

if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
        UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
        UPLOAD_ERR_PARTIAL => 'File upload was interrupted',
        UPLOAD_ERR_NO_FILE => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    $errorMessage = $errorMessages[$_FILES['logo']['error']] ?? 'Unknown upload error';
    sendResponse(false, 'Upload error: ' . $errorMessage);
}

$file = $_FILES['logo'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$fileType = mime_content_type($file['tmp_name']);
if (!in_array($fileType, $allowedTypes)) {
    sendResponse(false, 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
}

// Validate file size (max 2MB)
$maxSize = 2 * 1024 * 1024; // 2MB
if ($file['size'] > $maxSize) {
    sendResponse(false, 'File size too large. Maximum size is 2MB.');
}

// Create uploads directory if it doesn't exist
$uploadDir = __DIR__ . '/../../uploads/logos/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        sendResponse(false, 'Failed to create upload directory');
    }
}

// Check if directory is writable
if (!is_writable($uploadDir)) {
    sendResponse(false, 'Upload directory is not writable');
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'admin_logo_' . time() . '_' . uniqid() . '.' . $extension;
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    $error = error_get_last();
    sendResponse(false, 'Failed to save file: ' . ($error['message'] ?? 'Unknown error'));
}

// Generate URL for the uploaded file (relative to frontend pages)
$logoUrl = '../backend/uploads/logos/' . $filename;

// Update database with logo URL
try {
    // First, check if logo_url column exists, if not create it
    $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE 'logo_url'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN logo_url VARCHAR(255) NULL");
    }
    
    // Update admin user's logo URL
    $stmt = $db->prepare("UPDATE users SET logo_url = ? WHERE id = ? AND role = 'admin'");
    $stmt->execute([$logoUrl, $user['id']]);
    
    if ($stmt->rowCount() > 0) {
        sendResponse(true, 'Logo uploaded successfully', ['logo_url' => $logoUrl]);
    } else {
        sendResponse(false, 'Failed to update logo in database');
    }
} catch (PDOException $e) {
    // Clean up uploaded file if database update fails
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    sendResponse(false, 'Database error: ' . $e->getMessage());
}
?>
