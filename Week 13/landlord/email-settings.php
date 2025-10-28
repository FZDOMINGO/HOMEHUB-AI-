<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';

// Initialize session
initSession();

// Check if user is logged in as landlord
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'landlord') {
    redirect('login/login.html');
    exit;
}

// Redirect to the shared email settings page
redirect('admin/email-settings.php');
exit;
?>
