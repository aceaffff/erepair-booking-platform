# Collaboration Guide - Getting Started

## Quick Start for Team Members

### 1. Clone the Repository

```bash
git clone https://github.com/aceaffff/erepair-booking-platform.git
cd erepair-booking-platform
```

### 2. Open in Cursor IDE

**Option A: From Cursor**
1. Open Cursor IDE
2. Click **File** → **Open Folder**
3. Navigate to and select the `erepair-booking-platform` folder

**Option B: From Command Line**
```bash
cd erepair-booking-platform
cursor .
```

**Option C: Drag & Drop**
- Drag the `erepair-booking-platform` folder into Cursor window

### 3. Install Dependencies

**PHP Dependencies (Composer):**
```bash
composer install
```

**Node Dependencies (for Tailwind CSS):**
```bash
npm install
```

### 4. Configure Database

1. Edit `backend/config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'repair_booking';  // or your database name
   private $username = 'root';
   private $password = '';  // your MySQL password
   ```

2. Start XAMPP/WAMP:
   - Start Apache
   - Start MySQL

3. Run database setup:
   - Open browser: `http://localhost/ERepair/repair-booking-platform/backend/setup.php`
   - This will create the database and tables

### 5. Access the Application

- **Frontend**: `http://localhost/ERepair/repair-booking-platform/frontend/index.html`
- **Default Admin**: 
  - Email: `admin@repair.com`
  - Password: `admin123`

## Working with Git

### Pull Latest Changes
```bash
git pull origin main
```

### Make Changes
```bash
# Make your changes
git add .
git commit -m "Description of changes"
git push origin main
```

### Create a Branch (Recommended)
```bash
git checkout -b feature/your-feature-name
# Make changes
git add .
git commit -m "Add feature"
git push origin feature/your-feature-name
```

## Project Structure

```
repair-booking-platform/
├── backend/          # PHP Backend API
├── frontend/         # Web Frontend
├── README.md         # Main README
└── DOCUMENTATION.md  # Complete documentation
```

## Need Help?

- **Documentation**: See [DOCUMENTATION.md](DOCUMENTATION.md)
- **Repository**: https://github.com/aceaffff/erepair-booking-platform
- **Issues**: Create an issue on GitHub

---

**Repository Owner**: aceaffff  
**Repository URL**: https://github.com/aceaffff/erepair-booking-platform

