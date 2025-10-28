<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "Creating test properties for filtering...\n";

// First, check if we have any landlords
$landlordResult = $conn->query("SELECT id FROM landlords LIMIT 1");
$landlord = $landlordResult->fetch_assoc();

if (!$landlord) {
    echo "No landlords found. Creating a test landlord first...\n";
    
    // Create a test user first
    $conn->query("INSERT INTO users (first_name, last_name, email, password, user_type) VALUES ('Test', 'Landlord', 'test.landlord@example.com', 'password123', 'landlord')");
    $userId = $conn->insert_id;
    
    // Create landlord record
    $conn->query("INSERT INTO landlords (user_id) VALUES ($userId)");
    $landlordId = $conn->insert_id;
} else {
    $landlordId = $landlord['id'];
}

echo "Using landlord ID: $landlordId\n";

// Create 3 available properties
for ($i = 1; $i <= 3; $i++) {
    $title = "Available Property $i";
    $description = "This is a test available property number $i";
    $address = "123 Test Street $i";
    $city = "Test City";
    $state = "Test State";
    $zipCode = "1234$i";
    $rentAmount = 1000 + ($i * 100);
    
    $stmt = $conn->prepare("INSERT INTO properties (landlord_id, title, description, address, city, state, zip_code, property_type, bedrooms, bathrooms, square_feet, rent_amount, deposit_amount, availability_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'apartment', 2, 1, 800, ?, 500, CURDATE(), 'available', NOW())");
    
    $stmt->bind_param("issssssi", $landlordId, $title, $description, $address, $city, $state, $zipCode, $rentAmount);
    
    if ($stmt->execute()) {
        echo "Created: $title\n";
    } else {
        echo "Error creating $title: " . $conn->error . "\n";
    }
}

// Create 1 more suspended property
$title = "Suspended Property Test";
$description = "This is a test suspended property";
$address = "456 Suspended Street";
$city = "Test City";
$state = "Test State";
$zipCode = "56789";
$rentAmount = 1500;

$stmt = $conn->prepare("INSERT INTO properties (landlord_id, title, description, address, city, state, zip_code, property_type, bedrooms, bathrooms, square_feet, rent_amount, deposit_amount, availability_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'house', 3, 2, 1200, ?, 750, CURDATE(), 'suspended', NOW())");

$stmt->bind_param("issssssi", $landlordId, $title, $description, $address, $city, $state, $zipCode, $rentAmount);

if ($stmt->execute()) {
    echo "Created: $title\n";
} else {
    echo "Error creating $title: " . $conn->error . "\n";
}

echo "\nFinal properties status:\n";
$result = $conn->query("SELECT status, COUNT(*) as count FROM properties GROUP BY status");
while ($row = $result->fetch_assoc()) {
    echo "Status: " . $row['status'] . " - Count: " . $row['count'] . "\n";
}

$conn->close();
?>