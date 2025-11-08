# 🏠 HomeHub AI - Intelligent Property Management Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.4.6-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://www.mysql.com/)
[![Python](https://img.shields.io/badge/Python-3.8+-green.svg)](https://www.python.org/)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Live Demo](https://img.shields.io/badge/Demo-homehubai.shop-brightgreen.svg)](https://homehubai.shop)

**HomeHub AI** is a modern, AI-powered property rental management platform that connects tenants with landlords through intelligent matching, personalized recommendations, and predictive analytics. Built with PHP, MySQL, and Python-based AI models, HomeHub streamlines the entire property rental lifecycle.

## 🌟 Key Features

### 🤖 AI-Powered Features
- **Intelligent Tenant Matching** - Advanced cosine similarity algorithm matches tenants with properties based on preferences, budget, location, and lifestyle
- **Smart Property Recommendations** - Collaborative filtering provides personalized property suggestions based on user behavior and similar users' preferences
- **Predictive Analytics** - Machine learning models help landlords forecast rental demand, optimize pricing, and predict property performance

### 👥 User Management
- **Multi-User System** - Separate dashboards for Tenants, Landlords, and Administrators
- **Secure Authentication** - Session-based authentication with role-based access control (RBAC)
- **User Profiles** - Comprehensive profiles with preferences, search history, and saved properties

### 🏘️ Property Management
- **Property Listings** - Full CRUD operations for property management
- **Image Galleries** - Multiple property images with responsive carousel views
- **Advanced Filtering** - Filter by price, location, amenities, property type, and more
- **Property Status** - Track available, rented, pending, and suspended properties

### 📊 Analytics & Insights
- **Landlord Analytics Dashboard** - View property performance, visitor statistics, and revenue insights
- **Tenant Activity Tracking** - Monitor browsing history, saved properties, and interaction patterns
- **Admin Control Panel** - Platform-wide analytics, user management, and system monitoring

### 📧 Communication System
- **Email Notifications** - Automated emails for bookings, visit requests, and property updates
- **In-App Notifications** - Real-time notification system for user activities
- **Booking Management** - Handle property visit requests and reservations
- **Messaging System** - Direct communication between tenants and landlords

### 📱 Modern UI/UX
- **Responsive Design** - Mobile-first design that works on all devices
- **Interactive Property Cards** - Dynamic property displays with hover effects
- **Real-time Updates** - AJAX-powered features for seamless user experience
- **Dark/Light Themes** - Customizable interface themes

## 🏗️ System Architecture

```
HomeHub/
├── 📁 admin/                  # Admin control panel
│   ├── analytics.php          # Platform analytics dashboard
│   ├── manage-properties.php  # Property management interface
│   └── manage-users.php       # User management interface
│
├── 📁 ai/                     # Python AI backend (optional)
│   ├── config.py              # AI configuration
│   ├── database.py            # Database manager for AI
│   ├── cosine_similarity.py   # Tenant matching algorithm
│   ├── recommendation_engine.py # Recommendation system
│   └── requirements.txt       # Python dependencies
│
├── 📁 api/                    # RESTful API endpoints
│   ├── 📁 ai/                 # AI feature APIs
│   │   ├── get-matches.php    # Tenant matching endpoint
│   │   ├── get-recommendations.php # Property recommendations
│   │   └── get-analytics.php  # Analytics data endpoint
│   ├── get-history.php        # Browsing history API
│   ├── login.php              # Authentication API
│   └── register.php           # User registration API
│
├── 📁 assets/                 # Frontend resources
│   ├── 📁 css/                # Stylesheets
│   ├── 📁 js/                 # JavaScript files
│   │   └── ai-features.js     # AI features frontend logic
│   └── 📁 images/             # Static images
│
├── 📁 config/                 # Configuration files
│   ├── database.php           # Database connection handler
│   └── env.php                # Environment configuration
│
├── 📁 guest/                  # Guest/public pages
│   └── index.html             # Landing page
│
├── 📁 landlord/               # Landlord dashboard
│   ├── index.php              # Landlord homepage
│   └── properties.php         # Property management
│
├── 📁 login/                  # Authentication pages
│   ├── login.html             # Login interface
│   └── register.html          # Registration form
│
├── 📁 sql/                    # Database schemas
│   ├── ai_features_schema.sql # AI tables schema
│   ├── admin_schema.sql       # Admin tables
│   └── email_tables.sql       # Email system tables
│
├── 📁 tenant/                 # Tenant dashboard
│   ├── index.php              # Tenant homepage
│   └── saved-properties.php   # Saved properties list
│
├── 📁 uploads/                # User-uploaded files
│   └── properties/            # Property images
│
├── index.php                  # Main entry point
├── properties.php             # Property listings page
├── ai-features.php            # AI features showcase
├── bookings.php               # Booking management
└── history.php                # Browsing history page
```

## 🚀 Quick Start Guide

### Prerequisites

Before you begin, ensure you have the following installed:

- **XAMPP** (includes Apache & MySQL) - [Download](https://www.apachefriends.org/)
- **PHP 8.0+** - Included with XAMPP
- **MySQL 8.0+** - Included with XAMPP
- **Python 3.8+** - [Download](https://www.python.org/) (Optional, for AI features)
- **Web Browser** - Chrome, Firefox, Edge, or Safari

### Installation Steps

#### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/homehub.git
cd homehub
```

Or download and extract to:
```
C:\xampp\htdocs\HomeHub
```

#### 2. Database Setup

**Option A: Using phpMyAdmin (Recommended)**

1. Start XAMPP and launch **Apache** and **MySQL**
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Create a new database named `homehub`
4. Import the schema files in order:
   - `sql/homehub_schema.sql` (if exists)
   - `sql/ai_features_schema.sql`
   - `sql/admin_schema.sql`
   - `sql/email_tables.sql`

**Option B: Using MySQL Command Line**

```bash
mysql -u root -p
CREATE DATABASE homehub;
USE homehub;
SOURCE sql/ai_features_schema.sql;
SOURCE sql/admin_schema.sql;
SOURCE sql/email_tables.sql;
```

#### 3. Configure Environment

1. Copy `config/env.example.php` to `config/env.php` (if example exists)
2. Update database credentials in `config/env.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'homehub');
```

#### 4. Set Up File Permissions

Ensure the `uploads/` directory is writable:

```powershell
# Windows PowerShell
icacls uploads /grant Everyone:F /T

# Or manually: Right-click > Properties > Security > Edit > Add Write permissions
```

#### 5. Start the Application

1. Open XAMPP Control Panel
2. Start **Apache** (Web Server)
3. Start **MySQL** (Database)
4. Open browser and navigate to:

```
http://localhost/HomeHub/
```

You should be redirected to the guest landing page.

### 🤖 Optional: AI Features Setup

If you want to enable the advanced AI features:

#### 1. Install Python Dependencies

```powershell
# Navigate to HomeHub directory
cd C:\xampp\htdocs\HomeHub

# Create virtual environment
python -m venv ai_env

# Activate virtual environment
.\ai_env\Scripts\Activate.ps1

# Install dependencies
pip install -r ai/requirements.txt
```

#### 2. Configure AI Backend

Create `ai/.env` file:

```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=homehub
FLASK_PORT=5000
```

#### 3. Start AI Server (Optional)

```powershell
# With virtual environment activated
python ai/api_server.py
```

The AI server will run on `http://localhost:5000`

**Note:** AI features also work through PHP APIs without the Python server. The Python server is only needed for advanced machine learning features.

## 📖 Usage Guide

### For Tenants

1. **Register an Account**
   - Go to `http://localhost/HomeHub/login/register.html`
   - Select "Tenant" as user type
   - Fill in your details and preferences

2. **Browse Properties**
   - Navigate to the properties page
   - Use filters to narrow down options
   - Click on properties for detailed views

3. **Get AI Recommendations**
   - Visit the AI Features page
   - View personalized property recommendations
   - See intelligent matching scores

4. **Save & Book Properties**
   - Save favorite properties for later
   - Request property visits
   - Track booking status in your dashboard

### For Landlords

1. **Register as Landlord**
   - Create account with "Landlord" user type
   - Complete your profile

2. **Add Properties**
   - Go to your landlord dashboard
   - Click "Add New Property"
   - Upload images and property details

3. **Manage Listings**
   - Edit property information
   - Update availability status
   - View property analytics

4. **Track Performance**
   - View visitor statistics
   - Access predictive analytics
   - Monitor booking requests

### For Administrators

1. **Access Admin Panel**
   - Login with admin credentials
   - Navigate to `http://localhost/HomeHub/admin/`

2. **Platform Management**
   - View platform-wide analytics
   - Manage users and properties
   - Monitor system health
   - Review reported content

## 🔧 API Documentation

### Authentication Endpoints

#### POST `/api/login.php`
Authenticate user and create session.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "user_type": "tenant",
    "first_name": "John"
  }
}
```

#### POST `/api/register.php`
Register a new user account.

**Request Body:**
```json
{
  "email": "newuser@example.com",
  "password": "password123",
  "first_name": "Jane",
  "last_name": "Doe",
  "user_type": "tenant",
  "phone": "+1234567890"
}
```

### AI Feature Endpoints

#### GET `/api/ai/get-matches.php`
Get intelligent property matches for logged-in tenant.

**Response:**
```json
{
  "success": true,
  "matches": [
    {
      "property_id": 123,
      "similarity_score": 0.92,
      "title": "Modern 2BR Apartment",
      "location": "Downtown",
      "rent_amount": 1500
    }
  ]
}
```

#### GET `/api/ai/get-recommendations.php`
Get personalized property recommendations.

**Query Parameters:**
- `user_id` (optional) - Defaults to session user

**Response:**
```json
{
  "success": true,
  "recommendations": [
    {
      "property_id": 456,
      "confidence_score": 0.87,
      "reason": "Similar to properties you viewed"
    }
  ]
}
```

#### GET `/api/ai/get-analytics.php`
Get predictive analytics for landlord properties.

**Response:**
```json
{
  "success": true,
  "analytics": {
    "total_views": 523,
    "avg_daily_views": 18.7,
    "predicted_demand": "high",
    "price_recommendation": 1600
  }
}
```

### Property Endpoints

#### GET `/api/get-available-properties.php`
Fetch all available properties with optional filtering.

**Query Parameters:**
- `min_price` - Minimum rent amount
- `max_price` - Maximum rent amount
- `location` - City or area name
- `property_type` - apartment, house, condo, etc.

#### GET `/api/get-history.php`
Get user's browsing history.

**Response:**
```json
{
  "success": true,
  "history": [
    {
      "property_id": 789,
      "viewed_at": "2025-11-08T10:30:00",
      "title": "Luxury Condo"
    }
  ]
}
```

## 🗄️ Database Schema

### Core Tables

- **`users`** - User accounts (tenants, landlords, admins)
- **`landlords`** - Extended landlord information
- **`properties`** - Property listings
- **`saved_properties`** - User's saved/favorite properties
- **`bookings`** - Property visit requests and reservations
- **`notifications`** - In-app notification system
- **`messages`** - User-to-user messaging

### AI Tables

- **`tenant_preferences`** - Tenant search preferences and criteria
- **`property_vectors`** - Vectorized property features for ML
- **`browsing_history`** - User property viewing history
- **`user_interactions`** - Clicks, saves, and engagement tracking
- **`similarity_scores`** - Pre-computed property similarity matrix
- **`recommendation_cache`** - Cached recommendation results
- **`rental_analytics`** - Historical rental performance data
- **`property_demand_forecast`** - ML-based demand predictions

### Admin Tables

- **`admin_users`** - Administrator accounts
- **`admin_activity_log`** - Audit trail for admin actions
- **`platform_settings`** - System-wide configuration
- **`reported_content`** - User-reported issues

## 🛡️ Security Features

- **Password Hashing** - Bcrypt with salt for secure password storage
- **SQL Injection Prevention** - Prepared statements for all queries
- **XSS Protection** - Input sanitization and output escaping
- **CSRF Protection** - Token-based form validation
- **Session Security** - HTTP-only cookies and session regeneration
- **Role-Based Access Control** - User type verification for protected routes
- **File Upload Validation** - Type and size checks for uploaded images

## 🧪 Testing

### Manual Testing URLs

Test the application features using these URLs:

```
# Guest Pages
http://localhost/HomeHub/guest/index.html
http://localhost/HomeHub/properties.php

# Authentication
http://localhost/HomeHub/login/login.html
http://localhost/HomeHub/login/register.html

# Tenant Dashboard
http://localhost/HomeHub/tenant/index.php
http://localhost/HomeHub/tenant/saved-properties.php

# Landlord Dashboard
http://localhost/HomeHub/landlord/index.php
http://localhost/HomeHub/landlord/properties.php

# AI Features
http://localhost/HomeHub/ai-features.php

# Admin Panel
http://localhost/HomeHub/admin/analytics.php
http://localhost/HomeHub/admin/manage-users.php

# API Tests
http://localhost/HomeHub/api/get-available-properties.php
```

### Test User Accounts

Create test accounts using the registration page or manually insert into database:

```sql
-- Test Tenant
INSERT INTO users (email, password, first_name, last_name, user_type) 
VALUES ('tenant@test.com', '$2y$10$...', 'Test', 'Tenant', 'tenant');

-- Test Landlord
INSERT INTO users (email, password, first_name, last_name, user_type) 
VALUES ('landlord@test.com', '$2y$10$...', 'Test', 'Landlord', 'landlord');
```

## 🚀 Deployment to Production

### Deploying to Hostinger

1. **Export Database**
```bash
mysqldump -u root -p homehub > homehub_export.sql
```

2. **Upload Files**
   - Use FTP/SFTP or Hostinger File Manager
   - Upload all files except:
     - `check_*.php` (debug files)
     - `test_*.php` (test files)
     - `debug_*.php` (debug files)
     - `*.md` documentation files (optional)

3. **Import Database**
   - Access Hostinger phpMyAdmin
   - Create database
   - Import `homehub_export.sql`

4. **Update Configuration**
   - Edit `config/env.php` with production credentials
   - Update `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`

5. **Set File Permissions**
   - `uploads/` directory: 755
   - `config/env.php`: 644

6. **Test Production Site**
   - Visit your domain (e.g., `https://homehubai.shop`)
   - Test login, registration, and core features
   - Verify AI features work correctly

### Environment-Specific Code

The application automatically detects environment:

```php
// In config/env.php
function detectEnvironment() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return strpos($host, 'localhost') !== false ? 'development' : 'production';
}
```

## 📊 Performance Optimization

- **Database Indexing** - Indexes on frequently queried columns
- **Query Optimization** - Efficient JOIN operations and prepared statements
- **Caching** - Recommendation results cached for 24 hours
- **Image Optimization** - Compressed uploads and lazy loading
- **AJAX Loading** - Asynchronous data fetching for better UX
- **CDN Integration** - Ready for static asset CDN deployment

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. **Fork the Repository**
2. **Create a Feature Branch**
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. **Commit Changes**
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```
4. **Push to Branch**
   ```bash
   git push origin feature/AmazingFeature
   ```
5. **Open a Pull Request**

### Code Standards

- **PHP**: Follow PSR-12 coding standards
- **JavaScript**: Use ES6+ features and async/await
- **SQL**: Use prepared statements, never concatenate user input
- **Comments**: Document complex logic and functions
- **Testing**: Test on both localhost and production environments

## 🐛 Troubleshooting

### Common Issues

**Problem: "Database connection failed"**
- Solution: Check `config/env.php` credentials
- Verify MySQL service is running in XAMPP

**Problem: "HTTP 500 Error on AI features"**
- Solution: Check PHP error logs in `C:\xampp\php\logs\php_error_log`
- Ensure all required database tables exist
- Verify prepared statement compatibility

**Problem: "Property images not displaying"**
- Solution: Check `uploads/properties/` permissions
- Verify image paths in database are correct
- Ensure images were uploaded successfully

**Problem: "AI features not working on Hostinger"**
- Solution: Check `assets/js/ai-features.js` uses dynamic URLs
- Verify API endpoints return proper JSON
- Check browser console for JavaScript errors

### Debug Mode

Enable debug mode in `config/env.php`:

```php
define('DEBUG_MODE', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

**⚠️ Important:** Disable debug mode in production!

## 👨‍💻 Author

**HomeHub AI Team**
- Website: [homehubai.shop](https://homehubai.shop)
- Email: support@homehubai.shop

## 🙏 Acknowledgments

- **XAMPP** - Local development environment
- **PHP Community** - For excellent documentation
- **Machine Learning Libraries** - NumPy, pandas, scikit-learn
- **Bootstrap** - UI framework (if used)
- **Font Awesome** - Icons (if used)


---

<div align="center">

**⭐ Star this repository if you find it helpful!**

Made with ❤️ by the HomeHub AI Team

</div>
