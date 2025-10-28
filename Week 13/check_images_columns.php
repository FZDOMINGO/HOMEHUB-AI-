<?php
$conn = new mysqli('localhost', 'root', '', 'homehub');
$result = $conn->query('DESCRIBE property_images');
echo "property_images table columns:\n\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
