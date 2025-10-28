<?php
// Debug version of registration
error_reporting(0); // Completely disable error reporting to prevent any output
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
file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - POST DATA:\n" . print_r($_POST, true) . "\n", FILE_APPEND);

// Set headers for JSON response
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Request method is POST\n", FILE_APPEND);
    
    // Get and sanitize form data
    $fullName = htmlspecialchars(trim($_POST['fullName'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $userType = $_POST['userType'] ?? ''; // 'tenant' or 'landlord'
    
    file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Sanitized data: name=$fullName, email=$email, phone=$phone, type=$userType\n", FILE_APPEND);
    
    // Validate data
    if (empty($fullName) || empty($email) || empty($password) || empty($phone)) {
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Validation failed: Empty fields\n", FILE_APPEND);
        echo json_encode(["status" => "error", "message" => "All fields are required"]);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Validation failed: Invalid email\n", FILE_APPEND);
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        exit;
    }
    
    if (strlen($password) < 8) {
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Validation failed: Password too short\n", FILE_APPEND);
        echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters long"]);
        exit;
    }
    
    // Split full name into first and last name
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Getting DB connection\n", FILE_APPEND);
    
    // Get database connection
    try {
        $conn = getDbConnection();
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - DB connection successful\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - DB connection failed: " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(["status" => "error", "message" => "Database connection failed"]);
        exit;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Checking if email exists\n", FILE_APPEND);
        
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Email already exists\n", FILE_APPEND);
            echo json_encode(["status" => "error", "message" => "Email already exists"]);
            $checkStmt->close();
            $conn->close();
            exit;
        }
        
        $checkStmt->close();
        
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Inserting into users table\n", FILE_APPEND);
        
        // Insert into users table (without user_type column)
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $phone);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into users table: " . $stmt->error);
        }
        
        $userId = $conn->insert_id;
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - User inserted with ID: $userId\n", FILE_APPEND);
        
        $stmt->close();
        
        // Insert into tenant or landlord table based on user type
        if ($userType === 'tenant') {
            file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Inserting into tenants table\n", FILE_APPEND);
            $roleStmt = $conn->prepare("INSERT INTO tenants (user_id) VALUES (?)");
            $roleStmt->bind_param("i", $userId);
        } else {
            file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Inserting into landlords table\n", FILE_APPEND);
            $roleStmt = $conn->prepare("INSERT INTO landlords (user_id) VALUES (?)");
            $roleStmt->bind_param("i", $userId);
        }
        
        if (!$roleStmt->execute()) {
            throw new Exception("Failed to insert into $userType table: " . $roleStmt->error);
        }
        
        $roleStmt->close();
        
        // Commit transaction
        $conn->commit();
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Transaction committed\n", FILE_APPEND);
        
        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Session variables set\n", FILE_APPEND);
        
        // Close connection
        $conn->close();
        
        // Return success response
        echo json_encode([
            "status" => "success",
            "message" => "Registration successful",
            "redirect" => "../$userType/dashboard.php"
        ]);
        
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - SUCCESS: Registration complete\n\n", FILE_APPEND);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $conn->close();
        
        file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n\n", FILE_APPEND);
        
        echo json_encode([
            "status" => "error",
            "message" => "Registration failed: " . $e->getMessage()
        ]);
    }
} else {
    file_put_contents(__DIR__ . '/../registration_debug.log', date('Y-m-d H:i:s') . " - Not a POST request\n", FILE_APPEND);
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
