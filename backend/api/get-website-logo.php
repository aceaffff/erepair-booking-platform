<?php
/**
 * API to get the website logo (admin's logo_url)
 * This can be used by any page to get the favicon/logo
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Get admin user's logo_url
    $stmt = $db->prepare("SELECT logo_url FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $logoUrl = null;
    if ($admin && !empty($admin['logo_url'])) {
        $logoUrl = $admin['logo_url'];
    }
    
    // Return the raw logo_url - let the calling page normalize it based on their location
    // The logo_url is stored as: ../backend/uploads/logos/filename.jpg (relative to frontend/)
    // Each page will normalize it based on their directory structure
    
    // Default fallback if no logo
    if (!$logoUrl) {
        $logoUrl = 'https://ui-avatars.com/api/?name=ERepair&background=6366f1&color=fff';
    }
    
    echo json_encode([
        'success' => true,
        'logo_url' => $logoUrl
    ]);
    
} catch (Exception $e) {
    // Return default on error
    echo json_encode([
        'success' => true,
        'logo_url' => 'https://ui-avatars.com/api/?name=ERepair&background=6366f1&color=fff'
    ]);
}
?>

