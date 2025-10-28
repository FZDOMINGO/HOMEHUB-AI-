<?php
// Test script to validate the admin settings functionality
session_start();

// Simulate admin login
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_name'] = 'System Administrator';
$_SESSION['admin_role'] = 'super_admin';

echo "<h2>Admin Settings Page Test</h2>";

// Test 1: Check if we can include the settings page without errors
echo "<h3>Test 1: Include settings page</h3>";
try {
    ob_start();
    include 'admin/settings.php';
    $output = ob_get_clean();
    echo "✅ Settings page loads without PHP errors<br>";
    
    // Check if the form elements are present
    if (strpos($output, 'name="setting_site_name"') !== false) {
        echo "✅ Site name field present<br>";
    } else {
        echo "❌ Site name field missing<br>";
    }
    
    if (strpos($output, 'name="setting_contact_email"') !== false) {
        echo "✅ Contact email field present<br>";
    } else {
        echo "❌ Contact email field missing<br>";
    }
    
    if (strpos($output, 'changePasswordModal') !== false) {
        echo "✅ Password change modal present<br>";
    } else {
        echo "❌ Password change modal missing<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error loading settings page: " . $e->getMessage() . "<br>";
}

// Test 2: Check database connectivity
echo "<h3>Test 2: Database connectivity</h3>";
try {
    require_once 'config/db_connect.php';
    $conn = getDbConnection();
    echo "✅ Database connection successful<br>";
    
    // Check if settings table exists and has data
    $result = $conn->query("SELECT COUNT(*) as count FROM platform_settings");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "✅ Platform settings table accessible (rows: $count)<br>";
    } else {
        echo "❌ Cannot access platform_settings table<br>";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>All tests completed. You can now access the admin settings page:</strong></p>";
echo "<p><a href='admin/settings.php' target='_blank' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Open Admin Settings</a></p>";
?>