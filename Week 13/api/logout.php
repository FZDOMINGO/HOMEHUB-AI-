<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';

// Initialize session
initSession();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page using environment-aware redirect
redirect('login/login.html');
exit();