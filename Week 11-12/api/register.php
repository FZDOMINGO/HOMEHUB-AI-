<?php
// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data
    $fullName = filter_var($_POST['fullName'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $userType = $_POST['userType']; // 'tenant' or 'landlord'
    
    // Validate data
    if (empty($fullName) || empty($email) || empty($password) || empty($phone)) {
        echo json_encode(["status" => "error", "message" => "All fields are required"]);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        exit;
    }
    
    if (strlen($password) < 8) {
        echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters long"]);
        exit;
    }
    
    // Split full name into first and last name
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Get database connection
    $conn = getDbConnection();
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Email already exists"]);
            $checkStmt->close();
            $conn->close();
            exit;
        }
        $checkStmt->close();
        
        // Insert user into users table
        $userStmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?)");
        $userStmt->bind_param("sssss", $email, $hashedPassword, $firstName, $lastName, $phone);
        $userStmt->execute();
        $userId = $conn->insert_id;
        $userStmt->close();
        
        // Insert user into specific role table
        if ($userType === 'tenant') {
            $roleStmt = $conn->prepare("INSERT INTO tenants (user_id) VALUES (?)");
            $roleStmt->bind_param("i", $userId);
            $roleStmt->execute();
            $roleStmt->close();
        } else {
            $roleStmt = $conn->prepare("INSERT INTO landlords (user_id) VALUES (?)");
            $roleStmt->bind_param("i", $userId);
            $roleStmt->execute();
            $roleStmt->close();
        }
        
        // Commit the transaction
        $conn->commit();
        
        // Store user data in session
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        $_SESSION['user_email'] = $email;
        
        // Return success response
        echo json_encode([
            "status" => "success",
            "message" => "Registration successful",
            "redirect" => "../" . $userType . "/dashboard.php",
            "user" => [
                "name" => $_SESSION['user_name'],
                "email" => $_SESSION['user_email'],
                "type" => $_SESSION['user_type']
            ]
        ]);
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Registration failed: " . $e->getMessage()]);
    }
    
    $conn->close();
} else {
    // Not a POST request
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>