<?php
/**
 * Quick check of tenant_preferences table structure
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

$conn = getDbConnection();

echo "<h2>Tenant Preferences Table Structure</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// Get table structure
$result = $conn->query("DESCRIBE tenant_preferences");

if ($result) {
    echo "<h3>Column Structure:</h3>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Get sample data
echo "<h3>Sample Data (First 3 rows):</h3>";
$result = $conn->query("SELECT * FROM tenant_preferences LIMIT 3");

if ($result && $result->num_rows > 0) {
    echo "<table>";
    
    // Get column names
    $fields = $result->fetch_fields();
    echo "<tr>";
    foreach ($fields as $field) {
        echo "<th>{$field->name}</th>";
    }
    echo "</tr>";
    
    // Get data
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No data found in tenant_preferences table.</p>";
}

$conn->close();
?>
