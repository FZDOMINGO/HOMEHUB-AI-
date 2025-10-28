<?php
/**
 * HOSTINGER DATABASE CONFIGURATION
 * 
 * ⚠️  IMPORTANT: Update these values BEFORE uploading to Hostinger!
 * 
 * WHERE TO FIND CREDENTIALS:
 * 1. Login to Hostinger control panel
 * 2. Go to: Websites → Manage → MySQL Databases
 * 3. Your database should look like: u123456789_homehub
 * 4. Username format: u123456789_admin (or similar)
 * 5. Password: The one you set when creating the database
 * 
 * EXAMPLE VALUES (Hostinger format):
 * - DB_SERVER: 'localhost' (usually this)
 * - DB_USERNAME: 'u123456789_admin'
 * - DB_PASSWORD: 'YourStrongPassword123!'
 * - DB_NAME: 'u123456789_homehub'
 */

// ⚠️  REPLACE THESE WITH YOUR ACTUAL HOSTINGER CREDENTIALS
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_hostinger_username_here');    // Example: u123456789_admin
define('DB_PASSWORD', 'your_hostinger_password_here');    // Example: StrongPass123!
define('DB_NAME', 'your_hostinger_database_here');        // Example: u123456789_homehub

/**
 * Create database connection
 * Enhanced for production environment
 * 
 * @return mysqli Database connection object
 */
function getDbConnection() {
    // Create connection
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error to file (don't show details to users)
        error_log("Database connection failed: " . $conn->connect_error);
        
        // Return generic error to user
        die(json_encode([
            "status" => "error", 
            "message" => "Database connection failed. Please try again later or contact support."
        ]));
    }
    
    // Set charset to UTF-8 for proper character support (emojis, special chars)
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * DEPLOYMENT CHECKLIST:
 * =====================
 * 
 * ✅ Step 1: Get credentials from Hostinger MySQL Databases
 * ✅ Step 2: Replace the define() values above with YOUR credentials
 * ✅ Step 3: Export database from localhost phpMyAdmin (homehub.sql)
 * ✅ Step 4: Upload ALL files to Hostinger public_html/
 * ✅ Step 5: Import homehub.sql in Hostinger phpMyAdmin
 * ✅ Step 6: Test website: https://homehubai.shop/
 * ✅ Step 7: Delete all test_*.php and check_*.php files
 * 
 * TROUBLESHOOTING:
 * ================
 * 
 * Error: "Access denied for user"
 * → Wrong username or password
 * → Check MySQL Databases in Hostinger panel
 * 
 * Error: "Unknown database"
 * → Wrong database name
 * → Or database not created yet in Hostinger
 * 
 * Error: "Can't connect to MySQL server"
 * → Wrong DB_SERVER value
 * → Try 'localhost' or ask Hostinger support
 * 
 * SECURITY NOTES:
 * ===============
 * - Never commit this file with real credentials to Git
 * - Keep backups of this configuration
 * - Use strong passwords (20+ characters)
 * - Enable 2FA on Hostinger account
 */
?>
