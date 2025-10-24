# 🚀 HomeHub - Quick Start Guide

## How to Run the HomeHub Web Application

### Prerequisites Check ✅

Before starting, make sure you have:
- ✅ XAMPP installed (includes Apache & MySQL)
- ✅ Web browser (Chrome, Firefox, Edge, etc.)
- ✅ Python 3.8+ installed (for AI features)

---

## 🎯 OPTION 1: Run Basic Web Application (Without AI)

### Step 1: Start XAMPP Services

1. **Open XAMPP Control Panel**
   ```
   C:\xampp\xampp-control.exe
   ```

2. **Start Apache** (Web Server)
   - Click the "Start" button next to "Apache"
   - Wait for it to turn green
   - Port 80 should be active

3. **Start MySQL** (Database Server)
   - Click the "Start" button next to "MySQL"
   - Wait for it to turn green
   - Port 3306 should be active

### Step 2: Access the Application

Open your web browser and go to:

```
http://localhost/HomeHub/guest/index.html
```

**Other URLs you can access:**
- Guest Homepage: `http://localhost/HomeHub/guest/index.html`
- Properties Page: `http://localhost/HomeHub/properties.php`
- Login Page: `http://localhost/HomeHub/login/login.html`
- Register Page: `http://localhost/HomeHub/login/register.html`
- AI Features: `http://localhost/HomeHub/ai-features.php`
- Bookings: `http://localhost/HomeHub/bookings.php`
- History: `http://localhost/HomeHub/history.php`

### Step 3: Login to Test

**Default Test Accounts** (if you have them in database):
```
Tenant Account:
- Email: tenant@example.com
- Password: [your password]

Landlord Account:
- Email: landlord@example.com
- Password: [your password]
```

### Step 4: Stop Services When Done

In XAMPP Control Panel:
1. Click "Stop" next to MySQL
2. Click "Stop" next to Apache

---

## 🤖 OPTION 2: Run with AI Features (Full System)

### Step 1: Start XAMPP (Same as above)

1. Start Apache
2. Start MySQL
3. Wait for both to turn green

### Step 2: Set Up AI Backend (First Time Only)

Open PowerShell in HomeHub folder:

```powershell
# Navigate to HomeHub folder
cd C:\xampp\htdocs\HomeHub

# Run the setup script
.\setup_ai.bat
```

This will:
- ✅ Create Python virtual environment
- ✅ Install AI dependencies (numpy, pandas, scikit-learn, etc.)
- ✅ Set up configuration files
- ✅ Create necessary folders

### Step 3: Import AI Database Schema (First Time Only)

**Option A: Using phpMyAdmin**
1. Open: `http://localhost/phpmyadmin`
2. Click on `homehub` database (left sidebar)
3. Click "Import" tab
4. Click "Choose File"
5. Select: `C:\xampp\htdocs\HomeHub\sql\ai_features_schema.sql`
6. Click "Go" at the bottom
7. Wait for success message

**Option B: Using MySQL Command Line**
```powershell
# In PowerShell (HomeHub folder)
cd C:\xampp\htdocs\HomeHub
mysql -u root homehub < sql\ai_features_schema.sql
```

### Step 4: Start AI Backend Server

Open a **NEW PowerShell window**:

```powershell
# Navigate to HomeHub
cd C:\xampp\htdocs\HomeHub

# Activate Python environment
.\ai_env\Scripts\Activate.ps1

# You should see (ai_env) in prompt

# Start the Flask API server
python ai\api_server.py
```

**Expected output:**
```
 * Running on http://127.0.0.1:5000
 * AI Backend Server Started
```

**Keep this window open!** The AI server needs to run in the background.

### Step 5: Access the Application

Open browser and go to:
```
http://localhost/HomeHub/guest/index.html
```

### Step 6: Test AI Features

1. **Register/Login as a Tenant**
2. **Set Your Preferences**:
   ```
   http://localhost/HomeHub/tenant/setup-preferences.php
   ```
3. **View AI-Matched Properties**:
   ```
   http://localhost/HomeHub/properties.php?ai_match=true
   ```
4. **Check AI Features Page**:
   ```
   http://localhost/HomeHub/ai-features.php
   ```

### Step 7: Stop Services

**Stop AI Server:**
- In the PowerShell window running Flask, press `Ctrl+C`

**Stop XAMPP:**
- Stop MySQL
- Stop Apache

---

## 📊 Quick Access URLs

### Guest/Public Pages
```
Homepage:        http://localhost/HomeHub/guest/index.html
Properties:      http://localhost/HomeHub/properties.php
AI Features:     http://localhost/HomeHub/ai-features.php
Login:           http://localhost/HomeHub/login/login.html
Register:        http://localhost/HomeHub/login/register.html
```

### Tenant Pages (Login Required)
```
Dashboard:       http://localhost/HomeHub/tenant/dashboard.php
Set Preferences: http://localhost/HomeHub/tenant/setup-preferences.php
Saved Properties: http://localhost/HomeHub/tenant/saved.php
Notifications:   http://localhost/HomeHub/tenant/notifications.php
Profile:         http://localhost/HomeHub/tenant/profile.php
```

### Landlord Pages (Login Required)
```
Dashboard:       http://localhost/HomeHub/landlord/dashboard.php
Add Property:    http://localhost/HomeHub/landlord/add-property.php
Manage Properties: http://localhost/HomeHub/landlord/manage-properties.php
Notifications:   http://localhost/HomeHub/landlord/notifications.php
```

### Shared Pages
```
Bookings:        http://localhost/HomeHub/bookings.php
History:         http://localhost/HomeHub/history.php
```

### Admin/Debug Pages
```
phpMyAdmin:      http://localhost/phpmyadmin
Test Database:   http://localhost/HomeHub/test_database.php
```

---

## 🔧 Common Issues & Solutions

### ❌ "Apache won't start - Port 80 in use"

**Solution:**
```powershell
# Find what's using port 80
netstat -ano | findstr :80

# Option 1: Stop the conflicting service
# Option 2: Change Apache port in XAMPP config
```

### ❌ "MySQL won't start - Port 3306 in use"

**Solution:**
- Stop other MySQL services
- Check if MySQL is already running as Windows service
- Restart XAMPP as Administrator

### ❌ "Cannot access http://localhost/HomeHub"

**Check:**
1. Apache is running (green in XAMPP)
2. Correct URL: `http://localhost/HomeHub/guest/index.html`
3. Files are in `C:\xampp\htdocs\HomeHub\`

### ❌ "Database connection failed"

**Check:**
1. MySQL is running (green in XAMPP)
2. Database exists: `homehub`
3. Check credentials in `config/db_connect.php`:
   ```php
   DB_SERVER: localhost
   DB_USERNAME: root
   DB_PASSWORD: (empty)
   DB_NAME: homehub
   ```

### ❌ "Python not found" (For AI features)

**Solution:**
```powershell
# Install Python 3.8+ from python.org
# Add to PATH during installation
# Restart PowerShell
python --version
```

### ❌ "AI Backend not responding"

**Check:**
1. Flask server is running (check PowerShell window)
2. URL is: `http://127.0.0.1:5000`
3. Virtual environment is activated: `(ai_env)` in prompt

---

## 🎮 Development Workflow

### Daily Development Routine:

**1. Start Services:**
```powershell
# Start XAMPP (Apache + MySQL)
# Open XAMPP Control Panel → Start Apache → Start MySQL
```

**2. Start AI Backend (if using AI features):**
```powershell
cd C:\xampp\htdocs\HomeHub
.\ai_env\Scripts\Activate.ps1
python ai\api_server.py
```

**3. Open Browser:**
```
http://localhost/HomeHub/guest/index.html
```

**4. Make Changes:**
- Edit PHP/HTML/CSS/JS files
- Refresh browser to see changes (no restart needed)
- For Python AI changes: Restart Flask server (Ctrl+C, then run again)

**5. Stop Services:**
- Stop Flask (Ctrl+C)
- Stop MySQL & Apache in XAMPP

---

## 📱 Testing User Flows

### Test as Guest:
1. Go to: `http://localhost/HomeHub/guest/index.html`
2. Browse properties (no login needed)
3. View property details
4. See AI features page

### Test as Tenant:
1. Register: `http://localhost/HomeHub/login/register.html`
2. Login as tenant
3. Set preferences: `/tenant/setup-preferences.php`
4. View matched properties
5. Save properties
6. Request visits
7. Make reservations

### Test as Landlord:
1. Register as landlord
2. Login
3. Add property: `/landlord/add-property.php`
4. Manage properties
5. View bookings
6. Check notifications

---

## 🔍 Debugging Tips

### Check if Apache is working:
```
http://localhost
# Should show XAMPP welcome page
```

### Check if MySQL is working:
```
http://localhost/phpmyadmin
# Should show phpMyAdmin login
```

### Check PHP errors:
```php
// Add to top of PHP file temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check AI Backend logs:
```powershell
# View logs
type ai\logs\homehub_ai.log
```

### Check database:
```sql
-- In phpMyAdmin or MySQL command line
USE homehub;
SHOW TABLES;
SELECT COUNT(*) FROM properties;
SELECT * FROM tenant_preferences;
```

---

## 🚀 Quick Start Commands

### Windows PowerShell Commands:

```powershell
# Navigate to project
cd C:\xampp\htdocs\HomeHub

# Start AI environment (if needed)
.\ai_env\Scripts\Activate.ps1

# Run AI server
python ai\api_server.py

# Check Python version
python --version

# List installed packages
pip list

# Import database
mysql -u root homehub < sql\ai_features_schema.sql

# View file structure
tree /F

# Check if Apache is running
netstat -ano | findstr :80
```

---

## 📞 Need Help?

### Quick Checks:
1. ✅ XAMPP Apache & MySQL running (green status)
2. ✅ Correct URL: `http://localhost/HomeHub/...`
3. ✅ Database `homehub` exists
4. ✅ Files in `C:\xampp\htdocs\HomeHub\`

### Documentation:
- **Full Guide**: Read `AI_IMPLEMENTATION_GUIDE.md`
- **Summary**: Read `AI_SUMMARY.md`
- **Database**: Check `sql\ai_features_schema.sql`

### Common URLs for Troubleshooting:
- XAMPP Dashboard: `http://localhost`
- phpMyAdmin: `http://localhost/phpmyadmin`
- HomeHub: `http://localhost/HomeHub/guest/index.html`

---

## 🎉 You're Ready!

**Basic Setup (No AI):**
1. Start XAMPP (Apache + MySQL)
2. Open `http://localhost/HomeHub/guest/index.html`
3. Done! 🎉

**Full Setup (With AI):**
1. Run `setup_ai.bat` (first time only)
2. Import `sql\ai_features_schema.sql` (first time only)
3. Start XAMPP
4. Start AI server: `python ai\api_server.py`
5. Open `http://localhost/HomeHub/guest/index.html`
6. Done! 🚀

---

**Happy Coding!** 💻✨
