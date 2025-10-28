<?php
// Test register_debug.php directly
error_reporting(0);

// Simulate a POST request
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = [
    'fullName' => 'Test User',
    'email' => 'test' . time() . '@example.com',
    'password' => 'password123',
    'phone' => '1234567890',
    'userType' => 'tenant'
];

// Capture output
ob_start();
include 'api/register_debug.php';
$output = ob_get_clean();

// Display what was actually returned
echo "<h2>Raw Output from register_debug.php:</h2>";
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars($output);
echo "</pre>";

echo "<h2>First 500 characters (to see what's breaking JSON):</h2>";
echo "<pre style='background: #ffe6e6; padding: 15px; border-radius: 5px;'>";
echo htmlspecialchars(substr($output, 0, 500));
echo "</pre>";

// Try to decode as JSON
echo "<h2>JSON Decode Test:</h2>";
$decoded = json_decode($output, true);
if ($decoded === null) {
    echo "<p style='color: red;'>❌ Failed to decode JSON. Error: " . json_last_error_msg() . "</p>";
} else {
    echo "<p style='color: green;'>✅ Successfully decoded JSON:</p>";
    echo "<pre>" . print_r($decoded, true) . "</pre>";
}
?>
