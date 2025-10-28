<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "Properties Status Distribution:\n";
echo "==============================\n";

$result = $conn->query("SELECT status, COUNT(*) as count FROM properties GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Status: " . $row['status'] . " - Count: " . $row['count'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nFirst 5 Properties:\n";
echo "==================\n";

$result = $conn->query("SELECT id, title, status FROM properties LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Title: " . $row['title'] . " - Status: " . $row['status'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>