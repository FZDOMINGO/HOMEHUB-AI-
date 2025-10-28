<?php
require 'config/db_connect.php';
$conn = getDbConnection();

// Check landlords and their properties
$result = $conn->query("
    SELECT l.id as landlord_id, l.user_id, u.first_name, u.last_name, 
           COUNT(p.id) as property_count
    FROM landlords l
    JOIN users u ON l.user_id = u.id
    LEFT JOIN properties p ON l.id = p.landlord_id
    GROUP BY l.id
");

echo "Landlords in database:\n\n";
while($row = $result->fetch_assoc()) {
    echo "Landlord ID: " . $row['landlord_id'] . "\n";
    echo "User ID: " . $row['user_id'] . "\n";
    echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
    echo "Properties: " . $row['property_count'] . "\n\n";
}

// Check which user you're logged in as
echo "\nIf you're user_id 1, you're a tenant.\n";
echo "Check your session to see which user_id you are.\n";
?>
