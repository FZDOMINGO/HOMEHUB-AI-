<?php
/**
 * PRODUCTION DATABASE CONFIGURATION
 * 
 * IMPORTANT: Update these values with your hosting provider's database credentials
 * before uploading to production server.
 * 
 * Where to find these credentials:
 * - cPanel: MySQL Databases section
 * - Plesk: Databases section
 * - Other hosting: Contact support or check control panel
 */

// TODO: Replace these with your production database credentials
define('DB_SERVER', 'localhost');              // Usually 'localhost' or specific server name
define('DB_USERNAME', 'your_db_username');     // Get from hosting provider
define('DB_PASSWORD', 'your_db_password');     // Get from hosting provider
define('DB_NAME', 'your_db_name');             // Database name from hosting provider

/**
 * Create database connection
 * 
 * @return mysqli Database connection object
 */
function getDbConnection() {
    // Create connection
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        // In production, log errors instead of displaying them
        error_log("Database connection failed: " . $conn->connect_error);
        
        // Return generic error message to user
        die(json_encode([
            "status" => "error", 
            "message" => "Database connection failed. Please contact support if this problem persists."
        ]));
    }
    
    // Set charset to UTF-8 for proper character support
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * DEPLOYMENT INSTRUCTIONS:
 * 
 * 1. Get your database credentials from your hosting control panel
 * 2. Update the DB_SERVER, DB_USERNAME, DB_PASSWORD, and DB_NAME above
 * 3. Save this file as config/db_connect.php (replace the existing one)
 * 4. Export your local database from phpMyAdmin
 * 5. Import the SQL file to your production database
 * 6. Test the connection by visiting your site
 * 
 * SECURITY NOTE:
 * - Never use 'root' as username in production
 * - Always use a strong password
 * - Grant only necessary privileges to the database user
 */
?>
