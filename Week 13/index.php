<?php
/**
 * HomeHub - Main Landing Page
 * Automatically redirects users based on login status
 */

// Load environment configuration
require_once __DIR__ . '/config/env.php';

// Initialize session
initSession();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? ($_SESSION['user_type'] ?? null) : null;

// Redirect based on login status
if ($isLoggedIn) {
    // Redirect logged-in users to their respective homepages
    if ($userType === 'tenant') {
        redirect('tenant/index.php');
    } elseif ($userType === 'landlord') {
        redirect('landlord/index.php');
    } else {
        redirect('guest/index.html');
    }
} else {
    // Redirect guests to the guest landing page
    redirect('guest/index.html');
}
?>
