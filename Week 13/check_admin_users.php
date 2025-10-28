<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

// Check if admin_users table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'admin_users'");
if ($tableCheck->num_rows > 0) {
    echo "<h3>Admin Users Table Exists ✅</h3>";
    
    // Check if there are any admins
    $result = $conn->query("SELECT id, username, email, full_name, role FROM admin_users");
    if ($result->num_rows > 0) {
        echo "<p>Found " . $result->num_rows . " admin user(s):</p>";
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li><strong>" . htmlspecialchars($row['username']) . "</strong> - " . 
                 htmlspecialchars($row['email']) . " (" . htmlspecialchars($row['role']) . ")</li>";
        }
        echo "</ul>";
        echo "<p><a href='admin/login.php'>Go to Admin Login</a></p>";
    } else {
        echo "<p style='color: red;'>❌ No admin users found!</p>";
        echo "<p>Run: <a href='setup_admin.bat'>setup_admin.bat</a> to create an admin user</p>";
    }
} else {
    echo "<h3 style='color: red;'>❌ Admin Users Table Does Not Exist</h3>";
    echo "<p>Run: <a href='setup_admin.bat'>setup_admin.bat</a> to set up admin system</p>";
}

$conn->close();
?>
