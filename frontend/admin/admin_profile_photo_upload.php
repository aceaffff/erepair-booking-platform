<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';

function respond_json(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function ensure_post_method(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        respond_json(405, ['error' => 'Method not allowed']);
    }
}

function auth_admin(PDO $db): array {
    $token = $_COOKIE['auth_token'] ?? '';
    if (!$token) {
        respond_json(401, ['error' => 'Not authenticated']);
    }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON s.user_id = u.id WHERE s.token = ? AND s.expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !isset($user['id']) || $user['role'] !== 'admin') {
        respond_json(401, ['error' => 'Unauthorized - Admin access required']);
    }
    $user['id'] = (int)$user['id'];
    return $user;
}

function detect_image_mime(string $tmpPath, string $originalName): string {
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected = @finfo_file($finfo, $tmpPath);
            if (is_string($detected)) { $mime = $detected; }
            @finfo_close($finfo);
        }
    }
    if (!$mime && function_exists('getimagesize')) {
        $info = @getimagesize($tmpPath);
        if ($info && isset($info['mime']) && is_string($info['mime'])) { $mime = $info['mime']; }
    }
    if (!$mime) {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $fallback = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        $mime = $fallback[$ext] ?? '';
    }
    return $mime;
}

ensure_post_method();

try {
    $db = (new Database())->getConnection();
    $user = auth_admin($db);

    if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
        respond_json(400, ['error' => 'No file uploaded']);
    }
    $file = $_FILES['avatar'];
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        respond_json(400, ['error' => 'Upload failed']);
    }
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        respond_json(400, ['error' => 'Invalid upload']);
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $maxBytes = 2 * 1024 * 1024; // 2MB

    $mime = detect_image_mime($file['tmp_name'], $file['name'] ?? '');
    if (!isset($allowed[$mime])) {
        respond_json(400, ['error' => 'Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.']);
    }
    if (!isset($file['size']) || $file['size'] > $maxBytes) {
        respond_json(400, ['error' => 'Image too large (max 2MB)']);
    }

    $ext = $allowed[$mime];
    $dir = __DIR__ . '/../uploads/avatars';
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            respond_json(500, ['error' => 'Failed to prepare upload directory']);
        }
    }

    $random = bin2hex(random_bytes(6));
    $filename = 'user_' . $user['id'] . '_' . time() . '_' . $random . '.' . $ext;
    $dest = $dir . '/' . $filename;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        respond_json(500, ['error' => 'Failed to save image']);
    }
    @chmod($dest, 0644);
    $publicPath = 'uploads/avatars/' . $filename;

    $stmt = $db->prepare('UPDATE users SET avatar_url = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$publicPath, $user['id']]);

    respond_json(200, ['success' => true, 'avatar_url' => $publicPath]);
} catch (Throwable $e) {
    error_log('Admin avatar upload error: ' . $e->getMessage());
    respond_json(500, ['error' => 'Server error']);
}
?>

