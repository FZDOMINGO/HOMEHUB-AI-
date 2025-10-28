<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();
$result = $conn->query('DESCRIBE users');
echo "<h3>Users Table Columns:</h3><ul>";
while($row = $result->fetch_assoc()) {
    echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
}
echo "</ul>";
$conn->close();
?>
