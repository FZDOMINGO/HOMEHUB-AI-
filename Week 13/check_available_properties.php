<?php
require 'config/db_connect.php';
$conn = getDbConnection();

// Check available properties
$result = $conn->query("SELECT COUNT(*) as count, MIN(rent_amount) as min_rent, MAX(rent_amount) as max_rent FROM properties WHERE status='available'");
$row = $result->fetch_assoc();
echo "Available properties: " . $row['count'] . "\n";
echo "Rent range: $" . $row['min_rent'] . " - $" . $row['max_rent'] . "\n\n";

// Check your preferences
$result = $conn->query("SELECT tp.*, t.user_id FROM tenant_preferences tp JOIN tenants t ON tp.tenant_id = t.id WHERE t.user_id = 1");
if ($result->num_rows > 0) {
    $pref = $result->fetch_assoc();
    echo "Your preferences:\n";
    echo "Budget: $" . $pref['min_budget'] . " - $" . $pref['max_budget'] . "\n";
    echo "City: " . $pref['preferred_city'] . "\n";
    echo "Property type: " . $pref['property_type'] . "\n";
    echo "Min bedrooms: " . $pref['min_bedrooms'] . "\n\n";
    
    // Check matching properties
    $query = "SELECT id, title, city, rent_amount, property_type, bedrooms, status 
              FROM properties 
              WHERE status = 'available' 
              AND rent_amount BETWEEN " . ($pref['min_budget'] * 0.5) . " AND " . ($pref['max_budget'] * 1.5);
    $result = $conn->query($query);
    echo "Potentially matching properties: " . $result->num_rows . "\n";
    if ($result->num_rows > 0) {
        echo "\nMatching properties:\n";
        while ($prop = $result->fetch_assoc()) {
            echo "- " . $prop['title'] . " ($" . $prop['rent_amount'] . ", " . $prop['city'] . ", " . $prop['property_type'] . ")\n";
        }
    }
} else {
    echo "No preferences set for user 1\n";
}
?>
