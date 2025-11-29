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
require_once '../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $email = trim($data['email'] ?? '');
    if ($email === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Email is required']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare('SELECT id, name, email, email_verified FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    if ((bool)$user['email_verified'] === true) {
        echo json_encode(['success' => true, 'message' => 'Email already verified.']);
        exit;
    }

    // Check if a code was recently sent (within last 10 seconds) to prevent rapid resends
    $stmt = $db->prepare('SELECT id, created_at FROM email_verifications WHERE user_id = ? AND verified_at IS NULL AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$user['id']]);
    $recentCode = $stmt->fetch();
    
    if ($recentCode) {
        $createdAt = strtotime($recentCode['created_at']);
        $timeDiff = time() - $createdAt;
        
        // If code was created less than 10 seconds ago, don't create a new one
        if ($timeDiff < 10) {
            echo json_encode([
                'success' => true, 
                'message' => 'A verification code was recently sent. Please check your email or wait a few seconds before requesting a new code.',
                'code_already_sent' => true
            ]);
            exit;
        }
    }

    // Use transaction to ensure atomicity and prevent duplicate codes
    $db->beginTransaction();
    
    try {
        // Delete any existing unverified codes for this user (within transaction)
        $stmt = $db->prepare('DELETE FROM email_verifications WHERE user_id = ? AND verified_at IS NULL');
        $stmt->execute([$user['id']]);
        
        // Generate 6-digit code and set 5-minute expiry
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Insert new verification code into email_verifications table using MySQL's NOW() for timezone consistency
        $stmt = $db->prepare('INSERT INTO email_verifications (user_id, email, verification_code, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))');
        $stmt->execute([$user['id'], $user['email'], $code]);
        
        // Commit transaction
        $db->commit();
    } catch (Exception $e) {
        // Rollback on error
        $db->rollBack();
        throw $e;
    }

    // Send email with code
    $emailService = new EmailService();
    $emailSent = $emailService->sendVerificationCodeEmail($user['email'], $user['name'], $code);

    if ($emailSent) {
        echo json_encode(['success' => true, 'message' => 'Verification code sent successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send email. Please try again.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Resend verification code error: " . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>


