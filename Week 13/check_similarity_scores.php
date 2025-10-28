<?php
$conn = new mysqli('localhost', 'root', '', 'homehub');
$result = $conn->query('DESCRIBE similarity_scores');
echo "similarity_scores table columns:\n\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
