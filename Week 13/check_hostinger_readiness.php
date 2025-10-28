<?php
/**
 * HOSTINGER DEPLOYMENT CHECKER
 * Run this before uploading to Hostinger to identify issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🚀 HOSTINGER DEPLOYMENT CHECKER\n";
echo "================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Check if database export exists
echo "1️⃣  Checking Database Export...\n";
if (file_exists('homehub.sql')) {
    $success[] = "✅ Database export found (homehub.sql)";
} else {
    $errors[] = "❌ Database export NOT found! Export from phpMyAdmin first.";
}

// 2. Check config/db_connect.php
echo "\n2️⃣  Checking Database Configuration...\n";
if (file_exists('config/db_connect.php')) {
    $dbConfig = file_get_contents('config/db_connect.php');
    
    if (strpos($dbConfig, "DB_USERNAME', 'root'") !== false) {
        $warnings[] = "⚠️  Still using 'root' username - UPDATE for Hostinger!";
    }
    
    if (strpos($dbConfig, "DB_PASSWORD', ''") !== false) {
        $warnings[] = "⚠️  Empty password detected - UPDATE for Hostinger!";
    }
    
    if (strpos($dbConfig, "DB_NAME', 'homehub'") !== false) {
        $warnings[] = "⚠️  Using 'homehub' database name - UPDATE to Hostinger format (u123456789_homehub)";
    }
    
    $success[] = "✅ Database config file exists";
} else {
    $errors[] = "❌ config/db_connect.php NOT found!";
}

// 3. Check critical files
echo "\n3️⃣  Checking Critical Files...\n";
$criticalFiles = [
    'index.php',
    'tenant/index.php',
    'landlord/index.php',
    'guest/index.html',
    'api/auth/login.php',
    'api/auth/register.php',
    'includes/email_functions.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $success[] = "✅ Found: $file";
    } else {
        $errors[] = "❌ Missing: $file";
    }
}

// 4. Check for localhost URLs
echo "\n4️⃣  Checking for Localhost URLs...\n";
$localhostCount = 0;
$phpFiles = glob('{*.php,api/*.php,api/*/*.php,tenant/*.php,landlord/*.php,admin/*.php,includes/*.php}', GLOB_BRACE);

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    if (preg_match('/localhost:3000|http:\/\/localhost(?![\w])/', $content)) {
        $warnings[] = "⚠️  Localhost URL found in: $file";
        $localhostCount++;
    }
}

if ($localhostCount === 0) {
    $success[] = "✅ No localhost URLs found";
}

// 5. Check for hardcoded paths
echo "\n5️⃣  Checking File Paths...\n";
$pathIssues = 0;
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    if (preg_match('/C:\\\\xampp|C:\/xampp/i', $content)) {
        $warnings[] = "⚠️  Hardcoded XAMPP path in: $file";
        $pathIssues++;
    }
}

if ($pathIssues === 0) {
    $success[] = "✅ No hardcoded paths found";
}

// 6. Check .htaccess
echo "\n6️⃣  Checking .htaccess...\n";
if (file_exists('.htaccess')) {
    $success[] = "✅ .htaccess file exists";
} else {
    $warnings[] = "⚠️  .htaccess file missing (optional but recommended)";
}

// 7. Check uploads folder
echo "\n7️⃣  Checking Uploads Folder...\n";
if (is_dir('uploads')) {
    if (is_writable('uploads')) {
        $success[] = "✅ Uploads folder exists and writable";
    } else {
        $warnings[] = "⚠️  Uploads folder not writable (set to 755 on Hostinger)";
    }
} else {
    $errors[] = "❌ Uploads folder missing!";
}

// 8. Check PHPMailer
echo "\n8️⃣  Checking PHPMailer...\n";
if (file_exists('vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    $success[] = "✅ PHPMailer installed";
} else {
    $errors[] = "❌ PHPMailer NOT found! Run: composer install";
}

// 9. Check session configuration
echo "\n9️⃣  Checking Session Handling...\n";
$indexContent = file_get_contents('index.php');
if (strpos($indexContent, 'session.gc_maxlifetime') !== false) {
    $success[] = "✅ Session configuration added for production";
} else {
    $warnings[] = "⚠️  Session configuration not optimized for Hostinger";
}

// 10. Check for test files
echo "\n🔟 Checking for Test Files...\n";
$testFiles = glob('test_*.php');
$checkFiles = glob('check_*.php');
$debugFiles = glob('debug_*.php');

$allTestFiles = array_merge($testFiles, $checkFiles, $debugFiles);

if (count($allTestFiles) > 0) {
    $warnings[] = "⚠️  Found " . count($allTestFiles) . " test/debug files - DELETE before production!";
    foreach (array_slice($allTestFiles, 0, 5) as $file) {
        $warnings[] = "   - $file";
    }
    if (count($allTestFiles) > 5) {
        $warnings[] = "   - ... and " . (count($allTestFiles) - 5) . " more";
    }
} else {
    $success[] = "✅ No test files found";
}

// RESULTS
echo "\n\n";
echo "╔════════════════════════════════════════════╗\n";
echo "║           DEPLOYMENT REPORT                ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

// Show errors
if (count($errors) > 0) {
    echo "🔴 ERRORS (Must Fix):\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($errors as $error) {
        echo "$error\n";
    }
    echo "\n";
}

// Show warnings
if (count($warnings) > 0) {
    echo "🟡 WARNINGS (Should Fix):\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($warnings as $warning) {
        echo "$warning\n";
    }
    echo "\n";
}

// Show success
if (count($success) > 0) {
    echo "🟢 SUCCESS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach (array_slice($success, 0, 10) as $item) {
        echo "$item\n";
    }
    if (count($success) > 10) {
        echo "... and " . (count($success) - 10) . " more checks passed\n";
    }
    echo "\n";
}

// Final verdict
echo "╔════════════════════════════════════════════╗\n";
if (count($errors) === 0 && count($warnings) <= 2) {
    echo "║  ✅ READY FOR DEPLOYMENT                  ║\n";
} elseif (count($errors) === 0) {
    echo "║  ⚠️  DEPLOYMENT POSSIBLE (with warnings)  ║\n";
} else {
    echo "║  ❌ NOT READY - Fix errors first!         ║\n";
}
echo "╚════════════════════════════════════════════╝\n\n";

// Next steps
echo "📋 NEXT STEPS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. Fix all ❌ ERRORS above\n";
echo "2. Export database: phpMyAdmin → Export → homehub.sql\n";
echo "3. Get Hostinger credentials from MySQL Databases panel\n";
echo "4. Update config/db_connect.php with Hostinger credentials\n";
echo "5. Delete all test_*.php, check_*.php, debug_*.php files\n";
echo "6. Upload all files to Hostinger public_html/\n";
echo "7. Import homehub.sql in Hostinger phpMyAdmin\n";
echo "8. Test: https://homehubai.shop/\n\n";

echo "📖 Read HOSTINGER_DEPLOYMENT_GUIDE.md for detailed instructions!\n\n";
?>
