<?php
// Check and update phone numbers in users table
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<h1>Phone Number Status Check</h1>";

// Check users table structure
echo "<h2>1. Users Table Structure</h2>";
$columns = $conn->query("SHOW COLUMNS FROM users LIKE '%phone%'");
echo "<ul>";
while ($col = $columns->fetch_assoc()) {
    echo "<li><strong>" . $col['Field'] . "</strong> - " . $col['Type'] . "</li>";
}
echo "</ul>";

// Check how many users have phone numbers
echo "<h2>2. Phone Number Statistics</h2>";
$result = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN phone IS NOT NULL AND phone != '' THEN 1 ELSE 0 END) as has_phone,
    SUM(CASE WHEN phone IS NULL OR phone = '' THEN 1 ELSE 0 END) as no_phone
    FROM users");
$stats = $result->fetch_assoc();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Metric</th><th>Count</th></tr>";
echo "<tr><td>Total Users</td><td>" . $stats['total'] . "</td></tr>";
echo "<tr><td style='color:green'>✅ Users with Phone</td><td>" . $stats['has_phone'] . "</td></tr>";
echo "<tr><td style='color:red'>❌ Users without Phone</td><td>" . $stats['no_phone'] . "</td></tr>";
echo "</table>";

// Show users without phone numbers
echo "<h2>3. Users Missing Phone Numbers</h2>";
$result = $conn->query("SELECT id, name, email, phone, role FROM users WHERE phone IS NULL OR phone = ''");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Phone</th></tr>";
    while ($user = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td style='color:red'>" . ($user['phone'] ? $user['phone'] : 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Offer to add default phone numbers
    echo "<h3>Fix Option:</h3>";
    echo "<form method='POST'>";
    echo "<p>Add default phone numbers for users without phone data?</p>";
    echo "<button type='submit' name='add_default_phones'>Add Default Phone Numbers</button>";
    echo "</form>";
} else {
    echo "<p style='color:green'>✅ All users have phone numbers!</p>";
}

// Handle form submission
if (isset($_POST['add_default_phones'])) {
    echo "<h2>4. Adding Default Phone Numbers</h2>";
    $updated = 0;
    $result = $conn->query("SELECT id, role FROM users WHERE phone IS NULL OR phone = ''");
    while ($user = $result->fetch_assoc()) {
        // Generate a default phone based on user ID
        $defaultPhone = "+639" . str_pad($user['id'], 9, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->bind_param("si", $defaultPhone, $user['id']);
        if ($stmt->execute()) {
            $updated++;
            echo "<p>✅ Updated User ID " . $user['id'] . " with phone: " . $defaultPhone . "</p>";
        }
    }
    echo "<p style='color:green'><strong>✅ Updated " . $updated . " users</strong></p>";
    echo "<p><a href='check_phone_numbers.php'>Refresh Page</a></p>";
}

// Show sample of current phone numbers
echo "<h2>4. Sample Users with Phone Numbers</h2>";
$result = $conn->query("SELECT id, name, email, phone, role FROM users WHERE phone IS NOT NULL AND phone != '' LIMIT 10");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Phone</th></tr>";
    while ($user = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td style='color:green'>" . $user['phone'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
