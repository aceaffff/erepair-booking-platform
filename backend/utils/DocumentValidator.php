<?php

/**
 * Document Validation Utility
 * Validates if uploaded images actually look like ID documents or business permits
 * FREE - No API required
 */
class DocumentValidator {
    
    // Minimum dimensions for documents
    const MIN_WIDTH = 300;
    const MIN_HEIGHT = 200;
    
    // Aspect ratio ranges for different document types
    // IDs can be portrait (0.55-0.85) or landscape (1.15-1.8)
    const ID_ASPECT_RATIO_MIN = 0.55;
    const ID_ASPECT_RATIO_MAX = 1.8;
    // Business permits can be either portrait or landscape
    const PERMIT_ASPECT_RATIO_MIN = 0.6;
    const PERMIT_ASPECT_RATIO_MAX = 2.0;
    
    // Minimum edge density (documents have clear edges) - More lenient for screenshots
    const MIN_EDGE_DENSITY = 0.15;
    
    // Minimum text-like region density - More lenient
    const MIN_TEXT_DENSITY = 0.12;
    
    // Minimum structure score - More lenient
    const MIN_STRUCTURE_SCORE = 0.35;
    
    // Minimum border score (documents have clear borders) - More lenient for screenshots
    const MIN_BORDER_SCORE = 0.25;
    
    // Minimum background uniformity (documents have uniform backgrounds) - More lenient
    const MIN_BACKGROUND_UNIFORMITY = 0.40;
    
    // Minimum document layout score - More lenient
    const MIN_DOCUMENT_LAYOUT = 0.30;
    
    // Maximum color variance (documents are less colorful than photos) - More lenient for screenshots
    const MAX_COLOR_VARIANCE = 6000;
    
    // Minimum document quality score - More lenient
    const MIN_DOCUMENT_QUALITY = 0.35;
    
    /**
     * Validate if an image looks like an ID document
     */
    public static function validateIdDocument($filePath): array {
        $errors = [];
        
        // Check if file exists
        if (!file_exists($filePath)) {
            return ['valid' => false, 'error' => 'File not found'];
        }
        
        // Get image info
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'Invalid image file'];
        }
        
        [$width, $height] = $imageInfo;
        $aspectRatio = $width / $height;
        
        // Check minimum dimensions
        if ($width < self::MIN_WIDTH || $height < self::MIN_HEIGHT) {
            $errors[] = 'Image is too small. Please upload a clear, high-resolution image.';
        }
        
        // Check aspect ratio (IDs can be portrait or landscape)
        // Very lenient - accept wide range for screenshots and different ID formats
        // Only reject extremely unusual ratios
        if ($aspectRatio < 0.3 || $aspectRatio > 3.0) {
            $errors[] = 'Image does not appear to be an ID document. Invalid aspect ratio.';
        }
        // Note: Square images (0.9-1.1) are allowed - might be screenshots with UI
        
        // Load image for analysis
        $image = self::loadImage($filePath, $imageInfo[2]);
        if ($image === false) {
            return ['valid' => false, 'error' => 'Could not process image'];
        }
        
        // Analyze image characteristics
        $edgeScore = self::calculateEdgeDensity($image);
        $textScore = self::calculateTextDensity($image);
        $structureScore = self::calculateStructureScore($image);
        $borderScore = self::calculateBorderScore($image);
        $backgroundScore = self::calculateBackgroundUniformity($image);
        $documentLayoutScore = self::calculateDocumentLayoutScore($image);
        $colorVariance = self::calculateColorVariance($image);
        $qualityScore = self::calculateDocumentQualityScore($image);
        
        // Use a scoring system - need to pass at least 6 out of 9 checks
        $passedChecks = 0;
        $totalChecks = 9;
        
        // Check edge density (documents have clear edges)
        if ($edgeScore >= self::MIN_EDGE_DENSITY) {
            $passedChecks++;
        } else {
            $errors[] = 'Image does not appear to be a document. Please ensure the ID is clearly visible with sharp edges.';
        }
        
        // Check text density (IDs have text)
        if ($textScore >= self::MIN_TEXT_DENSITY) {
            $passedChecks++;
        } else {
            $errors[] = 'Image does not appear to contain sufficient text. Please upload a clear image of your ID with visible text.';
        }
        
        // Check border score (documents have clear borders) - Optional for screenshots
        if ($borderScore >= self::MIN_BORDER_SCORE) {
            $passedChecks++;
        } else {
            // Don't add error if it's close - might be a screenshot
            if ($borderScore < 0.15) {
                $errors[] = 'Image does not appear to have document-like borders. Please ensure the full ID is visible with clear edges.';
            }
        }
        
        // Check background uniformity (documents have uniform backgrounds)
        if ($backgroundScore >= self::MIN_BACKGROUND_UNIFORMITY) {
            $passedChecks++;
        } else {
            // More lenient - might be screenshot with UI elements
            if ($backgroundScore < 0.25) {
                $errors[] = 'Image does not appear to be a document. Documents typically have uniform backgrounds.';
            }
        }
        
        // Check document layout (structured text layout)
        if ($documentLayoutScore >= self::MIN_DOCUMENT_LAYOUT) {
            $passedChecks++;
        } else {
            if ($documentLayoutScore < 0.20) {
                $errors[] = 'Image does not appear to have document-like structure. Please ensure you\'re uploading an actual ID document.';
            }
        }
        
        // Check overall structure score
        if ($structureScore >= self::MIN_STRUCTURE_SCORE) {
            $passedChecks++;
        } else {
            if ($structureScore < 0.25) {
                $errors[] = 'Image does not appear to be a valid ID document. Please ensure the image is clear and shows the full ID.';
            }
        }
        
        // Check color variance (photos have high color variance, documents are more uniform)
        if ($colorVariance <= self::MAX_COLOR_VARIANCE) {
            $passedChecks++;
        } else {
            // More lenient - screenshots might have UI colors
            if ($colorVariance > 8000) {
                $errors[] = 'Image appears to be a photo rather than a document. Documents have more uniform colors.';
            }
        }
        
        // Check document quality score
        if ($qualityScore >= self::MIN_DOCUMENT_QUALITY) {
            $passedChecks++;
        } else {
            if ($qualityScore < 0.25) {
                $errors[] = 'Image does not meet document quality standards. Please upload a clear, well-lit image of the actual ID document.';
            }
        }
        
        // Check if image has reasonable dimensions (not too small)
        if ($width >= self::MIN_WIDTH && $height >= self::MIN_HEIGHT) {
            $passedChecks++;
        } else {
            $errors[] = 'Image is too small. Please upload a clear, high-resolution image.';
        }
        
        // Need to pass at least 5 out of 9 checks (55%) - More lenient for legitimate documents
        // This allows for screenshots with UI elements or slightly imperfect images
        if ($passedChecks < 5) {
            // Only show errors if we really failed multiple checks
            if (count($errors) === 0) {
                $errors[] = 'Image does not meet document validation requirements. Please ensure you\'re uploading a clear image of your ID document.';
            }
        } else {
            // If we passed enough checks, clear all non-critical errors
            // Only keep critical errors (too small, invalid aspect ratio)
            $errors = array_filter($errors, function($error) {
                return strpos($error, 'too small') !== false || 
                       strpos($error, 'Invalid aspect ratio') !== false;
            });
        }
        
        imagedestroy($image);
        
        return [
            'valid' => empty($errors),
            'error' => empty($errors) ? null : implode(' ', $errors),
            'scores' => [
                'edge' => $edgeScore,
                'text' => $textScore,
                'structure' => $structureScore,
                'border' => $borderScore,
                'background' => $backgroundScore,
                'layout' => $documentLayoutScore,
                'color_variance' => $colorVariance,
                'quality' => $qualityScore,
                'aspect_ratio' => $aspectRatio
            ]
        ];
    }
    
    /**
     * Validate if an image looks like a business permit
     */
    public static function validateBusinessPermit($filePath): array {
        $errors = [];
        
        // Check if file exists
        if (!file_exists($filePath)) {
            return ['valid' => false, 'error' => 'File not found'];
        }
        
        // Get image info
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'Invalid image file'];
        }
        
        [$width, $height] = $imageInfo;
        $aspectRatio = $width / $height;
        
        // Check minimum dimensions
        if ($width < self::MIN_WIDTH || $height < self::MIN_HEIGHT) {
            $errors[] = 'Image is too small. Please upload a clear, high-resolution image.';
        }
        
        // Check aspect ratio (permits can be portrait or landscape)
        if ($aspectRatio < self::PERMIT_ASPECT_RATIO_MIN || $aspectRatio > self::PERMIT_ASPECT_RATIO_MAX) {
            $errors[] = 'Image does not appear to be a business permit. Please ensure you\'re uploading a clear image of the actual document.';
        }
        
        // Load image for analysis
        $image = self::loadImage($filePath, $imageInfo[2]);
        if ($image === false) {
            return ['valid' => false, 'error' => 'Could not process image'];
        }
        
        // Analyze image characteristics
        $edgeScore = self::calculateEdgeDensity($image);
        $textScore = self::calculateTextDensity($image);
        $structureScore = self::calculateStructureScore($image);
        $borderScore = self::calculateBorderScore($image);
        $backgroundScore = self::calculateBackgroundUniformity($image);
        $documentLayoutScore = self::calculateDocumentLayoutScore($image);
        $colorVariance = self::calculateColorVariance($image);
        $qualityScore = self::calculateDocumentQualityScore($image);
        
        // Use a scoring system for business permits too - need to pass at least 6 out of 9 checks
        $passedChecks = 0;
        $totalChecks = 9;
        
        // Check edge density
        if ($edgeScore >= self::MIN_EDGE_DENSITY) {
            $passedChecks++;
        } else {
            $errors[] = 'Image does not appear to be a document. Please ensure the permit is clearly visible with sharp edges.';
        }
        
        // Check text density (permits have text)
        if ($textScore >= self::MIN_TEXT_DENSITY) {
            $passedChecks++;
        } else {
            $errors[] = 'Image does not appear to contain sufficient text. Please upload a clear image of your business permit with visible text.';
        }
        
        // Check border score (documents have clear borders) - Optional for screenshots
        if ($borderScore >= self::MIN_BORDER_SCORE) {
            $passedChecks++;
        } else {
            if ($borderScore < 0.15) {
                $errors[] = 'Image does not appear to have document-like borders. Please ensure the full permit is visible with clear edges.';
            }
        }
        
        // Check background uniformity (documents have uniform backgrounds)
        if ($backgroundScore >= self::MIN_BACKGROUND_UNIFORMITY) {
            $passedChecks++;
        } else {
            if ($backgroundScore < 0.25) {
                $errors[] = 'Image does not appear to be a document. Documents typically have uniform backgrounds.';
            }
        }
        
        // Check document layout (structured text layout)
        if ($documentLayoutScore >= self::MIN_DOCUMENT_LAYOUT) {
            $passedChecks++;
        } else {
            if ($documentLayoutScore < 0.20) {
                $errors[] = 'Image does not appear to have document-like structure. Please ensure you\'re uploading an actual business permit.';
            }
        }
        
        // Check overall structure score
        if ($structureScore >= self::MIN_STRUCTURE_SCORE) {
            $passedChecks++;
        } else {
            if ($structureScore < 0.25) {
                $errors[] = 'Image does not appear to be a valid business permit. Please ensure the image is clear and shows the full document.';
            }
        }
        
        // Check color variance (photos have high color variance, documents are more uniform)
        if ($colorVariance <= self::MAX_COLOR_VARIANCE) {
            $passedChecks++;
        } else {
            if ($colorVariance > 8000) {
                $errors[] = 'Image appears to be a photo rather than a document. Documents have more uniform colors.';
            }
        }
        
        // Check document quality score
        if ($qualityScore >= self::MIN_DOCUMENT_QUALITY) {
            $passedChecks++;
        } else {
            if ($qualityScore < 0.25) {
                $errors[] = 'Image does not meet document quality standards. Please upload a clear, well-lit image of the actual business permit.';
            }
        }
        
        // Check if image has reasonable dimensions
        if ($width >= self::MIN_WIDTH && $height >= self::MIN_HEIGHT) {
            $passedChecks++;
        } else {
            $errors[] = 'Image is too small. Please upload a clear, high-resolution image.';
        }
        
        // Need to pass at least 5 out of 9 checks (55%) - More lenient for legitimate documents
        if ($passedChecks < 5) {
            if (count($errors) === 0) {
                $errors[] = 'Image does not meet document validation requirements. Please ensure you\'re uploading a clear image of your business permit.';
            }
        } else {
            // If we passed enough checks, clear all non-critical errors
            $errors = array_filter($errors, function($error) {
                return strpos($error, 'too small') !== false || 
                       strpos($error, 'Invalid aspect ratio') !== false;
            });
        }
        
        imagedestroy($image);
        
        return [
            'valid' => empty($errors),
            'error' => empty($errors) ? null : implode(' ', $errors),
            'scores' => [
                'edge' => $edgeScore,
                'text' => $textScore,
                'structure' => $structureScore,
                'border' => $borderScore,
                'background' => $backgroundScore,
                'layout' => $documentLayoutScore,
                'color_variance' => $colorVariance,
                'quality' => $qualityScore,
                'aspect_ratio' => $aspectRatio
            ]
        ];
    }
    
    /**
     * Load image resource from file
     */
    private static function loadImage($filePath, $imageType) {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return @imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return @imagecreatefromgif($filePath);
            case IMAGETYPE_WEBP:
                return @imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }
    
    /**
     * Calculate edge density using Sobel edge detection
     */
    private static function calculateEdgeDensity($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Resize for faster processing
        $maxSize = 500;
        if ($width > $maxSize || $height > $maxSize) {
            $scale = min($maxSize / $width, $maxSize / $height);
            $newWidth = (int)($width * $scale);
            $newHeight = (int)($height * $scale);
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $resized;
            $width = $newWidth;
            $height = $newHeight;
        }
        
        // Convert to grayscale
        $gray = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $grayValue = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                imagesetpixel($gray, $x, $y, imagecolorallocate($gray, $grayValue, $grayValue, $grayValue));
            }
        }
        
        // Simple edge detection using gradient
        $edgePixels = 0;
        $totalPixels = ($width - 2) * ($height - 2);
        
        for ($y = 1; $y < $height - 1; $y++) {
            for ($x = 1; $x < $width - 1; $x++) {
                $center = self::getGrayValue($gray, $x, $y);
                $right = self::getGrayValue($gray, $x + 1, $y);
                $bottom = self::getGrayValue($gray, $x, $y + 1);
                
                $gradientX = abs($right - $center);
                $gradientY = abs($bottom - $center);
                $gradient = sqrt($gradientX * $gradientX + $gradientY * $gradientY);
                
                // Threshold for edge detection
                if ($gradient > 30) {
                    $edgePixels++;
                }
            }
        }
        
        imagedestroy($gray);
        if (isset($resized)) {
            imagedestroy($resized);
        }
        
        return $totalPixels > 0 ? ($edgePixels / $totalPixels) : 0;
    }
    
    /**
     * Get grayscale value at pixel
     */
    private static function getGrayValue($image, $x, $y): int {
        $rgb = imagecolorat($image, $x, $y);
        return ($rgb >> 16) & 0xFF;
    }
    
    /**
     * Calculate text density (regions with high contrast and structure)
     */
    private static function calculateTextDensity($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Resize for faster processing
        $maxSize = 500;
        if ($width > $maxSize || $height > $maxSize) {
            $scale = min($maxSize / $width, $maxSize / $height);
            $newWidth = (int)($width * $scale);
            $newHeight = (int)($height * $scale);
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $resized;
            $width = $newWidth;
            $height = $newHeight;
        }
        
        // Convert to grayscale
        $gray = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $grayValue = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                imagesetpixel($gray, $x, $y, imagecolorallocate($gray, $grayValue, $grayValue, $grayValue));
            }
        }
        
        // Detect text-like regions (high contrast areas)
        $textPixels = 0;
        $totalPixels = ($width - 2) * ($height - 2);
        
        for ($y = 1; $y < $height - 1; $y++) {
            for ($x = 1; $x < $width - 1; $x++) {
                $center = self::getGrayValue($gray, $x, $y);
                $neighbors = [
                    self::getGrayValue($gray, $x - 1, $y),
                    self::getGrayValue($gray, $x + 1, $y),
                    self::getGrayValue($gray, $x, $y - 1),
                    self::getGrayValue($gray, $x, $y + 1)
                ];
                
                // Calculate local variance (text has high local contrast)
                $variance = 0;
                foreach ($neighbors as $neighbor) {
                    $variance += abs($neighbor - $center);
                }
                $variance /= 4;
                
                // Text regions have high local variance
                if ($variance > 40) {
                    $textPixels++;
                }
            }
        }
        
        imagedestroy($gray);
        if (isset($resized)) {
            imagedestroy($resized);
        }
        
        return $totalPixels > 0 ? ($textPixels / $totalPixels) : 0;
    }
    
    /**
     * Calculate overall structure score (combination of various factors)
     */
    private static function calculateStructureScore($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Check for uniform background (documents often have uniform backgrounds)
        $uniformityScore = self::calculateUniformityScore($image);
        
        // Check for rectangular structure
        $structureScore = self::calculateRectangularStructure($image);
        
        // Combine scores
        return ($uniformityScore * 0.4 + $structureScore * 0.6);
    }
    
    /**
     * Calculate background uniformity (documents have relatively uniform backgrounds)
     */
    private static function calculateUniformityScore($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Sample corners and edges
        $samples = [];
        $sampleSize = min(50, min($width, $height) / 4);
        
        // Sample corners
        for ($y = 0; $y < $sampleSize; $y++) {
            for ($x = 0; $x < $sampleSize; $x++) {
                $samples[] = imagecolorat($image, $x, $y);
                $samples[] = imagecolorat($image, $width - 1 - $x, $y);
                $samples[] = imagecolorat($image, $x, $height - 1 - $y);
                $samples[] = imagecolorat($image, $width - 1 - $x, $height - 1 - $y);
            }
        }
        
        // Calculate average color
        $totalR = $totalG = $totalB = 0;
        foreach ($samples as $color) {
            $totalR += ($color >> 16) & 0xFF;
            $totalG += ($color >> 8) & 0xFF;
            $totalB += $color & 0xFF;
        }
        $count = count($samples);
        $avgR = $totalR / $count;
        $avgG = $totalG / $count;
        $avgB = $totalB / $count;
        
        // Calculate variance
        $variance = 0;
        foreach ($samples as $color) {
            $r = ($color >> 16) & 0xFF;
            $g = ($color >> 8) & 0xFF;
            $b = $color & 0xFF;
            $variance += pow($r - $avgR, 2) + pow($g - $avgG, 2) + pow($b - $avgB, 2);
        }
        $variance /= $count;
        
        // Lower variance = more uniform = higher score (max variance ~65025 for RGB)
        return max(0, 1 - ($variance / 10000));
    }
    
    /**
     * Calculate rectangular structure score
     */
    private static function calculateRectangularStructure($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Check edge regions for consistent structure
        $edgeSamples = 0;
        $edgeConsistent = 0;
        
        // Sample top and bottom edges
        for ($x = 0; $x < $width; $x += 10) {
            $topColor = imagecolorat($image, $x, 0);
            $bottomColor = imagecolorat($image, $x, $height - 1);
            $edgeSamples++;
            if (abs(($topColor & 0xFF) - ($bottomColor & 0xFF)) < 50) {
                $edgeConsistent++;
            }
        }
        
        // Sample left and right edges
        for ($y = 0; $y < $height; $y += 10) {
            $leftColor = imagecolorat($image, 0, $y);
            $rightColor = imagecolorat($image, $width - 1, $y);
            $edgeSamples++;
            if (abs(($leftColor & 0xFF) - ($rightColor & 0xFF)) < 50) {
                $edgeConsistent++;
            }
        }
        
        return $edgeSamples > 0 ? ($edgeConsistent / $edgeSamples) : 0;
    }
    
    /**
     * Calculate border score - checks for clear document borders
     */
    private static function calculateBorderScore($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Check border regions (outer 5% of image)
        $borderWidth = max(5, (int)($width * 0.05));
        $borderHeight = max(5, (int)($height * 0.05));
        
        // Sample border pixels
        $borderPixels = [];
        $interiorPixels = [];
        
        // Top border
        for ($x = 0; $x < $width; $x += 5) {
            for ($y = 0; $y < $borderHeight; $y++) {
                $borderPixels[] = imagecolorat($image, $x, $y);
            }
        }
        
        // Bottom border
        for ($x = 0; $x < $width; $x += 5) {
            for ($y = $height - $borderHeight; $y < $height; $y++) {
                $borderPixels[] = imagecolorat($image, $x, $y);
            }
        }
        
        // Left border
        for ($y = 0; $y < $height; $y += 5) {
            for ($x = 0; $x < $borderWidth; $x++) {
                $borderPixels[] = imagecolorat($image, $x, $y);
            }
        }
        
        // Right border
        for ($y = 0; $y < $height; $y += 5) {
            for ($x = $width - $borderWidth; $x < $width; $x++) {
                $borderPixels[] = imagecolorat($image, $x, $y);
            }
        }
        
        // Sample interior (center region)
        $centerX = (int)($width / 2);
        $centerY = (int)($height / 2);
        $sampleSize = min(100, min($width, $height) / 4);
        
        for ($x = $centerX - $sampleSize; $x < $centerX + $sampleSize; $x += 10) {
            for ($y = $centerY - $sampleSize; $y < $centerY + $sampleSize; $y += 10) {
                if ($x >= 0 && $x < $width && $y >= 0 && $y < $height) {
                    $interiorPixels[] = imagecolorat($image, $x, $y);
                }
            }
        }
        
        if (empty($borderPixels) || empty($interiorPixels)) {
            return 0;
        }
        
        // Calculate average brightness for borders and interior
        $borderBrightness = 0;
        foreach ($borderPixels as $color) {
            $r = ($color >> 16) & 0xFF;
            $g = ($color >> 8) & 0xFF;
            $b = $color & 0xFF;
            $borderBrightness += (0.299 * $r + 0.587 * $g + 0.114 * $b);
        }
        $borderBrightness /= count($borderPixels);
        
        $interiorBrightness = 0;
        foreach ($interiorPixels as $color) {
            $r = ($color >> 16) & 0xFF;
            $g = ($color >> 8) & 0xFF;
            $b = $color & 0xFF;
            $interiorBrightness += (0.299 * $r + 0.587 * $g + 0.114 * $b);
        }
        $interiorBrightness /= count($interiorPixels);
        
        // Documents often have lighter borders (white/light backgrounds)
        // or consistent borders (similar to interior)
        $brightnessDiff = abs($borderBrightness - $interiorBrightness);
        
        // Score higher if borders are light (typical of documents) or consistent
        if ($borderBrightness > 200 || $brightnessDiff < 30) {
            return 0.8;
        } elseif ($borderBrightness > 150) {
            return 0.6;
        } else {
            return 0.3;
        }
    }
    
    /**
     * Calculate background uniformity (enhanced version)
     */
    private static function calculateBackgroundUniformity($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Sample corners and edges more thoroughly
        $samples = [];
        $sampleSize = min(80, min($width, $height) / 3);
        
        // Sample all four corners
        for ($y = 0; $y < $sampleSize; $y++) {
            for ($x = 0; $x < $sampleSize; $x++) {
                // Top-left
                $samples[] = imagecolorat($image, $x, $y);
                // Top-right
                $samples[] = imagecolorat($image, $width - 1 - $x, $y);
                // Bottom-left
                $samples[] = imagecolorat($image, $x, $height - 1 - $y);
                // Bottom-right
                $samples[] = imagecolorat($image, $width - 1 - $x, $height - 1 - $y);
            }
        }
        
        // Calculate average color
        $totalR = $totalG = $totalB = 0;
        foreach ($samples as $color) {
            $totalR += ($color >> 16) & 0xFF;
            $totalG += ($color >> 8) & 0xFF;
            $totalB += $color & 0xFF;
        }
        $count = count($samples);
        $avgR = $totalR / $count;
        $avgG = $totalG / $count;
        $avgB = $totalB / $count;
        
        // Calculate variance
        $variance = 0;
        foreach ($samples as $color) {
            $r = ($color >> 16) & 0xFF;
            $g = ($color >> 8) & 0xFF;
            $b = $color & 0xFF;
            $variance += pow($r - $avgR, 2) + pow($g - $avgG, 2) + pow($b - $avgB, 2);
        }
        $variance /= $count;
        
        // Check if background is light (typical of documents)
        $avgBrightness = (0.299 * $avgR + 0.587 * $avgG + 0.114 * $avgB);
        $isLightBackground = $avgBrightness > 180;
        
        // Lower variance = more uniform = higher score
        $uniformityScore = max(0, 1 - ($variance / 8000));
        
        // Boost score if background is light (documents typically have light backgrounds)
        if ($isLightBackground) {
            $uniformityScore = min(1.0, $uniformityScore * 1.2);
        }
        
        return $uniformityScore;
    }
    
    /**
     * Calculate document layout score - checks for structured text layout
     */
    private static function calculateDocumentLayoutScore($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Resize for analysis
        $maxSize = 400;
        if ($width > $maxSize || $height > $maxSize) {
            $scale = min($maxSize / $width, $maxSize / $height);
            $newWidth = (int)($width * $scale);
            $newHeight = (int)($height * $scale);
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $resized;
            $width = $newWidth;
            $height = $newHeight;
        }
        
        // Convert to grayscale
        $gray = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $grayValue = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                imagesetpixel($gray, $x, $y, imagecolorallocate($gray, $grayValue, $grayValue, $grayValue));
            }
        }
        
        // Analyze horizontal and vertical text lines (documents have structured text)
        $horizontalLines = 0;
        $verticalStructure = 0;
        
        // Check for horizontal text lines (scan rows)
        $lineThreshold = 0.15; // Percentage of row that should be text-like
        for ($y = 0; $y < $height; $y += 5) {
            $textPixels = 0;
            $totalPixels = 0;
            
            for ($x = 1; $x < $width - 1; $x++) {
                $center = self::getGrayValue($gray, $x, $y);
                $left = self::getGrayValue($gray, $x - 1, $y);
                $right = self::getGrayValue($gray, $x + 1, $y);
                
                $contrast = max(abs($center - $left), abs($center - $right));
                if ($contrast > 30) {
                    $textPixels++;
                }
                $totalPixels++;
            }
            
            if ($totalPixels > 0 && ($textPixels / $totalPixels) > $lineThreshold) {
                $horizontalLines++;
            }
        }
        
        // Check for vertical structure (columns with consistent patterns)
        for ($x = 0; $x < $width; $x += 10) {
            $columnVariance = 0;
            $prevValue = self::getGrayValue($gray, $x, 0);
            
            for ($y = 1; $y < $height; $y++) {
                $currentValue = self::getGrayValue($gray, $x, $y);
                $columnVariance += abs($currentValue - $prevValue);
                $prevValue = $currentValue;
            }
            
            $avgVariance = $columnVariance / $height;
            if ($avgVariance > 15 && $avgVariance < 60) { // Structured but not too chaotic
                $verticalStructure++;
            }
        }
        
        imagedestroy($gray);
        if (isset($resized)) {
            imagedestroy($resized);
        }
        
        // Calculate scores
        $horizontalScore = min(1.0, ($horizontalLines / ($height / 5)) * 2);
        $verticalScore = min(1.0, ($verticalStructure / ($width / 10)) * 1.5);
        
        // Combine scores
        return ($horizontalScore * 0.6 + $verticalScore * 0.4);
    }
    
    /**
     * Calculate color variance - photos have high variance, documents are more uniform
     */
    private static function calculateColorVariance($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Sample colors throughout the image
        $colors = [];
        $sampleStep = 10; // Sample every 10 pixels
        
        for ($y = 0; $y < $height; $y += $sampleStep) {
            for ($x = 0; $x < $width; $x += $sampleStep) {
                $rgb = imagecolorat($image, $x, $y);
                $colors[] = [
                    'r' => ($rgb >> 16) & 0xFF,
                    'g' => ($rgb >> 8) & 0xFF,
                    'b' => $rgb & 0xFF
                ];
            }
        }
        
        if (empty($colors)) {
            return 0;
        }
        
        // Calculate average color
        $avgR = $avgG = $avgB = 0;
        foreach ($colors as $color) {
            $avgR += $color['r'];
            $avgG += $color['g'];
            $avgB += $color['b'];
        }
        $count = count($colors);
        $avgR /= $count;
        $avgG /= $count;
        $avgB /= $count;
        
        // Calculate variance
        $variance = 0;
        foreach ($colors as $color) {
            $variance += pow($color['r'] - $avgR, 2) + 
                        pow($color['g'] - $avgG, 2) + 
                        pow($color['b'] - $avgB, 2);
        }
        $variance /= $count;
        
        return $variance;
    }
    
    /**
     * Calculate document quality score - checks multiple document characteristics
     */
    private static function calculateDocumentQualityScore($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        $scores = [];
        
        // 1. Check for clear rectangular boundaries
        $rectangularScore = self::calculateRectangularBoundary($image);
        $scores[] = $rectangularScore;
        
        // 2. Check contrast (documents have good contrast between text and background)
        $contrastScore = self::calculateContrast($image);
        $scores[] = $contrastScore;
        
        // 3. Check for text alignment (documents have aligned text)
        $alignmentScore = self::calculateTextAlignment($image);
        $scores[] = $alignmentScore;
        
        // 4. Check sharpness (blurry images likely not documents)
        $sharpnessScore = self::calculateSharpness($image);
        $scores[] = $sharpnessScore;
        
        // Return average of all scores
        return array_sum($scores) / count($scores);
    }
    
    /**
     * Calculate rectangular boundary score
     */
    private static function calculateRectangularBoundary($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Check if edges have consistent colors (indicates document border)
        $edgePixels = [];
        
        // Sample top edge
        for ($x = 0; $x < $width; $x += 5) {
            $edgePixels[] = imagecolorat($image, $x, 0);
        }
        
        // Sample bottom edge
        for ($x = 0; $x < $width; $x += 5) {
            $edgePixels[] = imagecolorat($image, $x, $height - 1);
        }
        
        // Sample left edge
        for ($y = 0; $y < $height; $y += 5) {
            $edgePixels[] = imagecolorat($image, 0, $y);
        }
        
        // Sample right edge
        for ($y = 0; $y < $height; $y += 5) {
            $edgePixels[] = imagecolorat($image, $width - 1, $y);
        }
        
        // Calculate variance of edge colors
        $avgColor = array_sum($edgePixels) / count($edgePixels);
        $variance = 0;
        foreach ($edgePixels as $color) {
            $variance += pow($color - $avgColor, 2);
        }
        $variance /= count($edgePixels);
        
        // Lower variance = more consistent edges = higher score
        return max(0, 1 - ($variance / 100000000));
    }
    
    /**
     * Calculate contrast score
     */
    private static function calculateContrast($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        $minBrightness = 255;
        $maxBrightness = 0;
        
        // Sample brightness throughout image
        for ($y = 0; $y < $height; $y += 20) {
            for ($x = 0; $x < $width; $x += 20) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $brightness = (0.299 * $r + 0.587 * $g + 0.114 * $b);
                
                $minBrightness = min($minBrightness, $brightness);
                $maxBrightness = max($maxBrightness, $brightness);
            }
        }
        
        $contrast = $maxBrightness - $minBrightness;
        
        // Documents typically have good contrast (100-200 range)
        if ($contrast >= 80 && $contrast <= 220) {
            return 0.8;
        } elseif ($contrast >= 60 && $contrast <= 240) {
            return 0.6;
        } else {
            return 0.4;
        }
    }
    
    /**
     * Calculate text alignment score
     */
    private static function calculateTextAlignment($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Count horizontal edge transitions (indicates text lines)
        $horizontalTransitions = 0;
        
        for ($y = 0; $y < $height; $y += 5) {
            $transitions = 0;
            $prevGray = 0;
            
            for ($x = 1; $x < $width; $x += 2) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray = (0.299 * $r + 0.587 * $g + 0.114 * $b);
                
                if (abs($gray - $prevGray) > 40) {
                    $transitions++;
                }
                $prevGray = $gray;
            }
            
            // Text lines have moderate transitions (not too many, not too few)
            if ($transitions > 5 && $transitions < 50) {
                $horizontalTransitions++;
            }
        }
        
        $alignmentRatio = $horizontalTransitions / ($height / 5);
        
        // Documents typically have 20-60% rows with text
        if ($alignmentRatio >= 0.2 && $alignmentRatio <= 0.6) {
            return 0.8;
        } elseif ($alignmentRatio >= 0.1 && $alignmentRatio <= 0.7) {
            return 0.6;
        } else {
            return 0.3;
        }
    }
    
    /**
     * Calculate sharpness score (Laplacian variance)
     */
    private static function calculateSharpness($image): float {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Convert to grayscale and calculate Laplacian variance
        $laplacianSum = 0;
        $count = 0;
        
        for ($y = 1; $y < $height - 1; $y += 5) {
            for ($x = 1; $x < $width - 1; $x += 5) {
                // Get surrounding pixels
                $center = self::getPixelBrightness($image, $x, $y);
                $top = self::getPixelBrightness($image, $x, $y - 1);
                $bottom = self::getPixelBrightness($image, $x, $y + 1);
                $left = self::getPixelBrightness($image, $x - 1, $y);
                $right = self::getPixelBrightness($image, $x + 1, $y);
                
                // Laplacian operator
                $laplacian = abs(4 * $center - $top - $bottom - $left - $right);
                $laplacianSum += $laplacian;
                $count++;
            }
        }
        
        $avgLaplacian = $count > 0 ? $laplacianSum / $count : 0;
        
        // Sharp images have higher Laplacian values
        // Documents should be reasonably sharp (>15)
        if ($avgLaplacian >= 20) {
            return 0.9;
        } elseif ($avgLaplacian >= 15) {
            return 0.7;
        } elseif ($avgLaplacian >= 10) {
            return 0.5;
        } else {
            return 0.3;
        }
    }
    
    /**
     * Get pixel brightness
     */
    private static function getPixelBrightness($image, $x, $y): float {
        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        return (0.299 * $r + 0.587 * $g + 0.114 * $b);
    }
}

