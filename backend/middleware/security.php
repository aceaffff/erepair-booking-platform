<?php

/**
 * Security Middleware
 * Include this file in API endpoints to add security protections
 */

require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../utils/InputValidator.php';

/**
 * Apply security checks to the current request
 */
function applySecurityMiddleware(array $options = []): void {
    // Default options
    $defaults = [
        'rate_limit' => true,
        'rate_limit_max' => 60,
        'rate_limit_window' => 60,
        'csrf_protection' => false, // Enable for state-changing operations
        'validate_origin' => true,
        'require_https' => false // Set to true in production
    ];
    
    $options = array_merge($defaults, $options);
    
    // HTTPS check (in production)
    if ($options['require_https'] && !isHTTPS()) {
        http_response_code(426);
        echo json_encode(['error' => 'HTTPS required']);
        exit;
    }
    
    // Validate request origin
    if ($options['validate_origin'] && !SecurityManager::validateOrigin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid request origin']);
        exit;
    }
    
    // Rate limiting
    if ($options['rate_limit']) {
        $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
        if (!SecurityManager::checkRateLimit($endpoint, $options['rate_limit_max'], $options['rate_limit_window'])) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
            exit;
        }
    }
    
    // CSRF protection for state-changing operations
    if ($options['csrf_protection'] && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        $csrfToken = getCSRFTokenFromRequest();
        if (!SecurityManager::validateCSRFToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
    }
    
    // Add security headers
    addSecurityHeaders();
}

/**
 * Get CSRF token from request
 */
function getCSRFTokenFromRequest(): string {
    // Check header first
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    
    // Check POST data
    if (isset($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }
    
    // Check JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['csrf_token'])) {
        return $input['csrf_token'];
    }
    
    return '';
}

/**
 * Check if request is HTTPS
 */
function isHTTPS(): bool {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );
}

/**
 * Add security headers
 */
function addSecurityHeaders(): void {
    // Prevent XSS
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Prevent framing (clickjacking)
    header('X-Frame-Options: DENY');
    
    // Content Security Policy (basic)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://unpkg.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;");
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Remove server information
    header_remove('X-Powered-By');
    header_remove('Server');
}

/**
 * Validate authentication token with security checks
 */
function validateAuthToken(PDO $db, bool $required = true): ?array {
    $token = null;
    
    // Get token from various sources
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
        }
    } elseif (isset($_COOKIE['auth_token'])) {
        $token = $_COOKIE['auth_token'];
    } elseif (isset($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    // Validate token format
    $token = InputValidator::validateToken($token);
    if ($token === null) {
        if ($required) {
            SecurityManager::logSecurityEvent('invalid_token', ['reason' => 'invalid_format']);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid authentication token']);
            exit;
        }
        return null;
    }
    
    // Verify token in database
    $stmt = $db->prepare("
        SELECT u.id, u.name, u.email, u.role, u.email_verified, u.status,
               s.expires_at
        FROM users u
        INNER JOIN sessions s ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        if ($required) {
            SecurityManager::logSecurityEvent('invalid_token', ['reason' => 'not_found_or_expired']);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }
        return null;
    }
    
    // Check account status
    if ($user['status'] !== 'approved') {
        SecurityManager::logSecurityEvent('invalid_token', [
            'reason' => 'account_not_approved',
            'user_id' => $user['id'],
            'status' => $user['status']
        ]);
        http_response_code(403);
        echo json_encode(['error' => 'Account not approved']);
        exit;
    }
    
    return $user;
}

/**
 * Require specific role
 */
function requireRole(array $user, array $allowedRoles): void {
    if (!in_array($user['role'], $allowedRoles, true)) {
        SecurityManager::logSecurityEvent('invalid_token', [
            'reason' => 'insufficient_role',
            'user_id' => $user['id'],
            'user_role' => $user['role'],
            'required_roles' => $allowedRoles
        ]);
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        exit;
    }
}

/**
 * Validate JSON input with security checks
 */
function validateJsonInput(): ?array {
    $rawInput = file_get_contents('php://input');
    
    // Check input size (prevent DoS)
    if (strlen($rawInput) > 1048576) { // 1MB limit
        http_response_code(413);
        echo json_encode(['error' => 'Request too large']);
        exit;
    }
    
    $input = InputValidator::validateJsonInput($rawInput);
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    return $input;
}

/**
 * Clean up security data (call periodically)
 */
function cleanupSecurityData(): void {
    SecurityManager::cleanup();
}
