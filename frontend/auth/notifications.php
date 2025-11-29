<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/InputValidator.php';

function auth_user(PDO $db): array {
    $token = InputValidator::validateToken($_COOKIE['auth_token'] ?? '');
    if ($token === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    $stmt = $db->prepare("SELECT u.id, u.role FROM users u INNER JOIN sessions s ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    return $user;
}

try {
    $db = (new Database())->getConnection();
    
    // Create notifications table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'info',
        link VARCHAR(500) DEFAULT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    
    // Add link column if it doesn't exist (for existing installations)
    try {
        $db->exec("ALTER TABLE notifications ADD COLUMN link VARCHAR(500) DEFAULT NULL");
    } catch (Exception $e) {
        // Column already exists, ignore error
    }
    
    $user = auth_user($db);
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get notifications for user
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
        
        $sql = "SELECT id, title, message, type, link, is_read, created_at 
                FROM notifications 
                WHERE user_id = ?";
        
        if ($unreadOnly) {
            $sql .= " AND is_read = FALSE";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$user['id']]);
        $notifications = $stmt->fetchAll();
        
        // Get unread count
        $countStmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $countStmt->execute([$user['id']]);
        $unreadCount = $countStmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => (int)$unreadCount
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Mark notification(s) as read
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        
        if (isset($input['notification_id'])) {
            // Mark single notification as read
            $notifId = (int)$input['notification_id'];
            $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
            $stmt->execute([$notifId, $user['id']]);
        } elseif (isset($input['mark_all_read']) && $input['mark_all_read']) {
            // Mark all notifications as read
            $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
            $stmt->execute([$user['id']]);
        }
        
        echo json_encode(['success' => true]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Delete notification
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
        }
        
        echo json_encode(['success' => true]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
}
?>
