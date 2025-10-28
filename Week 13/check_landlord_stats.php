<?php
require 'config/db_connect.php';
$conn = getDbConnection();

// Get landlord's properties and their stats
$result = $conn->query("
    SELECT p.id, p.title, p.rent_amount, p.status,
           (SELECT COUNT(*) FROM browsing_history WHERE property_id = p.id) as total_views,
           (SELECT COUNT(DISTINCT user_id) FROM browsing_history WHERE property_id = p.id) as unique_visitors
    FROM properties p
    WHERE p.landlord_id = 1
");

echo "Landlord 1's Properties:\n\n";
while($row = $result->fetch_assoc()) {
    echo "Property: " . $row['title'] . "\n";
    echo "Rent: $" . $row['rent_amount'] . "\n";
    echo "Status: " . $row['status'] . "\n";
    echo "Total views: " . $row['total_views'] . "\n";
    echo "Unique visitors: " . $row['unique_visitors'] . "\n\n";
}
?>
