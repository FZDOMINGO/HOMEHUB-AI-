<?php
// Simple test script to check analytics API
session_start();

// Set up a test session for landlord
$_SESSION['user_id'] = 2; // Change this to your actual landlord user ID
$_SESSION['user_type'] = 'landlord';
$_SESSION['user_name'] = 'Test Landlord';

echo "<h1>Testing Analytics API</h1>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>User Type: " . $_SESSION['user_type'] . "</p>";
echo "<hr>";

// Include and execute the analytics API
ob_start();
require 'api/ai/get-analytics.php';
$response = ob_get_clean();

echo "<h2>API Response:</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h2>Formatted Response:</h2>";
$data = json_decode($response, true);
echo "<pre>" . print_r($data, true) . "</pre>";

if (isset($data['success']) && $data['success']) {
    echo "<h3 style='color: green;'>✓ Success! Analytics loaded</h3>";
    
    echo "<h3>Property Stats:</h3>";
    echo "<ul>";
    echo "<li>Total Properties: " . $data['properties']['total_properties'] . "</li>";
    echo "<li>Available: " . $data['properties']['available_properties'] . "</li>";
    echo "<li>Occupied: " . $data['properties']['occupied_properties'] . "</li>";
    echo "</ul>";
    
    if (isset($data['top_viewed_properties']) && count($data['top_viewed_properties']) > 0) {
        echo "<h3>Top Viewed Properties:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Rent</th><th>Views</th><th>Unique Visitors</th></tr>";
        foreach ($data['top_viewed_properties'] as $property) {
            echo "<tr>";
            echo "<td>" . $property['id'] . "</td>";
            echo "<td>" . htmlspecialchars($property['title']) . "</td>";
            echo "<td>₱" . number_format($property['rent_amount'], 2) . "</td>";
            echo "<td>" . $property['total_views'] . "</td>";
            echo "<td>" . $property['unique_visitors'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<h3 style='color: red;'>✗ Error: " . ($data['message'] ?? 'Unknown error') . "</h3>";
    if (isset($data['details'])) {
        echo "<p>Details: " . $data['details'] . "</p>";
    }
}
?>
