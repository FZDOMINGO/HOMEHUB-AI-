<?php
$conn = new mysqli('localhost', 'root', '', 'homehub');
$result = $conn->query('DESCRIBE properties');
echo "Properties table columns:\n\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
