# ERepair - Technology Stack and Development Tools

This document provides a comprehensive overview of all technologies, frameworks, libraries, and tools used in the development of the ERepair Electronics Repair Booking Platform.

---

## Backend Technologies

### Server-Side Programming
- **PHP 7.4+** - Server-side scripting language
  - Used for all backend logic, API endpoints, and server-side processing
  - Implements object-oriented programming principles
  - Handles authentication, authorization, and business logic

### Database Management
- **MySQL 5.7+ / MariaDB 10.2+** - Relational database management system
  - Stores all application data including users, bookings, shops, services, reviews
  - Uses InnoDB engine for transaction support and foreign key constraints
  - Character set: UTF8MB4 for full Unicode support
  - 16 database tables with proper relationships and indexes

### Database Access Layer
- **PDO (PHP Data Objects)** - Database abstraction layer
  - Provides secure database connections
  - Uses prepared statements to prevent SQL injection
  - Implements transaction handling for data integrity
  - Error handling with exception management

### Email Functionality
- **PHPMailer 6.8+** - Email sending library
  - Handles email verification codes
  - Sends password reset emails
  - Delivers booking notifications
  - Supports SMTP configuration

### Dependency Management
- **Composer** - PHP dependency manager
  - Manages PHPMailer and other PHP dependencies
  - Autoloads classes using PSR-4 standard
  - Handles package versioning

---

## Frontend Technologies

### Core Web Technologies
- **HTML5** - Markup language
  - Semantic HTML structure
  - Progressive Web App (PWA) support
  - Accessibility features

- **CSS3** - Styling language
  - Custom CSS for application-specific styles
  - Responsive design implementation
  - Animation and transition effects

- **JavaScript (ES6+)** - Client-side scripting
  - Modern JavaScript features
  - Async/await for asynchronous operations
  - DOM manipulation and event handling

### CSS Frameworks
- **Tailwind CSS 3.4.0** - Utility-first CSS framework
  - Rapid UI development with utility classes
  - Responsive design utilities
  - Custom color palette and theme configuration
  - PostCSS processing for optimization

- **Bootstrap 5.3.2** - CSS framework (used in specific components)
  - Modal dialogs for complex forms
  - Grid system for layouts
  - Component library for UI elements

### JavaScript Frameworks & Libraries

#### Core Frameworks
- **Alpine.js 3.x** - Lightweight JavaScript framework
  - Reactive data binding
  - Component-based architecture
  - Minimal JavaScript footprint
  - Declarative syntax with x-data, x-show, x-if directives

#### UI/UX Libraries
- **Notiflix 3.2.6** - Notification and dialog library
  - Success/error/warning messages
  - Confirmation dialogs
  - Loading indicators
  - Toast notifications
  - Report dialogs

- **SweetAlert2 11** - Beautiful alert dialogs (used in specific dashboards)
  - Complex input dialogs
  - HTML form inputs
  - File upload dialogs
  - Custom styling support

#### Mapping & Location Services
- **Leaflet.js 1.9.4** - Open-source mapping library
  - Interactive maps for shop locations
  - Route visualization
  - Marker placement
  - Integration with OpenStreetMap tiles

- **OpenStreetMap** - Map tile provider
  - Free and open-source map tiles
  - No API key required
  - Customizable map styles

- **OSRM (Open Source Routing Machine)** - Routing service
  - Route calculation between locations
  - Distance and time estimation
  - Driving directions

#### Charting & Visualization
- **Chart.js** - JavaScript charting library
  - Performance metrics visualization
  - Statistics and analytics charts
  - Interactive data visualization

#### Icon Libraries
- **Font Awesome 6.0.0** - Icon library
  - Comprehensive icon set
  - Scalable vector icons
  - Consistent iconography throughout the application

#### Avatar Generation
- **UI Avatars API** - Dynamic avatar generation
  - Generates avatars from user names
  - Fallback for users without profile photos
  - Customizable colors and sizes

---

## Progressive Web App (PWA) Technologies

### PWA Core Components
- **Web App Manifest** - Application manifest file
  - Defines app metadata (name, icons, theme colors)
  - Enables "Add to Home Screen" functionality
  - Configures app shortcuts
  - Standalone display mode

- **Service Worker** - Background script for offline functionality
  - Caches static assets
  - Enables offline access
  - Handles push notifications (ready for implementation)
  - Cache-first strategy for performance

### PWA Features
- **Offline Support** - Cached content available offline
- **Installable** - Can be installed on devices
- **App-like Experience** - Standalone display mode
- **Fast Loading** - Cached assets load quickly

---

## Development Tools & Build Tools

### Package Managers
- **npm (Node Package Manager)** - JavaScript package manager
  - Manages Tailwind CSS and build dependencies
  - Handles development scripts

- **Composer** - PHP package manager
  - Manages PHP dependencies
  - Autoloads classes

### Build Tools
- **Tailwind CLI** - Command-line tool for Tailwind CSS
  - Compiles Tailwind CSS from source
  - Watches for changes during development
  - Minifies CSS for production

- **PostCSS** - CSS processing tool
  - Processes Tailwind CSS
  - Handles CSS transformations

### Development Environment
- **XAMPP/WAMP/LAMP** - Local development server
  - Apache web server
  - MySQL database server
  - PHP runtime environment
  - phpMyAdmin for database management

---

## Security Technologies

### Authentication & Authorization
- **Token-Based Authentication** - Session management
  - 64-character hexadecimal tokens
  - 7-day session expiration
  - Secure token generation

- **Password Hashing** - PHP password_hash()
  - Bcrypt algorithm (PASSWORD_DEFAULT)
  - Secure password storage
  - Password verification

### Security Measures
- **Prepared Statements** - SQL injection prevention
  - Parameterized queries
  - Input sanitization

- **Input Validation** - Data validation
  - Server-side validation
  - Client-side validation
  - File upload security

- **Role-Based Access Control (RBAC)** - Authorization
  - Four user roles: customer, shop_owner, technician, admin
  - Permission-based access
  - Secure API endpoints

---

## File Upload & Storage

### File Handling
- **PHP File Upload** - Native PHP file handling
  - Image upload for device photos
  - Document upload for shop owner verification
  - Avatar/profile photo uploads
  - Shop logo uploads

### File Types Supported
- **Images**: JPG, PNG, WebP
- **Documents**: PDF (for business permits, IDs)

### Storage Structure
- `/backend/uploads/logos/` - Shop logos
- `/backend/uploads/shop_owners/` - Shop owner documents
- `/frontend/uploads/avatars/` - User avatars
- `/frontend/uploads/device_photos/` - Device photos
- `/frontend/uploads/shop_items/` - Shop item images

---

## API & Communication

### API Architecture
- **RESTful API Design** - API endpoint structure
  - Standard HTTP methods (GET, POST)
  - JSON response format
  - Consistent error handling

### API Endpoints
- Authentication APIs (login, register, logout)
- User management APIs
- Booking management APIs
- Shop and service APIs
- Review and rating APIs
- Notification APIs

### Communication Methods
- **AJAX/Fetch API** - Asynchronous communication
  - Real-time data updates
  - Form submissions without page reload
  - Dynamic content loading

- **Email Notifications** - PHPMailer
  - Email verification
  - Password reset
  - Booking notifications
  - Status updates

---

## Database Design

### Database Features
- **16 Database Tables** - Comprehensive data structure
- **Foreign Key Constraints** - Data integrity
- **Indexes** - Query optimization
- **Transactions** - Data consistency
- **Audit Trails** - Booking history tracking

### Key Tables
- Users, Sessions, Email Verifications
- Shop Owners, Repair Shops
- Technicians
- Services, Shop Services
- Bookings, Booking History
- Reviews, Shop Ratings, Technician Ratings
- Notifications
- Shop Items

---

## Browser Compatibility

### Supported Browsers
- **Chrome 80+**
- **Firefox 75+**
- **Safari 13+**
- **Edge 80+**
- **Mobile browsers** (iOS Safari, Chrome Mobile)

### Responsive Design
- **Mobile-first approach**
- **Responsive breakpoints**
- **Touch-friendly interfaces**
- **Cross-device compatibility**

---

## Additional Technologies & Services

### Fonts
- **Google Fonts** - Web font service
  - Inter font family (primary)
  - JetBrains Mono (monospace)
  - Custom font loading

### Geolocation
- **HTML5 Geolocation API** - Browser geolocation
  - User location detection
  - Shop location mapping
  - Distance calculations

### Image Processing
- **PHP GD Library** - Image manipulation
  - Icon generation
  - Image resizing
  - Thumbnail creation

---

## Development Workflow

### Version Control
- **Git** - Version control system (assumed)
- Code versioning and collaboration

### Code Organization
- **MVC-like Structure** - Separation of concerns
  - Frontend (presentation layer)
  - Backend (business logic)
  - Database (data layer)

### File Structure
```
repair-booking-platform/
├── backend/          # Server-side code
│   ├── api/          # API endpoints
│   ├── config/       # Configuration files
│   ├── utils/        # Helper classes
│   ├── middleware/   # Security middleware
│   └── uploads/      # File storage
├── frontend/         # Client-side code
│   ├── auth/         # Authentication pages
│   ├── customer/     # Customer dashboard
│   ├── shop/         # Shop owner dashboard
│   ├── admin/        # Admin dashboard
│   ├── technician/   # Technician dashboard
│   └── assets/       # CSS, JS, images
└── vendor/           # PHP dependencies
```

---

## Summary

The ERepair platform is built using a modern, full-stack web development approach combining:

- **Backend**: PHP 7.4+ with MySQL database and PDO
- **Frontend**: HTML5, CSS3, JavaScript with Tailwind CSS and Alpine.js
- **Libraries**: Notiflix, SweetAlert2, Leaflet.js, Chart.js, Font Awesome
- **PWA**: Service Worker and Web App Manifest
- **Security**: Token-based authentication, password hashing, prepared statements
- **Tools**: Composer, npm, Tailwind CLI, XAMPP

This technology stack provides a robust, secure, and user-friendly platform for managing electronics repair bookings with modern web development best practices.

---

