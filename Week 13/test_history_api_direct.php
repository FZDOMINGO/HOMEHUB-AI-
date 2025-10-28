<?php
session_start();

// Set test session if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'tenant';
    $_SESSION['user_name'] = 'Test User';
}

// Make API call
$url = 'http://localhost/HomeHub/api/get-history.php?category=all&offset=0&limit=20';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>API Response (get-history.php)</h2>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Session User ID:</strong> " . $_SESSION['user_id'] . "</p>";
echo "<p><strong>Session User Type:</strong> " . $_SESSION['user_type'] . "</p>";
echo "<hr>";
echo "<h3>Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
echo "<hr>";
echo "<h3>Formatted JSON:</h3>";
$data = json_decode($response, true);
echo "<pre>" . print_r($data, true) . "</pre>";

if (isset($data['activities'])) {
    echo "<h3>Activities Count: " . count($data['activities']) . "</h3>";
}
?>
