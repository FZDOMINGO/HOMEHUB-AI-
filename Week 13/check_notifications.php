<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

$userId = 1; // Tenant Profile

echo "=== NOTIFICATIONS FOR USER ID: $userId ===\n\n";

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Notification ID: " . $row['id'] . "\n";
        echo "Type: " . $row['type'] . "\n";
        echo "Content: " . $row['content'] . "\n";
        echo "Is Read: " . ($row['is_read'] ? 'Yes' : 'No') . "\n";
        echo "Created: " . $row['created_at'] . "\n";
        echo str_repeat("-", 50) . "\n\n";
    }
} else {
    echo "No notifications found.\n";
}

// Check if notifications table has the correct structure
echo "\n=== NOTIFICATIONS TABLE STRUCTURE ===\n";
$result = $conn->query("SHOW COLUMNS FROM notifications");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
