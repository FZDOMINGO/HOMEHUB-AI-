<?php
require 'config/db_connect.php';
$conn = getDbConnection();

// Check browsing history for user 1
$result = $conn->query("SELECT bh.*, p.title, p.property_type 
                        FROM browsing_history bh 
                        JOIN properties p ON bh.property_id = p.id 
                        WHERE bh.user_id = 1 
                        ORDER BY bh.created_at DESC");

echo "Browsing history for user 1:\n";
echo "Total records: " . $result->num_rows . "\n\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "- Property: " . $row['title'] . " (" . $row['property_type'] . ")\n";
        echo "  Action: " . $row['action_type'] . "\n";
        echo "  Viewed at: " . $row['created_at'] . "\n\n";
    }
} else {
    echo "No browsing history found.\n";
    echo "\nMake sure you:\n";
    echo "1. Are logged in as a tenant (user_id = 1)\n";
    echo "2. Clicked on property cards on the Properties page\n";
    echo "3. Viewed the property detail pages\n";
}
?>
