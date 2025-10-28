<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

$userId = 1; // Tenant Profile user

// Get tenant ID
$stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();
$tenantId = $tenant ? $tenant['id'] : 0;

echo "User ID: $userId\n";
echo "Tenant ID: $tenantId\n\n";

if ($tenantId > 0) {
    // Check saved properties
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM saved_properties WHERE tenant_id = ?");
    $stmt->bind_param("i", $tenantId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo "Saved Properties Count: " . $result['count'] . "\n\n";
    
    // List saved properties
    $stmt = $conn->prepare("
        SELECT sp.id, sp.tenant_id, sp.property_id, sp.saved_at,
               p.title, p.address, p.rent_amount
        FROM saved_properties sp
        JOIN properties p ON sp.property_id = p.id
        WHERE sp.tenant_id = ?
        ORDER BY sp.saved_at DESC
    ");
    $stmt->bind_param("i", $tenantId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "=== SAVED PROPERTIES ===\n";
    while ($row = $result->fetch_assoc()) {
        echo "Property ID: " . $row['property_id'] . "\n";
        echo "Title: " . $row['title'] . "\n";
        echo "Address: " . $row['address'] . "\n";
        echo "Rent: $" . $row['rent_amount'] . "\n";
        echo "Saved At: " . $row['saved_at'] . "\n";
        echo str_repeat("-", 40) . "\n";
    }
} else {
    echo "ERROR: Tenant ID not found for user ID $userId\n";
}
?>
