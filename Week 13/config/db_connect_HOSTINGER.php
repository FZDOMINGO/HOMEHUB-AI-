<?php
/**
 * DATABASE CONFIGURATION FOR HOSTINGER
 * 
 * REPLACE THE VALUES BELOW WITH YOUR HOSTINGER CREDENTIALS
 * Get these from: Hostinger Panel → MySQL Databases
 */

// 🔴 CHANGE THESE TO YOUR HOSTINGER DATABASE CREDENTIALS
define('DB_SERVER', 'localhost');                    // Usually 'localhost'
define('DB_USERNAME', 'YOUR_HOSTINGER_USERNAME');    // Example: u123456789_homehub
define('DB_PASSWORD', 'YOUR_HOSTINGER_PASSWORD');    // Your database password
define('DB_NAME', 'YOUR_HOSTINGER_DATABASE');        // Example: u123456789_homehub

/**
 * Create database connection with error handling
 */
function getDbConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        // Log error (don't expose details to users)
        error_log("Database connection failed: " . $conn->connect_error);
        
        die(json_encode([
            "status" => "error", 
            "message" => "Database connection failed: " . $conn->connect_error
        ]));
    }
    
    // Set UTF-8 encoding
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/*
 * ===================================================================
 * DEPLOYMENT INSTRUCTIONS:
 * ===================================================================
 * 
 * 1. Get your Hostinger database credentials:
 *    - Login to Hostinger
 *    - Go to: MySQL Databases
 *    - Copy your database name, username, and password
 * 
 * 2. Update lines 10-13 above with YOUR credentials
 * 
 * 3. Example of what Hostinger credentials look like:
 *    define('DB_USERNAME', 'u123456789_admin');
 *    define('DB_PASSWORD', 'MyPassword123!');
 *    define('DB_NAME', 'u123456789_homehub');
 * 
 * 4. Save this file as: config/db_connect.php
 * 
 * 5. Upload to Hostinger public_html/config/
 * 
 * ===================================================================
 */
?>
