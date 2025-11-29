<?php
// Check if this is an API request (for AJAX calls)
$isApiRequest = isset($_GET['api']) && $_GET['api'] === 'true';

if ($isApiRequest) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
} else {
    // For direct page access, redirect to the enhanced verification page
    $verificationCode = $_GET['code'] ?? '';
    
    // Simple hardcoded redirect that should work for localhost setup
    if (!empty($verificationCode)) {
        header("Location: ../../frontend/verification/verify-email.php?code=" . urlencode($verificationCode));
        exit;
    } else {
        header("Location: ../../frontend/verification/verify-email.php");
        exit;
    }
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$verificationCode = $_GET['code'] ?? '';

if (empty($verificationCode)) {
    http_response_code(400);
    echo json_encode(['error' => 'Verification code is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Find verification record with this code (not yet verified and not expired)
    $stmt = $db->prepare("
        SELECT ev.user_id, ev.id as verification_id, u.id, u.name, u.email, u.email_verified
        FROM email_verifications ev
        INNER JOIN users u ON ev.user_id = u.id
        WHERE ev.verification_code = ? 
        AND ev.verified_at IS NULL 
        AND ev.expires_at > NOW()
        AND u.email_verified = FALSE
        ORDER BY ev.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$verificationCode]);
    $verification = $stmt->fetch();

    if (!$verification) {
        // Check if already verified earlier (idempotent behavior)
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email 
            FROM users u
            INNER JOIN email_verifications ev ON u.id = ev.user_id
            WHERE ev.verification_code = ? 
            AND u.email_verified = TRUE
            LIMIT 1
        ");
        $stmt->execute([$verificationCode]);
        $already = $stmt->fetch();

        if ($already) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Email already verified.',
                'user' => [
                    'name' => $already['name'],
                    'email' => $already['email']
                ]
            ]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Invalid or expired verification code']);
        exit;
    }

    // Update verification and user as verified
    $db->beginTransaction();
    try {
        // Mark verification as used
        $stmt = $db->prepare("UPDATE email_verifications SET verified_at = NOW() WHERE id = ?");
        $stmt->execute([$verification['verification_id']]);
        
        // Update user as verified
        $stmt = $db->prepare("UPDATE users SET email_verified = TRUE, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$verification['id']]);
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update verification status']);
        exit;
    }
    
    $user = [
        'id' => $verification['id'],
        'name' => $verification['name'],
        'email' => $verification['email']
    ];
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully! You can now log in.',
        'user' => [
            'name' => $user['name'],
            'email' => $user['email']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
