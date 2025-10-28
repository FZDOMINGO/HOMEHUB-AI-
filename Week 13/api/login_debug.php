<?php
// Debug version of login
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error_log.txt');

// Start output buffering to catch any stray output
ob_start();

// Start session
session_start();

// Clear any output that might have been generated
ob_end_clean();

// Now start clean output buffering for our JSON response
ob_start();

// Log all POST data
file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - POST DATA:\n" . print_r($_POST, true) . "\n", FILE_APPEND);

// Set headers for JSON response
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

// Check if form is submitted with POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - Request method is POST\n", FILE_APPEND);
    
    // Get and sanitize form data
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $userType = $_POST['userType'] ?? ''; // 'tenant' or 'landlord'
    
    file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - Email: $email, Type: $userType\n", FILE_APPEND);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - Invalid email format\n", FILE_APPEND);
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        exit;
    }
    
    // Get database connection
    try {
        $conn = getDbConnection();
        file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - DB connection successful\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - DB connection failed: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(["status" => "error", "message" => "Database connection failed"]);
        exit;
    }
    
    // Prepare statement to get user details
    $table = ($userType === 'tenant') ? 'tenants' : 'landlords';
    
    file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - Checking $table table\n", FILE_APPEND);
    
    // First check if user exists in users table
    $stmt = $conn->prepare("SELECT u.id, u.password, u.first_name, u.last_name, u.email 
                           FROM users u 
                           JOIN $table t ON t.user_id = u.id 
                           WHERE u.email = ?");
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - Found " . $result->num_rows . " matching users\n", FILE_APPEND);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - User found: " . $user['first_name'] . " " . $user['last_name'] . "\n", FILE_APPEND);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - Password verified successfully\n", FILE_APPEND);
            
            // Update last login time
            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Store user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $userType;
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            
            file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - Session variables set\n", FILE_APPEND);
            
            // Return success response
            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "redirect" => "../" . $userType . "/dashboard.php",
                "user" => [
                    "name" => $_SESSION['user_name'],
                    "email" => $_SESSION['user_email'],
                    "type" => $_SESSION['user_type']
                ]
            ]);
            
            file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - SUCCESS: Login complete\n\n", FILE_APPEND);
        } else {
            // Password doesn't match
            file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - Password verification failed\n", FILE_APPEND);
            echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
        }
    } else {
        // User not found
        file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - User not found in $table table\n", FILE_APPEND);
        echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    // Not a POST request
    file_put_contents(__DIR__ . '/../login_debug.log', date('Y-m-d H:i:s') . " - Not a POST request\n", FILE_APPEND);
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
