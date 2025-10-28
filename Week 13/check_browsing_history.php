<?php
require 'config/db_connect.php';
$conn = getDbConnection();
$result = $conn->query('DESCRIBE browsing_history');
echo "browsing_history table columns:\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
