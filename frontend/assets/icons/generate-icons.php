<?php
/**
 * PWA Icon Generator
 * Generates PWA icons from the admin's website logo
 * Creates icons in various sizes required for PWA
 */

require_once __DIR__ . '/../../../backend/config/database.php';

// Icon sizes required for PWA
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$iconDir = __DIR__;

// Get admin's logo
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
                    die("Unsupported image format: $ext");
                }
                
                if (!$source) {
                    die("Failed to load source image");
                }
                
                // Get source dimensions
                $sourceWidth = imagesx($source);
                $sourceHeight = imagesy($source);
                
                // Generate icons for each size
                foreach ($sizes as $size) {
                    // Create new image with transparent background
                    $icon = imagecreatetruecolor($size, $size);
                    imagealphablending($icon, false);
                    imagesavealpha($icon, true);
                    
                    // Fill with transparent background
                    $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
                    imagefill($icon, 0, 0, $transparent);
                    
                    // Calculate scaling to fit icon (maintain aspect ratio)
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
                    
                    // Save icon
                    $iconPath = $iconDir . "/icon-{$size}x{$size}.png";
                    imagepng($icon, $iconPath);
                    imagedestroy($icon);
                    
                    echo "Generated: icon-{$size}x{$size}.png\n";
                }
                
                imagedestroy($source);
                echo "\nAll icons generated successfully!\n";
            } else {
                die("Logo file not found: $absolutePath");
            }
        } else {
            die("External URLs not supported. Please upload a logo file.");
        }
    } else {
        // Generate default icons with ERepair branding
        foreach ($sizes as $size) {
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
            
            // Save icon
            $iconPath = $iconDir . "/icon-{$size}x{$size}.png";
            imagepng($icon, $iconPath);
            imagedestroy($icon);
            
            echo "Generated default: icon-{$size}x{$size}.png\n";
        }
        echo "\nDefault icons generated successfully!\n";
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

