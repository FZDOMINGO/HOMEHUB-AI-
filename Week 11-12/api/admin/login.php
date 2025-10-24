<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/db_connect.php';

// Log admin activity (with error handling)
function logAdminActivity($adminId, $action, $details = null, $ip = null, $userAgent = null) {
    try {
        $conn = getDbConnection();
        
        // Check if table exists first
        $tableCheck = $conn->query("SHOW TABLES LIKE 'admin_activity_log'");
        if ($tableCheck->num_rows == 0) {
            return; // Table doesn't exist yet, skip logging
        }
        
        $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, target_type, details, ip_address, user_agent) VALUES (?, ?, 'system', ?, ?, ?)");
        $detailsJson = $details ? json_encode($details) : null;
        $stmt->bind_param("issss", $adminId, $action, $detailsJson, $ip, $userAgent);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        // Silently fail for logging - don't break the main function
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);
        
        if (empty($username) || empty($password)) {
            echo json_encode(["status" => "error", "message" => "Username and password are required"]);
            exit;
        }
        
        $conn = getDbConnection();
        
        // Check if admin_users table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'admin_users'");
        if ($tableCheck->num_rows == 0) {
            echo json_encode(["status" => "error", "message" => "Admin system not set up. Please run the database setup first."]);
            exit;
        }
    
    // Check if admin exists and is active
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role, permissions, is_active, failed_login_attempts, locked_until FROM admin_users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        
        // Check if account is locked
        if ($admin['locked_until'] && new DateTime() < new DateTime($admin['locked_until'])) {
            echo json_encode(["status" => "error", "message" => "Account is temporarily locked. Please try again later."]);
            exit;
        }
        
        // Check if account is active
        if (!$admin['is_active']) {
            echo json_encode(["status" => "error", "message" => "Account is deactivated. Contact system administrator."]);
            exit;
        }
        
        // Verify password
        if (password_verify($password, $admin['password'])) {
            // Reset failed attempts and unlock account
            $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW(), failed_login_attempts = 0, locked_until = NULL WHERE id = ?");
            $updateStmt->bind_param("i", $admin['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_permissions'] = json_decode($admin['permissions'], true);
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('admin_remember_token', $token, time() + (86400 * 30), "/"); // 30 days
                
                // Store token in database (you might want to create a remember_tokens table)
                $tokenStmt = $conn->prepare("UPDATE admin_users SET remember_token = ? WHERE id = ?");
                $hashedToken = password_hash($token, PASSWORD_DEFAULT);
                $tokenStmt->bind_param("si", $hashedToken, $admin['id']);
                $tokenStmt->execute();
                $tokenStmt->close();
            }
            
            // Log successful login
            logAdminActivity($admin['id'], 'login_success', ['username' => $username], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            
            echo json_encode([
                "status" => "success", 
                "message" => "Login successful! Redirecting...",
                "user" => [
                    "id" => $admin['id'],
                    "username" => $admin['username'],
                    "name" => $admin['full_name'],
                    "role" => $admin['role']
                ]
            ]);
            
        } else {
            // Increment failed attempts
            $failedAttempts = $admin['failed_login_attempts'] + 1;
            $lockUntil = null;
            
            // Lock account after 5 failed attempts for 30 minutes
            if ($failedAttempts >= 5) {
                $lockUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            }
            
            $updateStmt = $conn->prepare("UPDATE admin_users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?");
            $updateStmt->bind_param("isi", $failedAttempts, $lockUntil, $admin['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Log failed login
            logAdminActivity($admin['id'], 'login_failed', ['username' => $username, 'attempts' => $failedAttempts], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
            
            $message = "Invalid credentials.";
            if ($failedAttempts >= 5) {
                $message .= " Account locked for 30 minutes due to multiple failed attempts.";
            } else {
                $remaining = 5 - $failedAttempts;
                $message .= " $remaining attempts remaining.";
            }
            
            echo json_encode(["status" => "error", "message" => $message]);
        }
    } else {
        // Log attempt with non-existent user
        logAdminActivity(null, 'login_failed', ['username' => $username, 'reason' => 'user_not_found'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        
        echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
    }
    
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>