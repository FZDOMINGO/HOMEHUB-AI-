<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Not logged in. Please log in first.\n";
    exit;
}

require_once 'config/db_connect.php';
$conn = getDbConnection();

$userId = $_SESSION['user_id'];

echo "=== Testing Save History Tracking ===\n";
echo "User ID: {$userId}\n";
echo "User Type: " . ($_SESSION['user_type'] ?? 'unknown') . "\n\n";

// Check recent user interactions
echo "--- Recent User Interactions ---\n";
$query = "SELECT 
    ui.id,
    ui.interaction_type,
    ui.weight,
    ui.created_at,
    p.title as property_title
FROM user_interactions ui
LEFT JOIN properties p ON ui.property_id = p.id
WHERE ui.user_id = ?
ORDER BY ui.created_at DESC
LIMIT 10";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "Type: {$row['interaction_type']}\n";
        echo "Property: {$row['property_title']}\n";
        echo "Weight: {$row['weight']}\n";
        echo "Date: {$row['created_at']}\n";
        echo "---\n";
    }
} else {
    echo "No interactions found.\n";
}

echo "\n--- Recent Saved Properties ---\n";
$query = "SELECT 
    sp.id,
    sp.saved_at,
    p.title as property_title,
    p.city
FROM saved_properties sp
JOIN properties p ON sp.property_id = p.id
WHERE sp.tenant_id = (SELECT id FROM tenants WHERE user_id = ?)
ORDER BY sp.saved_at DESC
LIMIT 5";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Property: {$row['property_title']} ({$row['city']})\n";
        echo "Saved At: {$row['saved_at']}\n";
        echo "---\n";
    }
} else {
    echo "No saved properties found.\n";
}

$conn->close();
?>
