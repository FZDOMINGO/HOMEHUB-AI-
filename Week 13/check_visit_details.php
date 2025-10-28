<?php
require_once 'config/db_connect.php';

$conn = getDbConnection();

// Get all pending visits with tenant details
$query = "
    SELECT bv.id AS visit_id, bv.visit_date, bv.visit_time, bv.status,
           p.id AS property_id, p.title AS property_title,
           t.id AS tenant_id, u.id AS user_id, u.email, u.first_name, u.last_name, u.phone
    FROM booking_visits bv
    JOIN properties p ON bv.property_id = p.id
    JOIN tenants t ON bv.tenant_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE bv.status = 'pending'
    ORDER BY bv.created_at DESC
";

$result = $conn->query($query);

echo "=== PENDING VISIT REQUESTS WITH TENANT DETAILS ===\n\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Visit ID: " . $row['visit_id'] . "\n";
        echo "Property: " . $row['property_title'] . " (ID: " . $row['property_id'] . ")\n";
        echo "Visit Date: " . $row['visit_date'] . " at " . $row['visit_time'] . "\n";
        echo "Status: " . $row['status'] . "\n\n";
        echo "Tenant Details:\n";
        echo "  - Tenant ID: " . $row['tenant_id'] . "\n";
        echo "  - User ID: " . $row['user_id'] . "\n";
        echo "  - Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
        echo "  - Email: " . $row['email'] . "\n";
        echo "  - Phone: " . ($row['phone'] ?: 'NULL') . "\n";
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
} else {
    echo "No pending visit requests found.\n";
}
?>
