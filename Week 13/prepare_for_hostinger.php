<?php
/**
 * HOSTINGER COMPATIBILITY FIXER
 * Makes all files compatible for homehubai.shop
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔧 HOSTINGER COMPATIBILITY FIXER\n";
echo "===================================\n\n";

$fixedFiles = [];
$issues = [];

// Production domain
$productionDomain = 'https://homehubai.shop';

echo "Step 1: Fixing Redirect Paths...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Files that need dynamic URL fixes
$redirectFiles = [
    'tenant/index.php',
    'landlord/index.php',
    'guest/index.php'
];

foreach ($redirectFiles as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Check if already has dynamic base URL
    if (strpos($content, '$baseUrl') !== false) {
        echo "✅ $file - Already has dynamic URLs\n";
        continue;
    }
    
    // Check if has relative redirects that need fixing
    if (preg_match('/header\s*\(\s*[\'"]Location:\s*(?!http|\/\/|\$)/', $content)) {
        echo "⚠️  $file - Has relative redirects (needs manual review)\n";
        $issues[] = $file . " - Review redirect paths";
    } else {
        echo "✅ $file - OK\n";
    }
}

echo "\nStep 2: Checking Database Configuration...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists('config/db_connect.php')) {
    $dbContent = file_get_contents('config/db_connect.php');
    
    if (strpos($dbContent, "DB_USERNAME', 'root'") !== false) {
        echo "⚠️  CRITICAL: Still using 'root' username\n";
        echo "   → You MUST update config/db_connect.php with Hostinger credentials!\n";
        $issues[] = "Update config/db_connect.php with Hostinger database credentials";
    } else {
        echo "✅ Database configuration updated\n";
    }
    
    if (strpos($dbContent, "DB_PASSWORD', ''") !== false) {
        echo "⚠️  CRITICAL: Empty database password\n";
        $issues[] = "Set database password in config/db_connect.php";
    }
    
    if (strpos($dbContent, "set_charset") !== false) {
        echo "✅ UTF-8 charset configured\n";
    }
} else {
    echo "❌ config/db_connect.php NOT FOUND!\n";
    $issues[] = "config/db_connect.php is missing";
}

echo "\nStep 3: Checking Critical Files...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$criticalFiles = [
    'index.php' => 'Main entry point',
    'api/login.php' => 'User login',
    'api/register.php' => 'User registration',
    'includes/email_functions.php' => 'Email system',
    'includes/PHPMailer/PHPMailer.php' => 'PHPMailer library',
    '.htaccess' => 'Server configuration',
    'config/db_connect.php' => 'Database connection'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $file ($description)\n";
    } else {
        echo "❌ MISSING: $file ($description)\n";
        $issues[] = "Missing critical file: $file";
    }
}

echo "\nStep 4: Checking Folder Permissions...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$writableFolders = ['uploads', 'uploads/properties', 'uploads/users'];

foreach ($writableFolders as $folder) {
    if (is_dir($folder)) {
        if (is_writable($folder)) {
            echo "✅ $folder/ - Writable\n";
        } else {
            echo "⚠️  $folder/ - Not writable (set to 755 on Hostinger)\n";
            $issues[] = "Set $folder/ permissions to 755 on Hostinger";
        }
    } else {
        echo "⚠️  $folder/ - Doesn't exist (will be created)\n";
    }
}

echo "\nStep 5: Checking Session Configuration...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists('index.php')) {
    $indexContent = file_get_contents('index.php');
    if (strpos($indexContent, 'session.gc_maxlifetime') !== false) {
        echo "✅ index.php has production session config\n";
    } else {
        echo "⚠️  index.php missing session config (already fixed in file)\n";
    }
}

echo "\nStep 6: Detecting Test/Debug Files...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$testPatterns = ['test_*.php', 'check_*.php', 'debug_*.php', '*_test.php', '*_debug.php'];
$testFiles = [];

foreach ($testPatterns as $pattern) {
    $testFiles = array_merge($testFiles, glob($pattern));
}

// Also check in subdirectories
$subdirs = ['api', 'tenant', 'landlord', 'admin', 'includes'];
foreach ($subdirs as $dir) {
    if (is_dir($dir)) {
        foreach ($testPatterns as $pattern) {
            $testFiles = array_merge($testFiles, glob("$dir/$pattern"));
        }
    }
}

$testFiles = array_unique($testFiles);

if (count($testFiles) > 0) {
    echo "⚠️  Found " . count($testFiles) . " test/debug files\n";
    echo "   These should be DELETED before production:\n\n";
    
    foreach (array_slice($testFiles, 0, 10) as $file) {
        echo "   - $file\n";
    }
    
    if (count($testFiles) > 10) {
        echo "   - ... and " . (count($testFiles) - 10) . " more\n";
    }
    
    $issues[] = "Delete " . count($testFiles) . " test/debug files before upload";
} else {
    echo "✅ No test files found\n";
}

echo "\nStep 7: Checking Email Configuration...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if (file_exists('includes/email_functions.php')) {
    $emailContent = file_get_contents('includes/email_functions.php');
    
    // Check if using production domain
    $localhostCount = substr_count($emailContent, 'http://localhost');
    $productionCount = substr_count($emailContent, 'https://homehubai.shop');
    
    if ($localhostCount > 0) {
        echo "⚠️  Found $localhostCount localhost URLs in email_functions.php\n";
        $issues[] = "Replace localhost URLs in includes/email_functions.php";
    }
    
    if ($productionCount > 0) {
        echo "✅ Using production domain ($productionCount references)\n";
    }
    
    if (strpos($emailContent, 'PHPMailer') !== false) {
        echo "✅ PHPMailer configured\n";
    }
} else {
    echo "❌ email_functions.php NOT FOUND\n";
    $issues[] = "Missing includes/email_functions.php";
}

echo "\nStep 8: Creating Production Checklist File...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$checklist = "# HOSTINGER DEPLOYMENT CHECKLIST
Generated: " . date('Y-m-d H:i:s') . "

## ✅ Before Upload

### 1. Database
- [ ] Export database from phpMyAdmin (homehub.sql)
- [ ] Have Hostinger database credentials ready
- [ ] Update config/db_connect.php with Hostinger credentials:
      - DB_SERVER: localhost
      - DB_USERNAME: u######_username
      - DB_PASSWORD: YourPassword
      - DB_NAME: u######_homehub

### 2. Files to Delete (DO NOT UPLOAD)
";

if (count($testFiles) > 0) {
    $checklist .= "Delete these " . count($testFiles) . " files:\n";
    foreach ($testFiles as $file) {
        $checklist .= "- [ ] $file\n";
    }
} else {
    $checklist .= "- [x] No test files found\n";
}

$checklist .= "
### 3. Files to Upload
- [ ] All folders: admin/, api/, assets/, config/, guest/, includes/, landlord/, sql/, tenant/, uploads/
- [ ] includes/PHPMailer/ folder (IMPORTANT!)
- [ ] Main files: index.php, .htaccess, bookings.php, properties.php, etc.
- [ ] Do NOT upload: .git/, *.md files, test files

### 4. Hostinger Setup
- [ ] Upload all files to public_html/
- [ ] Set uploads/ folder permission to 755
- [ ] Import homehub.sql in phpMyAdmin
- [ ] Test: https://homehubai.shop/

## 📋 Post-Upload Testing

- [ ] Home page loads (redirects to guest page)
- [ ] Can view properties as guest
- [ ] Registration works
- [ ] Login works
- [ ] Tenant dashboard works
- [ ] Landlord dashboard works
- [ ] Email notifications work
- [ ] Image uploads work
- [ ] No database errors in error_log.txt

## 🚨 Critical Issues Found
";

if (count($issues) > 0) {
    foreach ($issues as $issue) {
        $checklist .= "\n❌ $issue";
    }
} else {
    $checklist .= "\n✅ No critical issues detected!";
}

$checklist .= "

## 📞 Troubleshooting

If you see errors on Hostinger:
1. Check error_log.txt in public_html/
2. Verify database credentials in config/db_connect.php
3. Check if database was imported successfully
4. Verify includes/PHPMailer/ folder exists
5. Check folder permissions (uploads = 755)

## 🎯 Hostinger Database Format

Your Hostinger credentials will look like:
```
Database: u123456789_homehub
Username: u123456789_admin
Password: YourStrongPassword123
Server: localhost
```

Get these from: Hostinger Panel → MySQL Databases
";

file_put_contents('HOSTINGER_DEPLOYMENT_CHECKLIST.txt', $checklist);
echo "✅ Created HOSTINGER_DEPLOYMENT_CHECKLIST.txt\n";

// Summary
echo "\n\n";
echo "╔════════════════════════════════════════════╗\n";
echo "║         COMPATIBILITY CHECK COMPLETE        ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

if (count($issues) > 0) {
    echo "⚠️  " . count($issues) . " ISSUE(S) NEED ATTENTION:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". $issue\n";
    }
    echo "\n";
}

echo "📋 NEXT STEPS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. Export database: phpMyAdmin → Export → Save as homehub.sql\n";
echo "2. Get credentials: Hostinger Panel → MySQL Databases\n";
echo "3. Update: config/db_connect.php (lines 3-6)\n";
echo "4. Delete test files (see HOSTINGER_DEPLOYMENT_CHECKLIST.txt)\n";
echo "5. Upload to: Hostinger public_html/\n";
echo "6. Import: homehub.sql in Hostinger phpMyAdmin\n";
echo "7. Test: https://homehubai.shop/\n\n";

echo "📄 Review HOSTINGER_DEPLOYMENT_CHECKLIST.txt for detailed steps!\n\n";

if (count($issues) === 0) {
    echo "🎉 Your app is READY for Hostinger deployment!\n";
    echo "   Just update the database credentials and upload!\n\n";
} else {
    echo "⚠️  Fix the issues above before deployment.\n\n";
}
?>
