<?php

/**
 * Security Configuration
 */

return [
    // Rate limiting defaults
    'rate_limiting' => [
        'default_max_attempts' => 60,
        'default_window_minutes' => 60,
        'strict_endpoints' => [
            '/api/login.php' => ['max' => 10, 'window' => 15],
            '/api/register-customer.php' => ['max' => 5, 'window' => 60],
            '/api/register-shop-owner.php' => ['max' => 3, 'window' => 60],
            '/api/forgot-password-request.php' => ['max' => 5, 'window' => 60],
            '/api/reset-password.php' => ['max' => 5, 'window' => 60],
        ]
    ],
    
    // CSRF protection
    'csrf' => [
        'token_lifetime' => 3600, // 1 hour
        'require_for_state_changes' => true,
    ],
    
    // Session security
    'session' => [
        'lifetime' => 86400, // 24 hours
        'cleanup_probability' => 0.01, // 1% chance to run cleanup
    ],
    
    // File upload security
    'file_upload' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => [
            'image/jpeg',
            'image/png', 
            'image/gif',
            'image/webp',
            'application/pdf'
        ],
        'upload_dir_permissions' => 0755,
    ],
    
    // Input validation
    'validation' => [
        'max_json_size' => 1048576, // 1MB
        'max_string_length' => 10000,
        'password_min_length' => 6,
    ],
    
    // Security headers
    'headers' => [
        'hsts_max_age' => 31536000, // 1 year
        'csp_report_uri' => null, // Set to your CSP report endpoint
    ],
    
    // Logging
    'logging' => [
        'log_security_events' => true,
        'log_retention_days' => 30,
        'log_failed_logins' => true,
    ],
    
    // Environment specific settings
    'production' => [
        'require_https' => true,
        'strict_transport_security' => true,
        'hide_error_details' => true,
    ],
    
    'development' => [
        'require_https' => false,
        'strict_transport_security' => false,
        'hide_error_details' => false,
    ]
];
