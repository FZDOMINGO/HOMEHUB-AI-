<?php
/**
 * Generate AI Recommendations
 * Populates the recommendation_cache table with recommendations
 */

require_once 'config/db_connect.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Generate AI Recommendations</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.result { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
.success { border-left: 4px solid #4CAF50; }
.error { border-left: 4px solid #f44336; }
.info { border-left: 4px solid #2196F3; }
h1 { color: #333; }
pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
.btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
.btn:hover { background: #45a049; }
</style></head><body>";

echo "<h1>🤖 AI Recommendations Generator</h1>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

$conn = getDbConnection();

// Step 1: Check if recommendation_cache table exists
echo "<div class='result info'>";
echo "<h2>Step 1: Checking Tables</h2>";
$tables = ['recommendation_cache', 'browsing_history', 'properties', 'users', 'tenants'];
$allTablesExist = true;

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color:green'>✓ Table '$table' exists</p>";
    } else {
        echo "<p style='color:red'>✗ Table '$table' is MISSING!</p>";
        $allTablesExist = false;
    }
}

if (!$allTablesExist) {
    echo "<p style='color:red'><strong>ERROR:</strong> Missing required tables. Cannot generate recommendations.</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Step 2: Get statistics
echo "<div class='result info'>";
echo "<h2>Step 2: Database Statistics</h2>";

$userCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'tenant'")->fetch_assoc()['count'];
$propertyCount = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status = 'available'")->fetch_assoc()['count'];
$existingRecs = $conn->query("SELECT COUNT(*) as count FROM recommendation_cache")->fetch_assoc()['count'];

echo "<pre>";
echo "Tenants: $userCount\n";
echo "Available Properties: $propertyCount\n";
echo "Existing Recommendations: $existingRecs\n";
echo "</pre>";

if ($userCount == 0) {
    echo "<p style='color:red'><strong>WARNING:</strong> No tenants found! Create tenant accounts first.</p>";
}

if ($propertyCount == 0) {
    echo "<p style='color:red'><strong>WARNING:</strong> No available properties! Add properties first.</p>";
}

if ($propertyCount < 3) {
    echo "<p style='color:orange'><strong>WARNING:</strong> Only $propertyCount properties. Recommendations work better with 5+ properties.</p>";
}
echo "</div>";

// Step 3: Generate Recommendations
if (isset($_GET['generate']) && $_GET['generate'] == 'yes') {
    echo "<div class='result'>";
    echo "<h2>Step 3: Generating Recommendations</h2>";
    
    if ($userCount == 0 || $propertyCount == 0) {
        echo "<p style='color:red'><strong>ERROR:</strong> Cannot generate recommendations without users and properties.</p>";
        echo "</div></body></html>";
        exit;
    }
    
    // Clear old recommendations first
    $conn->query("DELETE FROM recommendation_cache WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    echo "<p>✓ Cleared old recommendations (older than 7 days)</p>";
    
    $recommendationsGenerated = 0;
    
    // Get all tenants
    $tenantsQuery = $conn->query("SELECT t.id as tenant_id, t.user_id FROM tenants t INNER JOIN users u ON u.id = t.user_id WHERE u.user_type = 'tenant'");
    
    while ($tenant = $tenantsQuery->fetch_assoc()) {
        $userId = $tenant['user_id'];
        $tenantId = $tenant['tenant_id'];
        
        // Strategy 1: Based on browsing history
        $historyQuery = "
            SELECT DISTINCT p.id, p.property_type, p.rent_amount
            FROM browsing_history bh
            INNER JOIN properties p ON p.id = bh.property_id
            WHERE bh.user_id = ?
            ORDER BY bh.viewed_at DESC
            LIMIT 3
        ";
        
        $stmt = $conn->prepare($historyQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $viewedProperties = $stmt->get_result();
        
        $propertyTypes = [];
        $minRent = PHP_INT_MAX;
        $maxRent = 0;
        
        while ($viewed = $viewedProperties->fetch_assoc()) {
            $propertyTypes[] = $viewed['property_type'];
            $minRent = min($minRent, $viewed['rent_amount']);
            $maxRent = max($maxRent, $viewed['rent_amount']);
        }
        
        // Get recommendations based on similar properties
        if (!empty($propertyTypes)) {
            $types = "'" . implode("','", array_unique($propertyTypes)) . "'";
            $recommendQuery = "
                SELECT id FROM properties 
                WHERE property_type IN ($types)
                AND rent_amount BETWEEN ? AND ?
                AND status = 'available'
                AND id NOT IN (SELECT property_id FROM browsing_history WHERE user_id = ?)
                LIMIT 5
            ";
            
            $stmt = $conn->prepare($recommendQuery);
            $minRentRange = $minRent * 0.8;
            $maxRentRange = $maxRent * 1.2;
            $stmt->bind_param("ddi", $minRentRange, $maxRentRange, $userId);
            $stmt->execute();
            $recommendations = $stmt->get_result();
            
            while ($rec = $recommendations->fetch_assoc()) {
                $score = rand(70, 95) / 100; // Generate score between 0.70 and 0.95
                
                // Insert recommendation
                $insertQuery = "INSERT INTO recommendation_cache (user_id, property_id, recommendation_score, is_valid, created_at, updated_at) 
                               VALUES (?, ?, ?, 1, NOW(), NOW())
                               ON DUPLICATE KEY UPDATE recommendation_score = ?, updated_at = NOW()";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("iidd", $userId, $rec['id'], $score, $score);
                $stmt->execute();
                $recommendationsGenerated++;
            }
        }
        
        // Strategy 2: If no browsing history, recommend popular/new properties
        if (empty($propertyTypes)) {
            $popularQuery = "
                SELECT id FROM properties 
                WHERE status = 'available'
                ORDER BY created_at DESC
                LIMIT 3
            ";
            
            $result = $conn->query($popularQuery);
            while ($rec = $result->fetch_assoc()) {
                $score = rand(60, 80) / 100; // Lower score for generic recommendations
                
                $insertQuery = "INSERT INTO recommendation_cache (user_id, property_id, recommendation_score, is_valid, created_at, updated_at) 
                               VALUES (?, ?, ?, 1, NOW(), NOW())
                               ON DUPLICATE KEY UPDATE recommendation_score = ?, updated_at = NOW()";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("iidd", $userId, $rec['id'], $score, $score);
                $stmt->execute();
                $recommendationsGenerated++;
            }
        }
    }
    
    echo "<div class='success'>";
    echo "<h3>✓ Success!</h3>";
    echo "<p><strong>$recommendationsGenerated</strong> recommendations generated!</p>";
    echo "</div>";
    
    // Show sample
    echo "<h3>Sample Generated Recommendations:</h3>";
    $sampleQuery = "SELECT rc.*, u.email, p.title, p.rent_amount 
                   FROM recommendation_cache rc
                   INNER JOIN users u ON u.id = rc.user_id
                   INNER JOIN properties p ON p.id = rc.property_id
                   WHERE rc.is_valid = 1
                   ORDER BY rc.created_at DESC
                   LIMIT 10";
    $samples = $conn->query($sampleQuery);
    
    echo "<table border='1' cellpadding='5' style='width:100%; border-collapse:collapse;'>";
    echo "<tr style='background:#f9f9f9'><th>User</th><th>Property</th><th>Rent</th><th>Score</th><th>Created</th></tr>";
    while ($row = $samples->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['title']}</td>";
        echo "<td>₱" . number_format($row['rent_amount']) . "</td>";
        echo "<td>" . number_format($row['recommendation_score'], 2) . "</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "</div>";
    
    echo "<div class='result success'>";
    echo "<h2>✓ Complete!</h2>";
    echo "<p>Recommendations have been generated and cached.</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Visit <a href='ai-features.php' target='_blank'>AI Features Page</a></li>";
    echo "<li>Login as a tenant</li>";
    echo "<li>Click 'Get Recommendations' button</li>";
    echo "<li>You should now see recommended properties!</li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    // Show button to generate
    echo "<div class='result info'>";
    echo "<h2>Step 3: Ready to Generate</h2>";
    echo "<p>Click the button below to generate AI recommendations for all tenants.</p>";
    echo "<p><strong>This will:</strong></p>";
    echo "<ul>";
    echo "<li>Analyze browsing history for each tenant</li>";
    echo "<li>Find similar properties based on viewed properties</li>";
    echo "<li>Generate recommendations with scores</li>";
    echo "<li>Store them in recommendation_cache table</li>";
    echo "</ul>";
    
    if ($userCount > 0 && $propertyCount > 0) {
        echo "<a href='?generate=yes' class='btn'>Generate Recommendations Now</a>";
    } else {
        echo "<p style='color:red'><strong>Cannot generate:</strong> Need at least 1 tenant and 1 property.</p>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='check_ai_database.php'>← Back to Database Check</a> | ";
echo "<a href='ai-features.php'>View AI Features Page</a></p>";

$conn->close();
echo "</body></html>";
?>
