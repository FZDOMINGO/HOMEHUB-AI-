<?php
// HomeHub - Main Landing Page
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? $_SESSION['user_type'] : null;

// Redirect based on login status
if ($isLoggedIn) {
    // Redirect logged-in users to their dashboard
    if ($userType === 'tenant') {
        header('Location: tenant/dashboard.php');
    } elseif ($userType === 'landlord') {
        header('Location: landlord/dashboard.php');
    } else {
        header('Location: guest/index.html');
    }
} else {
    // Redirect guests to the guest landing page
    header('Location: guest/index.html');
}
exit;
?>
