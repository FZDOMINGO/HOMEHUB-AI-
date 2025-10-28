<?php
/**
 * Comprehensive System Health Check
 * Tests all major systems that are failing on production
 */

// Detect environment
$isProduction = !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']) 
                && strpos($_SERVER['HTTP_HOST'], 'localhost:') !== 0;

echo "<h1>HomeHub System Health Check</h1>";
echo "<p><strong>Environment:</strong> " . ($isProduction ? 'PRODUCTION' : 'DEVELOPMENT') . "</p>";
echo "<p><strong>Host:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Environment Configuration
echo "<h2>1. Environment Configuration</h2>";
try {
    require_once __DIR__ . '/config/env.php';
    echo "<span style='color: green;'>✅ Environment config loaded</span><br>";
    echo "App URL: " . (defined('APP_URL') ? APP_URL : 'NOT DEFINED') . "<br>";
    echo "Debug Mode: " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'ON' : 'OFF') : 'NOT DEFINED') . "<br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Environment config failed: " . $e->getMessage() . "</span><br>";
}

// Test 2: Database Configuration  
echo "<h2>2. Database Configuration</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "<span style='color: green;'>✅ Database config loaded</span><br>";
    
    echo "Database Host: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "<br>";
    echo "Database Name: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
    echo "Database User: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "<br>";
    echo "Database Pass: " . (defined('DB_PASS') ? (DB_PASS === 'YOUR_PASSWORD_HERE' ? '❌ DEFAULT PLACEHOLDER' : '✅ SET') : 'NOT DEFINED') . "<br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Database config failed: " . $e->getMessage() . "</span><br>";
}

// Test 3: Database Connection
echo "<h2>3. Database Connection</h2>";
try {
    $conn = getDbConnection();
    if ($conn) {
        echo "<span style='color: green;'>✅ Database connection successful</span><br>";
        echo "MySQL Version: " . $conn->server_info . "<br>";
        
        // Test critical tables
        $criticalTables = [
            'users' => 'User authentication',
            'tenants' => 'Tenant profiles', 
            'landlords' => 'Landlord profiles',
            'properties' => 'Property listings',
            'tenant_preferences' => 'AI matching preferences',
            'similarity_scores' => 'AI match scores',
            'browsing_history' => 'History tracking'
        ];
        
        echo "<h3>Table Status:</h3>";
        foreach ($criticalTables as $table => $description) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                // Get row count
                $countResult = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
                echo "<span style='color: green;'>✅ $table</span> ($description) - $count records<br>";
            } else {
                echo "<span style='color: red;'>❌ $table</span> ($description) - MISSING<br>";
            }
        }
        
        $conn->close();
    } else {
        echo "<span style='color: red;'>❌ Database connection failed</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Database connection error: " . $e->getMessage() . "</span><br>";
}

// Test 4: Session System
echo "<h2>4. Session System</h2>";
try {
    initSession();
    echo "<span style='color: green;'>✅ Session system working</span><br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "User logged in: " . (isset($_SESSION['user_id']) ? 'YES (ID: ' . $_SESSION['user_id'] . ')' : 'NO') . "<br>";
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Session error: " . $e->getMessage() . "</span><br>";
}

// Test 5: Critical File Checks
echo "<h2>5. Critical Files</h2>";
$criticalFiles = [
    'api/ai/get-matches.php' => 'AI Matching API',
    'api/ai/get-recommendations.php' => 'AI Recommendations API', 
    'api/ai/get-analytics.php' => 'AI Analytics API',
    'api/get-history.php' => 'History API',
    'assets/js/ai-features.js' => 'AI Features JavaScript',
    'tenant/index.php' => 'Tenant Dashboard'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<span style='color: green;'>✅ $file</span> ($description)<br>";
    } else {
        echo "<span style='color: red;'>❌ $file</span> ($description) - MISSING<br>";
    }
}

echo "<hr>";
echo "<h2>Summary & Next Steps</h2>";

if ($isProduction && (!defined('DB_PASS') || DB_PASS === 'YOUR_PASSWORD_HERE')) {
    echo "<div style='background: #ffe6e6; border: 2px solid red; padding: 15px; margin: 10px 0;'>";
    echo "<h3 style='color: red;'>🚨 CRITICAL ISSUE IDENTIFIED</h3>";
    echo "<p><strong>Your production database credentials are not configured!</strong></p>";
    echo "<p>This is why you're getting HTTP 500 errors on:</p>";
    echo "<ul>";
    echo "<li>AI Features</li>";
    echo "<li>History System</li>";
    echo "<li>Tenant Dashboard</li>";
    echo "<li>All database-dependent features</li>";
    echo "</ul>";
    echo "<p><strong>To fix:</strong></p>";
    echo "<ol>";
    echo "<li>Get your real database credentials from Hostinger control panel</li>";
    echo "<li>Update config/env.php with the real values</li>";
    echo "<li>Import your database to Hostinger if not done already</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background: #e6ffe6; border: 2px solid green; padding: 15px;'>";
    echo "<h3 style='color: green;'>✅ Configuration looks good!</h3>";
    echo "<p>If you're still having issues, check the specific error details above.</p>";
    echo "</div>";
}

echo "<p><strong>Delete this file after testing for security!</strong></p>";
?>