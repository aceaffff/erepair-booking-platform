<?php
/**
 * Serves the admin's website logo as favicon
 * This can be used directly in <link rel="icon"> tags
 */
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Get admin user's logo_url
    $stmt = $db->prepare("SELECT logo_url FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $logoPath = null;
    if ($admin && !empty($admin['logo_url'])) {
        $logoPath = $admin['logo_url'];
        
        // Convert relative path to absolute file path
        if (!preg_match('/^https?:\/\//', $logoPath)) {
            // Remove ../backend/ or backend/ prefix
            if (strpos($logoPath, '../backend/') === 0) {
                $logoPath = substr($logoPath, 3); // Remove '../'
            } elseif (strpos($logoPath, 'backend/') === 0) {
                // Already correct
            }
            
            // Build absolute path
            $absolutePath = __DIR__ . '/../' . $logoPath;
            
            if (file_exists($absolutePath)) {
                // Get file info
                $mimeType = mime_content_type($absolutePath);
                if (!$mimeType) {
                    $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
                    $mimeTypes = [
                        'png' => 'image/png',
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'gif' => 'image/gif',
                        'svg' => 'image/svg+xml',
                        'ico' => 'image/x-icon'
                    ];
                    $mimeType = $mimeTypes[$ext] ?? 'image/png';
                }
                
                // Set headers and output image
                header('Content-Type: ' . $mimeType);
                header('Cache-Control: public, max-age=3600');
                header('Content-Length: ' . filesize($absolutePath));
                readfile($absolutePath);
                exit;
            }
        } elseif (preg_match('/^https?:\/\//', $logoPath)) {
            // External URL - redirect to it
            header('Location: ' . $logoPath);
            exit;
        }
    }
    
    // Fallback: Generate default favicon
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600');
    
    // Create a simple default favicon (16x16 PNG)
    $img = imagecreatetruecolor(16, 16);
    $bg = imagecolorallocate($img, 99, 102, 241); // Indigo background
    $text = imagecolorallocate($img, 255, 255, 255); // White text
    imagefill($img, 0, 0, $bg);
    imagestring($img, 2, 2, 2, 'ER', $text);
    imagepng($img);
    imagedestroy($img);
    
} catch (Exception $e) {
    // Fallback on error
    header('Content-Type: image/png');
    $img = imagecreatetruecolor(16, 16);
    $bg = imagecolorallocate($img, 99, 102, 241);
    $text = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bg);
    imagestring($img, 2, 2, 2, 'ER', $text);
    imagepng($img);
    imagedestroy($img);
}
?>

