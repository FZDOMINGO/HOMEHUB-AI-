<!DOCTYPE html>
<html>
<head>
    <title>Test Preferences Schema</title>
</head>
<body>
    <h2>Testing tenant_preferences schema compatibility</h2>
    <?php
    require_once '../config/db_connect.php';
    $conn = getDbConnection();
    
    // Test data
    $testTenantId = 1; // Replace with a valid tenant ID
    $testData = [
        'tenant_id' => $testTenantId,
        'min_budget' => 500.00,
        'max_budget' => 1500.00,
        'preferred_cities' => json_encode(['New York', 'Brooklyn']),
        'preferred_property_types' => json_encode(['Apartment', 'Condo']),
        'min_bedrooms' => 1,
        'max_bedrooms' => 2,
        'min_bathrooms' => 1.0,
        'lifestyle_quiet_active' => 6,
        'lifestyle_family_single' => 5,
        'lifestyle_work_home' => 7,
        'pet_friendly_required' => 0,
        'furnished_preference' => 'either',
        'amenities_preferences' => json_encode([
            'parking' => 0.7,
            'gym' => 0.5,
            'pool' => 0.3
        ]),
        'parking_required' => 1,
        'near_public_transport' => 1
    ];
    
    echo "<h3>Test Data:</h3>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Try to prepare the INSERT statement
    $query = "
        INSERT INTO tenant_preferences 
        (tenant_id, min_budget, max_budget, preferred_cities, preferred_property_types,
         min_bedrooms, max_bedrooms, min_bathrooms,
         lifestyle_quiet_active, lifestyle_family_single, lifestyle_work_home,
         pet_friendly_required, furnished_preference, amenities_preferences,
         parking_required, near_public_transport)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            min_budget = VALUES(min_budget),
            max_budget = VALUES(max_budget),
            preferred_cities = VALUES(preferred_cities),
            preferred_property_types = VALUES(preferred_property_types),
            min_bedrooms = VALUES(min_bedrooms),
            max_bedrooms = VALUES(max_bedrooms),
            min_bathrooms = VALUES(min_bathrooms),
            lifestyle_quiet_active = VALUES(lifestyle_quiet_active),
            lifestyle_family_single = VALUES(lifestyle_family_single),
            lifestyle_work_home = VALUES(lifestyle_work_home),
            pet_friendly_required = VALUES(pet_friendly_required),
            furnished_preference = VALUES(furnished_preference),
            amenities_preferences = VALUES(amenities_preferences),
            parking_required = VALUES(parking_required),
            near_public_transport = VALUES(near_public_transport),
            updated_at = CURRENT_TIMESTAMP
    ";
    
    try {
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            echo "<p style='color: green;'>✓ SQL query prepared successfully!</p>";
            echo "<p>The column names match the database schema.</p>";
            
            // Note: We're not executing to avoid modifying real data
            echo "<p><strong>Note:</strong> Query was only prepared, not executed (test only).</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to prepare query: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
    }
    
    $conn->close();
    ?>
</body>
</html>
