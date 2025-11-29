# ERepair Platform - Complete Documentation

**Version**: 1.0  
**Last Updated**: 2024

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Technology Stack](#technology-stack)
4. [Installation & Setup](#installation--setup)
5. [Backend Architecture](#backend-architecture)
6. [Frontend Architecture](#frontend-architecture)
7. [Database Architecture](#database-architecture)
8. [API Documentation](#api-documentation)
9. [Android App](#android-app)
10. [Security](#security)
11. [Deployment](#deployment)

---

## Project Overview

ERepair is a comprehensive electronics repair booking platform with role-based access for customers, shop owners, technicians, and administrators.

### Key Features

- **Multi-role System**: Customer, Shop Owner, Technician, Admin
- **Booking Management**: Complete repair booking workflow
- **Real-time Updates**: Status tracking and notifications
- **Location Services**: GPS-based shop discovery
- **Review System**: Customer reviews and ratings
- **Progressive Web App**: Installable, offline-capable web app
- **Android App**: Native mobile application (documented)

---

## System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    CLIENT LAYER                             │
│  ┌──────────────────┐      ┌──────────────────┐             │
│  │   Web Browser    │      │  Android App     │             │
│  │  (HTML/CSS/JS)   │      │  (Kotlin/Compose)│             │
│  └──────────────────┘      └──────────────────┘             │
└────────────────────┬───────────────────┬────────────────────┘
                     │                   │
                     │ HTTP/HTTPS        │ HTTP/HTTPS
                     │ JSON API          │ JSON API
                     │                   │
┌────────────────────▼───────────────────▼─────────────────────┐
│                    PRESENTATION LAYER                        │
│              Frontend (Web Application)                       │
└────────────────────────────┬─────────────────────────────────┘
                              │
                              │ AJAX/Fetch API Calls
                              │
┌────────────────────────────▼──────────────────────────────────┐
│                    APPLICATION LAYER                          │
│              Backend API (PHP)                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                    │
│  │   API    │  │Middleware│  │ Business │                    │
│  │Endpoints │  │ Security │  │  Logic   │                    │
│  └──────────┘  └──────────┘  └──────────┘                    │
└────────────────────────────┬──────────────────────────────────┘
                              │
                              │ PDO Prepared Statements
                              │
┌────────────────────────────▼──────────────────────────────────┐
│                      DATA LAYER                               │
│              MySQL Database (16 Tables)                        │
└───────────────────────────────────────────────────────────────┘
```

### Architecture Layers

1. **Client Layer**: Web browsers and Android app
2. **Presentation Layer**: Frontend web application (HTML/CSS/JS)
3. **Application Layer**: Backend API (PHP) with middleware
4. **Data Layer**: MySQL database with 16 tables

---

## Technology Stack

### Frontend
- **HTML5**: Semantic markup, PWA support
- **CSS3**: Styling
- **TailwindCSS 3.4**: Utility-first CSS framework
- **JavaScript (ES6+)**: Client-side logic
- **Alpine.js 3.x**: Lightweight reactive framework
- **SweetAlert2**: Alert dialogs (for complex inputs)
- **Notiflix 3.2.6**: Notifications and dialogs
- **Font Awesome 6.0**: Icon library
- **Leaflet.js 1.9.4**: Open-source mapping (free)
- **Chart.js**: Data visualization

### Backend
- **PHP 7.4+**: Server-side scripting
- **MySQL 5.7+**: Database management
- **PDO**: Database abstraction with prepared statements
- **PHPMailer 6.8+**: Email functionality
- **Composer**: Dependency management

### Android App (Optional)
- **Kotlin 1.9+**: Programming language
- **Jetpack Compose**: Modern UI toolkit
- **Material Design 3**: Design system
- **Retrofit 2**: HTTP client
- **Room**: Local database
- **MVVM Architecture**: Clean architecture pattern
- **OpenStreetMap (osmdroid)**: Free maps (no API key needed)

### Database
- **MySQL 5.7+ / MariaDB 10.2+**
- **InnoDB Engine**
- **UTF8MB4 Encoding**
- **16 Tables**
- **3 Views**
- **3 Stored Procedures**

---

## Installation & Setup

### Prerequisites
- XAMPP/WAMP/LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Setup Instructions

1. **Place the project** in your web server directory
   - For XAMPP: `C:\xampp\htdocs\ERepair\repair-booking-platform`

2. **Start your web server**
   - Start Apache and MySQL services in XAMPP

3. **Initialize the database**
   - Navigate to: `http://localhost/ERepair/repair-booking-platform/backend/setup.php`
   - This creates the database and tables automatically

4. **Access the application**
   - Open: `http://localhost/ERepair/repair-booking-platform/frontend/index.html`

### Default Admin Credentials
- **Email**: admin@repair.com
- **Password**: admin123
- **⚠️ IMPORTANT**: Change immediately in production!

---

## Backend Architecture

### Directory Structure

```
backend/
├── api/                          # API Endpoints
│   ├── admin/                    # Admin endpoints
│   ├── users/                    # User endpoints
│   ├── login.php                 # Authentication
│   ├── register-customer.php
│   ├── register-shop-owner.php
│   └── [other endpoints]
├── config/                       # Configuration
│   ├── database.php              # Database connection
│   ├── email.php                 # Email service
│   └── security.php              # Security settings
├── middleware/                   # Middleware
│   └── security.php              # Security middleware
├── utils/                        # Utility Classes
│   ├── ResponseHelper.php       # JSON responses
│   ├── DBTransaction.php        # Transaction handling
│   ├── InputValidator.php       # Input validation
│   └── [other utilities]
└── uploads/                      # File Storage
```

### Backend Architecture Pattern

```
API Endpoint
    ↓
Security Middleware
    ↓
Input Validation
    ↓
Business Logic
    ↓
Database Access
    ↓
Response Generation
```

### Key Components

- **ResponseHelper**: Standardized JSON responses
- **DBTransaction**: Safe transaction handling
- **InputValidator**: Input validation and sanitization
- **SecurityManager**: Security utilities and rate limiting
- **NotificationHelper**: Notification management

---

## Frontend Architecture

### Directory Structure

```
frontend/
├── index.html                    # Landing page
├── manifest.json                 # PWA manifest
├── service-worker.js            # PWA service worker
├── auth/                        # Authentication pages
├── customer/                    # Customer dashboard
├── shop/                        # Shop owner dashboard
├── technician/                  # Technician dashboard
├── admin/                       # Admin dashboard
└── assets/                      # CSS, JS, images
```

### Frontend Patterns

- **Component-Based**: Reusable Alpine.js components
- **State Management**: Alpine.js reactive data
- **API Communication**: Centralized API request functions
- **PWA Support**: Service worker for offline functionality

---

## Database Architecture

### Database Schema

The system uses **16 interconnected tables**:

#### Core Tables (4)
- `users` - All user accounts
- `sessions` - Authentication tokens
- `email_verifications` - Email verification codes
- `password_resets` - Password reset tokens

#### Shop Tables (2)
- `shop_owners` - Shop owner profiles and business documents
- `repair_shops` - Physical shop locations and details

#### Technician Tables (1)
- `technicians` - Technician profiles

#### Service Tables (2)
- `services` - Services offered by shops
- `shop_services` - Alternative service structure

#### Booking Tables (2)
- `bookings` - Main booking/repair job records
- `booking_history` - Complete audit trail

#### Review Tables (3)
- `reviews` - Customer reviews
- `shop_ratings` - Aggregated shop ratings
- `technician_ratings` - Aggregated technician ratings

#### Other Tables (2)
- `notifications` - System notifications
- `shop_items` - Shop products/items

### Database Features

- **Foreign Keys**: Data integrity enforcement
- **Indexes**: Optimized query performance
- **Views**: Predefined queries (view_active_bookings, view_shop_performance, view_pending_approvals)
- **Stored Procedures**: Automated rating calculations
- **Events**: Automated cleanup of expired data

---

## API Documentation

### API Design Principles

1. **RESTful Design**: Standard HTTP methods
2. **JSON Format**: All requests/responses in JSON
3. **Consistent Responses**: Standardized response format
4. **Error Handling**: Proper HTTP status codes
5. **Security First**: All endpoints protected

### API Endpoints

#### Authentication
- `POST /api/login.php` - User login
- `POST /api/logout.php` - User logout
- `POST /api/register-customer.php` - Customer registration
- `POST /api/register-shop-owner.php` - Shop owner registration
- `POST /api/verify-email-code.php` - Email verification
- `POST /api/forgot-password-request.php` - Password reset request
- `POST /api/reset-password.php` - Reset password

#### User Management
- `GET /api/users/me.php` - Get current user

#### Shop Management
- `GET /api/shop-homepage.php` - Get shop details
- `GET /api/shop-items.php` - Get shop items

#### Review & Rating
- `POST /api/submit-review.php` - Submit review
- `GET /api/get-ratings.php` - Get ratings

#### Admin
- `POST /api/admin/approve-shop.php` - Approve/reject shop
- `POST /api/admin/profile-update.php` - Update admin profile

### Request/Response Format

**Success Response:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { /* response data */ }
}
```

**Error Response:**
```json
{
  "error": true,
  "message": "Error description",
  "details": { /* optional error details */ }
}
```

---

## Android App

### Architecture: MVVM (Model-View-ViewModel)

```
UI Layer (Activities/Fragments/Compose)
    ↓
ViewModel Layer
    ↓
Repository Layer
    ↓
Data Sources (API, Room DB, SharedPreferences)
```

### Technology Stack

- **Kotlin 1.9+**: Programming language
- **Jetpack Compose**: Modern UI toolkit
- **Material Design 3**: Design system
- **Retrofit 2**: HTTP client
- **Room**: Local database
- **Coroutines**: Async programming
- **Hilt**: Dependency injection
- **OpenStreetMap (osmdroid)**: Free maps (no API key)

### Key Features

- Multi-role authentication
- Real-time booking management
- GPS-based shop discovery
- Device photo capture and upload
- Push notifications (optional)
- Dark mode support
- Biometric authentication
- Offline support with sync

### ✅ 100% FREE Development

All services are free:
- OpenStreetMap (free, no API key)
- Android Location Services (free for basic use)
- Your PHP Backend API (free on your hosting)
- All Android libraries (free and open source)

**No paid APIs needed!** See Android documentation for details.

### API Integration

The Android app integrates with the same PHP backend API. All endpoints are documented in the API section above.

---

## Security

### Security Layers

1. **Input Validation**: Server-side validation, SQL injection prevention, XSS prevention
2. **Authentication**: Token-based authentication (64-char hex tokens), 7-day expiration
3. **Authorization**: Role-based access control (RBAC)
4. **Rate Limiting**: Prevents brute force attacks
5. **CSRF Protection**: Token-based for state changes
6. **File Upload Security**: Type validation, size limits, secure storage

### Security Implementation

- **Password Hashing**: Bcrypt (PASSWORD_DEFAULT)
- **Prepared Statements**: PDO for all database queries
- **Input Sanitization**: All inputs validated and sanitized
- **Output Escaping**: XSS prevention
- **Session Management**: Secure token storage

---

## Deployment

### Development Environment

```
Local Machine
├── XAMPP/WAMP
│   ├── Apache (Port 80)
│   └── MySQL (Port 3306)
├── Web Browser
└── Code Editor
```

### Production Environment

```
Production Server
├── Web Server (Apache/Nginx)
│   ├── PHP 7.4+
│   └── SSL Certificate (HTTPS)
├── MySQL Database
│   ├── Regular Backups
│   └── Replication (Optional)
└── File Storage
    └── Uploaded Files
```

### Deployment Checklist

- [ ] Configure web server (Apache/Nginx)
- [ ] Set up PHP 7.4+
- [ ] Configure MySQL database
- [ ] Set up SSL certificate (HTTPS)
- [ ] Configure email service (SMTP)
- [ ] Set file permissions
- [ ] Configure security settings
- [ ] Set up database backups
- [ ] Configure error logging
- [ ] Test all endpoints

---

## Project Structure

```
repair-booking-platform/
├── backend/                      # Backend API
│   ├── api/                      # API endpoints
│   ├── config/                   # Configuration
│   ├── middleware/               # Security middleware
│   ├── utils/                    # Utility classes
│   ├── uploads/                  # File storage
│   └── setup.php                 # Setup script
├── frontend/                     # Web Frontend
│   ├── index.html                # Landing page
│   ├── auth/                     # Authentication
│   ├── customer/                 # Customer pages
│   ├── shop/                     # Shop owner pages
│   ├── technician/               # Technician pages
│   ├── admin/                    # Admin pages
│   ├── assets/                   # CSS, JS, images
│   ├── manifest.json            # PWA manifest
│   └── service-worker.js         # PWA service worker
├── vendor/                       # PHP Dependencies
├── README.md                     # Main README
└── DOCUMENTATION.md              # This file
```

---

## Progressive Web App (PWA)

### Features

- **Offline Support**: Cached static assets
- **Installable**: Can be installed on devices
- **App-like Experience**: Standalone display mode
- **Fast Loading**: Cached assets load quickly

### PWA Components

- **manifest.json**: App metadata and icons
- **service-worker.js**: Background script for caching
- **Dynamic Icons**: Generated from admin logo

---

## User Roles & Permissions

### Customer
- Register account, create bookings, view own bookings, submit reviews, update profile

### Shop Owner
- Register shop (pending approval), manage shop profile, view/manage bookings, provide quotations, assign technicians, manage services

### Technician
- View assigned jobs, update job status, view job details, track performance

### Admin
- Approve/reject shops, manage all users, view system statistics, manage platform settings

---

## Booking Workflow

1. **Customer** creates booking with device details and photos
2. **Shop** receives notification and provides diagnosis/quotation
3. **Customer** confirms or cancels the quotation
4. **Shop** approves and assigns technician
5. **Technician** updates job status (in_progress → completed)
6. **Customer** receives notifications throughout the process
7. **Customer** submits review after completion

### Booking Status Flow

```
pending_review
    ↓
awaiting_customer_confirmation
    ↓
confirmed_by_customer
    ↓
approved
    ↓
assigned
    ↓
in_progress
    ↓
completed
```

---

## Performance Optimization

### Frontend
- CSS and JavaScript minification
- Browser caching for static assets
- Lazy loading
- Image optimization
- CDN for static assets (optional)

### Backend
- Database indexing
- Query optimization
- Caching (consider Redis)
- Connection pooling
- Code optimization

### Database
- Strategic indexing
- Query optimization
- Connection management
- Regular maintenance

---

## Support & Maintenance

### Regular Maintenance Tasks
- Database backups (daily automated)
- Security updates (keep dependencies updated)
- Performance monitoring
- Error tracking
- User feedback

### Support Channels
- Email: support@erepair.com
- Issue Tracker: GitHub Issues
- Documentation: This file

---

## License

This project is open source and available under the MIT License.

---

**For detailed implementation guides, refer to the source code and inline comments.**

**Last Updated**: 2024  
**Version**: 1.0  
**Maintained by**: ERepair Development Team

