<?php
require 'config/db_connect.php';
$conn = getDbConnection();
$result = $conn->query('DESCRIBE properties');
echo "Properties table columns:\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
