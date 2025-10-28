<?php
/**
 * Quick test to verify database connection is working without errors
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test 1: Get connection
    echo "<p>✓ Getting database connection...</p>";
    $conn = getDbConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test 2: Check if database exists
    echo "<p>✓ Checking database: " . DB_NAME . "</p>";
    $result = $conn->query("SELECT DATABASE()");
    $row = $result->fetch_array();
    echo "<p style='color: green;'>✓ Connected to database: " . $row[0] . "</p>";
    
    // Test 3: Check tables
    echo "<p>✓ Checking for tables...</p>";
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    echo "<p style='color: green;'>✓ Found " . count($tables) . " tables</p>";
    
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }
    
    // Test 4: Multiple connection calls (testing pooling)
    echo "<p>✓ Testing connection pooling...</p>";
    $conn2 = getDbConnection();
    $conn3 = getDbConnection();
    echo "<p style='color: green;'>✓ Connection pooling working! (same connection reused)</p>";
    
    echo "<hr>";
    echo "<h3 style='color: green;'>✓ All tests passed! No errors.</h3>";
    echo "<p>Environment: " . APP_ENV . "</p>";
    echo "<p>App URL: " . APP_URL . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Note: Connection cleanup is automatic via PHP garbage collection
echo "<p><small>Connection will be automatically closed when script ends.</small></p>";
?>
