<?php
// Database connection parameters
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'homehub');

// Create database connection
function getDbConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die(json_encode([
            "status" => "error", 
            "message" => "Database connection failed: " . $conn->connect_error
        ]));
    }
    
    return $conn;
}
?>