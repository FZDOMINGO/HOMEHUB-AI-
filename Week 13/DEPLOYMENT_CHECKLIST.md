# 🚀 QUICK DEPLOYMENT CHECKLIST

**Print this and check off as you go!**

---

## BEFORE YOU START
- [ ] HomeHub working perfectly on localhost
- [ ] All admin/tenant/landlord features tested locally
- [ ] Hostinger account ready
- [ ] Domain name ready (or use Hostinger subdomain)
- [ ] 2-3 hours of uninterrupted time

---

## STEP 1: EXPORT DATABASE (5 min)
- [ ] Open http://localhost/phpmyadmin/
- [ ] Select `homehub` database
- [ ] Click Export → Custom → SQL format
- [ ] Enable "DROP TABLE" and "IF NOT EXISTS"
- [ ] Download as `homehub_backup_YYYY-MM-DD.sql`
- [ ] Verify file is > 50KB

---

## STEP 2: PREPARE FILES (10 min)
- [ ] Create `.htaccess` file (see guide)
- [ ] Create `uploads/properties/` folders
- [ ] Remove `ai_env/` folder from upload
- [ ] Remove test files (optional)
- [ ] Verify all PHP files have relative paths

---

## STEP 3: CREATE HOSTINGER DATABASE (10 min)
- [ ] Login to Hostinger hPanel
- [ ] Go to Databases → MySQL Databases
- [ ] Create new database: `homehub`
- [ ] Create database user with strong password
- [ ] **SAVE CREDENTIALS:**
  ```
  DB Name: u________homehub
  DB User: u________admin
  DB Pass: ___________________
  ```
- [ ] Access phpMyAdmin
- [ ] Import your SQL backup file
- [ ] Verify ~25 tables created successfully

---

## STEP 4: UPLOAD FILES (30 min)
- [ ] Download FileZilla FTP client
- [ ] Get FTP credentials from Hostinger
- [ ] Connect to Hostinger FTP
- [ ] Navigate to `public_html/`
- [ ] Delete default files
- [ ] Upload ALL HomeHub files
- [ ] Wait for upload to complete
- [ ] Verify folder structure matches local

---

## STEP 5: CONFIGURE ENV.PHP (15 min)
- [ ] Edit `config/env.php` on server
- [ ] Update production section:
  ```php
  'hosts' => ['yourdomain.com'],
  'username' => 'u________admin',
  'password' => 'YOUR_DB_PASSWORD',
  'database' => 'u________homehub',
  'url' => 'https://yourdomain.com',
  'debug' => false,  // MUST BE FALSE!
  ```
- [ ] Create email account in Hostinger
- [ ] Update email SMTP settings
- [ ] Save and upload env.php

---

## STEP 6: SET PERMISSIONS (5 min)
- [ ] Set `uploads/` to 755 permissions
- [ ] Set `uploads/properties/` to 755
- [ ] Set `assets/images/` to 755
- [ ] Verify other folders are 755
- [ ] Verify PHP files are 644

---

## STEP 7: TEST DEPLOYMENT (30 min)

### Basic Tests:
- [ ] Visit https://yourdomain.com/test_database.php
  - Should show "Connected!" with 25 tables
- [ ] Visit https://yourdomain.com/
  - Homepage should load
- [ ] Visit https://yourdomain.com/guest/
  - Should show properties

### User Tests:
- [ ] Register new tenant account
- [ ] Login as tenant
- [ ] Browse properties
- [ ] Request property visit
- [ ] Check notifications

### Admin Tests:
- [ ] Login to admin panel
  - Username: admin
  - Password: admin123
- [ ] View dashboard
- [ ] View users list
- [ ] View properties list

### Email Test:
- [ ] Visit https://yourdomain.com/test_email.php
- [ ] Check inbox for test email
- [ ] Verify email received

---

## STEP 8: SECURITY HARDENING (10 min)
- [ ] Change admin password (NOT admin123!)
- [ ] Verify debug mode is OFF in env.php
- [ ] Delete test files from production:
  - test_*.php
  - check_*.php
  - debug_*.php
- [ ] Test config files are protected:
  - Visit https://yourdomain.com/config/env.php
  - Should show 403 Forbidden ✅
- [ ] Enable SSL certificate in Hostinger
- [ ] Force HTTPS in .htaccess
- [ ] Set strong database password

---

## STEP 9: FINAL CHECKS (10 min)
- [ ] All links work (no 404 errors)
- [ ] Images load correctly
- [ ] CSS/JS load correctly
- [ ] No console errors (press F12)
- [ ] No PHP errors in error log
- [ ] Mobile view works
- [ ] Page loads in < 3 seconds

---

## STEP 10: GO LIVE! 🎉
- [ ] Announce to small test group
- [ ] Monitor error logs daily
- [ ] Fix any issues immediately
- [ ] Collect user feedback
- [ ] Public launch when stable

---

## COMMON ISSUES & QUICK FIXES

### 500 Error:
→ Check .htaccess syntax
→ Enable error display
→ Check PHP version (8.1+)

### Database Error:
→ Verify credentials in env.php
→ Check database user has permissions
→ Test connection in phpMyAdmin

### White Screen:
→ Enable error display
→ Check error_log file
→ Verify PHP syntax: `php -l file.php`

### CSS Not Loading:
→ Clear browser cache (Ctrl+Shift+Delete)
→ Check file paths are relative
→ Verify assets/ folder uploaded

### Can't Upload Images:
→ Set uploads/ to 755 permissions
→ Check PHP limits in .htaccess
→ Create missing subdirectories

### Email Not Sending:
→ Verify email account created
→ Check SMTP credentials
→ Try port 587 (TLS) or 465 (SSL)
→ Check spam folder

---

## SAVED CREDENTIALS (Fill This Out!)

**Database:**
```
Host: localhost
Name: u________________homehub
User: u________________admin  
Pass: _________________________
```

**FTP:**
```
Host: ftp.________________.com
User: u________________
Pass: _________________________
Port: 21
```

**Email:**
```
SMTP Host: smtp.hostinger.com
Email: noreply@________________.com
Pass: _________________________
Port: 587
```

**Admin:**
```
URL: https://________________.com/admin/
Username: admin
Old Pass: admin123
New Pass: _________________________ (CHANGE THIS!)
```

---

## SUPPORT

**Hostinger Support:** 24/7 Live Chat in hPanel  
**Guide Location:** HOSTINGER_DEPLOYMENT_GUIDE.md  
**Emergency:** Contact Hostinger support immediately

---

**Total Estimated Time:** 2-3 hours  
**Difficulty:** Intermediate  
**Success Rate:** 95% (if you follow every step!)

**Good luck! You've got this! 🚀**

---

*Print this checklist and keep it handy during deployment!*
