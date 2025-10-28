<?php
$conn = new mysqli('localhost', 'root', '', 'homehub');
$result = $conn->query('SHOW TABLES');
while($row = $result->fetch_array()) {
    echo $row[0] . "\n";
}
?>
