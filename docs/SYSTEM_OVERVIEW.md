# ERepair System Overview

## Complete System Architecture

The ERepair platform is a comprehensive electronics repair booking system consisting of:

1. **Web Frontend** - HTML/CSS/JavaScript web application
2. **Backend API** - PHP-based RESTful API
3. **Android Mobile App** - Native Kotlin Android application
4. **MySQL Database** - Centralized data storage

---

## System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Web Frontend (Browser)                    │
│              HTML5, TailwindCSS, AlpineJS                    │
└───────────────────────────┬───────────────────────────────┘
                              │
┌─────────────────────────────▼───────────────────────────────┐
│                    Android Mobile App                        │
│              Kotlin, Jetpack Compose, MVVM                   │
└───────────────────────────┬───────────────────────────────────┘
                              │
                              │ HTTP/HTTPS
                              │ JSON API
                              │
┌─────────────────────────────▼───────────────────────────────┐
│                    Backend API (PHP)                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │  API Layer   │  │ Middleware   │  │  Business    │       │
│  │  (Endpoints) │  │  (Security)  │  │   Logic     │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└───────────────────────────┬───────────────────────────────────┘
                              │
┌─────────────────────────────▼───────────────────────────────┐
│                    MySQL Database                              │
│  16 Tables, Views, Stored Procedures, Events                  │
└───────────────────────────────────────────────────────────────┘
```

---

## Technology Stack Summary

### Web Frontend
- **HTML5** - Semantic markup
- **TailwindCSS 3.4** - Utility-first CSS framework
- **AlpineJS 3.x** - Lightweight JavaScript framework
- **SweetAlert2** - Alert dialogs
- **Font Awesome 6.0** - Icons
- **Leaflet.js** - Maps integration
- **Chart.js** - Data visualization

### Backend API
- **PHP 7.4+** - Server-side language
- **MySQL 5.7+** - Database
- **PDO** - Database abstraction
- **PHPMailer 6.8+** - Email service
- **Composer** - Dependency management

### Android App
- **Kotlin 1.9+** - Programming language
- **Jetpack Compose** - Modern UI toolkit
- **Material Design 3** - Design system
- **Retrofit 2** - HTTP client
- **Room** - Local database
- **Coroutines** - Async programming
- **Hilt** - Dependency injection

### Database
- **MySQL 5.7+ / MariaDB 10.2+**
- **InnoDB Engine**
- **UTF8MB4 Encoding**
- **16 Tables**
- **3 Views**
- **3 Stored Procedures**

---

## User Roles & Permissions

### 1. Customer
- **Can Do:**
  - Register account
  - Create bookings
  - View own bookings
  - Submit reviews
  - Update profile
  - View shop listings

- **Cannot Do:**
  - Manage shop bookings
  - Approve/reject shops
  - Access admin features

### 2. Shop Owner
- **Can Do:**
  - Register shop (pending approval)
  - Manage shop profile
  - View/manage bookings
  - Provide quotations
  - Assign technicians
  - Manage services
  - View analytics

- **Cannot Do:**
  - Approve other shops
  - Access admin features

### 3. Technician
- **Can Do:**
  - View assigned jobs
  - Update job status
  - View job details
  - Track performance

- **Cannot Do:**
  - Create bookings
  - Manage shop settings
  - Access admin features

### 4. Admin
- **Can Do:**
  - Approve/reject shops
  - Manage all users
  - View system statistics
  - Manage platform settings
  - Access all features

---

## Data Flow

### Booking Creation Flow

```
1. Customer (Web/App)
   ↓ Creates booking with device details
2. Backend API
   ↓ Validates input, stores in database
3. Database
   ↓ Booking record created (status: pending_review)
4. Backend API
   ↓ Sends notification to Shop Owner
5. Shop Owner (Web/App)
   ↓ Receives notification, reviews booking
6. Shop Owner
   ↓ Provides diagnosis and quotation
7. Backend API
   ↓ Updates booking (status: awaiting_customer_confirmation)
8. Customer
   ↓ Receives notification, reviews quotation
9. Customer
   ↓ Confirms or cancels
10. Backend API
    ↓ Updates booking status
11. Shop Owner
    ↓ Assigns technician (if confirmed)
12. Technician
    ↓ Updates job status (in_progress → completed)
13. Customer
    ↓ Submits review
14. Database
    ↓ Review stored, ratings updated
```

---

## API Endpoints Overview

### Authentication
- `POST /api/login.php` - User login
- `POST /api/register-customer.php` - Customer registration
- `POST /api/register-shop-owner.php` - Shop owner registration
- `POST /api/logout.php` - Logout
- `POST /api/verify-email-code.php` - Email verification
- `POST /api/forgot-password-request.php` - Password reset request
- `POST /api/reset-password.php` - Reset password

### User Management
- `GET /api/users/me.php` - Get current user

### Bookings
- `POST /api/bookings/create.php` - Create booking
- `GET /api/bookings/list.php` - List bookings
- `GET /api/bookings/{id}.php` - Get booking details
- `POST /api/bookings/{id}/update-status.php` - Update status

### Shops
- `GET /api/shop-homepage.php` - Get shop details
- `GET /api/shops/list.php` - List shops
- `GET /api/shop-items.php` - Get shop items

### Reviews & Ratings
- `POST /api/submit-review.php` - Submit review
- `GET /api/get-ratings.php` - Get ratings

### Admin
- `POST /api/admin/approve-shop.php` - Approve/reject shop
- `POST /api/admin/profile-update.php` - Update admin profile

---

## Security Architecture

### Authentication
- **Token-based**: 64-character hexadecimal tokens
- **Session Duration**: 7 days
- **Storage**: Encrypted (Android), Secure cookies (Web)

### Authorization
- **Role-based Access Control (RBAC)**
- **Permission checks** on all protected endpoints
- **Token validation** on every request

### Data Protection
- **Password Hashing**: Bcrypt (PASSWORD_DEFAULT)
- **SQL Injection Prevention**: Prepared statements only
- **XSS Prevention**: Input sanitization and output escaping
- **CSRF Protection**: Token-based for state changes
- **Rate Limiting**: Prevents brute force attacks
- **File Upload Security**: Type validation, size limits, secure storage

---

## Database Schema Overview

### Core Tables
- `users` - All user accounts
- `sessions` - Authentication tokens
- `email_verifications` - Email verification codes
- `password_resets` - Password reset tokens

### Shop Tables
- `shop_owners` - Shop owner profiles and documents
- `repair_shops` - Shop locations and details

### Technician Tables
- `technicians` - Technician profiles

### Service Tables
- `services` - Shop services
- `shop_services` - Alternative service structure

### Booking Tables
- `bookings` - Main booking records
- `booking_history` - Status change audit trail

### Review Tables
- `reviews` - Customer reviews
- `shop_ratings` - Aggregated shop ratings
- `technician_ratings` - Aggregated technician ratings

### Other Tables
- `notifications` - System notifications
- `shop_items` - Shop products/items

---

## Deployment Architecture

### Development Environment
```
Local Machine
├── XAMPP/WAMP
│   ├── Apache (Web Server)
│   └── MySQL (Database)
├── Android Studio
│   └── Android Emulator/Device
└── Web Browser
```

### Production Environment
```
Cloud Server / VPS
├── Web Server (Apache/Nginx)
│   ├── PHP 7.4+
│   └── SSL Certificate (HTTPS)
├── MySQL Database
│   ├── Regular Backups
│   └── Replication (Optional)
└── File Storage
    └── Uploaded Files

Google Play Store
└── Android App (.aab)
```

---

## Key Features

### Real-time Updates
- **Notifications**: In-app and push notifications
- **Status Tracking**: Real-time booking status updates
- **Live Data**: WebSocket or polling for updates

### Location Services
- **GPS Integration**: Shop discovery based on location
- **Distance Calculation**: Show nearby shops
- **Navigation**: Integration with maps apps

### File Management
- **Image Upload**: Device photos, IDs, shop logos
- **File Validation**: Type and size checks
- **Secure Storage**: Organized file structure

### Offline Support (Android)
- **Local Caching**: Room database for offline access
- **Sync**: Automatic sync when online
- **Queue**: Queue actions when offline

---

## Performance Considerations

### Backend
- **Database Indexing**: Optimized queries
- **Caching**: Consider Redis for frequently accessed data
- **CDN**: For static assets
- **Load Balancing**: For high traffic

### Android App
- **Image Compression**: Before upload
- **Lazy Loading**: Load data as needed
- **Background Sync**: WorkManager for sync tasks
- **ProGuard**: Code obfuscation and optimization

### Database
- **Query Optimization**: Use EXPLAIN for slow queries
- **Connection Pooling**: Reuse connections
- **Regular Maintenance**: Cleanup expired data

---

## Monitoring & Logging

### Backend Logging
- **Error Logging**: PHP error logs
- **Security Events**: Failed login attempts, suspicious activity
- **API Logging**: Request/response logging (debug mode)

### Android Logging
- **Crash Reporting**: Firebase Crashlytics (optional)
- **Analytics**: User behavior tracking
- **Performance Monitoring**: App performance metrics

### Database Monitoring
- **Slow Query Log**: Identify slow queries
- **Connection Monitoring**: Track database connections
- **Backup Monitoring**: Verify backup success

---

## Scalability

### Horizontal Scaling
- **Load Balancer**: Distribute traffic
- **Multiple API Servers**: Scale backend horizontally
- **Database Replication**: Read replicas for scaling reads

### Vertical Scaling
- **Server Resources**: Increase CPU, RAM, storage
- **Database Optimization**: Optimize queries and indexes

### Caching Strategy
- **Application Cache**: Cache frequently accessed data
- **CDN**: Cache static assets
- **Database Query Cache**: MySQL query cache

---

## Documentation Structure

```
repair-booking-platform/
├── README.md                          # Main project README
│
└── docs/                              # All Documentation
    ├── README.md                      # Documentation index
    ├── SYSTEM_OVERVIEW.md            # This file - System overview
    ├── WEB_SYSTEM_ARCHITECTURE.md    # Complete web architecture
    ├── DATABASE_SCHEMA_DESCRIPTION.md # Database schema details
    ├── TECHNOLOGY_STACK.md           # Technology stack details
    │
    ├── backend/
    │   └── ARCHITECTURE.md            # Backend architecture
    │
    └── android/
        ├── ARCHITECTURE.md            # Android architecture
        ├── API_INTEGRATION.md         # API integration guide
        ├── README.md                  # Android setup guide
        └── FREE_SERVICES_GUIDE.md     # Free services guide
```

---

## Development Workflow

### Backend Development
1. Set up local environment (XAMPP/WAMP)
2. Configure database connection
3. Run migrations/setup scripts
4. Develop API endpoints
5. Test with Postman/curl
6. Deploy to staging
7. Test in production

### Android Development
1. Clone repository
2. Open in Android Studio
3. Configure API base URL
4. Sync Gradle dependencies
5. Run on emulator/device
6. Test features
7. Build release APK/AAB
8. Upload to Play Store

---

## Future Enhancements

### Planned Features
- **iOS App**: Native iOS application
- **Payment Integration**: Online payment processing
- **Chat System**: Real-time messaging
- **Advanced Analytics**: Business intelligence dashboard
- **Multi-language Support**: Internationalization
- **Push Notifications**: Firebase Cloud Messaging
- **Social Login**: Google, Facebook authentication

### Technical Improvements
- **API Versioning**: Version API endpoints
- **GraphQL**: Consider GraphQL for flexible queries
- **Microservices**: Break into microservices if needed
- **Containerization**: Docker for deployment
- **CI/CD Pipeline**: Automated testing and deployment

---

## Support & Maintenance

### Regular Maintenance Tasks
- **Database Backups**: Daily automated backups
- **Security Updates**: Keep dependencies updated
- **Performance Monitoring**: Monitor system performance
- **Error Tracking**: Review and fix errors
- **User Feedback**: Address user concerns

### Support Channels
- **Email**: support@erepair.com
- **Issue Tracker**: GitHub Issues
- **Documentation**: Comprehensive docs in repository

---

## Conclusion

The ERepair platform is a comprehensive, scalable solution for electronics repair booking with:

- **Multi-platform Support**: Web and Android
- **Secure Architecture**: Multiple security layers
- **Scalable Design**: Ready for growth
- **Modern Technology**: Latest best practices
- **Comprehensive Documentation**: Well-documented codebase

For detailed information on specific components, refer to the respective architecture documents.

---

**Last Updated**: 2024  
**Version**: 1.0  
**Maintained by**: ERepair Development Team

