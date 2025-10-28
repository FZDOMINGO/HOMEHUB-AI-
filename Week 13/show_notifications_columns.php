<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<h2>Notifications Table Columns</h2>";
$result = $conn->query("SHOW COLUMNS FROM notifications");

echo "<ol>";
while ($row = $result->fetch_assoc()) {
    echo "<li><strong style='color: blue;'>{$row['Field']}</strong> ({$row['Type']})</li>";
}
echo "</ol>";

$conn->close();
?>
