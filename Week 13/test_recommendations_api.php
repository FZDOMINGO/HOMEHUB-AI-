<?php
// Simple test script to check recommendations API
session_start();

// Set up a test session (you can modify the user_id)
$_SESSION['user_id'] = 1; // Change this to your actual user ID
$_SESSION['user_type'] = 'tenant';
$_SESSION['user_name'] = 'Test User';

echo "<h1>Testing Recommendations API</h1>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>User Type: " . $_SESSION['user_type'] . "</p>";
echo "<hr>";

// Include and execute the recommendations API
ob_start();
require 'api/ai/get-recommendations.php';
$response = ob_get_clean();

echo "<h2>API Response:</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h2>Formatted Response:</h2>";
$data = json_decode($response, true);
echo "<pre>" . print_r($data, true) . "</pre>";

if (isset($data['success']) && $data['success']) {
    echo "<h3 style='color: green;'>✓ Success! Found " . $data['count'] . " recommendations</h3>";
    
    if ($data['count'] > 0) {
        echo "<h3>Recommended Properties:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Rent</th><th>Reason</th></tr>";
        foreach ($data['recommendations'] as $property) {
            echo "<tr>";
            echo "<td>" . $property['id'] . "</td>";
            echo "<td>" . htmlspecialchars($property['title']) . "</td>";
            echo "<td>" . $property['property_type'] . "</td>";
            echo "<td>₱" . number_format($property['rent_amount'], 2) . "</td>";
            echo "<td>" . $property['recommendation_reason'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<h3 style='color: red;'>✗ Error: " . ($data['message'] ?? 'Unknown error') . "</h3>";
}
?>
