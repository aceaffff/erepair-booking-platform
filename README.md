# ERepair - Electronics Repair Booking Platform

A comprehensive web application for booking electronics repair services with role-based access for customers, shop owners, technicians, and administrators.

## Features

### 🏠 Landing Page
- Modern, responsive design with hero section
- Feature highlights and how-it-works section
- Call-to-action buttons for login/register

### 👤 User Authentication
- **Customer Registration**: Simple form with name, email, phone, password
- **Shop Owner Registration**: Extended form with shop details and document uploads
- **Login System**: Role-based redirects to appropriate dashboards
- **Email Verification**: Built-in email verification system

### 📱 Role-Based Dashboards

#### Customer Dashboard
- View and manage repair bookings
- Track repair status in real-time
- View repair history and notifications
- Profile management

#### Shop Owner Dashboard
- Manage shop information and services
- View and manage customer bookings
- Diagnose issues and provide quotations
- Assign technicians to jobs
- Track shop performance metrics

#### Admin Dashboard
- Approve/reject shop owner applications
- View platform statistics
- Manage users and shops
- Monitor system activity

#### Technician Dashboard
- View assigned repair tasks
- Update task status (in_progress, completed)
- Track performance metrics
- Manage work schedule

## Technology Stack

### Frontend
- **HTML5** - Semantic markup
- **TailwindCSS** - Utility-first CSS framework
- **AlpineJS** - Lightweight JavaScript framework
- **SweetAlert2** - Beautiful alert dialogs
- **Font Awesome** - Icon library

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL** - Database management
- **PDO** - Database abstraction layer with safe transaction handling
- **PHPMailer** - Email functionality

### Mobile App (Android)
- **Kotlin** - Programming language
- **Jetpack Compose** - Modern UI toolkit
- **Material Design 3** - Design system
- **Retrofit** - HTTP client for API calls
- **Room** - Local database
- **MVVM Architecture** - Clean architecture pattern

## Installation & Setup

### Prerequisites
- XAMPP/WAMP/LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Setup Instructions

1. **Clone/Download the project**
   ```bash
   # Place the project in your web server directory
   # For XAMPP: C:\xampp\htdocs\ERepair\repair-booking-platform
   ```

2. **Start your web server**
   - Start Apache and MySQL services in XAMPP

3. **Initialize the database**
   - Open your browser and navigate to: `http://localhost/ERepair/repair-booking-platform/backend/setup.php`
   - This will create the database and tables automatically

4. **Access the application**
   - Open: `http://localhost/ERepair/repair-booking-platform/frontend/index.html`

### Default Admin Credentials
- **Email**: admin@repair.com
- **Password**: admin123

## Project Structure

```
repair-booking-platform/
├── backend/                    # Backend API (PHP)
│   ├── api/                    # API endpoints
│   ├── config/                 # Database and email configuration
│   ├── utils/                  # Helper classes (DBTransaction, ResponseHelper, etc.)
│   ├── middleware/             # Security middleware
│   ├── uploads/                # File uploads
│   └── setup.php              # Database initialization
├── frontend/                   # Web Frontend
│   ├── index.html             # Landing page
│   ├── auth/                  # Authentication pages
│   ├── customer/              # Customer dashboard and pages
│   ├── shop/                  # Shop owner dashboard and pages
│   ├── admin/                 # Admin dashboard
│   ├── technician/            # Technician dashboard
│   └── assets/                # CSS, JS, and images
├── android/                    # Android Mobile App (Optional)
├── docs/                       # 📚 All Documentation
│   ├── README.md              # Documentation index
│   ├── SYSTEM_OVERVIEW.md    # System overview
│   ├── WEB_SYSTEM_ARCHITECTURE.md # Complete web architecture
│   ├── DATABASE_SCHEMA_DESCRIPTION.md # Database schema
│   ├── TECHNOLOGY_STACK.md    # Technology stack
│   ├── backend/               # Backend documentation
│   └── android/               # Android documentation
├── vendor/                    # PHPMailer dependencies
└── README.md                  # This file
```

## Key Features

### Booking Workflow
1. **Customer** creates booking with device details and photos
2. **Shop** receives notification and provides diagnosis/quotation
3. **Customer** confirms or cancels the quotation
4. **Shop** approves and assigns technician
5. **Technician** updates job status (in_progress → completed)
6. **Customer** receives notifications throughout the process

### Security Features
- Password hashing using PHP's `password_hash()`
- Input validation and sanitization
- SQL injection prevention with prepared statements
- Safe database transaction handling
- File upload security measures
- Role-based access control

### Database Features
- Safe transaction handling with `DBTransaction` helper class
- Automatic rollback on errors
- Consistent JSON API responses
- Email notifications for all major events

## API Endpoints

### Authentication
- `POST /backend/api/login.php` - User login
- `POST /backend/api/register-customer.php` - Customer registration
- `POST /backend/api/register-shop-owner.php` - Shop owner registration
- `POST /backend/api/logout.php` - User logout

### Booking Management
- `POST /frontend/shop/booking_manage.php` - Shop booking management
- `POST /frontend/technician/job_status_update.php` - Technician status updates
- `POST /frontend/customer/booking_customer_confirm.php` - Customer confirmation

## Browser Compatibility

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## License

This project is open source and available under the MIT License.

## Documentation

📚 **All documentation is now organized in the [`docs/`](docs/) folder.**

### Quick Links
- **[📖 Documentation Index](docs/README.md)** - Complete documentation index and quick links
- **[🌐 System Overview](docs/SYSTEM_OVERVIEW.md)** - Complete system architecture overview
- **[🏗️ Web System Architecture](docs/WEB_SYSTEM_ARCHITECTURE.md)** - Full web platform architecture (frontend + backend)

### Backend Documentation
- **[Backend Architecture](docs/backend/ARCHITECTURE.md)** - Complete backend architecture, API design, security, and best practices

### Android App Documentation
- **[Android Architecture](docs/android/ARCHITECTURE.md)** - Android app architecture, components, and implementation details
- **[API Integration Guide](docs/android/API_INTEGRATION.md)** - Detailed guide for integrating with the backend API
- **[Android README](docs/android/README.md)** - Android app setup, configuration, and development guide
- **[Free Services Guide](docs/android/FREE_SERVICES_GUIDE.md)** - Building Android app without paid APIs

### Additional Documentation
- **[Database Schema](docs/DATABASE_SCHEMA_DESCRIPTION.md)** - Complete database schema documentation
- **[Technology Stack](docs/TECHNOLOGY_STACK.md)** - Comprehensive technology stack overview

## Mobile App

The ERepair platform includes a native Android mobile application built with Kotlin and Jetpack Compose.

### Android App Features
- 🔐 Multi-role authentication (Customer, Shop Owner, Technician, Admin)
- 📱 Real-time booking management
- 📍 GPS-based shop discovery
- 📸 Device photo capture and upload
- 🔔 Push notifications
- 🌙 Dark mode support
- 🔒 Biometric authentication
- 📴 Offline support with sync

### Getting Started with Android App
1. See [Android README](docs/android/README.md) for setup instructions
2. Configure API base URL in `ApiClient.kt`
3. Build and run the app in Android Studio
4. **✅ 100% FREE**: See [Free Services Guide](docs/android/FREE_SERVICES_GUIDE.md) - No paid APIs needed!

## Support

For support and questions, please contact the development team or create an issue in the project repository.

---

**Note**: This is a development version. For production deployment, additional security measures and optimizations should be implemented.