<?php
// Test landlord reservations API
session_start();

// Set test session as landlord
$_SESSION['user_id'] = 1; // Change this to your landlord user_id
$_SESSION['user_type'] = 'landlord';

echo "<h1>Testing Landlord Reservations API</h1>";

// Include database connection
require_once 'config/db_connect.php';
$conn = getDbConnection();

// Check if user exists and is landlord
$stmt = $conn->prepare("SELECT u.*, l.id as landlord_id FROM users u 
                        LEFT JOIN landlords l ON u.id = l.user_id 
                        WHERE u.id = ? AND u.role = 'landlord'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<p><strong>✅ User found:</strong> " . $user['name'] . " (ID: " . $user['id'] . ")</p>";
    echo "<p><strong>✅ Landlord ID:</strong> " . $user['landlord_id'] . "</p>";
    
    // Check properties owned by this landlord
    $stmt = $conn->prepare("SELECT * FROM properties WHERE landlord_id = ?");
    $stmt->bind_param("i", $user['landlord_id']);
    $stmt->execute();
    $properties = $stmt->get_result();
    
    echo "<h2>Properties owned by this landlord:</h2>";
    echo "<ul>";
    if ($properties->num_rows > 0) {
        while ($prop = $properties->fetch_assoc()) {
            echo "<li>Property ID: " . $prop['id'] . " - " . $prop['title'] . "</li>";
        }
    } else {
        echo "<li style='color:red'>❌ No properties found for this landlord</li>";
    }
    echo "</ul>";
    
    // Check reservations for these properties
    echo "<h2>Checking property_reservations table:</h2>";
    $stmt = $conn->prepare("SELECT pr.*, p.title as property_title, u.name as tenant_name
                           FROM property_reservations pr
                           LEFT JOIN properties p ON pr.property_id = p.id
                           LEFT JOIN tenants t ON pr.tenant_id = t.id
                           LEFT JOIN users u ON t.user_id = u.id
                           WHERE p.landlord_id = ?");
    $stmt->bind_param("i", $user['landlord_id']);
    $stmt->execute();
    $reservations = $stmt->get_result();
    
    if ($reservations->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Property</th><th>Tenant</th><th>Move-in Date</th><th>Status</th><th>Created</th></tr>";
        while ($res = $reservations->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $res['id'] . "</td>";
            echo "<td>" . $res['property_title'] . "</td>";
            echo "<td>" . $res['tenant_name'] . "</td>";
            echo "<td>" . $res['move_in_date'] . "</td>";
            echo "<td>" . $res['status'] . "</td>";
            echo "<td>" . $res['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠️ No reservations found for this landlord's properties</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ User not found or not a landlord</p>";
}

// Test the API directly
echo "<h2>Testing API Response:</h2>";
echo "<pre>";
include 'api/get-landlord-reservations.php';
echo "</pre>";

$conn->close();
?>
