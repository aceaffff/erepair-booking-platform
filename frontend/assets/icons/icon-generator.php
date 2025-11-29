<?php
/**
 * Dynamic Icon Generator for PWA
 * Serves PWA icons on-demand based on requested size
 * Falls back to admin logo or generates default icon
 */

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400'); // Cache for 1 day

// Get requested size from URL (e.g., icon-192x192.png) or query parameter (e.g., ?size=192)
$requestUri = $_SERVER['REQUEST_URI'];
$size = 192; // Default size

// Try to get size from query parameter first
if (isset($_GET['size']) && is_numeric($_GET['size'])) {
    $size = (int)$_GET['size'];
} else {
    // Try to extract from URL pattern (icon-192x192.png)
    preg_match('/icon-(\d+)x\d+\.png/', $requestUri, $matches);
    if (isset($matches[1])) {
        $size = (int)$matches[1];
    }
}

// Validate size
$validSizes = [72, 96, 128, 144, 152, 192, 384, 512];
if (!in_array($size, $validSizes)) {
    $size = 192;
}

require_once __DIR__ . '/../../../backend/config/database.php';

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT logo_url FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $logoPath = null;
    if ($admin && !empty($admin['logo_url'])) {
        $logoPath = $admin['logo_url'];
        
        // Convert relative path to absolute file path
        if (!preg_match('/^https?:\/\//', $logoPath)) {
            if (strpos($logoPath, '../backend/') === 0) {
                $logoPath = substr($logoPath, 3);
            }
            $absolutePath = __DIR__ . '/../../../backend/' . $logoPath;
            
            if (file_exists($absolutePath)) {
                // Load the source image
                $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
                
                if ($ext === 'png') {
                    $source = imagecreatefrompng($absolutePath);
                } elseif (in_array($ext, ['jpg', 'jpeg'])) {
                    $source = imagecreatefromjpeg($absolutePath);
                } else {
                    $source = null;
                }
                
                if ($source) {
                    // Get source dimensions
                    $sourceWidth = imagesx($source);
                    $sourceHeight = imagesy($source);
                    
                    // Create new image
                    $icon = imagecreatetruecolor($size, $size);
                    imagealphablending($icon, false);
                    imagesavealpha($icon, true);
                    
                    // Fill with transparent background
                    $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
                    imagefill($icon, 0, 0, $transparent);
                    
                    // Calculate scaling
                    $scale = min($size / $sourceWidth, $size / $sourceHeight);
                    $newWidth = (int)($sourceWidth * $scale);
                    $newHeight = (int)($sourceHeight * $scale);
                    
                    // Center the image
                    $x = (int)(($size - $newWidth) / 2);
                    $y = (int)(($size - $newHeight) / 2);
                    
                    // Resize and copy
                    imagecopyresampled(
                        $icon, $source,
                        $x, $y, 0, 0,
                        $newWidth, $newHeight,
                        $sourceWidth, $sourceHeight
                    );
                    
                    imagepng($icon);
                    imagedestroy($icon);
                    imagedestroy($source);
                    exit;
                }
            }
        }
    }
    
    // Fallback: Generate default icon
    $icon = imagecreatetruecolor($size, $size);
    
    // Create gradient background (indigo to purple)
    for ($y = 0; $y < $size; $y++) {
        $ratio = $y / $size;
        $r = (int)(99 + (139 - 99) * $ratio);
        $g = (int)(102 + (92 - 102) * $ratio);
        $b = (int)(241 + (246 - 241) * $ratio);
        $color = imagecolorallocate($icon, $r, $g, $b);
        imageline($icon, 0, $y, $size, $y, $color);
    }
    
    // Add white text "ER"
    $textColor = imagecolorallocate($icon, 255, 255, 255);
    $fontSize = (int)($size * 0.4);
    $x = (int)(($size - $fontSize * 0.6) / 2);
    $y = (int)(($size + $fontSize * 0.4) / 2);
    
    imagestring($icon, 5, $x, $y, 'ER', $textColor);
    
    imagepng($icon);
    imagedestroy($icon);
    
} catch (Exception $e) {
    // Ultimate fallback: simple colored square
    $icon = imagecreatetruecolor($size, $size);
    $bg = imagecolorallocate($icon, 99, 102, 241);
    imagefill($icon, 0, 0, $bg);
    imagepng($icon);
    imagedestroy($icon);
}
?>

