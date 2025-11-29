# ERepair Backend Architecture Documentation

## Table of Contents
1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Directory Structure](#directory-structure)
4. [API Architecture](#api-architecture)
5. [Database Layer](#database-layer)
6. [Security Architecture](#security-architecture)
7. [Authentication & Authorization](#authentication--authorization)
8. [Request/Response Flow](#requestresponse-flow)
9. [Error Handling](#error-handling)
10. [File Upload System](#file-upload-system)
11. [Email Service](#email-service)
12. [Best Practices](#best-practices)

---

## Overview

The ERepair backend is built using **PHP 7.4+** with a **MySQL 5.7+** database. It follows a **layered architecture** pattern with clear separation of concerns:

- **API Layer**: RESTful endpoints handling HTTP requests
- **Business Logic Layer**: Core application logic and validation
- **Data Access Layer**: Database operations using PDO
- **Utility Layer**: Reusable helper classes and services
- **Middleware Layer**: Security, authentication, and request processing

---

## System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Client (Frontend)                     │
│                    (HTML/CSS/JavaScript)                     │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP/HTTPS
                            │ JSON Requests/Responses
┌───────────────────────────▼─────────────────────────────────┐
│                      API Endpoints Layer                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Auth APIs   │  │ Booking APIs │  │  Admin APIs  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│                    Middleware Layer                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Security   │  │  Auth Check  │  │ Rate Limiting│      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│                  Business Logic Layer                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Validators  │  │  Helpers     │  │  Services    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│                    Data Access Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Database   │  │ Transactions │  │   Queries    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│                      MySQL Database                           │
│              (16 Tables, Views, Procedures)                   │
└─────────────────────────────────────────────────────────────┘
```

### Technology Stack

- **Language**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **Database Access**: PDO (PHP Data Objects)
- **Email**: PHPMailer 6.8+
- **Dependency Management**: Composer
- **Character Encoding**: UTF8MB4

---

## Directory Structure

```
backend/
├── api/                          # API Endpoints
│   ├── admin/                    # Admin-specific endpoints
│   │   ├── approve-shop.php
│   │   ├── profile-update.php
│   │   └── upload-logo.php
│   ├── users/                    # User management endpoints
│   │   └── me.php                # Get current user info
│   ├── login.php                 # Authentication
│   ├── logout.php                # Session termination
│   ├── register-customer.php     # Customer registration
│   ├── register-shop-owner.php   # Shop owner registration
│   ├── verify-email.php          # Email verification
│   ├── verify-email-code.php     # Verify email code
│   ├── forgot-password-request.php
│   ├── reset-password.php
│   ├── shop-homepage.php         # Shop information
│   ├── shop-items.php            # Shop products/items
│   ├── get-ratings.php           # Rating information
│   ├── submit-review.php         # Review submission
│   └── get-website-logo.php      # Logo retrieval
│
├── config/                       # Configuration Files
│   ├── database.php              # Database connection class
│   ├── email.php                 # Email service configuration
│   ├── security.php              # Security settings
│   └── api_keys.php              # API keys (if needed)
│
├── middleware/                   # Middleware Components
│   └── security.php              # Security middleware functions
│
├── utils/                        # Utility Classes
│   ├── ResponseHelper.php        # Standardized JSON responses
│   ├── DBTransaction.php        # Transaction management
│   ├── InputValidator.php        # Input validation & sanitization
│   ├── SecurityManager.php       # Security utilities
│   ├── NotificationHelper.php    # Notification management
│   ├── DocumentValidator.php     # Document validation
│   └── DocumentAPIValidator.php  # API document validation
│
├── migrations/                   # Database Migrations
│   └── [migration files]
│
├── scripts/                      # Utility Scripts
│   └── security_cleanup.php
│
├── uploads/                      # File Upload Storage
│   ├── customers/               # Customer documents
│   ├── shop_owners/             # Shop owner documents
│   └── logos/                   # Shop logos
│
├── setup.php                     # Database initialization
├── setup_database.php            # Database setup script
└── setup_email.php               # Email configuration setup
```

---

## API Architecture

### API Design Principles

1. **RESTful Design**: Standard HTTP methods (GET, POST, PUT, DELETE)
2. **JSON Format**: All requests and responses use JSON
3. **Consistent Responses**: Standardized response format
4. **Error Handling**: Proper HTTP status codes and error messages
5. **Security First**: All endpoints protected by security middleware

### API Endpoint Structure

#### Authentication Endpoints

| Endpoint | Method | Description | Auth Required |
|----------|--------|-------------|---------------|
| `/api/login.php` | POST | User login | No |
| `/api/logout.php` | POST | User logout | Yes |
| `/api/register-customer.php` | POST | Customer registration | No |
| `/api/register-shop-owner.php` | POST | Shop owner registration | No |
| `/api/verify-email.php` | POST | Request email verification | No |
| `/api/verify-email-code.php` | POST | Verify email code | No |
| `/api/resend-verification-code.php` | POST | Resend verification code | No |
| `/api/forgot-password-request.php` | POST | Request password reset | No |
| `/api/reset-password.php` | POST | Reset password | No |

#### User Endpoints

| Endpoint | Method | Description | Auth Required |
|----------|--------|-------------|---------------|
| `/api/users/me.php` | GET | Get current user info | Yes |

#### Shop Endpoints

| Endpoint | Method | Description | Auth Required |
|----------|--------|-------------|---------------|
| `/api/shop-homepage.php` | GET | Get shop information | No |
| `/api/shop-items.php` | GET | Get shop items/products | No |
| `/api/get-ratings.php` | GET | Get shop/technician ratings | No |
| `/api/submit-review.php` | POST | Submit review | Yes |

#### Admin Endpoints

| Endpoint | Method | Description | Auth Required |
|----------|--------|-------------|---------------|
| `/api/admin/approve-shop.php` | POST | Approve/reject shop | Yes (Admin) |
| `/api/admin/profile-update.php` | POST | Update admin profile | Yes (Admin) |
| `/api/admin/upload-logo.php` | POST | Upload website logo | Yes (Admin) |

### Request/Response Format

#### Standard Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  }
}
```

#### Standard Error Response
```json
{
  "error": true,
  "message": "Error description",
  "details": {
    // Optional error details
  }
}
```

#### Authentication Response
```json
{
  "success": true,
  "token": "64-character-hex-token",
  "role": "customer|shop_owner|technician|admin",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "customer"
  }
}
```

---

## Database Layer

### Database Connection

The `Database` class provides a singleton-like connection pattern:

```php
$database = new Database();
$db = $database->getConnection();
```

**Location**: `backend/config/database.php`

**Features**:
- PDO-based connection
- Exception handling
- UTF8MB4 character encoding
- Prepared statement support

### Transaction Management

The `DBTransaction` class provides safe transaction handling:

```php
use DBTransaction;

// Start transaction
DBTransaction::start($db);

try {
    // Database operations
    DBTransaction::commit($db);
} catch (Exception $e) {
    DBTransaction::rollback($db);
    throw $e;
}
```

**Location**: `backend/utils/DBTransaction.php`

**Features**:
- Prevents "no active transaction" errors
- Automatic rollback on exceptions
- Nested transaction support

### Database Schema

The system uses **16 interconnected tables**:

1. **Core Tables**: `users`, `sessions`, `email_verifications`, `password_resets`
2. **Shop Tables**: `shop_owners`, `repair_shops`
3. **Technician Tables**: `technicians`
4. **Service Tables**: `services`, `shop_services`
5. **Booking Tables**: `bookings`, `booking_history`
6. **Review Tables**: `reviews`, `shop_ratings`, `technician_ratings`
7. **Notification Tables**: `notifications`
8. **Product Tables**: `shop_items`

**Full Schema**: See `backend/schema_complete.sql`

---

## Security Architecture

### Security Layers

1. **Input Validation**: All inputs validated and sanitized
2. **SQL Injection Prevention**: Prepared statements only
3. **XSS Prevention**: Output escaping
4. **CSRF Protection**: Token-based protection for state changes
5. **Rate Limiting**: Prevents brute force attacks
6. **Password Security**: Bcrypt hashing
7. **Session Security**: Token-based with expiration

### Security Middleware

**Location**: `backend/middleware/security.php`

**Functions**:
- `applySecurityMiddleware()`: Apply security checks
- `requireAuth()`: Require authentication
- `requireRole()`: Require specific role
- `validateJsonInput()`: Validate JSON payloads

**Usage**:
```php
require_once '../middleware/security.php';

// Apply security
applySecurityMiddleware([
    'rate_limit' => true,
    'rate_limit_max' => 60,
    'rate_limit_window' => 60,
    'csrf_protection' => false,
    'validate_origin' => true
]);

// Require authentication
$user = requireAuth();

// Require specific role
requireRole($user, ['admin']);
```

### SecurityManager Class

**Location**: `backend/utils/SecurityManager.php`

**Features**:
- Rate limiting
- Origin validation
- CSRF token management
- Security event logging
- IP-based tracking

### Input Validation

**Location**: `backend/utils/InputValidator.php`

**Methods**:
- `validateEmail()`: Email validation
- `validatePassword()`: Password validation
- `validateString()`: String validation
- `validateJsonInput()`: JSON validation
- `detectSqlInjection()`: SQL injection detection
- `validateFileUpload()`: File upload validation

---

## Authentication & Authorization

### Authentication Flow

1. **Login Request**: Client sends email/password
2. **Validation**: Server validates credentials
3. **Session Creation**: Server creates session token
4. **Token Response**: Server returns token to client
5. **Token Storage**: Client stores token (localStorage/cookie)
6. **Subsequent Requests**: Client sends token in header

### Session Management

- **Token Format**: 64-character hexadecimal string
- **Token Storage**: `sessions` table
- **Expiration**: 7 days (configurable)
- **Token Validation**: Checked on each authenticated request

### Authorization Levels

1. **Public**: No authentication required
2. **Authenticated**: Valid token required
3. **Role-Based**: Specific role required (customer, shop_owner, technician, admin)

### Token Usage

```php
// In API endpoint
$user = requireAuth(); // Validates token and returns user

// Check role
requireRole($user, ['admin', 'shop_owner']);
```

---

## Request/Response Flow

### Standard Request Flow

```
1. Client Request
   ↓
2. Security Middleware
   - Rate limiting
   - Origin validation
   - CSRF check (if needed)
   ↓
3. Input Validation
   - JSON parsing
   - Input sanitization
   - SQL injection detection
   ↓
4. Authentication Check (if required)
   - Token validation
   - User retrieval
   - Role verification
   ↓
5. Business Logic
   - Data processing
   - Database operations
   - Business rules
   ↓
6. Response Generation
   - Success/Error formatting
   - JSON encoding
   ↓
7. Client Response
```

### Example API Endpoint Structure

```php
<?php
// 1. Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 2. Dependencies
require_once '../config/database.php';
require_once '../utils/ResponseHelper.php';
require_once '../middleware/security.php';

// 3. Security Middleware
applySecurityMiddleware(['rate_limit' => true]);

// 4. Method Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ResponseHelper::methodNotAllowed();
}

// 5. Input Validation
$input = validateJsonInput();
$email = InputValidator::validateEmail($input['email'] ?? '');

// 6. Authentication (if needed)
$user = requireAuth();
requireRole($user, ['customer']);

// 7. Business Logic
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Database operations
    // ...
    
    ResponseHelper::success('Operation successful', $data);
} catch (Exception $e) {
    ResponseHelper::serverError('Operation failed', $e->getMessage());
}
?>
```

---

## Error Handling

### Error Response Format

All errors follow a consistent format:

```json
{
  "error": true,
  "message": "Human-readable error message",
  "details": {
    // Optional technical details
  }
}
```

### HTTP Status Codes

- **200 OK**: Successful request
- **400 Bad Request**: Invalid input or validation error
- **401 Unauthorized**: Authentication required or failed
- **403 Forbidden**: Insufficient permissions
- **404 Not Found**: Resource not found
- **405 Method Not Allowed**: Wrong HTTP method
- **409 Conflict**: Resource conflict (e.g., duplicate email)
- **413 Payload Too Large**: Request too large
- **429 Too Many Requests**: Rate limit exceeded
- **500 Internal Server Error**: Server error

### ResponseHelper Methods

```php
ResponseHelper::success($message, $data, $httpCode);
ResponseHelper::error($message, $httpCode, $details);
ResponseHelper::validationError($message, $errors);
ResponseHelper::unauthorized($message);
ResponseHelper::forbidden($message);
ResponseHelper::notFound($message);
ResponseHelper::serverError($message, $details);
```

---

## File Upload System

### Upload Directories

- **Customer Documents**: `backend/uploads/customers/`
- **Shop Owner Documents**: `backend/uploads/shop_owners/`
- **Shop Logos**: `backend/uploads/logos/`
- **Device Photos**: `frontend/uploads/device_photos/`
- **Avatars**: `frontend/uploads/avatars/`
- **Shop Items**: `frontend/uploads/shop_items/`

### File Validation

**Security Measures**:
- File type validation (MIME type)
- File size limits (5MB default)
- Filename sanitization
- Unique filename generation
- Secure file storage

**Example**:
```php
$file_data = InputValidator::validateFileUpload($_FILES['file'] ?? null);
if ($file_data === null) {
    ResponseHelper::error('Invalid file upload');
}

// Move file
$filename = uniqid('prefix_', true) . '.' . $ext;
move_uploaded_file($file_data['tmp_name'], $upload_dir . $filename);
```

### Allowed File Types

- **Images**: `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- **Documents**: `application/pdf`

---

## Email Service

### EmailService Class

**Location**: `backend/config/email.php`

**Features**:
- PHPMailer integration
- SMTP configuration
- Email templates
- Verification code emails
- Password reset emails
- Notification emails

### Email Types

1. **Verification Code**: 6-digit code, 5-minute expiry
2. **Password Reset**: 12-character code, 15-minute expiry
3. **Booking Notifications**: Status updates, confirmations
4. **Admin Notifications**: Shop approval, system alerts

---

## Best Practices

### Code Organization

1. **Single Responsibility**: Each class/function has one purpose
2. **DRY Principle**: Don't repeat yourself
3. **Separation of Concerns**: Clear layer separation
4. **Consistent Naming**: Follow PHP naming conventions

### Security Best Practices

1. **Always use prepared statements**: Never concatenate SQL
2. **Validate all inputs**: Server-side validation required
3. **Sanitize outputs**: Prevent XSS attacks
4. **Use HTTPS in production**: Encrypt all communications
5. **Implement rate limiting**: Prevent abuse
6. **Log security events**: Monitor for attacks
7. **Keep dependencies updated**: Security patches

### Database Best Practices

1. **Use transactions**: For multi-step operations
2. **Index frequently queried columns**: Performance optimization
3. **Use foreign keys**: Data integrity
4. **Avoid SELECT ***: Select only needed columns
5. **Use LIMIT**: For pagination
6. **Clean up expired data**: Regular maintenance

### API Best Practices

1. **Consistent response format**: Standard JSON structure
2. **Proper HTTP methods**: GET, POST, PUT, DELETE
3. **Meaningful error messages**: Helpful for debugging
4. **Versioning**: Consider API versioning for future changes
5. **Documentation**: Keep API docs updated
6. **Rate limiting**: Protect against abuse

### Error Handling Best Practices

1. **Never expose sensitive information**: In error messages
2. **Log detailed errors**: Server-side logging
3. **User-friendly messages**: Client-side messages
4. **Proper HTTP status codes**: Accurate status representation
5. **Graceful degradation**: Handle errors gracefully

---

## Development Guidelines

### Adding New API Endpoints

1. Create file in appropriate directory (`api/`, `api/admin/`, etc.)
2. Include required dependencies
3. Apply security middleware
4. Validate inputs
5. Implement business logic
6. Return standardized response
7. Handle errors properly

### Adding New Utility Classes

1. Create file in `utils/` directory
2. Follow PSR-4 autoloading standards
3. Use static methods for utility functions
4. Document all public methods
5. Include error handling

### Database Migrations

1. Create migration file in `migrations/` directory
2. Use transactions for safety
3. Test on development first
4. Backup database before production
5. Document schema changes

---

## Performance Considerations

### Optimization Strategies

1. **Database Indexing**: Index frequently queried columns
2. **Query Optimization**: Use EXPLAIN to analyze queries
3. **Caching**: Consider caching for frequently accessed data
4. **Connection Pooling**: Reuse database connections
5. **Lazy Loading**: Load data only when needed

### Monitoring

1. **Error Logging**: Monitor error logs
2. **Performance Metrics**: Track response times
3. **Database Queries**: Monitor slow queries
4. **Security Events**: Track security incidents

---

## Deployment Considerations

### Production Checklist

- [ ] Enable HTTPS
- [ ] Update database credentials
- [ ] Configure email service
- [ ] Set proper file permissions
- [ ] Enable error logging (hide from users)
- [ ] Configure rate limiting
- [ ] Set up database backups
- [ ] Enable security headers
- [ ] Review and update dependencies
- [ ] Test all endpoints
- [ ] Set up monitoring

### Environment Configuration

- **Development**: Relaxed security, detailed errors
- **Staging**: Production-like, testing environment
- **Production**: Maximum security, minimal error exposure

---

## Conclusion

This architecture provides a solid foundation for the ERepair platform with:

- **Security**: Multiple layers of protection
- **Scalability**: Modular, extensible design
- **Maintainability**: Clear structure and documentation
- **Reliability**: Error handling and transaction support
- **Performance**: Optimized database and query patterns


