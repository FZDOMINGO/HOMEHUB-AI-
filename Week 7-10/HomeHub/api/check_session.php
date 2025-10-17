<?php
// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    echo json_encode([
        "status" => "success",
        "loggedIn" => true,
        "user" => [
            "id" => $_SESSION['user_id'],
            "name" => $_SESSION['user_name'],
            "email" => $_SESSION['user_email'],
            "type" => $_SESSION['user_type']
        ]
    ]);
} else {
    echo json_encode([
        "status" => "success", 
        "loggedIn" => false
    ]);
}
?>