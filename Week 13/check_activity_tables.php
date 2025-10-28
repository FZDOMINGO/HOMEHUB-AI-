<?php
$conn = new mysqli('localhost', 'root', '', 'homehub');

$tables = [
    'property_reservations',
    'booking_visits', 
    'browsing_history',
    'saved_properties',
    'user_interactions',
    'messages',
    'notifications',
    'search_queries'
];

foreach ($tables as $table) {
    echo "\n=== $table ===\n";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    }
}
?>
