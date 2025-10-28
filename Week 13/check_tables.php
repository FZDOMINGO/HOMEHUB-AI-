<?php
// Check what tables exist
$conn = new mysqli('localhost', 'root', '', 'homehub');
$result = $conn->query('SHOW TABLES');
echo "All tables in homehub database:\n";
while($row = $result->fetch_array()) {
    echo "  - " . $row[0] . "\n";
}

// Check for reservation-related tables
echo "\nSearching for reservation tables:\n";
$result = $conn->query("SHOW TABLES LIKE '%reserv%'");
if ($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        echo "Found: " . $row[0] . "\n";
    }
} else {
    echo "No reservation tables found!\n";
}

// Check for booking-related tables
echo "\nSearching for booking tables:\n";
$result = $conn->query("SHOW TABLES LIKE '%booking%'");
if ($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        echo "Found: " . $row[0] . "\n";
    }
} else {
    echo "No booking tables found!\n";
}
?>
