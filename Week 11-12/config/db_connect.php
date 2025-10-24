<?php
// Database connection parameters for Railway
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // Parse Railway DATABASE_URL
    $db_parts = parse_url($database_url);
    define('DB_SERVER', $db_parts['host']);
    define('DB_USERNAME', $db_parts['user']);
    define('DB_PASSWORD', $db_parts['pass']);
    define('DB_NAME', ltrim($db_parts['path'], '/'));
    define('DB_PORT', $db_parts['port'] ?? 3306);
} else {
    // Fallback to environment variables or local defaults
    define('DB_SERVER', getenv('DB_HOST') ?: 'localhost');
    define('DB_USERNAME', getenv('DB_USER') ?: 'root');
    define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'homehub');
    define('DB_PORT', getenv('DB_PORT') ?: 3306);
}

// Create database connection
function getDbConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
    
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