<?php
// Test file to check database columns
session_start();

// Include database connection
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<h2>Database Column Test</h2>";

// Check if property_reservations table exists
$result = $conn->query("SHOW TABLES LIKE 'property_reservations'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Table 'property_reservations' exists</p>";
    
    // Get columns
    $columns = $conn->query("DESCRIBE property_reservations");
    echo "<h3>Current Columns:</h3>";
    echo "<ul>";
    while ($col = $columns->fetch_assoc()) {
        echo "<li><strong>{$col['Field']}</strong> - Type: {$col['Type']}, Null: {$col['Null']}, Default: {$col['Default']}</li>";
    }
    echo "</ul>";
    
    // Check for new columns
    echo "<h3>New Columns Check:</h3>";
    $requiredColumns = [
        'reservation_fee',
        'payment_method',
        'employment_status',
        'monthly_income',
        'reservation_date',
        'expiration_date',
        'approval_date',
        'completion_date',
        'documents_submitted',
        'lease_signed',
        'payment_confirmed',
        'cancellation_reason',
        'notes'
    ];
    
    foreach ($requiredColumns as $colName) {
        $check = $conn->query("SHOW COLUMNS FROM property_reservations LIKE '$colName'");
        if ($check->num_rows > 0) {
            echo "<p style='color: green;'>✓ Column '$colName' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Column '$colName' is MISSING!</p>";
        }
    }
    
} else {
    echo "<p style='color: red;'>✗ Table 'property_reservations' does not exist!</p>";
}

$conn->close();
?>
