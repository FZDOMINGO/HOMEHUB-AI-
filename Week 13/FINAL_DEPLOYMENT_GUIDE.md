# 🚀 HomeHub Production Deployment Guide

## Pre-Deployment Checklist

### ✅ Before You Deploy - Run These Checks:

1. **Run Pre-Deployment Check**
   ```
   Visit: http://localhost/HomeHub/pre_deployment_check.php
   ```
   - Must show 90%+ readiness
   - Fix all critical errors
   - Address warnings if possible

2. **Test Locally First**
   - Login as tenant ✓
   - Login as landlord ✓
   - Browse properties ✓
   - Save a property ✓
   - Request a visit ✓
   - Check AI features ✓
   - View bookings ✓

3. **Database Ready**
   - All tables exist ✓
   - Sample data present ✓
   - AI recommendations generated ✓

---

## 📦 Files Ready for Deployment

### All Fixed Issues:
- ✅ landlord/index.php - Fixed localhost:3000 URL
- ✅ includes/email_functions.php - All URLs point to homehubai.shop
- ✅ admin/email-preview.php - URLs updated
- ✅ api/test-email.php - URLs updated
- ✅ ai-features.php - Added user type detection
- ✅ assets/js/ai-features.js - Smart routing for tenants/landlords
- ✅ 5 files with deprecated PHP code - All fixed
- ✅ .htaccess - Security configuration created
- ✅ config/db_connect.PRODUCTION.php - Template created

### Files to Upload to homehubai.shop:
**Upload ALL files from C:\xampp\htdocs\HomeHub\ to your hosting**

Recommended method:
- FTP/SFTP: FileZilla, WinSCP
- Hosting Control Panel: File Manager
- Git: Push to repository and pull on server

---

## 🔧 Step-by-Step Deployment

### Step 1: Export Database (5 minutes)

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Select `homehub` database (left sidebar)
3. Click **"Export"** tab
4. Choose **"Quick"** export method
5. Format: **SQL**
6. Click **"Export"** button
7. Save file as: `homehub_production.sql`

**Alternative (Custom Export):**
- Export → Custom → Select all tables
- Structure: Include CREATE TABLE
- Data: Include INSERT INTO
- Check "Add DROP TABLE"
- Export

### Step 2: Update Database Configuration (2 minutes)

1. Open `config/db_connect.php`
2. Update with your hosting provider's credentials:

```php
<?php
// Production Database Configuration
define('DB_SERVER', 'localhost');           // or your host's DB server
define('DB_USERNAME', 'homehubai_admin');   // your DB username
define('DB_PASSWORD', 'your_secure_password'); // your DB password
define('DB_NAME', 'homehubai_homehub');    // your DB name

// Create database connection
function getDbConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        die(json_encode([
            "status" => "error", 
            "message" => "Database connection failed: " . $conn->connect_error
        ]));
    }
    
    return $conn;
}
?>
```

**Where to find credentials:**
- Check your hosting provider's email
- Or login to hosting control panel → MySQL Databases

### Step 3: Upload Files to Production (10-15 minutes)

**Option A: Using FTP/SFTP**
1. Install FileZilla or WinSCP
2. Connect to your hosting:
   - Host: ftp.homehubai.shop (or provided by host)
   - Username: your FTP username
   - Password: your FTP password
   - Port: 21 (FTP) or 22 (SFTP)
3. Navigate to public_html or www folder
4. Upload entire HomeHub folder contents
5. Wait for upload to complete

**Option B: Using Hosting Control Panel**
1. Login to cPanel/Plesk/DirectAdmin
2. Go to File Manager
3. Navigate to public_html or www
4. Upload files as ZIP
5. Extract on server

**Important:** Maintain folder structure!
```
public_html/
├── index.php
├── properties.php
├── ai-features.php
├── .htaccess
├── config/
├── includes/
├── api/
├── assets/
├── tenant/
├── landlord/
├── admin/
└── login/
```

### Step 4: Import Database to Production (5 minutes)

1. Login to hosting control panel
2. Find **phpMyAdmin** icon/link
3. Create database if not exists:
   - MySQL Databases → Create Database
   - Name: `homehubai_homehub` (or your choice)
4. Select the new database (left sidebar)
5. Click **"Import"** tab
6. Click **"Choose File"**
7. Select `homehub_production.sql`
8. Click **"Import"** button at bottom
9. Wait for success message

**Verify:**
- Check if all tables imported (should see 11+ tables)
- Click on `users` table → Browse (should show your users)
- Click on `properties` table → Browse (should show properties)

### Step 5: Test Production Site (10 minutes)

1. **Visit:** https://homehubai.shop/pre_deployment_check.php
   - Should show 90%+ readiness
   - Check for any critical errors
   - Note any warnings

2. **Test Homepage:** https://homehubai.shop/
   - Should load without errors
   - Logo should display
   - Navigation should work

3. **Test Registration:**
   - https://homehubai.shop/login/register.html
   - Register a new tenant account
   - Should redirect to tenant dashboard

4. **Test Login:**
   - https://homehubai.shop/login/login.html
   - Login with account created above
   - Should redirect properly

5. **Test Properties:**
   - https://homehubai.shop/properties.php
   - Should display property listings
   - Click on a property
   - Property detail modal should open

6. **Test Save Property:**
   - While logged in, browse properties
   - Click heart ❤️ icon
   - Should show "Property saved" message
   - Go to: https://homehubai.shop/tenant/saved.php
   - Saved property should appear

7. **Test Visit Request:**
   - Open property detail
   - Click "Schedule Visit"
   - Fill form and submit
   - Should show success message
   - Check: https://homehubai.shop/bookings.php

8. **Test AI Features:**
   - https://homehubai.shop/ai-features.php
   - Login as tenant → Click "Get Recommendations"
   - Login as landlord → Click "View Analytics"
   - Both should work without errors

### Step 6: Configure Email (Optional, 5 minutes)

1. Login as admin: https://homehubai.shop/admin/login.php
2. Go to Email Settings
3. Configure SMTP:

**Option A: Gmail**
```
SMTP Host: smtp.gmail.com
SMTP Port: 587
SMTP User: your.email@gmail.com
SMTP Password: [App Password]
Encryption: TLS
```

**Option B: cPanel Email**
```
SMTP Host: mail.homehubai.shop
SMTP Port: 587
SMTP User: noreply@homehubai.shop
SMTP Password: [your password]
Encryption: TLS
```

4. Test email by sending a test notification

---

## 🔒 Post-Deployment Security

### Delete These Files from Production:
```bash
# Diagnostic/Test Files (Delete after verifying everything works)
pre_deployment_check.php
check_ai_database.php
generate_ai_recommendations.php
production_test.php
test_*.php
check_*.php
debug_*.php
simple_setup.php
```

**How to delete:**
- Via FTP: Connect and delete files
- Via File Manager: Select files → Delete
- Via SSH: `rm -f test_*.php check_*.php debug_*.php`

### Set Proper File Permissions:
```bash
Directories: 755 (rwxr-xr-x)
PHP Files: 644 (rw-r--r--)
config/db_connect.php: 600 or 644
uploads/: 755 or 777 (must be writable)
logs/: 755 or 777 (must be writable)
```

### Enable Error Logging (Not Display):
Update `.htaccess` or `php.ini`:
```
display_errors = Off
log_errors = On
error_log = /path/to/logs/php_error.log
```

---

## 🧪 Testing Checklist

Use this checklist after deployment:

### Guest (Not Logged In):
- [ ] Homepage loads: https://homehubai.shop/
- [ ] Navigation works (all links)
- [ ] Properties page displays listings
- [ ] Property detail modal opens
- [ ] AI Features page loads (shows login prompts)
- [ ] Registration form works
- [ ] Login form works

### Tenant Account:
- [ ] Can register new tenant
- [ ] Can login
- [ ] Dashboard shows correct stats
- [ ] Can browse properties
- [ ] Can save properties (heart icon)
- [ ] Saved properties show in tenant/saved.php
- [ ] Can request property visit
- [ ] Visit request appears in bookings
- [ ] Can view browsing history
- [ ] AI Features → Get Recommendations works
- [ ] AI Features → Tenant Matching works
- [ ] Notifications work
- [ ] Profile update works

### Landlord Account:
- [ ] Can register new landlord
- [ ] Can login
- [ ] Dashboard shows property stats
- [ ] Can add new property
- [ ] Can upload property images
- [ ] Property appears in listings
- [ ] Can edit property
- [ ] Can view bookings/requests
- [ ] Can approve/decline visits
- [ ] AI Features → View Analytics works
- [ ] Notifications work
- [ ] Profile update works

### Admin:
- [ ] Can login to admin panel
- [ ] Can view all properties
- [ ] Can manage users
- [ ] Email settings work
- [ ] Can send test emails

### Email Notifications:
- [ ] Welcome email on registration
- [ ] Visit request email to landlord
- [ ] Visit approval email to tenant
- [ ] All email links point to homehubai.shop

---

## ❗ Troubleshooting

### Issue: Blank Page / White Screen
**Causes:**
- PHP error
- Database connection failed
- Missing files

**Solutions:**
1. Check error logs in hosting control panel
2. Enable display_errors temporarily to see error
3. Verify database credentials in config/db_connect.php
4. Check all files uploaded correctly

### Issue: Database Connection Error
**Error:** "Database connection failed"

**Solutions:**
1. Verify database credentials:
   ```php
   // Check these in config/db_connect.php
   DB_SERVER - usually 'localhost'
   DB_USERNAME - from hosting provider
   DB_PASSWORD - from hosting provider
   DB_NAME - database you created
   ```
2. Check if database exists in phpMyAdmin
3. Verify database user has permissions
4. Contact hosting support for correct DB_SERVER value

### Issue: Images Not Loading
**Causes:**
- Incorrect file paths
- Permission issues
- Files not uploaded

**Solutions:**
1. Check uploads/ folder exists
2. Set uploads/ permissions to 755 or 777
3. Verify images uploaded correctly
4. Check browser console for 404 errors

### Issue: CSS/JS Not Loading
**Causes:**
- Files not uploaded
- Incorrect paths
- Caching

**Solutions:**
1. Clear browser cache (Ctrl + F5)
2. Check assets/ folder uploaded
3. Verify file paths in source code
4. Check browser console for errors

### Issue: AI Features Not Working
**Causes:**
- recommendation_cache empty
- API endpoints failing
- Session issues

**Solutions:**
1. Run: https://homehubai.shop/check_ai_database.php
2. If cache empty: https://homehubai.shop/generate_ai_recommendations.php
3. Check browser console (F12) for API errors
4. Verify session is working (check_session.php)

### Issue: Emails Not Sending
**Causes:**
- SMTP not configured
- Wrong credentials
- Firewall blocking

**Solutions:**
1. Login to admin panel → Email Settings
2. Configure SMTP with hosting provider's details
3. Test with simple email first
4. Check if port 587 or 465 is open
5. Try using hosting provider's SMTP instead of Gmail

### Issue: "Nothing works when logged in"
**Solution:** Already fixed! But if still happening:
1. Clear browser cookies
2. Check session.php errors
3. Verify database tables exist
4. Check browser console for JavaScript errors

---

## 📞 Support Resources

### Hosting Provider Support:
- Database credentials
- SMTP settings
- File permissions
- Server configuration

### Error Checking:
```
1. Browser Console (F12) → Console Tab
   - Shows JavaScript errors

2. Browser Console (F12) → Network Tab
   - Shows failed API requests
   - Check response for error messages

3. Hosting Error Logs
   - cPanel → Error Logs
   - Usually in logs/error_log

4. Check Session:
   https://homehubai.shop/api/check_session.php
   Should return: {"loggedIn":true, ...}
```

---

## ✅ Deployment Complete Checklist

- [ ] Pre-deployment check shows 90%+ readiness
- [ ] Database exported from localhost
- [ ] config/db_connect.php updated with production credentials
- [ ] All files uploaded to homehubai.shop
- [ ] Database imported to production
- [ ] Homepage loads without errors
- [ ] Can register new account
- [ ] Can login
- [ ] Properties display correctly
- [ ] Save property works
- [ ] Visit requests work
- [ ] AI Features work for tenants
- [ ] AI Features work for landlords
- [ ] Email notifications configured
- [ ] All test files deleted
- [ ] File permissions set correctly
- [ ] Error logging enabled
- [ ] SSL certificate active (https://)

---

## 🎉 Success!

**Your HomeHub application is now LIVE at:**
### https://homehubai.shop/

### What's Working:
✅ User registration and authentication
✅ Property browsing and search
✅ Save properties (favorites)
✅ Visit and reservation requests
✅ Landlord property management
✅ Tenant dashboard with AI recommendations
✅ Landlord analytics dashboard
✅ Email notifications
✅ Booking management
✅ Browsing history tracking
✅ AI-powered property matching
✅ Predictive analytics for landlords

### Monitor Your Application:
- Check error logs daily (first week)
- Monitor user registrations
- Check email deliverability
- Review AI recommendation quality
- Monitor property listings
- Check booking requests

---

**Deployment Date:** October 28, 2025
**Status:** Production Ready ✓
**Next Steps:** Monitor and optimize based on user feedback

**Good luck with your launch! 🚀**
