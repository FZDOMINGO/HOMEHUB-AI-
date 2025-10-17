<?php
// Start session
session_start();

// Set headers for JSON response
header('Content-Type: application/json');

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Return success response
echo json_encode([
    "status" => "success",
    "message" => "Logged out successfully",
    "redirect" => "../login/login.html"
]);
?>