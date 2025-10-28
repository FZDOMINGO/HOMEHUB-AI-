<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<h2>Properties Table Columns</h2>";
$result = $conn->query("SHOW COLUMNS FROM properties");

echo "<ol>";
while ($row = $result->fetch_assoc()) {
    echo "<li><strong style='color: blue;'>{$row['Field']}</strong> ({$row['Type']})</li>";
}
echo "</ol>";

echo "<h3>Looking for rent-related columns:</h3>";
$result = $conn->query("SHOW COLUMNS FROM properties");
$found = false;
while ($row = $result->fetch_assoc()) {
    if (stripos($row['Field'], 'rent') !== false || 
        stripos($row['Field'], 'price') !== false || 
        stripos($row['Field'], 'cost') !== false) {
        echo "<p style='color: green; font-size: 18px;'>✓ Found: <strong>{$row['Field']}</strong></p>";
        $found = true;
    }
}

if (!$found) {
    echo "<p style='color: red;'>No rent/price column found! Please tell me all the column names above.</p>";
}

$conn->close();
?>
