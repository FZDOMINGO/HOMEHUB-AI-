<?php
session_start();

// Simulate landlord session
$_SESSION['user_id'] = 2; // Assuming landlord user ID is 2
$_SESSION['user_type'] = 'landlord';

// Simulate POST request for approving visit ID 17
$_POST['id'] = 17;
$_POST['action'] = 'approve';
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "=== TESTING VISIT APPROVAL EMAIL ===\n\n";
echo "Simulating approval of Visit ID: 17\n";
echo "Expected tenant email: goodplayer981@gmail.com\n\n";

// Include the actual processing file
ob_start();
include 'api/process-visit-request.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n\n";

// Check the log
if (file_exists('tenant_notification_debug.log')) {
    echo "=== LAST 20 LINES FROM DEBUG LOG ===\n";
    $log = file_get_contents('tenant_notification_debug.log');
    $lines = explode("\n", $log);
    $lastLines = array_slice($lines, -20);
    echo implode("\n", $lastLines);
}
?>
