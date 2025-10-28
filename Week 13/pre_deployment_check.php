<?php
/**
 * COMPREHENSIVE PRE-DEPLOYMENT CHECK
 * Run this before deploying to production
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Pre-Deployment Check</title>";
echo "<style>
body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
.section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.pass { border-left: 5px solid #4CAF50; }
.fail { border-left: 5px solid #f44336; }
.warn { border-left: 5px solid #ff9800; }
.info { border-left: 5px solid #2196F3; }
h1 { margin: 0; font-size: 32px; }
h2 { color: #333; margin-top: 0; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
h3 { color: #666; margin-top: 15px; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f9f9f9; font-weight: 600; }
.status { font-weight: bold; padding: 4px 8px; border-radius: 4px; }
.status-ok { background: #4CAF50; color: white; }
.status-error { background: #f44336; color: white; }
.status-warn { background: #ff9800; color: white; }
.count { font-size: 28px; font-weight: bold; color: #667eea; }
pre { background: #f9f9f9; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 13px; }
.progress { background: #e0e0e0; height: 30px; border-radius: 15px; overflow: hidden; margin: 10px 0; }
.progress-bar { height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); text-align: center; color: white; line-height: 30px; transition: width 0.3s; }
.checklist { list-style: none; padding: 0; }
.checklist li { padding: 8px; margin: 5px 0; background: #f9f9f9; border-radius: 4px; }
.checklist li:before { content: '✓ '; color: #4CAF50; font-weight: bold; margin-right: 5px; }
.checklist li.fail:before { content: '✗ '; color: #f44336; }
.summary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-top: 20px; }
</style></head><body>";

echo "<div class='header'>";
echo "<h1>🚀 HomeHub Pre-Deployment Check</h1>";
echo "<p style='margin:10px 0 0 0; opacity:0.9;'>Comprehensive system validation before production deployment</p>";
echo "<p style='margin:5px 0 0 0; opacity:0.8;'><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";

$errors = [];
$warnings = [];
$passed = [];
$totalChecks = 0;
$passedChecks = 0;

// ============================================================================
// 1. CORE FILES CHECK
// ============================================================================
echo "<div class='section info'>";
echo "<h2>📁 1. Core Files Structure</h2>";

$coreFiles = [
    'config/db_connect.php' => 'Database configuration',
    'includes/navbar.php' => 'Navigation component',
    'includes/email_functions.php' => 'Email system',
    'index.php' => 'Homepage',
    'properties.php' => 'Properties listing',
    'bookings.php' => 'Bookings management',
    'history.php' => 'Browsing history',
    'ai-features.php' => 'AI features page',
    '.htaccess' => 'Security configuration'
];

echo "<table>";
echo "<tr><th>File</th><th>Description</th><th>Status</th></tr>";
foreach ($coreFiles as $file => $desc) {
    $totalChecks++;
    if (file_exists($file)) {
        echo "<tr><td><code>$file</code></td><td>$desc</td><td><span class='status status-ok'>✓ EXISTS</span></td></tr>";
        $passedChecks++;
        $passed[] = "Core file: $file";
    } else {
        echo "<tr><td><code>$file</code></td><td>$desc</td><td><span class='status status-error'>✗ MISSING</span></td></tr>";
        $errors[] = "Missing core file: $file";
    }
}
echo "</table>";
echo "</div>";

// ============================================================================
// 2. API ENDPOINTS CHECK
// ============================================================================
echo "<div class='section info'>";
echo "<h2>🔌 2. API Endpoints</h2>";

$apiFiles = [
    'api/login.php' => 'User login',
    'api/register.php' => 'User registration',
    'api/logout.php' => 'User logout',
    'api/check_session.php' => 'Session validation',
    'api/get-notifications.php' => 'Notifications',
    'api/get-property-details.php' => 'Property details',
    'api/get-history.php' => 'Browsing history',
    'api/ai/get-recommendations.php' => 'AI recommendations',
    'api/ai/get-matches.php' => 'AI matching',
    'api/ai/get-analytics.php' => 'Landlord analytics'
];

echo "<table>";
echo "<tr><th>Endpoint</th><th>Purpose</th><th>Status</th></tr>";
foreach ($apiFiles as $file => $desc) {
    $totalChecks++;
    if (file_exists($file)) {
        echo "<tr><td><code>$file</code></td><td>$desc</td><td><span class='status status-ok'>✓ EXISTS</span></td></tr>";
        $passedChecks++;
        $passed[] = "API: $file";
    } else {
        echo "<tr><td><code>$file</code></td><td>$desc</td><td><span class='status status-error'>✗ MISSING</span></td></tr>";
        $errors[] = "Missing API endpoint: $file";
    }
}
echo "</table>";
echo "</div>";

// ============================================================================
// 3. USER FOLDERS CHECK
// ============================================================================
echo "<div class='section info'>";
echo "<h2>👥 3. User Interface Folders</h2>";

$userFolders = [
    'tenant' => ['dashboard.php', 'profile.php', 'saved.php', 'index.php'],
    'landlord' => ['dashboard.php', 'profile.php', 'index.php', 'manage-properties.php'],
    'admin' => ['login.php', 'dashboard.php', 'properties.php'],
    'login' => ['login.html', 'register.html']
];

foreach ($userFolders as $folder => $files) {
    echo "<h3>📂 $folder/</h3>";
    echo "<table>";
    echo "<tr><th>File</th><th>Status</th></tr>";
    foreach ($files as $file) {
        $totalChecks++;
        $path = "$folder/$file";
        if (file_exists($path)) {
            echo "<tr><td><code>$file</code></td><td><span class='status status-ok'>✓</span></td></tr>";
            $passedChecks++;
        } else {
            echo "<tr><td><code>$file</code></td><td><span class='status status-error'>✗</span></td></tr>";
            $errors[] = "Missing: $path";
        }
    }
    echo "</table>";
}
echo "</div>";

// ============================================================================
// 4. ASSETS CHECK
// ============================================================================
echo "<div class='section info'>";
echo "<h2>🎨 4. Assets (CSS, JS, Images)</h2>";

$assetFiles = [
    'assets/css/properties.css' => 'Properties styling',
    'assets/css/ai-features.css' => 'AI features styling',
    'assets/js/properties.js' => 'Properties functionality',
    'assets/js/ai-features.js' => 'AI features functionality',
    'assets/js/bookings.js' => 'Bookings functionality',
    'assets/js/history.js' => 'History functionality',
    'assets/homehublogo.jpg' => 'Logo image'
];

echo "<table>";
echo "<tr><th>Asset File</th><th>Purpose</th><th>Status</th></tr>";
foreach ($assetFiles as $file => $desc) {
    $totalChecks++;
    if (file_exists($file)) {
        $size = filesize($file);
        $sizeKB = round($size / 1024, 2);
        echo "<tr><td><code>$file</code></td><td>$desc</td><td><span class='status status-ok'>✓ {$sizeKB}KB</span></td></tr>";
        $passedChecks++;
    } else {
        echo "<tr><td><code>$file</code></td><td>$desc</td><td><span class='status status-error'>✗ MISSING</span></td></tr>";
        $warnings[] = "Missing asset: $file";
    }
}
echo "</table>";
echo "</div>";

// ============================================================================
// 5. DATABASE CONNECTION TEST
// ============================================================================
echo "<div class='section info'>";
echo "<h2>🗄️ 5. Database Connection</h2>";

$totalChecks++;
try {
    require_once 'config/db_connect.php';
    $conn = getDbConnection();
    
    echo "<div class='pass'>";
    echo "<h3>✓ Database Connected Successfully</h3>";
    
    // Get database info
    $result = $conn->query("SELECT DATABASE() as db_name, VERSION() as version");
    $dbInfo = $result->fetch_assoc();
    
    echo "<pre>";
    echo "Database Name: " . $dbInfo['db_name'] . "\n";
    echo "MySQL Version: " . $dbInfo['version'] . "\n";
    echo "Connection: Active\n";
    echo "</pre>";
    
    $passedChecks++;
    $passed[] = "Database connection successful";
    
} catch (Exception $e) {
    echo "<div class='fail'>";
    echo "<h3>✗ Database Connection Failed</h3>";
    echo "<p style='color:#f44336'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
    $errors[] = "Database connection failed: " . $e->getMessage();
}
echo "</div>";

// ============================================================================
// 6. REQUIRED TABLES CHECK
// ============================================================================
if (isset($conn)) {
    echo "<div class='section info'>";
    echo "<h2>📊 6. Database Tables</h2>";
    
    $requiredTables = [
        'users' => 'User accounts',
        'tenants' => 'Tenant profiles',
        'landlords' => 'Landlord profiles',
        'properties' => 'Property listings',
        'saved_properties' => 'Saved/favorited properties',
        'browsing_history' => 'User property views',
        'booking_visits' => 'Visit requests',
        'booking_reservations' => 'Reservation requests',
        'notifications' => 'User notifications',
        'recommendation_cache' => 'AI recommendations',
        'email_config' => 'Email SMTP settings'
    ];
    
    echo "<table>";
    echo "<tr><th>Table Name</th><th>Purpose</th><th>Status</th><th>Rows</th></tr>";
    
    foreach ($requiredTables as $table => $desc) {
        $totalChecks++;
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            $countResult = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $countResult->fetch_assoc()['count'];
            echo "<tr><td><strong>$table</strong></td><td>$desc</td><td><span class='status status-ok'>✓ EXISTS</span></td><td>$count</td></tr>";
            $passedChecks++;
        } else {
            echo "<tr><td><strong>$table</strong></td><td>$desc</td><td><span class='status status-error'>✗ MISSING</span></td><td>-</td></tr>";
            $errors[] = "Missing table: $table";
        }
    }
    echo "</table>";
    echo "</div>";
}

// ============================================================================
// 7. PHP VERSION & EXTENSIONS
// ============================================================================
echo "<div class='section info'>";
echo "<h2>🐘 7. PHP Environment</h2>";

echo "<table>";
echo "<tr><th>Check</th><th>Status</th><th>Value</th></tr>";

$totalChecks++;
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<tr><td>PHP Version</td><td><span class='status status-ok'>✓</span></td><td>$phpVersion</td></tr>";
    $passedChecks++;
} else {
    echo "<tr><td>PHP Version</td><td><span class='status status-error'>✗</span></td><td>$phpVersion (Need 7.4+)</td></tr>";
    $errors[] = "PHP version too old: $phpVersion";
}

$requiredExtensions = ['mysqli', 'json', 'session', 'filter'];
foreach ($requiredExtensions as $ext) {
    $totalChecks++;
    if (extension_loaded($ext)) {
        echo "<tr><td>$ext extension</td><td><span class='status status-ok'>✓</span></td><td>Loaded</td></tr>";
        $passedChecks++;
    } else {
        echo "<tr><td>$ext extension</td><td><span class='status status-error'>✗</span></td><td>Missing</td></tr>";
        $errors[] = "Missing PHP extension: $ext";
    }
}

echo "</table>";
echo "</div>";

// ============================================================================
// 8. SECURITY CHECK
// ============================================================================
echo "<div class='section info'>";
echo "<h2>🔒 8. Security Configuration</h2>";

echo "<table>";
echo "<tr><th>Check</th><th>Status</th><th>Details</th></tr>";

// Check .htaccess
$totalChecks++;
if (file_exists('.htaccess')) {
    echo "<tr><td>.htaccess file</td><td><span class='status status-ok'>✓</span></td><td>Present</td></tr>";
    $passedChecks++;
    $passed[] = "Security: .htaccess present";
} else {
    echo "<tr><td>.htaccess file</td><td><span class='status status-warn'>⚠</span></td><td>Missing (recommended)</td></tr>";
    $warnings[] = ".htaccess file missing - security headers not enforced";
}

// Check for localhost URLs in email functions
$totalChecks++;
if (file_exists('includes/email_functions.php')) {
    $content = file_get_contents('includes/email_functions.php');
    $localhostCount = substr_count(strtolower($content), 'localhost');
    if ($localhostCount == 0) {
        echo "<tr><td>Email URLs</td><td><span class='status status-ok'>✓</span></td><td>No localhost references</td></tr>";
        $passedChecks++;
        $passed[] = "Email URLs updated for production";
    } else {
        echo "<tr><td>Email URLs</td><td><span class='status status-error'>✗</span></td><td>$localhostCount localhost references found</td></tr>";
        $errors[] = "Email functions still have localhost URLs";
    }
}

// Check for test files
$testFiles = glob('test_*.php');
$testFiles = array_merge($testFiles, glob('check_*.php'));
$testFiles = array_merge($testFiles, glob('debug_*.php'));
$totalChecks++;
if (count($testFiles) > 0) {
    echo "<tr><td>Test Files</td><td><span class='status status-warn'>⚠</span></td><td>" . count($testFiles) . " test files found</td></tr>";
    $warnings[] = count($testFiles) . " test files should be deleted before production";
} else {
    echo "<tr><td>Test Files</td><td><span class='status status-ok'>✓</span></td><td>None found</td></tr>";
    $passedChecks++;
}

echo "</table>";
echo "</div>";

// ============================================================================
// 9. CRITICAL FUNCTIONALITY TESTS
// ============================================================================
if (isset($conn)) {
    echo "<div class='section info'>";
    echo "<h2>⚙️ 9. Functional Tests</h2>";
    
    echo "<table>";
    echo "<tr><th>Function</th><th>Status</th><th>Details</th></tr>";
    
    // Test 1: Can query users table
    $totalChecks++;
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $userCount = $result->fetch_assoc()['count'];
        echo "<tr><td>Query users table</td><td><span class='status status-ok'>✓</span></td><td>$userCount users</td></tr>";
        $passedChecks++;
    } catch (Exception $e) {
        echo "<tr><td>Query users table</td><td><span class='status status-error'>✗</span></td><td>Failed</td></tr>";
        $errors[] = "Cannot query users table";
    }
    
    // Test 2: Can query properties table
    $totalChecks++;
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status = 'available'");
        $propCount = $result->fetch_assoc()['count'];
        echo "<tr><td>Query properties</td><td><span class='status status-ok'>✓</span></td><td>$propCount available</td></tr>";
        $passedChecks++;
    } catch (Exception $e) {
        echo "<tr><td>Query properties</td><td><span class='status status-error'>✗</span></td><td>Failed</td></tr>";
        $errors[] = "Cannot query properties table";
    }
    
    // Test 3: Check recommendation_cache
    $totalChecks++;
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM recommendation_cache WHERE is_valid = 1");
        $recCount = $result->fetch_assoc()['count'];
        if ($recCount > 0) {
            echo "<tr><td>AI Recommendations</td><td><span class='status status-ok'>✓</span></td><td>$recCount cached</td></tr>";
            $passedChecks++;
        } else {
            echo "<tr><td>AI Recommendations</td><td><span class='status status-warn'>⚠</span></td><td>Cache empty</td></tr>";
            $warnings[] = "AI recommendation cache is empty - run generator";
        }
    } catch (Exception $e) {
        echo "<tr><td>AI Recommendations</td><td><span class='status status-warn'>⚠</span></td><td>Table check failed</td></tr>";
        $warnings[] = "recommendation_cache table issue";
    }
    
    echo "</table>";
    echo "</div>";
}

// ============================================================================
// 10. FILE PERMISSIONS (for uploads)
// ============================================================================
echo "<div class='section info'>";
echo "<h2>📤 10. File Permissions</h2>";

$uploadDirs = ['uploads', 'uploads/properties', 'logs'];

echo "<table>";
echo "<tr><th>Directory</th><th>Status</th><th>Writable</th></tr>";

foreach ($uploadDirs as $dir) {
    $totalChecks++;
    if (file_exists($dir)) {
        $writable = is_writable($dir);
        if ($writable) {
            echo "<tr><td><code>$dir/</code></td><td><span class='status status-ok'>✓ EXISTS</span></td><td>Yes</td></tr>";
            $passedChecks++;
        } else {
            echo "<tr><td><code>$dir/</code></td><td><span class='status status-warn'>⚠ EXISTS</span></td><td>No (set to 755 or 777)</td></tr>";
            $warnings[] = "$dir is not writable - change permissions";
        }
    } else {
        echo "<tr><td><code>$dir/</code></td><td><span class='status status-error'>✗ MISSING</span></td><td>-</td></tr>";
        $warnings[] = "$dir directory missing - create it";
    }
}

echo "</table>";
echo "</div>";

// ============================================================================
// FINAL SUMMARY
// ============================================================================
$percentage = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100) : 0;
$status = $percentage >= 90 ? 'READY' : ($percentage >= 70 ? 'WARNING' : 'NOT READY');
$statusColor = $percentage >= 90 ? '#4CAF50' : ($percentage >= 70 ? '#ff9800' : '#f44336');

echo "<div class='summary'>";
echo "<h2 style='color:white; border:none;'>📋 Deployment Readiness Summary</h2>";
echo "<div class='progress'>";
echo "<div class='progress-bar' style='width: {$percentage}%'>{$percentage}%</div>";
echo "</div>";

echo "<table style='color:white;'>";
echo "<tr><td><strong>Total Checks:</strong></td><td>{$totalChecks}</td></tr>";
echo "<tr><td><strong>Passed:</strong></td><td style='color:#4CAF50'>{$passedChecks}</td></tr>";
echo "<tr><td><strong>Failed:</strong></td><td style='color:#ffeb3b'>" . count($errors) . "</td></tr>";
echo "<tr><td><strong>Warnings:</strong></td><td style='color:#ffcc80'>" . count($warnings) . "</td></tr>";
echo "<tr><td><strong>Status:</strong></td><td style='font-size:24px; font-weight:bold;'>{$status}</td></tr>";
echo "</table>";
echo "</div>";

// ============================================================================
// ISSUES FOUND
// ============================================================================
if (count($errors) > 0 || count($warnings) > 0) {
    echo "<div class='section " . (count($errors) > 0 ? 'fail' : 'warn') . "'>";
    echo "<h2>⚠️ Issues Requiring Attention</h2>";
    
    if (count($errors) > 0) {
        echo "<h3 style='color:#f44336'>Critical Errors (Must Fix):</h3>";
        echo "<ul class='checklist'>";
        foreach ($errors as $error) {
            echo "<li class='fail'>$error</li>";
        }
        echo "</ul>";
    }
    
    if (count($warnings) > 0) {
        echo "<h3 style='color:#ff9800'>Warnings (Recommended to Fix):</h3>";
        echo "<ul class='checklist'>";
        foreach ($warnings as $warning) {
            echo "<li style='background:#fff3e0'>⚠ $warning</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
}

// ============================================================================
// DEPLOYMENT CHECKLIST
// ============================================================================
echo "<div class='section info'>";
echo "<h2>✅ Pre-Deployment Checklist</h2>";
echo "<ul class='checklist'>";
echo "<li>All core files present and accessible</li>";
echo "<li>Database connection working</li>";
echo "<li>All required tables exist</li>";
echo "<li>Email URLs updated to production domain</li>";
echo "<li>No localhost references in code</li>";
echo "<li>Test files removed or secured</li>";
echo "<li>.htaccess security configured</li>";
echo "<li>Upload directories writable</li>";
echo "<li>PHP version 7.4+ with required extensions</li>";
echo "<li>AI recommendation cache populated</li>";
echo "</ul>";
echo "</div>";

// ============================================================================
// NEXT STEPS
// ============================================================================
echo "<div class='section info'>";
echo "<h2>🚀 Next Steps for Deployment</h2>";

if ($percentage >= 90) {
    echo "<div class='pass'>";
    echo "<h3>✓ System is Ready for Deployment!</h3>";
    echo "<ol>";
    echo "<li><strong>Export Database:</strong> Go to phpMyAdmin → Export → Quick export → Go</li>";
    echo "<li><strong>Upload Files:</strong> Upload all files to homehubai.shop via FTP or hosting panel</li>";
    echo "<li><strong>Import Database:</strong> In production phpMyAdmin → Import → Choose SQL file</li>";
    echo "<li><strong>Update config/db_connect.php:</strong> Update with production database credentials</li>";
    echo "<li><strong>Test Everything:</strong> Visit https://homehubai.shop/production_test.php</li>";
    echo "<li><strong>Delete Test Files:</strong> Remove all test_*.php, check_*.php, debug_*.php files</li>";
    echo "<li><strong>Configure Email:</strong> Update SMTP settings in admin panel</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='fail'>";
    echo "<h3>⚠ Please Fix Issues Before Deploying</h3>";
    echo "<p>Address the critical errors and warnings listed above before proceeding with deployment.</p>";
    echo "</div>";
}

echo "</div>";

// Close connection
if (isset($conn)) {
    $conn->close();
}

echo "<hr>";
echo "<p style='text-align:center; color:#666;'>";
echo "<strong>Pre-Deployment Check Complete</strong><br>";
echo "Generated: " . date('Y-m-d H:i:s') . "<br>";
echo "<a href='index.php'>← Back to Home</a> | ";
echo "<a href='check_ai_database.php'>Check AI Database</a> | ";
echo "<a href='generate_ai_recommendations.php'>Generate Recommendations</a>";
echo "</p>";

echo "</body></html>";
?>
