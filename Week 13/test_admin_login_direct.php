<?php
require_once 'config/env.php';
require_once 'config/database.php';

echo "<h2>Testing Admin Login Directly</h2>";

try {
    $conn = getDbConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if admin user exists
    $stmt = $conn->prepare("SELECT id, username, email, password, full_name, role, is_active FROM admin_users WHERE username = ?");
    $username = 'admin';
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Admin user found</p>";
        echo "<pre>";
        print_r([
            'id' => $admin['id'],
            'username' => $admin['username'],
            'email' => $admin['email'],
            'full_name' => $admin['full_name'],
            'role' => $admin['role'],
            'is_active' => $admin['is_active']
        ]);
        echo "</pre>";
        
        // Test password verification
        $testPassword = 'admin123';
        if (password_verify($testPassword, $admin['password'])) {
            echo "<p style='color: green;'>✅ Password 'admin123' is correct</p>";
        } else {
            echo "<p style='color: red;'>❌ Password 'admin123' is incorrect</p>";
            echo "<p>Trying to create correct password hash...</p>";
            
            // Update with correct password
            $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
            $updateStmt->bind_param("ss", $hashedPassword, $username);
            
            if ($updateStmt->execute()) {
                echo "<p style='color: green;'>✅ Password updated successfully! Try logging in again.</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to update password: " . $conn->error . "</p>";
            }
            $updateStmt->close();
        }
        
        // Check if account is active
        if ($admin['is_active'] == 1) {
            echo "<p style='color: green;'>✅ Account is active</p>";
        } else {
            echo "<p style='color: red;'>❌ Account is inactive</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Admin user 'admin' not found</p>";
        echo "<p>Creating admin user...</p>";
        
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $email = 'admin@homehub.com';
        $fullName = 'System Administrator';
        $role = 'super_admin';
        
        $createStmt = $conn->prepare("INSERT INTO admin_users (username, email, password, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $createStmt->bind_param("sssss", $username, $email, $hashedPassword, $fullName, $role);
        
        if ($createStmt->execute()) {
            echo "<p style='color: green;'>✅ Admin user created! Username: admin, Password: admin123</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create admin user: " . $conn->error . "</p>";
        }
        $createStmt->close();
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
