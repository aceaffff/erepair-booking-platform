<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once '../utils/InputValidator.php';

// Validate JSON input
$input = InputValidator::validateJsonInput(file_get_contents('php://input'));
if ($input === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$email = InputValidator::validateEmail($input['email'] ?? '');
if ($email === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid email is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare('SELECT id, name, email FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always respond success to prevent user enumeration
    if (!$user) {
        http_response_code(200);
        echo json_encode(['success' => true]);
        exit;
    }

    // Ensure table exists (in case schema wasn't migrated)
    $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        code VARCHAR(12) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id),
        CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Generate 6-digit code and expiry
    $code = str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + 15 * 60);

    // Upsert into password_resets table
    $db->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
    $insert = $db->prepare('INSERT INTO password_resets (user_id, code, expires_at) VALUES (?, ?, ?)');
    $insert->execute([$user['id'], $code, $expiresAt]);

    // Send email
    try {
        $emailService = new EmailService();
        
        // Log code for debugging (remove in production)
        error_log("Password reset code generated for " . $user['email'] . ": " . $code);
        
        // Test email connection first
        if (!$emailService->testEmailConnection()) {
            error_log("Email service connection failed. Code for " . $user['email'] . ": " . $code);
            // Still return success to prevent user enumeration
        } else {
            $emailSent = $emailService->sendPasswordResetCode($user['email'], $user['name'], $code);
            
            if (!$emailSent) {
                error_log("Failed to send password reset email to: " . $user['email'] . ". Code was: " . $code);
                // Still return success to prevent user enumeration, but log the code for debugging
            } else {
                error_log("Password reset email sent successfully to: " . $user['email']);
            }
        }
    } catch (Exception $emailException) {
        error_log("Email service error: " . $emailException->getMessage());
        error_log("Password reset code (for manual testing) for " . $user['email'] . ": " . $code);
        // Still return success to prevent user enumeration
    }

    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Forgot password request error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Server error. Please try again later.']);
}
?>


