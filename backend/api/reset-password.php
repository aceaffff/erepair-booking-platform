<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../utils/InputValidator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Validate JSON input
$input = InputValidator::validateJsonInput(file_get_contents('php://input'));
if ($input === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate inputs
$email = InputValidator::validateEmail($input['email'] ?? '');
$codeInput = trim($input['code'] ?? '');
$newPassword = InputValidator::validatePassword($input['new_password'] ?? '', 6);

// Better error messages
$errors = [];
if ($email === null) {
    $errors[] = 'Valid email is required';
}
if (empty($codeInput) || !preg_match('/^[0-9]{6}$/', $codeInput)) {
    $errors[] = 'Valid 6-digit code is required';
}
if ($newPassword === null) {
    $errors[] = 'Password must be at least 6 characters';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => implode('. ', $errors)]);
    exit;
}

// Clean code (remove any non-numeric characters)
$code = preg_replace('/[^0-9]/', '', $codeInput);

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid code or email']);
        exit;
    }

    // Ensure table exists (in case schema didn't run)
    $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        code VARCHAR(12) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id),
        CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Verify code not expired (check in PHP to avoid timezone mismatch)
    $check = $db->prepare('SELECT id, expires_at FROM password_resets WHERE user_id = ? AND code = ? ORDER BY id DESC LIMIT 1');
    $check->execute([$user['id'], $code]);
    $row = $check->fetch();
    if (!$row) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or expired code']);
        exit;
    }
    if (strtotime($row['expires_at']) <= time()) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or expired code']);
        exit;
    }

    // Update password
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
    $upd->execute([$hashed, $user['id']]);

    // Cleanup
    $db->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);

    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => 'Server error. Please try again later.']);
}
?>


