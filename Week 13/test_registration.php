<?php
// Test registration system
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';

echo "<h2>Registration System Test</h2>";

// Test 1: Check if tables exist
echo "<h3>1. Checking Tables</h3>";
$tables = ['users', 'tenants', 'landlords'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' MISSING<br>";
    }
}

// Test 2: Check users table structure
echo "<h3>2. Users Table Structure</h3>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Error describing users table: " . $conn->error . "<br>";
}

// Test 3: Check tenants table structure
echo "<h3>3. Tenants Table Structure</h3>";
$result = $conn->query("DESCRIBE tenants");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Error describing tenants table: " . $conn->error . "<br>";
}

// Test 4: Check landlords table structure
echo "<h3>4. Landlords Table Structure</h3>";
$result = $conn->query("DESCRIBE landlords");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Error describing landlords table: " . $conn->error . "<br>";
}

// Test 5: Test a sample registration (dry run)
echo "<h3>5. Test Registration Data</h3>";
$test_email = 'test_' . time() . '@example.com';
echo "Test email: $test_email<br>";
echo "Testing if email is unique...<br>";

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "❌ Email already exists<br>";
} else {
    echo "✅ Email is unique<br>";
}

// Test 6: Check if sessions work
echo "<h3>6. Session Test</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['test'] = 'working';
if (isset($_SESSION['test']) && $_SESSION['test'] === 'working') {
    echo "✅ Sessions are working<br>";
    unset($_SESSION['test']);
} else {
    echo "❌ Sessions not working<br>";
}

// Test 7: Check PHP version and required functions
echo "<h3>7. PHP Environment</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "password_hash available: " . (function_exists('password_hash') ? '✅ Yes' : '❌ No') . "<br>";
echo "password_verify available: " . (function_exists('password_verify') ? '✅ Yes' : '❌ No') . "<br>";
echo "mysqli available: " . (class_exists('mysqli') ? '✅ Yes' : '❌ No') . "<br>";

$conn->close();
?>
