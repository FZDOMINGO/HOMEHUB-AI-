<?php
// Test get-matches.php directly
session_start();

// Set session as tenant for testing
$_SESSION['user_id'] = 1; // Change to your tenant user ID
$_SESSION['user_type'] = 'tenant';

echo "<h1>Testing get-matches.php</h1>";
echo "<p>Session user_id: " . $_SESSION['user_id'] . "</p>";
echo "<p>Session user_type: " . $_SESSION['user_type'] . "</p>";

echo "<h2>Calling API...</h2>";
echo "<pre>";

// Include the API file
ob_start();
include 'api/ai/get-matches.php';
$output = ob_get_clean();

echo "API Output:\n";
echo htmlspecialchars($output);
echo "\n\nParsed JSON:\n";
$data = json_decode($output, true);
print_r($data);

echo "</pre>";
?>
