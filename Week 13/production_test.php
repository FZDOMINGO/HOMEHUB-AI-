<?php
/**
 * Production Functionality Test
 * Test this on https://homehubai.shop/production_test.php
 */

session_start();
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>HomeHub Production Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.test { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
.pass { border-left: 4px solid #4CAF50; }
.fail { border-left: 4px solid #f44336; }
.warn { border-left: 4px solid #ff9800; }
h1 { color: #333; }
h3 { margin: 0 0 10px 0; color: #666; }
pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🧪 HomeHub Production Functionality Test</h1>";
echo "<p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Test URL:</strong> " . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";

// Test 1: Database Connection
echo "<div class='test'>";
echo "<h3>1. Database Connection</h3>";
try {
    require_once 'config/db_connect.php';
    $conn = getDbConnection();
    echo "<div class='pass'><strong>✓ PASS:</strong> Database connected successfully</div>";
    
    // Get database info
    $result = $conn->query("SELECT DATABASE() as db_name");
    $row = $result->fetch_assoc();
    echo "<pre>Database: " . $row['db_name'] . "\nConnection: Active</pre>";
} catch (Exception $e) {
    echo "<div class='fail'><strong>✗ FAIL:</strong> " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 2: Session System
echo "<div class='test'>";
echo "<h3>2. Session System</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div class='pass'><strong>✓ PASS:</strong> Session system working</div>";
    echo "<pre>";
    echo "Session ID: " . session_id() . "\n";
    echo "Session Status: Active\n";
    if (isset($_SESSION['user_id'])) {
        echo "User ID: " . $_SESSION['user_id'] . "\n";
        echo "User Type: " . $_SESSION['user_type'] . "\n";
        echo "User Name: " . $_SESSION['user_name'] . "\n";
    } else {
        echo "User: Not logged in (test by logging in first)\n";
    }
    echo "</pre>";
} else {
    echo "<div class='fail'><strong>✗ FAIL:</strong> Session not active</div>";
}
echo "</div>";

// Test 3: Tables Existence
echo "<div class='test'>";
echo "<h3>3. Required Database Tables</h3>";
$requiredTables = ['users', 'tenants', 'landlords', 'properties', 'saved_properties', 
                   'booking_visits', 'booking_reservations', 'browsing_history', 
                   'recommendation_cache', 'notifications'];
$missingTables = [];
foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missingTables[] = $table;
    }
}
if (empty($missingTables)) {
    echo "<div class='pass'><strong>✓ PASS:</strong> All required tables exist</div>";
} else {
    echo "<div class='fail'><strong>✗ FAIL:</strong> Missing tables: " . implode(', ', $missingTables) . "</div>";
}
echo "</div>";

// Test 4: File Permissions
echo "<div class='test'>";
echo "<h3>4. Critical Files</h3>";
$criticalFiles = [
    'config/db_connect.php' => 'Database configuration',
    'api/login.php' => 'Login API',
    'api/check_session.php' => 'Session checker',
    'api/get-notifications.php' => 'Notifications',
    'includes/navbar.php' => 'Navigation bar'
];
$fileIssues = 0;
foreach ($criticalFiles as $file => $desc) {
    if (!file_exists($file)) {
        echo "<div class='fail'><strong>✗ MISSING:</strong> $file ($desc)</div>";
        $fileIssues++;
    }
}
if ($fileIssues == 0) {
    echo "<div class='pass'><strong>✓ PASS:</strong> All critical files present</div>";
}
echo "</div>";

// Test 5: Sample Data
echo "<div class='test'>";
echo "<h3>5. Sample Data Check</h3>";
$userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$propertyCount = $conn->query("SELECT COUNT(*) as count FROM properties")->fetch_assoc()['count'];
echo "<pre>";
echo "Total Users: $userCount\n";
echo "Total Properties: $propertyCount\n";
echo "</pre>";
if ($userCount > 0 && $propertyCount > 0) {
    echo "<div class='pass'><strong>✓ PASS:</strong> Database has data</div>";
} else {
    echo "<div class='warn'><strong>⚠ WARNING:</strong> Database appears empty</div>";
}
echo "</div>";

// Test 6: API Endpoints
echo "<div class='test'>";
echo "<h3>6. API Endpoint Test</h3>";
echo "<p>Test these URLs manually:</p>";
echo "<ul>";
echo "<li><a href='api/check_session.php' target='_blank'>api/check_session.php</a> - Check session status</li>";
echo "<li><a href='api/get-available-properties.php' target='_blank'>api/get-available-properties.php</a> - Get properties</li>";
echo "<li><a href='api/get-notifications.php' target='_blank'>api/get-notifications.php</a> - Get notifications</li>";
echo "</ul>";
echo "</div>";

// Test 7: URLs Configuration
echo "<div class='test'>";
echo "<h3>7. URL Configuration Check</h3>";
$emailFunctionsFile = 'includes/email_functions.php';
if (file_exists($emailFunctionsFile)) {
    $content = file_get_contents($emailFunctionsFile);
    $localhostCount = substr_count($content, 'localhost');
    $homehubaiCount = substr_count($content, 'homehubai.shop');
    
    echo "<pre>";
    echo "Email Functions File:\n";
    echo "  'localhost' occurrences: $localhostCount\n";
    echo "  'homehubai.shop' occurrences: $homehubaiCount\n";
    echo "</pre>";
    
    if ($localhostCount > 0) {
        echo "<div class='warn'><strong>⚠ WARNING:</strong> Found localhost references in email templates</div>";
    } else {
        echo "<div class='pass'><strong>✓ PASS:</strong> No localhost references found</div>";
    }
}
echo "</div>";

// Test 8: PHP Configuration
echo "<div class='test'>";
echo "<h3>8. PHP Configuration</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Upload Max Size: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "</pre>";
echo "</div>";

$conn->close();

echo "<hr>";
echo "<h2>📋 Next Steps:</h2>";
echo "<ol>";
echo "<li>If all tests pass, try <a href='login/login.html'>logging in</a></li>";
echo "<li>Test tenant functionality: Register → Browse Properties → Save Property</li>";
echo "<li>Test landlord functionality: Login → Manage Properties → View Bookings</li>";
echo "<li>Check <a href='properties.php'>properties page</a></li>";
echo "<li>Delete this file after testing: <code>production_test.php</code></li>";
echo "</ol>";

echo "<p><strong>Report Issues:</strong> If something doesn't work, note which test failed above.</p>";
echo "</body></html>";
?>
