<?php
/**
 * HomeHub Environment Configuration
 * 
 * Automatically detects environment (localhost vs production)
 * and loads appropriate configuration
 * 
 * Usage:
 *   require_once __DIR__ . '/config/env.php';
 *   echo APP_URL;  // Automatically uses correct URL
 */

// Detect environment automatically
function detectEnvironment() {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isLocalhost = in_array($host, ['localhost', '127.0.0.1', '::1']) 
                   || strpos($host, 'localhost:') === 0;
    
    return $isLocalhost ? 'development' : 'production';
}

// Set environment
define('APP_ENV', detectEnvironment());
define('IS_PRODUCTION', APP_ENV === 'production');
define('IS_DEVELOPMENT', APP_ENV === 'development');

// Environment-specific configurations
if (IS_DEVELOPMENT) {
    // LOCALHOST CONFIGURATION
    define('APP_URL', 'http://localhost/HomeHub');
    define('APP_PATH', 'C:/xampp/htdocs/HomeHub'); // Not used in web context
    
    // Database - Localhost
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'homehub');
    
    // Debug settings
    define('DEBUG_MODE', true);
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    
    // Email test mode
    define('EMAIL_TEST_MODE', false); // Set true to prevent sending real emails
    
} else {
    // PRODUCTION CONFIGURATION (HOSTINGER)
    define('APP_URL', 'https://homehubai.shop');
    define('APP_PATH', 'Files/htdocs/homehubai.shop');
    
    // Database - Hostinger
    // ⚠️ UPDATE THESE WITH YOUR ACTUAL HOSTINGER CREDENTIALS
    define('DB_HOST', 'localhost');
    define('DB_USER', 'homehub');  // UPDATE THIS
    define('DB_PASS', 'Studentmapua2025#');   // UPDATE THIS
    define('DB_NAME', 'homehub');  // UPDATE THIS
    
    // Debug settings - OFF in production
    define('DEBUG_MODE', false);
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../error_log.txt');
    
    // Email - Production mode
    define('EMAIL_TEST_MODE', false);
}

// Common settings for all environments
define('APP_NAME', 'HomeHub AI');
define('APP_VERSION', '2.0');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'HOMEHUB_SESSION');

// File upload settings
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']);

// Pagination
define('ITEMS_PER_PAGE', 12);

// Helper function to get base URL
function getBaseUrl() {
    return APP_URL;
}

// Helper function to get asset URL
function asset($path) {
    $path = ltrim($path, '/');
    return APP_URL . '/' . $path;
}

// Helper function to get API URL
function apiUrl($endpoint) {
    $endpoint = ltrim($endpoint, '/');
    return APP_URL . '/api/' . $endpoint;
}

// Helper function to redirect
function redirect($path) {
    $path = ltrim($path, '/');
    header('Location: ' . APP_URL . '/' . $path);
    exit;
}

// Initialize session with proper settings
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        
        if (IS_PRODUCTION) {
            ini_set('session.cookie_secure', 1); // HTTPS only
        }
        
        session_name(SESSION_NAME);
        session_start();
    }
}

// Log function for debugging
function logDebug($message, $data = null) {
    if (DEBUG_MODE) {
        $log = date('Y-m-d H:i:s') . ' - ' . $message;
        if ($data !== null) {
            $log .= ' - ' . print_r($data, true);
        }
        error_log($log);
    }
}

// Success! Environment loaded
logDebug('Environment loaded', [
    'env' => APP_ENV,
    'url' => APP_URL,
    'debug' => DEBUG_MODE
]);
