<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Test Page</h1>";
echo "<p>PHP is working!</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test database connection
echo "<h2>Database Connection Test:</h2>";
require_once 'config/db_connect.php';

try {
    $conn = getDbConnection();
    echo "<p style='color:green;'>✅ Database connected successfully!</p>";
    
    // Test a simple query
    $result = $conn->query("SELECT COUNT(*) as count FROM properties");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Properties count: " . $row['count'] . "</p>";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Environment Variables:</h2>";
echo "<pre>";
echo "DATABASE_URL exists: " . (getenv('DATABASE_URL') ? 'YES' : 'NO') . "\n";
echo "DB_HOST: " . (defined('DB_SERVER') ? DB_SERVER : 'NOT SET') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT SET') . "\n";
echo "</pre>";
?>