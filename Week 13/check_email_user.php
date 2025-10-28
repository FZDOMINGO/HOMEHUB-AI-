<?php
require_once 'config/db_connect.php';

$conn = getDbConnection();
$email = 'goodplayer981@gmail.com';

$result = $conn->query("SELECT id, email, first_name, last_name FROM users WHERE email = '$email'");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "User ID: " . $row['id'] . "\n";
        echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
        echo "Email: " . $row['email'] . "\n\n";
    }
} else {
    echo "No user found with email: $email\n";
}

// Also check the tenant with ID 1
echo "\n--- Current Tenant (ID=1) Info ---\n";
$tenant_result = $conn->query("SELECT id, email, first_name, last_name FROM users WHERE id = 1");
if ($tenant_result->num_rows > 0) {
    $tenant = $tenant_result->fetch_assoc();
    echo "User ID: " . $tenant['id'] . "\n";
    echo "Name: " . $tenant['first_name'] . " " . $tenant['last_name'] . "\n";
    echo "Email: " . $tenant['email'] . "\n";
}
?>
