<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'tenant';

require 'api/ai/get-recommendations.php';
?>
