<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<h2>Recent User Registrations</h2>";
$result = $conn->query("SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
                        CASE 
                            WHEN t.user_id IS NOT NULL THEN 'tenant'
                            WHEN l.user_id IS NOT NULL THEN 'landlord'
                            ELSE 'unknown'
                        END as user_type
                        FROM users u
                        LEFT JOIN tenants t ON t.user_id = u.id
                        LEFT JOIN landlords l ON l.user_id = u.id
                        ORDER BY u.created_at DESC
                        LIMIT 10");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #8b5cf6; color: white;'>";
echo "<th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Type</th><th>Created At</th>";
echo "</tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['first_name']} {$row['last_name']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>{$row['phone']}</td>";
    echo "<td style='color: " . ($row['user_type'] === 'tenant' ? 'blue' : 'green') . "'><strong>{$row['user_type']}</strong></td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><p style='color: green;'><strong>✅ Registration system is working!</strong></p>";
echo "<p>If you can see users in the table above, registration is successful.</p>";

$conn->close();
?>
