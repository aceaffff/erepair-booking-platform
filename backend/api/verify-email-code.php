<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Turn off error display to prevent HTML in JSON response
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request body']);
        exit;
    }
    
    $data = json_decode($raw, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON in request body: ' . json_last_error_msg()]);
        exit;
    }

    $email = trim($data['email'] ?? '');
    $code = trim($data['code'] ?? '');
    
    // Validate email format
    if ($email === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Email is required']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }
    
    // Extract only digits from code (in case of any formatting issues)
    $code = preg_replace('/[^0-9]/', '', $code);
    
    if ($code === '' || !preg_match('/^\d{6}$/', $code)) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid 6-digit code is required. You entered: ' . ($code ?: 'empty')]);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }

    // Load user
    $stmt = $db->prepare('SELECT id, name, email, email_verified FROM users WHERE email = ?');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query preparation failed']);
        exit;
    }
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    if ((bool)$user['email_verified'] === true) {
        echo json_encode(['success' => true, 'message' => 'Email already verified.']);
        exit;
    }

    // Load active verification code from email_verifications table
    // Use ID DESC to get the most recently created code (more reliable than timestamp)
    $stmt = $db->prepare('
        SELECT id, verification_code, expires_at, verified_at, created_at
        FROM email_verifications 
        WHERE user_id = ? 
          AND verified_at IS NULL
          AND expires_at > NOW()
        ORDER BY id DESC 
        LIMIT 1
    ');
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query preparation failed']);
        exit;
    }
    $stmt->execute([$user['id']]);
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$verification) {
        http_response_code(400);
        echo json_encode(['error' => 'No active verification code found or the code has expired. Please request a new verification code.']);
        exit;
    }

    $storedCode = $verification['verification_code'];
    $expiresAt = strtotime($verification['expires_at']);
    $currentTime = time();

    // Log for debugging
    error_log("Verification attempt - Email: $email, User ID: {$user['id']}, Stored code: $storedCode, Entered code: $code, Expires at: {$verification['expires_at']} (timestamp: $expiresAt), Current time: $currentTime");

    // Check if code has expired
    if ($currentTime > $expiresAt) {
        http_response_code(400);
        echo json_encode(['error' => 'Verification code has expired. Please request a new code.']);
        exit;
    }

    // Compare codes (use hash_equals for timing-safe comparison)
    if (!hash_equals($storedCode, $code)) {
        http_response_code(400);
        error_log("Code mismatch - Stored: '$storedCode' (length: " . strlen($storedCode) . "), Entered: '$code' (length: " . strlen($code) . ")");
        echo json_encode(['error' => 'Invalid verification code. Please check the code and try again.']);
        exit;
    }

    // Mark verification as used and user as verified
    $db->beginTransaction();
    try {
        // Update email_verifications table
        $stmt = $db->prepare('UPDATE email_verifications SET verified_at = NOW() WHERE id = ?');
        $stmt->execute([$verification['id']]);
        
        // Update users table
        $stmt = $db->prepare('UPDATE users SET email_verified = TRUE, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$user['id']]);
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update verification status']);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully!',
        'user' => [
            'name' => $user['name'],
            'email' => $user['email']
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Verify email code error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>


