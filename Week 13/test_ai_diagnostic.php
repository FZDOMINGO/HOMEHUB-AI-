<?php
/**
 * AI FEATURES DIAGNOSTIC TEST
 * Upload this to Hostinger to diagnose AI features issues
 * Visit: https://homehubai.shop/test_ai_diagnostic.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>AI Features Diagnostic</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
h1 { color: #333; }
h2 { color: #666; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 AI Features Diagnostic Test</h1>";
echo "<p>Testing AI features compatibility on Hostinger...</p>";

// Test 1: PHP Version
echo "<div class='section'>";
echo "<h2>Test 1: PHP Version</h2>";
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "<p class='success'>✅ PHP Version: $phpVersion (Compatible)</p>";
} else {
    echo "<p class='error'>❌ PHP Version: $phpVersion (Requires 7.4+)</p>";
}
echo "</div>";

// Test 2: Required Extensions
echo "<div class='section'>";
echo "<h2>Test 2: Required PHP Extensions</h2>";
$extensions = ['mysqli', 'json', 'session'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✅ $ext extension loaded</p>";
    } else {
        echo "<p class='error'>❌ $ext extension NOT loaded</p>";
    }
}
echo "</div>";

// Test 3: Database Connection
echo "<div class='section'>";
echo "<h2>Test 3: Database Connection</h2>";
try {
    require_once 'config/db_connect.php';
    $conn = getDbConnection();
    echo "<p class='success'>✅ Database connection successful</p>";
    
    // Show database name (obfuscated)
    $result = $conn->query("SELECT DATABASE() as db_name");
    $row = $result->fetch_assoc();
    $dbName = substr($row['db_name'], 0, 10) . "***";
    echo "<p>Connected to: <strong>$dbName</strong></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='warning'>⚠️ Check config/db_connect.php has correct Hostinger credentials!</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Test 4: AI Required Tables
echo "<div class='section'>";
echo "<h2>Test 4: AI Required Tables</h2>";
$requiredTables = [
    'tenants' => 'Tenant profiles',
    'landlords' => 'Landlord profiles',
    'tenant_preferences' => 'Tenant search preferences',
    'similarity_scores' => 'Cached property matches',
    'browsing_history' => 'Property view history',
    'properties' => 'Property listings',
    'property_images' => 'Property photos'
];

$missingTables = [];
foreach ($requiredTables as $table => $description) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        // Count records
        $countResult = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $countResult->fetch_assoc()['count'];
        echo "<p class='success'>✅ $table ($description) - $count records</p>";
    } else {
        echo "<p class='error'>❌ $table ($description) - TABLE MISSING!</p>";
        $missingTables[] = $table;
    }
}

if (count($missingTables) > 0) {
    echo "<p class='error'><strong>❌ PROBLEM FOUND!</strong></p>";
    echo "<p class='warning'>Missing tables: " . implode(', ', $missingTables) . "</p>";
    echo "<p>These tables are required for AI features to work.</p>";
    echo "<p><strong>Solution:</strong> Re-import your complete database from localhost!</p>";
}
echo "</div>";

// Test 5: API Files
echo "<div class='section'>";
echo "<h2>Test 5: AI API Files</h2>";
$apiFiles = [
    'api/ai/get-matches.php' => 'AI Matching',
    'api/ai/get-recommendations.php' => 'Smart Recommendations',
    'api/ai/get-analytics.php' => 'Predictive Analytics'
];

foreach ($apiFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $file ($description)</p>";
    } else {
        echo "<p class='error'>❌ $file ($description) - FILE MISSING!</p>";
    }
}
echo "</div>";

// Test 6: Session Test
echo "<div class='section'>";
echo "<h2>Test 6: Session Functionality</h2>";
session_start();
$_SESSION['test'] = 'session_works';

if (isset($_SESSION['test']) && $_SESSION['test'] === 'session_works') {
    echo "<p class='success'>✅ Sessions working properly</p>";
} else {
    echo "<p class='error'>❌ Session storage not working</p>";
}

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    echo "<p class='success'>✅ User session active</p>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>User Type: " . $_SESSION['user_type'] . "</p>";
} else {
    echo "<p class='warning'>⚠️ No user logged in (this is OK for testing)</p>";
    echo "<p>To test fully, login as tenant and revisit this page.</p>";
}
echo "</div>";

// Test 7: Test AI Matching Query
echo "<div class='section'>";
echo "<h2>Test 7: AI Matching Query Test</h2>";

// Check if we have test data
$result = $conn->query("SELECT COUNT(*) as count FROM tenants WHERE id = 1");
$tenantCount = $result->fetch_assoc()['count'];

if ($tenantCount > 0) {
    echo "<p class='success'>✅ Test tenant exists</p>";
    
    // Try to run a simple matching query
    try {
        $testQuery = "
            SELECT p.*, 
                   (SELECT image_url FROM property_images 
                    WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM properties p
            WHERE p.status = 'available'
            LIMIT 3
        ";
        
        $result = $conn->query($testQuery);
        $propertyCount = $result->num_rows;
        
        echo "<p class='success'>✅ AI query executed successfully</p>";
        echo "<p>Found $propertyCount available properties for matching</p>";
        
        if ($propertyCount > 0) {
            echo "<p class='success'>✅ AI matching should work!</p>";
        } else {
            echo "<p class='warning'>⚠️ No properties available (add properties to test)</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Query error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='warning'>⚠️ No test tenant found (need to register as tenant)</p>";
}
echo "</div>";

// Test 8: Direct API Test
echo "<div class='section'>";
echo "<h2>Test 8: Direct API Access Test</h2>";
echo "<p>Try accessing the AI API endpoints directly:</p>";
echo "<ul>";
echo "<li><a href='api/ai/get-matches.php' target='_blank'>Test AI Matching API</a></li>";
echo "<li><a href='api/ai/get-recommendations.php' target='_blank'>Test Recommendations API</a></li>";
echo "<li><a href='api/ai/get-analytics.php' target='_blank'>Test Analytics API</a></li>";
echo "</ul>";
echo "<p>Expected: JSON response (may say 'Unauthorized' if not logged in - that's OK!)</p>";
echo "</div>";

// Summary
echo "<div class='section'>";
echo "<h2>📊 SUMMARY</h2>";

if (count($missingTables) === 0) {
    echo "<h3 class='success'>✅ AI FEATURES SHOULD BE WORKING!</h3>";
    echo "<p>All required tables exist. If AI features still don't work:</p>";
    echo "<ol>";
    echo "<li>Make sure you're logged in as a <strong>tenant</strong></li>";
    echo "<li>Set your preferences at: tenant/setup-preferences.php</li>";
    echo "<li>Check browser console (F12) for JavaScript errors</li>";
    echo "<li>Check error_log.txt for PHP errors</li>";
    echo "</ol>";
} else {
    echo "<h3 class='error'>❌ AI FEATURES WON'T WORK - MISSING TABLES</h3>";
    echo "<p><strong>Problem:</strong> " . count($missingTables) . " required tables are missing</p>";
    echo "<p><strong>Solution:</strong></p>";
    echo "<ol>";
    echo "<li>Go to localhost phpMyAdmin</li>";
    echo "<li>Export 'homehub' database (ALL tables, structure + data)</li>";
    echo "<li>Go to Hostinger phpMyAdmin</li>";
    echo "<li>DROP all existing tables (if any)</li>";
    echo "<li>Import the fresh homehub.sql file</li>";
    echo "<li>Refresh this page to verify</li>";
    echo "</ol>";
}

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Delete this test file after diagnosis (security)</li>";
echo "<li>Test actual AI features as logged-in tenant</li>";
echo "<li>Check error_log.txt if issues persist</li>";
echo "</ul>";
echo "</div>";

$conn->close();

echo "<hr><p style='color: #999; font-size: 12px;'>Diagnostic completed at " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>
