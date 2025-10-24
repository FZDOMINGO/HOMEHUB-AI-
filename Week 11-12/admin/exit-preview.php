<?php
/**
 * Exit Admin Preview Mode
 * Restores admin session and returns to admin dashboard
 */
session_start();

// Check if we're in preview mode
if (isset($_SESSION['admin_preview_mode']) && $_SESSION['admin_preview_mode']) {
    // Restore original admin session
    if (isset($_SESSION['admin_original_session'])) {
        $_SESSION['admin_logged_in'] = $_SESSION['admin_original_session']['admin_logged_in'];
        $_SESSION['admin_id'] = $_SESSION['admin_original_session']['admin_id'];
        $_SESSION['admin_username'] = $_SESSION['admin_original_session']['admin_username'];
        $_SESSION['admin_name'] = $_SESSION['admin_original_session']['admin_name'];
        $_SESSION['admin_role'] = $_SESSION['admin_original_session']['admin_role'];
    }
    
    // Clear preview session data
    unset($_SESSION['admin_preview_mode']);
    unset($_SESSION['admin_original_session']);
    unset($_SESSION['preview_mode']);
    
    // Clear user session data
    unset($_SESSION['user_id']);
    unset($_SESSION['user_type']);
    unset($_SESSION['user_name']);
    unset($_SESSION['first_name']);
    unset($_SESSION['last_name']);
    unset($_SESSION['email']);
}

// Redirect back to admin dashboard
header('Location: dashboard.php');
exit;
?>