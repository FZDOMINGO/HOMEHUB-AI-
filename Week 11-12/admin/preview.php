<?php
/**
 * Admin Site Preview Helper - Guest Mode Only
 * Allows admins to preview the site as a guest without logging out
 */
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

// Get the target page
$targetPage = $_GET['page'] ?? '';

// Store admin session temporarily
$_SESSION['admin_preview_mode'] = true;
$_SESSION['admin_original_session'] = [
    'admin_logged_in' => $_SESSION['admin_logged_in'],
    'admin_id' => $_SESSION['admin_id'],
    'admin_username' => $_SESSION['admin_username'],
    'admin_name' => $_SESSION['admin_name'],
    'admin_role' => $_SESSION['admin_role']
];

// Clear user session for guest view
unset($_SESSION['user_id']);
unset($_SESSION['user_type']);
unset($_SESSION['user_name']);
unset($_SESSION['first_name']);
unset($_SESSION['last_name']);
unset($_SESSION['email']);

// Ensure no temporary user IDs that might cause database issues
$_SESSION['user_id'] = null;

// Determine target page
$redirectPage = '../';
switch ($targetPage) {
    case 'properties':
        $redirectPage = '../properties.php';
        break;
    case 'ai-features':
        $redirectPage = '../ai-features.php';
        break;
    case 'guest':
        $redirectPage = '../guest/index.php';
        break;
    default:
        $redirectPage = '../guest/index.php';
        break;
}

// Add preview indicator to session
$_SESSION['preview_mode'] = [
    'active' => true,
    'type' => 'guest',
    'admin_name' => $_SESSION['admin_original_session']['admin_name']
];

// Redirect to target page
header('Location: ' . $redirectPage);
exit;
?>