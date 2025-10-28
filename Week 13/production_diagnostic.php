<?php
/**
 * HOMEHUB PRODUCTION READINESS DIAGNOSTIC
 * Run this script to check all potential issues before deployment
 */

echo "=== HOMEHUB WEB APP DIAGNOSTIC ===\n\n";

$issues = [];
$warnings = [];
$passed = [];

// 1. CHECK DATABASE CONNECTION
echo "1. Checking Database Connection...\n";
require_once 'config/db_connect.php';
try {
    $conn = getDbConnection();
    $passed[] = "✓ Database connection successful";
    echo "   ✓ Connected to database: " . DB_NAME . "\n";
} catch (Exception $e) {
    $issues[] = "✗ Database connection failed: " . $e->getMessage();
    echo "   ✗ FAILED: " . $e->getMessage() . "\n";
}

// 2. CHECK HARDCODED LOCALHOST REFERENCES
echo "\n2. Checking for hardcoded localhost URLs...\n";
$files_with_localhost = [];
$critical_files = [
    'includes/email_functions.php',
    'admin/email-preview.php',
    'api/test-email.php'
];

foreach ($critical_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (preg_match('/http:\/\/localhost/i', $content)) {
            $files_with_localhost[] = $file;
        }
    }
}

if (!empty($files_with_localhost)) {
    $warnings[] = "⚠ Found localhost URLs in: " . implode(', ', $files_with_localhost);
    echo "   ⚠ WARNING: These files contain 'localhost' URLs:\n";
    foreach ($files_with_localhost as $file) {
        echo "     - $file\n";
    }
    echo "   ACTION REQUIRED: Replace with your production domain\n";
} else {
    $passed[] = "✓ No hardcoded localhost URLs found";
    echo "   ✓ No hardcoded localhost URLs\n";
}

// 3. CHECK FOR DEPRECATED PHP CODE
echo "\n3. Checking for deprecated PHP functions...\n";
$files_with_deprecated = [];
$deprecated_functions = ['FILTER_SANITIZE_STRING'];

$php_files = glob('{*.php,*/*.php,*/*/*.php}', GLOB_BRACE);
foreach ($php_files as $file) {
    if (strpos($file, 'test_') === 0 || strpos($file, 'check_') === 0) continue;
    
    $content = file_get_contents($file);
    foreach ($deprecated_functions as $func) {
        if (strpos($content, $func) !== false) {
            $files_with_deprecated[$file][] = $func;
        }
    }
}

if (!empty($files_with_deprecated)) {
    $warnings[] = "⚠ Found deprecated FILTER_SANITIZE_STRING in " . count($files_with_deprecated) . " files";
    echo "   ⚠ WARNING: " . count($files_with_deprecated) . " files use deprecated FILTER_SANITIZE_STRING\n";
    echo "   ACTION: These will cause warnings in PHP 8.1+\n";
    $count = 0;
    foreach ($files_with_deprecated as $file => $funcs) {
        echo "     - $file\n";
        if (++$count >= 5) {
            echo "     ... and " . (count($files_with_deprecated) - 5) . " more files\n";
            break;
        }
    }
} else {
    $passed[] = "✓ No deprecated PHP functions found";
    echo "   ✓ No deprecated functions\n";
}

// 4. CHECK EMAIL CONFIGURATION
echo "\n4. Checking Email Configuration...\n";
$stmt = $conn->prepare("SELECT * FROM email_config LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$emailConfig = $result->fetch_assoc();

if ($emailConfig) {
    if ($emailConfig['use_smtp'] == 1) {
        $passed[] = "✓ SMTP email configured";
        echo "   ✓ SMTP enabled\n";
        echo "     Host: " . $emailConfig['smtp_host'] . "\n";
        echo "     Port: " . $emailConfig['smtp_port'] . "\n";
        echo "     From: " . $emailConfig['from_email'] . "\n";
        
        if (empty($emailConfig['smtp_password'])) {
            $warnings[] = "⚠ SMTP password not set";
            echo "   ⚠ WARNING: SMTP password is empty\n";
        }
    } else {
        $warnings[] = "⚠ SMTP not enabled - using PHP mail()";
        echo "   ⚠ WARNING: SMTP not enabled, using PHP mail() function\n";
    }
} else {
    $issues[] = "✗ No email configuration found";
    echo "   ✗ FAILED: No email configuration in database\n";
}

// 5. CHECK CRITICAL TABLES
echo "\n5. Checking Database Tables...\n";
$required_tables = [
    'users', 'landlords', 'tenants', 'properties', 'property_images',
    'saved_properties', 'browsing_history', 'notifications', 
    'booking_visits', 'property_reservations', 'email_config'
];

$missing_tables = [];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
    }
}

if (empty($missing_tables)) {
    $passed[] = "✓ All critical tables exist";
    echo "   ✓ All " . count($required_tables) . " critical tables exist\n";
} else {
    $issues[] = "✗ Missing tables: " . implode(', ', $missing_tables);
    echo "   ✗ FAILED: Missing tables: " . implode(', ', $missing_tables) . "\n";
}

// 6. CHECK FILE PERMISSIONS
echo "\n6. Checking File Permissions...\n";
$writable_dirs = ['uploads', 'logs'];
$permission_issues = [];

foreach ($writable_dirs as $dir) {
    if (!file_exists($dir)) {
        $warnings[] = "⚠ Directory '$dir' doesn't exist";
        echo "   ⚠ WARNING: Directory '$dir' doesn't exist\n";
    } elseif (!is_writable($dir)) {
        $permission_issues[] = $dir;
    }
}

if (empty($permission_issues)) {
    $passed[] = "✓ Directory permissions OK";
    echo "   ✓ Writable directories are accessible\n";
} else {
    $warnings[] = "⚠ Some directories not writable: " . implode(', ', $permission_issues);
    echo "   ⚠ WARNING: Not writable: " . implode(', ', $permission_issues) . "\n";
}

// 7. CHECK SESSION CONFIGURATION
echo "\n7. Checking Session Configuration...\n";
if (session_status() === PHP_SESSION_ACTIVE) {
    $passed[] = "✓ PHP sessions enabled";
    echo "   ✓ PHP sessions are working\n";
} else {
    $issues[] = "✗ PHP sessions not working";
    echo "   ✗ FAILED: PHP sessions not working\n";
}

// 8. CHECK PHPMAILER
echo "\n8. Checking PHPMailer Installation...\n";
if (file_exists('includes/PHPMailer/PHPMailer.php')) {
    $passed[] = "✓ PHPMailer installed";
    echo "   ✓ PHPMailer found\n";
} else {
    $issues[] = "✗ PHPMailer not found";
    echo "   ✗ FAILED: PHPMailer not installed\n";
}

// 9. CHECK FOR TEST FILES IN PRODUCTION
echo "\n9. Checking for test files...\n";
$test_files = glob('test_*.php');
$test_files = array_merge($test_files, glob('check_*.php'));
$test_files = array_merge($test_files, glob('debug_*.php'));

if (count($test_files) > 0) {
    $warnings[] = "⚠ Found " . count($test_files) . " test/debug files";
    echo "   ⚠ WARNING: " . count($test_files) . " test/debug files found\n";
    echo "   RECOMMENDATION: Remove or restrict access to test files in production\n";
    echo "   Files: " . implode(', ', array_slice($test_files, 0, 5));
    if (count($test_files) > 5) echo " ... +" . (count($test_files) - 5) . " more";
    echo "\n";
} else {
    $passed[] = "✓ No test files found";
    echo "   ✓ No test files\n";
}

// 10. CHECK ERROR LOGGING
echo "\n10. Checking Error Logging Configuration...\n";
$display_errors = ini_get('display_errors');
$log_errors = ini_get('log_errors');

if ($display_errors == '1') {
    $warnings[] = "⚠ display_errors is ON - should be OFF in production";
    echo "   ⚠ WARNING: display_errors is ON (should be OFF in production)\n";
} else {
    $passed[] = "✓ display_errors is OFF";
    echo "   ✓ display_errors is OFF\n";
}

if ($log_errors == '1') {
    $passed[] = "✓ error logging enabled";
    echo "   ✓ Error logging is enabled\n";
} else {
    $warnings[] = "⚠ error logging is OFF";
    echo "   ⚠ WARNING: Error logging is OFF\n";
}

// 11. CHECK DATABASE CREDENTIALS
echo "\n11. Checking Database Credentials...\n";
if (DB_USERNAME === 'root' && DB_PASSWORD === '') {
    $warnings[] = "⚠ Using default MySQL root credentials";
    echo "   ⚠ WARNING: Using default MySQL credentials (root with no password)\n";
    echo "   SECURITY RISK: Change database credentials in production\n";
} else {
    $passed[] = "✓ Custom database credentials";
    echo "   ✓ Using custom database credentials\n";
}

// SUMMARY
echo "\n" . str_repeat("=", 60) . "\n";
echo "DIAGNOSTIC SUMMARY\n";
echo str_repeat("=", 60) . "\n\n";

echo "✓ PASSED: " . count($passed) . " checks\n";
foreach ($passed as $p) {
    echo "  $p\n";
}

echo "\n⚠ WARNINGS: " . count($warnings) . " issues\n";
foreach ($warnings as $w) {
    echo "  $w\n";
}

echo "\n✗ CRITICAL ISSUES: " . count($issues) . " problems\n";
foreach ($issues as $i) {
    echo "  $i\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "PRODUCTION READINESS: ";
if (count($issues) > 0) {
    echo "NOT READY ✗\n";
    echo "Fix critical issues before deployment!\n";
} elseif (count($warnings) > 3) {
    echo "NEEDS ATTENTION ⚠\n";
    echo "Address warnings for optimal production deployment.\n";
} else {
    echo "READY ✓\n";
    echo "App is ready for production with minor warnings.\n";
}
echo str_repeat("=", 60) . "\n";

?>
