<?php
// Create test reservation for landlord
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<h1>Creating Test Reservation</h1>";

// Get first landlord
$landlord = $conn->query("SELECT l.id as landlord_id, l.user_id, u.name 
                         FROM landlords l 
                         JOIN users u ON l.user_id = u.id 
                         LIMIT 1")->fetch_assoc();

if (!$landlord) {
    die("❌ No landlord found in database");
}

echo "<p>✅ Found landlord: " . $landlord['name'] . " (ID: " . $landlord['landlord_id'] . ")</p>";

// Get first property of this landlord
$property = $conn->query("SELECT * FROM properties WHERE landlord_id = " . $landlord['landlord_id'] . " LIMIT 1")->fetch_assoc();

if (!$property) {
    die("❌ No property found for this landlord");
}

echo "<p>✅ Found property: " . $property['title'] . " (ID: " . $property['id'] . ")</p>";

// Get first tenant
$tenant = $conn->query("SELECT t.id as tenant_id, t.user_id, u.name 
                       FROM tenants t 
                       JOIN users u ON t.user_id = u.id 
                       LIMIT 1")->fetch_assoc();

if (!$tenant) {
    die("❌ No tenant found in database");
}

echo "<p>✅ Found tenant: " . $tenant['name'] . " (ID: " . $tenant['tenant_id'] . ")</p>";

// Check if property_reservations table has the correct columns
$columns = $conn->query("SHOW COLUMNS FROM property_reservations");
echo "<h3>Table columns:</h3><ul>";
while ($col = $columns->fetch_assoc()) {
    echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
}
echo "</ul>";

// Create test reservation
$moveInDate = date('Y-m-d', strtotime('+30 days'));
$reservationDate = date('Y-m-d H:i:s');
$expirationDate = date('Y-m-d H:i:s', strtotime('+7 days'));

$stmt = $conn->prepare("INSERT INTO property_reservations 
    (property_id, tenant_id, move_in_date, lease_duration, reservation_fee, 
     payment_method, employment_status, monthly_income, requirements, 
     status, reservation_date, expiration_date, created_at) 
    VALUES (?, ?, ?, 12, 5000.00, 'bank_transfer', 'employed', 50000.00, 
            'Valid ID, Proof of Income', 'pending', ?, ?, NOW())");

$stmt->bind_param("iisss", 
    $property['id'], 
    $tenant['tenant_id'], 
    $moveInDate,
    $reservationDate,
    $expirationDate
);

if ($stmt->execute()) {
    $reservationId = $stmt->insert_id;
    echo "<h2 style='color:green'>✅ Test reservation created successfully!</h2>";
    echo "<p><strong>Reservation ID:</strong> " . $reservationId . "</p>";
    echo "<p><strong>Property:</strong> " . $property['title'] . "</p>";
    echo "<p><strong>Tenant:</strong> " . $tenant['name'] . "</p>";
    echo "<p><strong>Move-in Date:</strong> " . $moveInDate . "</p>";
    echo "<p><strong>Status:</strong> Pending</p>";
    
    echo "<hr>";
    echo "<p>✅ Now the landlord should be able to see this reservation in the 'Manage Reservations' section!</p>";
    echo "<p><a href='bookings.php'>Go to Bookings Page</a></p>";
} else {
    echo "<h2 style='color:red'>❌ Failed to create reservation</h2>";
    echo "<p>Error: " . $stmt->error . "</p>";
}

$conn->close();
?>
