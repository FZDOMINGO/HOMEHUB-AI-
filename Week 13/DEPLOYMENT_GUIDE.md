# HomeHub Deployment Guide

## Overview
HomeHub has been completely refactored to work seamlessly on both localhost (XAMPP) and production (Hostinger) environments without manual configuration changes.

## ✅ What's Been Updated

### Phase 1: Foundation (6 files)
- ✅ `config/env.php` - Automatic environment detection and configuration
- ✅ `config/database.php` - Unified database connection layer with pooling
- ✅ `setup/database_setup.php` - Automated table creation script

### Phase 2: API Endpoints (22 files)
- ✅ All authentication APIs (`api/login.php`, `api/register.php`, `api/logout.php`, `api/check_session.php`)
- ✅ All AI feature APIs (`api/ai/get-analytics.php`, `api/ai/get-recommendations.php`, `api/ai/get-matches.php`)
- ✅ All booking APIs (landlord visits, reservations, status checks)
- ✅ All notification APIs (get, mark-read, count)
- ✅ All property APIs (details, available properties, history)
- ✅ All admin APIs (login, logout, users, stats)

### Phase 3: Page Files (43 files)
- ✅ All tenant pages (dashboard, saved, profile, history, notifications, preferences, email settings)
- ✅ All landlord pages (dashboard, add-property, manage-properties, edit-property, manage-availability, notifications, profile, history, email settings)
- ✅ All admin pages (analytics, dashboard, properties, users, email-settings, settings, preview, exit-preview, email-preview)
- ✅ All root pages (index, ai-features, properties, property-detail, bookings, history, save-property, process forms)
- ✅ Debug files (tenant/debug_navbar.php, landlord/debug_navbar.php)

### Phase 4: Email System (1 file)
- ✅ `includes/email_functions.php` - All email links now use APP_URL constant
  - Visit request emails
  - Booking request emails
  - Reservation approval emails
  - Visit approval emails
  - Property performance emails
  - New message emails
  - Welcome emails

### Phase 5: JavaScript (Verified)
- ✅ All JavaScript files already use relative paths
- ✅ No hardcoded URLs found in any .js files

**Total Files Updated: 72 files**

---

## 🚀 Deployment to Localhost (XAMPP)

### Prerequisites
- XAMPP installed with Apache and MySQL running
- HomeHub folder in `c:\xampp\htdocs\HomeHub`

### Steps

1. **Ensure Environment Detection Works**
   - Open `http://localhost/HomeHub/index.php` in browser
   - The system will automatically detect localhost environment

2. **Run Database Setup**
   - Navigate to: `http://localhost/HomeHub/setup/database_setup.php`
   - This creates all 13 required tables
   - Verify success message shows all tables created

3. **Test Registration & Login**
   - Register as tenant: `http://localhost/HomeHub/login/`
   - Register as landlord: `http://localhost/HomeHub/login/`
   - Test login with created accounts

4. **Test Core Features**
   - Landlord: Add a property, manage availability
   - Tenant: Browse properties, save properties, request visits
   - Check email notifications are sent with correct localhost URLs
   - Test AI features (recommendations, matches, analytics)

5. **Optional: Run Cleanup Script**
   ```powershell
   cd c:\xampp\htdocs\HomeHub
   .\cleanup_old_files.ps1
   ```
   This removes old test files and creates a backup

---

## 🌐 Deployment to Hostinger Production

### Prerequisites
- Hostinger account with PHP & MySQL
- Domain: `homehubai.shop`
- Database credentials ready

### Steps

#### 1. Update Database Credentials (ONE-TIME)

Edit `config/env.php` lines 36-40 with your Hostinger database credentials:

```php
// Production (Hostinger) configuration
case 'production':
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'u123456789_homehub');  // ← UPDATE THIS
    define('DB_PASSWORD', 'YourSecurePassword');   // ← UPDATE THIS
    define('DB_NAME', 'u123456789_homehub');       // ← UPDATE THIS
    break;
```

**To get these credentials:**
1. Log in to Hostinger Control Panel
2. Go to "Databases" → "MySQL Databases"
3. Find your HomeHub database
4. Copy: Database name, Username, Password

#### 2. Upload Files via FTP or File Manager

**Option A: FTP Upload (Recommended)**
```
1. Connect to FTP: ftp.homehubai.shop
2. Username: Your Hostinger FTP username
3. Password: Your Hostinger FTP password
4. Upload entire HomeHub folder to public_html/
```

**Option B: File Manager**
```
1. Log in to Hostinger Control Panel
2. Go to "Files" → "File Manager"
3. Navigate to public_html/
4. Upload HomeHub files
```

**Files to Upload:**
- All PHP files (index.php, all folders)
- config/env.php (with updated credentials)
- config/database.php
- setup/database_setup.php
- All api/ folder files
- All tenant/, landlord/, admin/ folder files
- All assets/ folder files
- includes/ folder (email_functions.php, PHPMailer/)
- login/, guest/ folders

**Files NOT to Upload:**
- test_*.php (test files)
- check_*.php (check files)
- debug*.php (root debug files, keep tenant/landlord debug files)
- backup_* folders
- ai_env/ (Python virtual environment - only needed if AI is hosted on Hostinger)

#### 3. Run Database Setup on Production

Navigate to: `https://homehubai.shop/setup/database_setup.php`

This will:
- Create all 13 required tables
- Set up foreign keys
- Initialize schema

**Expected Output:**
```
✓ Users table created
✓ Tenants table created
✓ Landlords table created
✓ Properties table created
✓ Property images table created
✓ Tenant preferences table created
✓ Similarity scores table created
✓ Browsing history table created
✓ Property reservations table created
✓ Booking visits table created
✓ Saved properties table created
✓ Notifications table created
✓ Recommendation cache table created

All tables created successfully!
```

#### 4. Test Production Environment

1. **Test Environment Detection**
   - Visit: `https://homehubai.shop/`
   - Check browser console for any errors
   - System should automatically detect production environment

2. **Test Registration**
   - Register as tenant: `https://homehubai.shop/login/`
   - Register as landlord: `https://homehubai.shop/login/`
   - Check email notifications (welcome emails should have correct production URLs)

3. **Test Authentication**
   - Login as tenant
   - Login as landlord
   - Test logout
   - Verify sessions work correctly

4. **Test Core Functionality**
   - Landlord: Add property, upload images, manage availability
   - Tenant: Browse properties, save properties, request visit
   - Check booking flow (visit requests, reservations)
   - Verify email notifications contain correct URLs (https://homehubai.shop/...)

5. **Test AI Features**
   - Navigate to: `https://homehubai.shop/ai-features.php`
   - Test recommendations
   - Test property matches
   - Test analytics dashboard

6. **Test Admin Panel** (if admin account exists)
   - Login to admin: `https://homehubai.shop/admin/`
   - Check user management
   - Check property management
   - Check analytics

#### 5. Monitor Error Logs

Check for any errors:
```
Location: public_html/HomeHub/error_log.txt
```

If errors occur, they will be logged here with full details in production mode.

---

## 🔧 Environment Configuration Details

### How Environment Detection Works

The system automatically detects the environment by checking the HTTP_HOST:

```php
// config/env.php
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    $environment = 'development';
} else {
    $environment = 'production';
}
```

### Environment Constants Available

**In PHP Files:**
```php
APP_ENV              // 'development' or 'production'
IS_PRODUCTION        // true on Hostinger, false on localhost
IS_DEVELOPMENT       // true on localhost, false on Hostinger
APP_URL              // 'http://localhost/HomeHub' or 'https://homehubai.shop'
DEBUG_MODE           // true on localhost, false on production
DB_SERVER            // Automatically set based on environment
DB_USERNAME          // Automatically set based on environment
DB_PASSWORD          // Automatically set based on environment
DB_NAME              // Automatically set based on environment
```

**Usage Examples:**
```php
// Redirect using environment-aware helper
redirect('/tenant/dashboard.php');

// Get asset URL
$logo = asset('assets/images/logo.png');

// Get API URL
$apiUrl = apiUrl('api/login.php');

// Log debug info (only shows on localhost)
logDebug('User logged in', ['user_id' => 123]);

// Initialize session (uses secure settings on production)
initSession();
```

---

## 📧 Email System Configuration

Email notifications are sent using PHPMailer with SMTP configuration stored in database.

### Email Configuration Table

The system uses the `email_config` table to store SMTP settings:

```sql
SELECT * FROM email_config WHERE id = 1;
```

**Fields:**
- `use_smtp` - 1 to use SMTP, 0 to use PHP mail()
- `smtp_host` - SMTP server (e.g., smtp.gmail.com)
- `smtp_port` - SMTP port (usually 587 for TLS, 465 for SSL)
- `smtp_username` - SMTP username/email
- `smtp_password` - SMTP password/app password
- `smtp_encryption` - 'tls' or 'ssl'
- `from_email` - Sender email address
- `from_name` - Sender name (e.g., "HomeHub")
- `reply_to_email` - Reply-to email address

### Email Links Work Automatically

All email templates now use `APP_URL` constant:

```php
// Email links automatically adapt to environment
<a href="' . APP_URL . '/landlord/bookings.php">View Request</a>

// On localhost: http://localhost/HomeHub/landlord/bookings.php
// On production: https://homehubai.shop/landlord/bookings.php
```

**Email Functions:**
- `sendVisitRequestEmail()` - Notifies landlord of visit request
- `sendBookingRequestEmail()` - Notifies landlord of reservation request
- `sendReservationApprovedEmail()` - Notifies tenant of approved reservation
- `sendVisitApprovedEmail()` - Notifies tenant of approved visit
- `sendPropertyPerformanceEmail()` - Notifies landlord of property trends
- `sendNewMessageEmail()` - Notifies user of new messages
- `sendWelcomeEmail()` - Welcomes new users

---

## 🔍 Troubleshooting

### Issue: Database Connection Failed on Hostinger

**Solution:**
1. Verify credentials in `config/env.php` match Hostinger database
2. Check database exists in Hostinger Control Panel
3. Ensure database user has all privileges
4. Run `setup/database_setup.php` to create tables

### Issue: Email Links Point to Wrong Domain

**Solution:**
- This should be automatic now
- Verify `config/env.php` is detecting environment correctly
- Check browser console: `console.log(window.location.host)`
- If localhost detected wrong, check HTTP_HOST in `phpinfo()`

### Issue: 404 Errors on Hostinger

**Solution:**
1. Check files uploaded to correct directory (public_html/)
2. Verify .htaccess file exists (if using mod_rewrite)
3. Check file permissions (644 for files, 755 for folders)

### Issue: Session Lost on Page Navigation

**Solution:**
- Ensure all files use `initSession()` instead of `session_start()`
- Check Hostinger PHP settings allow sessions
- Verify session.save_path is writable

### Issue: AI Features Not Working

**Solution:**
1. Check Python environment is set up (if using AI on Hostinger)
2. Verify Flask API is running (if AI separate)
3. Check API endpoints in browser console
4. Verify database tables have sample data for recommendations

---

## 📊 Database Tables Created

The setup script creates these tables:

1. **users** - Main user accounts (tenants and landlords)
2. **tenants** - Tenant-specific profile data
3. **landlords** - Landlord-specific profile data
4. **properties** - Property listings
5. **property_images** - Property photos
6. **tenant_preferences** - Tenant search preferences for AI matching
7. **similarity_scores** - AI-calculated property match scores
8. **browsing_history** - User browsing activity for recommendations
9. **property_reservations** - Reservation requests and status
10. **booking_visits** - Visit requests and scheduling
11. **saved_properties** - User's saved/favorited properties
12. **notifications** - In-app notification system
13. **recommendation_cache** - Cached AI recommendations

---

## 🎯 Next Steps After Deployment

1. **Test Complete User Journey**
   - Register → Browse → Save Property → Request Visit → Get Email
   - Verify all links in emails work correctly

2. **Configure Email SMTP**
   - Set up SMTP settings in `email_config` table
   - Test email notifications are delivered

3. **Add Sample Data**
   - Create landlord accounts
   - Add 10-20 sample properties with images
   - This helps test AI features (recommendations, matches)

4. **Monitor Performance**
   - Check `error_log.txt` regularly
   - Monitor database query performance
   - Optimize slow queries if needed

5. **Set Up Backups**
   - Schedule daily database backups
   - Back up uploaded property images
   - Keep backup of `config/env.php`

6. **Security Checklist**
   - Change all default passwords
   - Use strong database password
   - Enable HTTPS (SSL certificate) on Hostinger
   - Review file permissions
   - Delete setup/database_setup.php after initial setup (optional)

---

## 📝 File Structure Reference

```
HomeHub/
├── config/
│   ├── env.php              ← Environment auto-detection
│   ├── database.php         ← Unified DB connection
│   └── db_connect.php       ← Legacy (keep for backward compatibility)
│
├── setup/
│   └── database_setup.php   ← One-time table creation
│
├── api/
│   ├── login.php, register.php, logout.php, check_session.php
│   ├── ai/ (get-analytics.php, get-recommendations.php, get-matches.php)
│   ├── admin/ (login.php, logout.php, users.php, get-stats.php)
│   └── [all other API endpoints]
│
├── tenant/
│   ├── index.php, dashboard.php, saved.php, profile.php
│   ├── history.php, notifications.php, email-settings.php
│   └── setup-preferences.php
│
├── landlord/
│   ├── index.php, dashboard.php, add-property.php
│   ├── manage-properties.php, edit-property.php
│   ├── manage-availability.php, notifications.php
│   └── profile.php, history.php, email-settings.php
│
├── admin/
│   ├── dashboard.php, analytics.php, properties.php
│   ├── users.php, email-settings.php, settings.php
│   └── preview.php, exit-preview.php, email-preview.php
│
├── includes/
│   ├── email_functions.php  ← Email system with APP_URL
│   └── PHPMailer/           ← Email library
│
├── assets/
│   ├── css/ (styles)
│   ├── js/ (JavaScript files with relative paths)
│   └── images/ (static images)
│
├── login/ (authentication UI)
├── guest/ (guest/preview features)
├── property_images/ (uploaded property photos)
│
├── index.php                ← Main landing page
├── properties.php           ← Property listings
├── property-detail.php      ← Property details
├── ai-features.php          ← AI features showcase
├── bookings.php             ← Booking management
│
└── cleanup_old_files.ps1    ← Cleanup script for test files
```

---

## ✅ Success Criteria

Your deployment is successful when:

1. ✅ You can access the site on both localhost AND Hostinger
2. ✅ No manual configuration changes needed when switching environments
3. ✅ Users can register and login on both environments
4. ✅ Landlords can add properties with images
5. ✅ Tenants can browse, save, and request visits
6. ✅ Email notifications are sent with correct domain URLs
7. ✅ AI features work (recommendations, matches, analytics)
8. ✅ Admin panel accessible and functional
9. ✅ No errors in error_log.txt
10. ✅ All 13 database tables exist with correct schema

---

## 📞 Support

If you encounter issues:

1. Check `error_log.txt` for detailed error messages
2. Verify database credentials in `config/env.php`
3. Ensure all 13 tables exist: run `setup/database_setup.php`
4. Test environment detection: Add `<?php echo APP_ENV; ?>` to test page
5. Check browser console for JavaScript errors

---

**Last Updated:** December 2024  
**Version:** 2.0 (Complete Environment-Agnostic Refactor)  
**Status:** Production Ready ✅
