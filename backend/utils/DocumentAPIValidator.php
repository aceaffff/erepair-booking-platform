<?php

/**
 * Document API Validator
 * Uses external APIs to validate document images
 * Falls back to custom validation if APIs are unavailable
 */

require_once __DIR__ . '/DocumentValidator.php';

class DocumentAPIValidator {
    
    private static $apiConfig = null;
    
    /**
     * Get API configuration
     */
    private static function getApiConfig() {
        if (self::$apiConfig === null) {
            $configPath = __DIR__ . '/../config/api_keys.php';
            if (file_exists($configPath)) {
                self::$apiConfig = require $configPath;
            } else {
                self::$apiConfig = [
                    'google_vision_api_key' => '',
                    'use_google_vision' => false,
                    'pixlab_api_key' => '',
                    'use_pixlab' => false,
                ];
            }
        }
        return self::$apiConfig;
    }
    
    /**
     * Validate ID document using external APIs
     */
    public static function validateIdDocument($filePath): array {
        $config = self::getApiConfig();
        
        // Try Google Vision API first
        if ($config['use_google_vision'] && !empty($config['google_vision_api_key'])) {
            $result = self::validateWithGoogleVision($filePath, 'id');
            if ($result['valid'] !== null) {
                return $result;
            }
        }
        
        // Try PixLab API if available
        if ($config['use_pixlab'] && !empty($config['pixlab_api_key'])) {
            $result = self::validateWithPixLab($filePath, 'id');
            if ($result['valid'] !== null) {
                return $result;
            }
        }
        
        // Fallback to custom validation
        return DocumentValidator::validateIdDocument($filePath);
    }
    
    /**
     * Validate business permit using external APIs
     */
    public static function validateBusinessPermit($filePath): array {
        $config = self::getApiConfig();
        
        // Try Google Vision API first
        if ($config['use_google_vision'] && !empty($config['google_vision_api_key'])) {
            $result = self::validateWithGoogleVision($filePath, 'permit');
            if ($result['valid'] !== null) {
                return $result;
            }
        }
        
        // Try PixLab API if available
        if ($config['use_pixlab'] && !empty($config['pixlab_api_key'])) {
            $result = self::validateWithPixLab($filePath, 'permit');
            if ($result['valid'] !== null) {
                return $result;
            }
        }
        
        // Fallback to custom validation
        return DocumentValidator::validateBusinessPermit($filePath);
    }
    
    /**
     * Validate using Google Cloud Vision API
     * Free tier: $300 credit/month (includes 1,000 requests/month)
     */
    private static function validateWithGoogleVision($filePath, $documentType): array {
        $config = self::getApiConfig();
        $apiKey = $config['google_vision_api_key'];
        
        try {
            // Read image and encode to base64
            $imageData = file_get_contents($filePath);
            $base64Image = base64_encode($imageData);
            
            // Prepare API request
            $url = 'https://vision.googleapis.com/v1/images:annotate?key=' . urlencode($apiKey);
            
            $requestData = [
                'requests' => [
                    [
                        'image' => [
                            'content' => $base64Image
                        ],
                        'features' => [
                            [
                                'type' => 'DOCUMENT_TEXT_DETECTION',
                                'maxResults' => 1
                            ],
                            [
                                'type' => 'TEXT_DETECTION',
                                'maxResults' => 1
                            ]
                        ]
                    ]
                ]
            ];
            
            // Make API request
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log("Google Vision API error: HTTP $httpCode - $response");
                return ['valid' => null]; // Fallback to custom validation
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['responses'][0])) {
                return ['valid' => null];
            }
            
            $responseData = $result['responses'][0];
            
            // Check for errors
            if (isset($responseData['error'])) {
                error_log("Google Vision API error: " . $responseData['error']['message']);
                return ['valid' => null];
            }
            
            // Analyze text detection results
            $hasText = false;
            $textContent = '';
            
            if (isset($responseData['fullTextAnnotation']['text'])) {
                $textContent = $responseData['fullTextAnnotation']['text'];
                $hasText = !empty(trim($textContent));
            } elseif (isset($responseData['textAnnotations'][0]['description'])) {
                $textContent = $responseData['textAnnotations'][0]['description'];
                $hasText = !empty(trim($textContent));
            }
            
            // Check for document-like keywords
            $documentKeywords = [
                'id' => ['id', 'license', 'passport', 'national', 'driver', 'card', 'number', 'expiry', 'date of birth', 'address'],
                'permit' => ['permit', 'business', 'license', 'municipality', 'province', 'issued', 'expiry', 'date', 'authorized', 'registration']
            ];
            
            $keywords = $documentKeywords[$documentType] ?? [];
            $foundKeywords = 0;
            $textLower = strtolower($textContent);
            
            foreach ($keywords as $keyword) {
                if (strpos($textLower, $keyword) !== false) {
                    $foundKeywords++;
                }
            }
            
            // Validation logic
            $errors = [];
            
            if (!$hasText) {
                $errors[] = 'Image does not appear to contain readable text. Please upload a clear image of the document.';
            } elseif ($foundKeywords < 2) {
                // Need at least 2 document-related keywords
                $errors[] = 'Image does not appear to be a valid ' . ($documentType === 'id' ? 'ID document' : 'business permit') . '. Please ensure the document is clearly visible.';
            }
            
            // Additional checks based on text structure
            if ($hasText && strlen($textContent) < 50) {
                $errors[] = 'Document appears to have insufficient text content. Please ensure the full document is visible.';
            }
            
            return [
                'valid' => empty($errors),
                'error' => empty($errors) ? null : implode(' ', $errors),
                'scores' => [
                    'api' => 'google_vision',
                    'has_text' => $hasText,
                    'keyword_matches' => $foundKeywords,
                    'text_length' => strlen($textContent)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Google Vision API exception: " . $e->getMessage());
            return ['valid' => null]; // Fallback to custom validation
        }
    }
    
    /**
     * Validate using PixLab DOCSCAN API
     * Free tier available at: https://pixlab.io/
     */
    private static function validateWithPixLab($filePath, $documentType): array {
        $config = self::getApiConfig();
        $apiKey = $config['pixlab_api_key'];
        
        try {
            // PixLab requires file upload via multipart/form-data
            $url = 'https://api.pixlab.io/docscan';
            
            // Check if CURLFile is available (PHP 5.5+)
            if (class_exists('CURLFile')) {
                $postData = [
                    'key' => $apiKey,
                    'file' => new CURLFile($filePath)
                ];
            } else {
                // Fallback for older PHP versions
                $postData = [
                    'key' => $apiKey,
                    'file' => '@' . $filePath
                ];
            }
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                error_log("PixLab API error: HTTP $httpCode - $response");
                return ['valid' => null];
            }
            
            $result = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("PixLab API error: Invalid JSON response - " . json_last_error_msg());
                return ['valid' => null];
            }
            
            // Check for API errors
            if (isset($result['error']) || (isset($result['status']) && $result['status'] !== 200)) {
                $errorMsg = $result['error'] ?? $result['message'] ?? 'Unknown error';
                error_log("PixLab API error: $errorMsg");
                return ['valid' => null];
            }
            
            // PixLab returns document type and extracted data
            // Response format: { "type": "passport", "fields": {...}, "status": 200 }
            $detectedType = $result['type'] ?? $result['document_type'] ?? '';
            $hasData = (isset($result['fields']) && !empty($result['fields'])) || 
                       (isset($result['data']) && !empty($result['data']));
            
            $errors = [];
            
            if (!$hasData) {
                $errors[] = 'Could not extract document information. Please ensure the document is clearly visible.';
            }
            
            // Check if detected type matches expected type
            if ($documentType === 'id' && !empty($detectedType)) {
                $idTypes = ['id', 'passport', 'license', 'driver'];
                $isIdType = false;
                foreach ($idTypes as $idType) {
                    if (stripos($detectedType, $idType) !== false) {
                        $isIdType = true;
                        break;
                    }
                }
                if (!$isIdType) {
                    $errors[] = 'Detected document type does not appear to be an ID document.';
                }
            }
            
            return [
                'valid' => empty($errors),
                'error' => empty($errors) ? null : implode(' ', $errors),
                'scores' => [
                    'api' => 'pixlab',
                    'detected_type' => $detectedType,
                    'has_data' => $hasData
                ]
            ];
            
        } catch (Exception $e) {
            error_log("PixLab API exception: " . $e->getMessage());
            return ['valid' => null];
        }
    }
}

