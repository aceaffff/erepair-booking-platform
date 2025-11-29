<?php

// /**
//  * Security Cleanup Script
//  * Run this periodically via cron job to clean up security data
//  * 
//  * Recommended cron schedule: */15 * * * * (every 15 minutes)
//  */

require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../config/database.php';

try {
    echo "Starting security cleanup...\n";
    
    // Clean up expired security data
    SecurityManager::cleanup();
    echo "✓ Cleaned up expired CSRF tokens and rate limit records\n";
    
    // Clean up expired sessions
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->exec("DELETE FROM sessions WHERE expires_at < NOW()");
    echo "✓ Cleaned up {$stmt} expired sessions\n";
    
    // Clean up old password reset codes
    $stmt = $db->exec("DELETE FROM password_resets WHERE expires_at < NOW()");
    echo "✓ Cleaned up {$stmt} expired password reset codes\n";
    
    // Get security statistics
    $stats = SecurityManager::getSecurityStats();
    echo "\nSecurity Statistics (last 24 hours):\n";
    echo "- CSRF violations: {$stats['csrf_violations_24h']}\n";
    echo "- Rate limit violations: {$stats['rate_limit_violations_24h']}\n";
    echo "- Currently blocked identifiers: {$stats['blocked_identifiers']}\n";
    echo "- Active CSRF tokens: {$stats['active_csrf_tokens']}\n";
    
    echo "\nSecurity cleanup completed successfully.\n";
    
} catch (Exception $e) {
    echo "Error during security cleanup: " . $e->getMessage() . "\n";
    exit(1);
}
