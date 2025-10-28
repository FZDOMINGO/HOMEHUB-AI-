<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<h2>Reset Admin Password</h2>";

// Get current admin
$result = $conn->query("SELECT id, username, email FROM admin_users WHERE username = 'admin'");
$admin = $result->fetch_assoc();

if ($admin) {
    echo "<p>Admin user found: <strong>" . $admin['username'] . "</strong> (" . $admin['email'] . ")</p>";
    
    // Reset password to 'admin123'
    $newPassword = 'admin123';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $admin['id']);
    
    if ($stmt->execute()) {
        echo "<div style='background: #d1fae5; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h3 style='color: #065f46;'>✅ Password Reset Successful!</h3>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>New Password:</strong> <code>admin123</code></p>";
        echo "<p><a href='admin/login.php' style='color: #8b5cf6; font-weight: bold;'>→ Go to Admin Login</a></p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>Failed to reset password</p>";
    }
} else {
    echo "<p style='color: red;'>Admin user not found</p>";
}

$conn->close();
?>
