<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $token = $_COOKIE['auth_token'] ?? '';
    if ($token !== '') {
        $stmt = $db->prepare('DELETE FROM sessions WHERE token = ?');
        $stmt->execute([$token]);
        setcookie('auth_token', '', time() - 3600, '/');
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>


