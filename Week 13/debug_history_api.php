<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>Session Debug</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>API Test</h2>";

// Make a request to the API
$url = 'http://localhost/HomeHub/api/get-history.php?category=all&limit=5';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>HTTP Status: $httpCode</h3>";
echo "<h3>Raw Response:</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h3>JSON Decode:</h3>";
$data = json_decode($response, true);
if ($data === null) {
    echo "JSON Error: " . json_last_error_msg();
} else {
    echo "<pre>" . print_r($data, true) . "</pre>";
}
?>
