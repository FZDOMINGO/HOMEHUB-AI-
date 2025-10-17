<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<h2>Messages Table Structure</h2>";

// Get table structure
$result = $conn->query("DESCRIBE messages");
echo "<h3>Column Structure:</h3>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Get foreign keys
$result = $conn->query("SHOW CREATE TABLE messages");
$row = $result->fetch_assoc();
echo "<h3>Table Creation SQL (shows constraints):</h3>";
echo "<pre>";
print_r($row['Create Table']);
echo "</pre>";

$conn->close();
?>