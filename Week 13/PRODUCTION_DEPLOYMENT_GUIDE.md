# HOMEHUB PRODUCTION DEPLOYMENT CHECKLIST

## 🚨 CRITICAL ISSUES FOUND

Your HomeHub app has several issues that will cause problems when deployed to a live web service. Here's what needs to be fixed:

---

## 1. ⚠️ HARDCODED LOCALHOST URLs (CRITICAL)

**Problem:** Multiple files contain `http://localhost/HomeHub/` URLs that won't work in production.

**Files that need updating:**
- `includes/email_functions.php`
- `admin/email-preview.php`
- `api/test-email.php`

**How to Fix:**
Replace ALL instances of `http://localhost/HomeHub/` with your production domain.

**Example:**
```php
// BEFORE (won't work in production):
<a href="http://localhost/HomeHub/landlord/bookings.php">

// AFTER (will work in production):
<a href="https://yourdomain.com/landlord/bookings.php">
```

**Search and Replace Steps:**
1. Open each file listed above
2. Find: `http://localhost/HomeHub/`
3. Replace with: `https://yourdomain.com/` (your actual domain)

---

## 2. ⚠️ DATABASE CONNECTION CONFIGURATION (CRITICAL)

**Problem:** Your `config/db_connect.php` uses localhost MySQL credentials.

**Current Configuration:**
```php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'homehub');
```

**What to do:**
1. Get database credentials from your web hosting provider
2. Update `config/db_connect.php` with production values:
   ```php
   define('DB_SERVER', 'your-db-host');     // Often: localhost or db.yourhost.com
   define('DB_USERNAME', 'your-db-user');   // NOT root!
   define('DB_PASSWORD', 'your-db-pass');   // MUST have a password!
   define('DB_NAME', 'your-db-name');       // Your database name
   ```

---

## 3. ⚠️ DEPRECATED PHP CODE (WARNING - PHP 8.1+)

**Problem:** 9 files use `FILTER_SANITIZE_STRING` which is deprecated in PHP 8.1+.

**Affected Files:**
- `process-booking.php`
- `process-reservation-clean.php`
- `process-reservation.php`
- `process-visit.php`
- `tenant/profile.php`
- And 4 more files

**How to Fix:**
Replace `FILTER_SANITIZE_STRING` with one of these:

```php
// OLD (deprecated):
$name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);

// NEW Option 1 (recommended):
$name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');

// NEW Option 2:
$name = strip_tags($_POST['name'] ?? '');
```

---

## 4. ⚠️ TEST FILES IN PRODUCTION (SECURITY RISK)

**Problem:** 50+ test files in root directory expose sensitive information.

**Files to DELETE or RESTRICT:**
- All `test_*.php` files (50+ files)
- All `check_*.php` files (20+ files)
- All `debug_*.php` files
- `setup_*.php` files
- `simple_setup.php`

**Action:**
Either:
1. DELETE these files completely, OR
2. Move them to a separate directory and protect with `.htaccess`:
   ```apache
   # Deny access to test directory
   Deny from all
   ```

---

## 5. ⚠️ ERROR DISPLAY SETTINGS (SECURITY RISK)

**Problem:** PHP may be showing errors to users (security risk in production).

**How to Fix:**
Create or update `.htaccess` file in root directory:

```apache
# Disable error display in production
php_flag display_errors Off
php_flag log_errors On
php_value error_log /path/to/your/error.log

# Enable error logging
php_flag display_startup_errors Off
```

**OR** ask your hosting provider to disable `display_errors` in PHP configuration.

---

## 6. ⚠️ MISSING .HTACCESS FILE

**Problem:** No `.htaccess` file found for URL rewriting and security.

**Create `.htaccess` in root directory:**

```apache
# HomeHub .htaccess

# Enable RewriteEngine
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Force HTTPS (if you have SSL)
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Redirect root to index.php
    RewriteRule ^$ index.php [L]
</IfModule>

# Disable directory browsing
Options -Indexes

# Protect configuration files
<FilesMatch "^(db_connect\.php|email_config\.php)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect log files
<FilesMatch "\.(log|txt)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Set default document
DirectoryIndex index.php index.html

# Error pages (optional)
ErrorDocument 404 /404.html
ErrorDocument 500 /500.html
```

---

## 7. ⚠️ DATABASE EXPORT & IMPORT

**Before uploading:**
1. Export your local database:
   - Open phpMyAdmin
   - Select `homehub` database
   - Click "Export" tab
   - Choose "Quick" export method
   - Click "Go" to download SQL file

2. Import to production:
   - Log into your hosting control panel (cPanel/Plesk)
   - Open phpMyAdmin
   - Create new database
   - Import the SQL file you downloaded

---

## 8. ⚠️ FILE UPLOAD & PERMISSIONS

**After uploading files:**

1. Set correct permissions:
   ```
   Directories: 755
   Files: 644
   config/ files: 600 (more secure)
   ```

2. Ensure these directories are writable:
   ```
   uploads/           (755 or 777)
   logs/              (755 or 777)
   ```

---

## 9. ⚠️ EMAIL CONFIGURATION

**Update email settings:**

1. Log into admin panel: `https://yourdomain.com/admin/login.php`
2. Go to Email Settings
3. Update SMTP configuration with your hosting provider's SMTP settings

**Common SMTP Configurations:**

**cPanel/Shared Hosting:**
```
SMTP Host: localhost or mail.yourdomain.com
SMTP Port: 587
SMTP Security: TLS
```

**Gmail (if using):**
```
SMTP Host: smtp.gmail.com
SMTP Port: 587
SMTP Security: TLS
Username: your-email@gmail.com
Password: App Password (not regular password)
```

---

## 10. 📋 STEP-BY-STEP DEPLOYMENT CHECKLIST

### Before Upload:
- [ ] Update all `localhost` URLs to production domain
- [ ] Update `config/db_connect.php` with production database credentials
- [ ] Fix deprecated `FILTER_SANITIZE_STRING` functions
- [ ] Create `.htaccess` file
- [ ] Export database from phpMyAdmin
- [ ] Remove or move test files to secure directory
- [ ] Test locally one final time

### During Upload:
- [ ] Upload all files via FTP/SFTP or File Manager
- [ ] Verify all files uploaded correctly
- [ ] Check file permissions (755 for folders, 644 for files)
- [ ] Create writable directories: `uploads/`, `logs/`

### After Upload:
- [ ] Import database SQL file
- [ ] Test database connection
- [ ] Update email SMTP settings via admin panel
- [ ] Test user registration
- [ ] Test user login (tenant and landlord)
- [ ] Test property listings
- [ ] Test email notifications
- [ ] Test image uploads
- [ ] Test booking/reservation flow

---

## 11. 🔧 QUICK FIX SCRIPT

I'll create a script to automatically fix the most critical issues...

---

## 12. 📞 COMMON DEPLOYMENT ERRORS & SOLUTIONS

### Error: "Database connection failed"
**Solution:** Update `config/db_connect.php` with correct production credentials

### Error: "500 Internal Server Error"
**Solution:** 
1. Check PHP error logs
2. Verify file permissions
3. Check `.htaccess` syntax

### Error: "404 Not Found" on all pages
**Solution:** Enable mod_rewrite or fix `.htaccess` RewriteBase

### Error: Images/CSS not loading
**Solution:** 
1. Check file paths (should be relative, not absolute)
2. Verify `uploads/` directory exists and is writable

### Error: Emails not sending
**Solution:**
1. Update SMTP settings via admin panel
2. Test with hosting provider's SMTP server first
3. Check email logs in production

---

## 13. 🎯 PRIORITY FIXES (DO THESE FIRST)

**MUST FIX (App won't work without these):**
1. ✅ Update `config/db_connect.php` with production database credentials
2. ✅ Replace all `http://localhost/HomeHub/` with production domain
3. ✅ Import database to production server

**SHOULD FIX (Security/Performance):**
4. ✅ Remove test files or restrict access
5. ✅ Disable error display (`display_errors = Off`)
6. ✅ Create `.htaccess` file
7. ✅ Update email SMTP settings

**NICE TO FIX (Future-proofing):**
8. ⚪ Fix deprecated `FILTER_SANITIZE_STRING`
9. ⚪ Add custom error pages
10. ⚪ Enable HTTPS redirect

---

## 14. 📝 HOSTING PROVIDER SPECIFIC NOTES

### cPanel Hosting:
1. Use File Manager or FTP to upload files
2. Import database via phpMyAdmin
3. Use localhost for DB_SERVER
4. Email SMTP often uses `mail.yourdomain.com`

### Shared Hosting (GoDaddy, Bluehost, etc.):
1. Database host might be `localhost` or specific server
2. Get database credentials from hosting control panel
3. May need to use hosting provider's SMTP for emails

### VPS/Dedicated Server:
1. You have full control over MySQL configuration
2. May need to configure MySQL to allow remote connections
3. Can use any SMTP service (Gmail, SendGrid, etc.)

---

## 15. 🚀 POST-DEPLOYMENT TESTING

After deployment, test these features:

**User System:**
- [ ] Registration (tenant and landlord)
- [ ] Login/logout
- [ ] Password reset (if implemented)

**Property Listings:**
- [ ] Browse properties
- [ ] View property details
- [ ] Search/filter properties

**Tenant Features:**
- [ ] Save properties
- [ ] Request visits
- [ ] Request reservations
- [ ] View notifications
- [ ] Update profile

**Landlord Features:**
- [ ] Add new property
- [ ] Edit property
- [ ] Upload property images
- [ ] Manage bookings
- [ ] Approve/reject visits
- [ ] View analytics

**Email Notifications:**
- [ ] Visit request emails to landlord
- [ ] Reservation request emails to landlord
- [ ] Approval emails to tenant
- [ ] Test all email types

**Admin Panel:**
- [ ] Admin login
- [ ] View statistics
- [ ] Manage users
- [ ] Email settings

---

## 16. 💡 TIPS FOR SMOOTH DEPLOYMENT

1. **Backup Everything:** Keep a backup of all files and database locally
2. **Test Locally First:** Ensure everything works on localhost before uploading
3. **Use Version Control:** Consider using Git for future updates
4. **Monitor Logs:** Check error logs regularly after deployment
5. **Gradual Rollout:** Test with a few users before public launch
6. **SSL Certificate:** Get free SSL with Let's Encrypt or from your host
7. **Documentation:** Keep notes on what you changed for future reference

---

## 17. 🆘 NEED HELP?

If deployment fails, check:
1. PHP error logs (usually in control panel or `/logs/`)
2. Apache error logs
3. Database connection test
4. File permissions
5. `.htaccess` syntax

**Run diagnostic after upload:**
```
https://yourdomain.com/production_diagnostic.php
```

---

## SUMMARY

**Critical Issues:** 3
- Hardcoded localhost URLs
- Database configuration
- Test files in production

**Warnings:** 4
- Deprecated PHP functions
- Error display settings
- Missing .htaccess
- Security settings

**Action Required:**
Fix the 3 critical issues before deployment, or the app will not work!

---

**Good luck with your deployment! 🚀**
