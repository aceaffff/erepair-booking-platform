<?php

/**
 * Security Manager for CSRF Protection and Rate Limiting
 */
class SecurityManager {
    
    private static $pdo = null;
    
    /**
     * Initialize database connection
     */
    private static function getDb(): PDO {
        if (self::$pdo === null) {
            require_once __DIR__ . '/../config/database.php';
            $database = new Database();
            self::$pdo = $database->getConnection();
            
            // Create security tables if they don't exist
            self::createTables();
        }
        return self::$pdo;
    }
    
    /**
     * Create security-related tables
     */
    private static function createTables(): void {
        $db = self::$pdo;
        
        // CSRF tokens table
        $db->exec("CREATE TABLE IF NOT EXISTS csrf_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            user_id INT,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Rate limiting table
        $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            attempts INT DEFAULT 1,
            window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            blocked_until TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_rate_limit (identifier, endpoint),
            INDEX idx_identifier (identifier),
            INDEX idx_endpoint (endpoint),
            INDEX idx_blocked (blocked_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Security events log
        $db->exec("CREATE TABLE IF NOT EXISTS security_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type ENUM('csrf_violation', 'rate_limit_exceeded', 'invalid_token', 'suspicious_activity') NOT NULL,
            identifier VARCHAR(255),
            endpoint VARCHAR(255),
            user_agent TEXT,
            ip_address VARCHAR(45),
            details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_type (event_type),
            INDEX idx_identifier (identifier),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(int $userId = null): string {
        $db = self::getDb();
        
        // Clean up expired tokens
        $db->exec("DELETE FROM csrf_tokens WHERE expires_at < NOW()");
        
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store token
        $stmt = $db->prepare("INSERT INTO csrf_tokens (token, user_id, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$token, $userId, $expiresAt]);
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken(string $token, int $userId = null): bool {
        if (empty($token)) return false;
        
        $db = self::getDb();
        
        // Clean up expired tokens first
        $db->exec("DELETE FROM csrf_tokens WHERE expires_at < NOW()");
        
        // Validate token
        $stmt = $db->prepare("SELECT id FROM csrf_tokens WHERE token = ? AND expires_at > NOW() AND (user_id = ? OR user_id IS NULL)");
        $stmt->execute([$token, $userId]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Token is valid, remove it (one-time use)
            $stmt = $db->prepare("DELETE FROM csrf_tokens WHERE token = ?");
            $stmt->execute([$token]);
            return true;
        }
        
        // Log CSRF violation
        self::logSecurityEvent('csrf_violation', [
            'token' => substr($token, 0, 8) . '...',
            'user_id' => $userId
        ]);
        
        return false;
    }
    
    /**
     * Check rate limit for an endpoint
     */
    public static function checkRateLimit(string $endpoint, int $maxAttempts = 60, int $windowMinutes = 60): bool {
        $db = self::getDb();
        $identifier = self::getClientIdentifier();
        
        // Clean up old rate limit records
        $db->exec("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        
        // Check if currently blocked
        $stmt = $db->prepare("SELECT blocked_until FROM rate_limits WHERE identifier = ? AND endpoint = ? AND blocked_until > NOW()");
        $stmt->execute([$identifier, $endpoint]);
        if ($stmt->fetch()) {
            self::logSecurityEvent('rate_limit_exceeded', [
                'endpoint' => $endpoint,
                'identifier' => $identifier,
                'status' => 'blocked'
            ]);
            return false;
        }
        
        // Get current window data
        $windowStart = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));
        $stmt = $db->prepare("SELECT attempts FROM rate_limits WHERE identifier = ? AND endpoint = ? AND window_start > ?");
        $stmt->execute([$identifier, $endpoint, $windowStart]);
        $current = $stmt->fetch();
        
        $attempts = $current ? $current['attempts'] : 0;
        
        if ($attempts >= $maxAttempts) {
            // Block for double the window time
            $blockedUntil = date('Y-m-d H:i:s', time() + ($windowMinutes * 120));
            
            $stmt = $db->prepare("INSERT INTO rate_limits (identifier, endpoint, attempts, blocked_until) 
                                 VALUES (?, ?, ?, ?) 
                                 ON DUPLICATE KEY UPDATE attempts = ?, blocked_until = ?");
            $stmt->execute([$identifier, $endpoint, $attempts + 1, $blockedUntil, $attempts + 1, $blockedUntil]);
            
            self::logSecurityEvent('rate_limit_exceeded', [
                'endpoint' => $endpoint,
                'identifier' => $identifier,
                'attempts' => $attempts + 1,
                'blocked_until' => $blockedUntil
            ]);
            
            return false;
        }
        
        // Update attempts
        $stmt = $db->prepare("INSERT INTO rate_limits (identifier, endpoint, attempts) 
                             VALUES (?, ?, 1) 
                             ON DUPLICATE KEY UPDATE attempts = attempts + 1, updated_at = NOW()");
        $stmt->execute([$identifier, $endpoint]);
        
        return true;
    }
    
    /**
     * Get client identifier for rate limiting
     */
    private static function getClientIdentifier(): string {
        $ip = self::getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Create a hash of IP + User Agent for privacy
        return hash('sha256', $ip . '|' . $userAgent);
    }
    
    /**
     * Get real client IP address
     */
    public static function getClientIP(): string {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent(string $eventType, array $details = []): void {
        try {
            $db = self::getDb();
            
            $identifier = self::getClientIdentifier();
            $endpoint = $_SERVER['REQUEST_URI'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ipAddress = self::getClientIP();
            
            $stmt = $db->prepare("INSERT INTO security_events (event_type, identifier, endpoint, user_agent, ip_address, details) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $eventType,
                $identifier,
                $endpoint,
                $userAgent,
                $ipAddress,
                json_encode($details)
            ]);
        } catch (Exception $e) {
            // Log to error log if database logging fails
            error_log("Security event logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Validate request origin (basic CSRF protection)
     */
    public static function validateOrigin(): bool {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // For AJAX requests, check Origin header
        if (!empty($origin)) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            return $originHost === $host;
        }
        
        // For form submissions, check Referer
        if (!empty($referer)) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            return $refererHost === $host;
        }
        
        // If neither is present, it might be a direct API call
        // Allow it but log for monitoring
        self::logSecurityEvent('suspicious_activity', [
            'reason' => 'missing_origin_and_referer',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        return true; // Allow but monitor
    }
    
    /**
     * Generate secure session ID
     */
    public static function generateSecureSessionId(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Validate session token format
     */
    public static function validateSessionToken(string $token): bool {
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }
    
    /**
     * Clean up expired security data
     */
    public static function cleanup(): void {
        $db = self::getDb();
        
        // Clean up expired CSRF tokens
        $db->exec("DELETE FROM csrf_tokens WHERE expires_at < NOW()");
        
        // Clean up old rate limit records (older than 24 hours)
        $db->exec("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        
        // Clean up old security events (older than 30 days)
        $db->exec("DELETE FROM security_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    }
    
    /**
     * Get security statistics
     */
    public static function getSecurityStats(): array {
        $db = self::getDb();
        
        $stats = [];
        
        // CSRF violations in last 24 hours
        $stmt = $db->query("SELECT COUNT(*) as count FROM security_events WHERE event_type = 'csrf_violation' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stats['csrf_violations_24h'] = $stmt->fetchColumn();
        
        // Rate limit violations in last 24 hours
        $stmt = $db->query("SELECT COUNT(*) as count FROM security_events WHERE event_type = 'rate_limit_exceeded' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stats['rate_limit_violations_24h'] = $stmt->fetchColumn();
        
        // Currently blocked IPs
        $stmt = $db->query("SELECT COUNT(DISTINCT identifier) as count FROM rate_limits WHERE blocked_until > NOW()");
        $stats['blocked_identifiers'] = $stmt->fetchColumn();
        
        // Active CSRF tokens
        $stmt = $db->query("SELECT COUNT(*) as count FROM csrf_tokens WHERE expires_at > NOW()");
        $stats['active_csrf_tokens'] = $stmt->fetchColumn();
        
        return $stats;
    }
}
