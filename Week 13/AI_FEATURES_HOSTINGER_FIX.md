# 🔍 WHY AI FEATURES WORK ON LOCALHOST BUT NOT ON HOSTINGER

## Root Cause Analysis

Your AI features work locally but fail on Hostinger due to **5 potential issues**:

---

## ❌ Issue 1: Missing Database Tables (MOST LIKELY)

The AI features require specific database tables that might not exist on Hostinger:

### Required Tables:
1. **`tenant_preferences`** - Stores tenant search preferences
2. **`similarity_scores`** - Stores calculated property matches
3. **`browsing_history`** - Tracks property views
4. **`property_images`** - Property photos
5. **`tenants`** - Tenant profiles
6. **`landlords`** - Landlord profiles

### Why This Causes Failure:
- If these tables don't exist on Hostinger, the AI queries will fail
- Error: "Table 'u######_homehub.tenant_preferences' doesn't exist"

### Solution:
Your database export (`homehub.sql`) should include ALL tables. When you import on Hostinger, verify all tables exist.

---

## ❌ Issue 2: Session Issues on Hostinger

### The Problem:
```php
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    // This fails if session is not properly started
}
```

### Why It Fails on Hostinger:
- Hostinger may have stricter session handling
- Session data might not persist across requests
- Different session path permissions

### Symptoms:
- User appears logged in on main page
- AI features say "Unauthorized" or "Please log in"
- Works after multiple attempts

### Solution:
Session configuration is already fixed in updated files, but check:
```php
// At top of api/ai/get-matches.php
session_start();
```

---

## ❌ Issue 3: File Paths and Relative URLs

### The Problem:
```php
require_once __DIR__ . '/../../config/db_connect.php';
```

### Why It Can Fail:
- Different directory structure on Hostinger
- Symbolic links behave differently
- Case-sensitive file systems

### Symptoms:
- "Failed to open stream: No such file or directory"
- Works sometimes, fails other times

### Solution:
Already using `__DIR__` which is correct. But verify file structure on Hostinger matches localhost.

---

## ❌ Issue 4: PHP Version Differences

### The Problem:
Your code uses:
```php
$stmt->bind_param("ddddssssiddi", ...);  // 12 parameters
```

### Why It Can Fail:
- Hostinger might run different PHP version
- Different MySQLi extension configuration
- Stricter error reporting

### Symptoms:
- "Warning: mysqli_stmt::bind_param(): Number of variables doesn't match"
- "Fatal error: Call to undefined function"

### Check Your Hostinger PHP Version:
Create `phpinfo.php` on Hostinger:
```php
<?php phpinfo(); ?>
```

Visit: `https://homehubai.shop/phpinfo.php`

Required: **PHP 7.4+**

---

## ❌ Issue 5: Database Connection Configuration

### The Problem:
If you didn't update `config/db_connect.php` with Hostinger credentials:

```php
// ❌ Wrong (localhost values)
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'homehub');

// ✅ Correct (Hostinger values)
define('DB_USERNAME', 'u123456789_admin');
define('DB_PASSWORD', 'YourPassword123');
define('DB_NAME', 'u123456789_homehub');
```

### Why AI Features Fail:
- All API calls fail with database connection errors
- Returns generic "Server error" message
- Works on pages that don't need database (static pages)

---

## 🔧 TROUBLESHOOTING STEPS

### Step 1: Check If Database Is Connected
Create `test_ai_connection.php` in your Hostinger public_html/:

```php
<?php
session_start();
$_SESSION['user_id'] = 1;  // Fake session for testing
$_SESSION['user_type'] = 'tenant';

require_once 'config/db_connect.php';

echo "<h2>AI Features Database Test</h2>";

try {
    $conn = getDbConnection();
    echo "✅ Database connected!<br>";
    
    // Test 1: Check tenants table
    $result = $conn->query("SELECT COUNT(*) as count FROM tenants");
    echo "✅ tenants table exists (" . $result->fetch_assoc()['count'] . " records)<br>";
    
    // Test 2: Check tenant_preferences table
    $result = $conn->query("SELECT COUNT(*) as count FROM tenant_preferences");
    echo "✅ tenant_preferences table exists (" . $result->fetch_assoc()['count'] . " records)<br>";
    
    // Test 3: Check similarity_scores table
    $result = $conn->query("SELECT COUNT(*) as count FROM similarity_scores");
    echo "✅ similarity_scores table exists (" . $result->fetch_assoc()['count'] . " records)<br>";
    
    // Test 4: Check browsing_history table
    $result = $conn->query("SELECT COUNT(*) as count FROM browsing_history");
    echo "✅ browsing_history table exists (" . $result->fetch_assoc()['count'] . " records)<br>";
    
    $conn->close();
    echo "<br><strong>All tables exist! AI features should work.</strong>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
```

Visit: `https://homehubai.shop/test_ai_connection.php`

### Step 2: Test AI API Directly
Visit: `https://homehubai.shop/api/ai/get-matches.php`

**Expected Responses:**

✅ **If working:**
```json
{
  "success": false,
  "message": "Unauthorized. This feature is only available for tenants."
}
```
(This is correct - means API is working but you're not logged in)

❌ **If broken:**
- Blank page
- "Parse error" or "Syntax error"
- "Table doesn't exist" error
- "Database connection failed"

### Step 3: Check Error Logs
In Hostinger File Manager, check:
- `public_html/error_log.txt`
- Or Hostinger Control Panel → Error Logs

Look for:
- "Table 'xxx' doesn't exist"
- "Connection failed"
- "Failed to open stream"

---

## ✅ SOLUTION CHECKLIST

### Before Troubleshooting, Verify:

- [ ] **Database imported on Hostinger**
  - Go to Hostinger phpMyAdmin
  - Check if these tables exist:
    - tenant_preferences
    - similarity_scores
    - tenants
    - landlords
    - browsing_history
    - property_images

- [ ] **Database credentials updated in `config/db_connect.php`**
  ```php
  define('DB_USERNAME', 'u######_your_username');
  define('DB_PASSWORD', 'YourActualPassword');
  define('DB_NAME', 'u######_homehub');
  ```

- [ ] **All API files uploaded**
  - `api/ai/get-matches.php`
  - `api/ai/get-recommendations.php`
  - `api/ai/get-analytics.php`

- [ ] **User can login on Hostinger**
  - Test login at: https://homehubai.shop/
  - Create tenant account
  - Try accessing AI features

---

## 🎯 MOST COMMON ISSUE

**90% of the time**, AI features fail on Hostinger because:

### ❌ The database wasn't fully imported
- You exported the database
- But some tables failed to import
- Or you imported an old backup without AI tables

### ✅ Solution:
1. Export fresh database from localhost (ALL tables)
2. Delete ALL tables in Hostinger database
3. Import fresh homehub.sql
4. Verify all tables exist in phpMyAdmin

---

## 📞 Quick Diagnostic

Run these SQL queries in Hostinger phpMyAdmin:

```sql
-- Check if AI tables exist
SHOW TABLES LIKE 'tenant%';
SHOW TABLES LIKE 'similarity%';
SHOW TABLES LIKE 'browsing%';

-- Check if tenant preferences exist
SELECT COUNT(*) FROM tenant_preferences;

-- Check if tenants exist
SELECT COUNT(*) FROM tenants;
```

If any of these fail with "Table doesn't exist" → **That's your problem!**

---

## 🚀 Quick Fix (If Tables Are Missing)

Re-export and re-import database:

1. **On localhost phpMyAdmin:**
   - Select 'homehub' database
   - Export → Custom → Select ALL tables
   - Include structure and data
   - Save as homehub_complete.sql

2. **On Hostinger phpMyAdmin:**
   - Select your database
   - Import → homehub_complete.sql
   - Wait for success

3. **Test again!**

---

## 📝 Note About Python AI Service

The `ai/` folder with Python code (`api_server.py`, `config.py`, etc.) is **NOT NEEDED** on Hostinger!

The PHP implementation is standalone and doesn't call the Python service. You can ignore that folder for production deployment.

---

Need help? Check `error_log.txt` on Hostinger for exact error messages!
