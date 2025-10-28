<?php
// Check users table structure for phone field
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<h1>Users Table Structure Check</h1>";

// Show columns
$result = $conn->query("SHOW COLUMNS FROM users");
echo "<h2>Columns in 'users' table:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($col = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>" . $col['Field'] . "</strong></td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check for phone-related columns
echo "<h2>Phone-related columns:</h2>";
$result = $conn->query("SHOW COLUMNS FROM users LIKE '%phone%'");
if ($result->num_rows > 0) {
    while ($col = $result->fetch_assoc()) {
        echo "<p>✅ Found: <strong>" . $col['Field'] . "</strong> (" . $col['Type'] . ")</p>";
    }
} else {
    echo "<p style='color:red'>❌ No phone column found!</p>";
}

// Check sample data
echo "<h2>Sample User Data (with phone):</h2>";
$result = $conn->query("SELECT id, name, email, phone, contact_number, mobile FROM users LIMIT 5");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Contact Number</th><th>Mobile</th></tr>";
    while ($user = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . ($user['phone'] ?? 'NULL') . "</td>";
        echo "<td>" . ($user['contact_number'] ?? 'N/A') . "</td>";
        echo "<td>" . ($user['mobile'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Error: " . $conn->error . "</p>";
}

$conn->close();
?>
