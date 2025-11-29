<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

function auth_admin_or_unauthorized(PDO $db): array {
    $token = $_COOKIE['auth_token'] ?? '';
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
    return $user;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $user = auth_admin_or_unauthorized($db);

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');

    if ($name === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Name is required']);
        exit;
    }

    if ($email === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email is required']);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }

    // Check if email is already taken by another user
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $stmt->execute([$email, $user['id']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email already taken']);
        exit;
    }

    // Update admin profile
    $stmt = $db->prepare('UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$name, $email, $user['id']]);

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
