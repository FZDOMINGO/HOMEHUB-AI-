<?php
session_start();

// Simple test to simulate admin login for testing settings page
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_name'] = 'System Administrator';
$_SESSION['admin_role'] = 'super_admin';

echo "Admin session created. You can now access the settings page.";
echo "<br><a href='admin/settings.php'>Go to Settings</a>";
?>