<?php
/**
 * Response Helper for Standardized JSON API Responses
 * Ensures consistent JSON output across all API endpoints
 */

class ResponseHelper {
    
    /**
     * Send a successful JSON response
     * 
     * @param string $message Success message
     * @param mixed $data Optional data to include
     * @param int $httpCode HTTP status code (default: 200)
     */
    public static function success($message, $data = null, $httpCode = 200) {
        // Suppress any output that might have been generated
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set proper headers
        header('Content-Type: application/json');
        http_response_code($httpCode);
        
        // Build response array
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        // Output JSON and exit
        echo json_encode($response);
        exit;
    }
    
    /**
     * Send an error JSON response
     * 
     * @param string $message Error message
     * @param int $httpCode HTTP status code (default: 400)
     * @param mixed $details Optional error details
     */
    public static function error($message, $httpCode = 400, $details = null) {
        // Suppress any output that might have been generated
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set proper headers
        header('Content-Type: application/json');
        http_response_code($httpCode);
        
        // Build response array
        $response = [
            'error' => true,
            'message' => $message
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        // Output JSON and exit
        echo json_encode($response);
        exit;
    }
    
    /**
     * Send a validation error response
     * 
     * @param string $message Validation error message
     * @param array $errors Optional array of field-specific errors
     */
    public static function validationError($message, $errors = []) {
        self::error($message, 400, $errors);
    }
    
    /**
     * Send an unauthorized response
     * 
     * @param string $message Unauthorized message
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }
    
    /**
     * Send a forbidden response
     * 
     * @param string $message Forbidden message
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }
    
    /**
     * Send a not found response
     * 
     * @param string $message Not found message
     */
    public static function notFound($message = 'Not found') {
        self::error($message, 404);
    }
    
    /**
     * Send a conflict response
     * 
     * @param string $message Conflict message
     */
    public static function conflict($message = 'Conflict') {
        self::error($message, 409);
    }
    
    /**
     * Send a server error response
     * 
     * @param string $message Server error message
     * @param mixed $details Optional error details
     */
    public static function serverError($message = 'Server error', $details = null) {
        self::error($message, 500, $details);
    }
    
    /**
     * Send a method not allowed response
     * 
     * @param string $message Method not allowed message
     */
    public static function methodNotAllowed($message = 'Method not allowed') {
        self::error($message, 405);
    }
    
    /**
     * Initialize error reporting for API endpoints
     * Call this at the beginning of API files
     */
    public static function initApi() {
        // Suppress error display but log them
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        
        // Start output buffering to catch any unexpected output
        ob_start();
    }
    
    /**
     * Clean and send response
     * Call this before sending any response to ensure clean output
     */
    public static function cleanOutput() {
        // Clean any output buffer
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Flush any remaining output
        flush();
    }
}
