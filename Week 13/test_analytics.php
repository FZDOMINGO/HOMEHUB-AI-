<?php
session_start();
$_SESSION['user_id'] = 2;  // Landlord user
$_SESSION['user_type'] = 'landlord';

require 'api/ai/get-analytics.php';
?>
