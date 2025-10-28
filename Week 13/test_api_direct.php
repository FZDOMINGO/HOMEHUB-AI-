<?php
session_start();

// Set test session if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'tenant';
}

// Capture output
ob_start();

include 'api/get-history.php';

$output = ob_get_clean();

echo "<h2>API Output:</h2>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

echo "<h2>Check if it's valid JSON:</h2>";
$data = json_decode($output, true);
if ($data === null) {
    echo "❌ Error: " . json_last_error_msg();
    echo "<br><br>First 500 characters:<br>";
    echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
} else {
    echo "✅ Valid JSON";
    echo "<pre>" . print_r($data, true) . "</pre>";
}
?>
