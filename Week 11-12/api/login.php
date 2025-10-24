<?php
// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

// Check if form is submitted with POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $userType = $_POST['userType']; // 'tenant' or 'landlord'
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        exit;
    }
    
    // Get database connection
    $conn = getDbConnection();
    
    // Prepare statement to get user details
    $table = ($userType === 'tenant') ? 'tenants' : 'landlords';
    
    // First check if user exists in users table
    $stmt = $conn->prepare("SELECT u.id, u.password, u.first_name, u.last_name, u.email 
                           FROM users u 
                           JOIN $table t ON t.user_id = u.id 
                           WHERE u.email = ?");
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
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
        } else {
            // Password doesn't match
            echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
        }
    } else {
        // User not found
        echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
    }
    
    $stmt->close();
    $conn->close();
} else {
    // Not a POST request
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>