<?php
/**
 * Get AI-powered property matches for logged-in tenant
 * This endpoint calls the Python AI backend and returns matches
 */

session_start();
header('Content-Type: application/json');

// Add error handling for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, only log them
ini_set('log_errors', 1);

try {
    // Check if user is logged in and is a tenant
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please login as a tenant.'
        ]);
        exit;
    }

    require_once __DIR__ . '/../../config/db_connect.php';
    $conn = getDbConnection();

$userId = $_SESSION['user_id'];

// Get tenant ID
$stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Tenant profile not found.'
    ]);
    exit;
}

$tenant = $result->fetch_assoc();
$tenantId = $tenant['id'];

// Check if tenant has preferences set
$stmt = $conn->prepare("SELECT * FROM tenant_preferences WHERE tenant_id = ?");
$stmt->bind_param("i", $tenantId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'error' => 'no_preferences',
        'message' => 'Please set your preferences first.',
        'action' => 'setup_preferences'
    ]);
    exit;
}

$preferences = $result->fetch_assoc();

// Try to get cached matches first
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

$stmt = $conn->prepare("
    SELECT 
        ss.*,
        p.title,
        p.description,
        p.address,
        p.city,
        p.state,
        p.rent_amount,
        p.bedrooms,
        p.bathrooms,
        p.square_feet,
        p.property_type,
        (SELECT image_url FROM property_images 
         WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM similarity_scores ss
    JOIN properties p ON ss.property_id = p.id
    WHERE ss.tenant_id = ?
    AND ss.is_valid = TRUE
    AND p.status = 'available'
    ORDER BY ss.match_score DESC
    LIMIT ?
");

$stmt->bind_param("ii", $tenantId, $limit);
$stmt->execute();
$result = $stmt->get_result();

$matches = [];

// If we have cached matches, use them
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $breakdown = json_decode($row['feature_breakdown'], true);
        
        $matches[] = [
            'property_id' => $row['property_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'address' => $row['address'],
            'city' => $row['city'],
            'state' => $row['state'],
            'rent_amount' => $row['rent_amount'],
            'bedrooms' => $row['bedrooms'],
            'bathrooms' => $row['bathrooms'],
            'square_feet' => $row['square_feet'],
            'property_type' => $row['property_type'],
            'primary_image' => $row['primary_image'],
            'match_score' => $row['match_score'],
            'feature_breakdown' => $breakdown
        ];
    }
} else {
    // No cached matches, calculate simple matches based on preferences
    $minBudget = $preferences['min_budget'];
    $maxBudget = $preferences['max_budget'];
    $preferredCity = $preferences['preferred_city'];
    $propertyType = $preferences['property_type'];
    $minBedrooms = $preferences['min_bedrooms'];
    
    // Simple matching query
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            (SELECT image_url FROM property_images 
             WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
            -- Calculate simple match score
            (
                -- Budget match (40%)
                CASE 
                    WHEN p.rent_amount BETWEEN ? AND ? THEN 0.4
                    WHEN p.rent_amount BETWEEN ? * 0.8 AND ? * 1.2 THEN 0.2
                    ELSE 0
                END +
                -- City match (30%)
                CASE 
                    WHEN ? IS NOT NULL AND p.city = ? THEN 0.3
                    ELSE 0.1
                END +
                -- Property type match (20%)
                CASE 
                    WHEN ? IS NOT NULL AND p.property_type = ? THEN 0.2
                    ELSE 0.05
                END +
                -- Bedroom match (10%)
                CASE 
                    WHEN p.bedrooms >= ? THEN 0.1
                    ELSE 0.05
                END
            ) as match_score
        FROM properties p
        WHERE p.status = 'available'
        AND p.rent_amount BETWEEN ? * 0.5 AND ? * 1.5
        HAVING match_score > 0.3
        ORDER BY match_score DESC
        LIMIT ?
    ");
    
    $stmt->bind_param("ddddssssiddi", 
        $minBudget, $maxBudget, $minBudget, $maxBudget,
        $preferredCity, $preferredCity,
        $propertyType, $propertyType,
        $minBedrooms,
        $minBudget, $maxBudget,
        $limit
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $matches[] = [
            'property_id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'address' => $row['address'],
            'city' => $row['city'],
            'state' => $row['state'],
            'rent_amount' => $row['rent_amount'],
            'bedrooms' => $row['bedrooms'],
            'bathrooms' => $row['bathrooms'],
            'square_feet' => $row['square_feet'],
            'property_type' => $row['property_type'],
            'primary_image' => $row['primary_image'],
            'match_score' => $row['match_score'],
            'feature_breakdown' => [
                'budget' => ($row['rent_amount'] >= $minBudget && $row['rent_amount'] <= $maxBudget) ? 1.0 : 0.5,
                'location' => ($row['city'] == $preferredCity) ? 1.0 : 0.3,
                'property_type' => ($row['property_type'] == $propertyType) ? 1.0 : 0.5,
                'size' => ($row['bedrooms'] >= $minBedrooms) ? 1.0 : 0.5
            ]
        ];
    }
}

// Check if we still have no matches
if (empty($matches)) {
    echo json_encode([
        'success' => false,
        'error' => 'no_matches',
        'message' => 'No properties match your preferences. Try adjusting your budget or location.',
        'preferences' => [
            'budget_range' => "$" . number_format($minBudget) . " - $" . number_format($maxBudget),
            'city' => $preferredCity,
            'property_type' => $propertyType,
            'min_bedrooms' => $minBedrooms
        ]
    ]);
    exit;
}

$conn->close();

echo json_encode([
    'success' => true,
    'tenant_id' => $tenantId,
    'total_matches' => count($matches),
    'matches' => $matches,
    'message' => count($matches) > 0 ? 
        "Found " . count($matches) . " matching properties!" : 
        'No matches found. Try adjusting your preferences.'
]);

} catch (Exception $e) {
    error_log("AI Matches Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'error' => 'server_error',
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
