<?php
/**
 * AI Features Database Diagnostic
 * Check all tables related to AI recommendations
 */

header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>AI Database Check</title>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
.section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
.pass { border-left: 4px solid #4CAF50; }
.fail { border-left: 4px solid #f44336; }
.warn { border-left: 4px solid #ff9800; }
.info { border-left: 4px solid #2196F3; }
h1 { color: #333; }
h2 { color: #666; margin-top: 0; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f9f9f9; font-weight: bold; }
pre { background: #f9f9f9; padding: 10px; overflow-x: auto; font-size: 12px; }
.count { font-size: 24px; font-weight: bold; color: #2196F3; }
</style></head><body>";

echo "<h1>🤖 AI Features Database Diagnostic</h1>";
echo "<p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

$conn = getDbConnection();

// 1. Check all required AI tables
echo "<div class='section info'>";
echo "<h2>1. AI-Related Tables Check</h2>";
$aiTables = [
    'recommendation_cache' => 'Stores AI recommendations for users',
    'browsing_history' => 'Tracks user property views',
    'tenant_preferences' => 'Stores tenant preferences for matching',
    'saved_properties' => 'User saved/favorited properties',
    'similarity_scores' => 'Property similarity calculations (optional)',
    'properties' => 'Property listings'
];

echo "<table>";
echo "<tr><th>Table Name</th><th>Description</th><th>Status</th><th>Row Count</th></tr>";
foreach ($aiTables as $table => $desc) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        $countResult = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $countResult->fetch_assoc()['count'];
        $status = $count > 0 ? "✓ EXISTS ($count rows)" : "⚠ EXISTS (EMPTY)";
        $color = $count > 0 ? "green" : "orange";
        echo "<tr><td><strong>$table</strong></td><td>$desc</td><td style='color:$color'>$status</td><td>$count</td></tr>";
    } else {
        echo "<tr><td><strong>$table</strong></td><td>$desc</td><td style='color:red'>✗ MISSING</td><td>0</td></tr>";
    }
}
echo "</table>";
echo "</div>";

// 2. Check recommendation_cache table structure
echo "<div class='section'>";
echo "<h2>2. Recommendation Cache Table Structure</h2>";
$result = $conn->query("SHOW TABLES LIKE 'recommendation_cache'");
if ($result->num_rows > 0) {
    $columns = $conn->query("DESCRIBE recommendation_cache");
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($col = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='fail'><strong>✗ CRITICAL:</strong> recommendation_cache table does not exist!</div>";
}
echo "</div>";

// 3. Check recommendation_cache data
echo "<div class='section'>";
echo "<h2>3. Recommendation Cache Data</h2>";
$result = $conn->query("SHOW TABLES LIKE 'recommendation_cache'");
if ($result->num_rows > 0) {
    $query = "SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN is_valid = 1 THEN 1 END) as valid,
        COUNT(CASE WHEN is_valid = 0 THEN 1 END) as invalid,
        COUNT(DISTINCT user_id) as unique_users
    FROM recommendation_cache";
    
    $result = $conn->query($query);
    $stats = $result->fetch_assoc();
    
    echo "<div class='info'>";
    echo "<table>";
    echo "<tr><td><strong>Total Recommendations:</strong></td><td class='count'>{$stats['total']}</td></tr>";
    echo "<tr><td><strong>Valid Recommendations:</strong></td><td class='count' style='color:green'>{$stats['valid']}</td></tr>";
    echo "<tr><td><strong>Invalid/Expired:</strong></td><td class='count' style='color:orange'>{$stats['invalid']}</td></tr>";
    echo "<tr><td><strong>Users with Recommendations:</strong></td><td class='count'>{$stats['unique_users']}</td></tr>";
    echo "</table>";
    echo "</div>";
    
    // Show sample recommendations
    echo "<h3>Sample Recommendations (Last 10):</h3>";
    $sampleQuery = "SELECT rc.*, u.email as user_email
                   FROM recommendation_cache rc
                   LEFT JOIN users u ON u.id = rc.user_id
                   ORDER BY rc.created_at DESC LIMIT 10";
    $samples = $conn->query($sampleQuery);
    
    if ($samples && $samples->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>User Email</th><th>Properties (JSON)</th><th>Confidence</th><th>Valid</th><th>Created</th></tr>";
        while ($row = $samples->fetch_assoc()) {
            $validIcon = $row['is_valid'] ? '✓' : '✗';
            $validColor = $row['is_valid'] ? 'green' : 'red';
            
            // Decode the JSON to show property IDs
            $properties = json_decode($row['recommended_properties'], true);
            $propertyDisplay = is_array($properties) ? count($properties) . ' properties' : 'N/A';
            
            // Handle null confidence_score
            $confidence = $row['confidence_score'] !== null ? number_format($row['confidence_score'], 2) : 'N/A';
            
            echo "<tr>";
            echo "<td>{$row['user_email']}</td>";
            echo "<td>$propertyDisplay</td>";
            echo "<td>$confidence</td>";
            echo "<td style='color:$validColor'>$validIcon</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warn'><strong>⚠ WARNING:</strong> No recommendations found in cache!</div>";
    }
} else {
    echo "<div class='fail'><strong>✗ ERROR:</strong> Cannot check - table missing</div>";
}
echo "</div>";

// 4. Check browsing_history
echo "<div class='section'>";
echo "<h2>4. Browsing History Check</h2>";
$result = $conn->query("SHOW TABLES LIKE 'browsing_history'");
if ($result->num_rows > 0) {
    $query = "SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT user_id) as unique_users,
        COUNT(DISTINCT property_id) as unique_properties,
        MAX(viewed_at) as last_view
    FROM browsing_history";
    $result = $conn->query($query);
    $stats = $result->fetch_assoc();
    
    echo "<table>";
    echo "<tr><td><strong>Total Views:</strong></td><td class='count'>{$stats['total']}</td></tr>";
    echo "<tr><td><strong>Users Who Viewed:</strong></td><td class='count'>{$stats['unique_users']}</td></tr>";
    echo "<tr><td><strong>Properties Viewed:</strong></td><td class='count'>{$stats['unique_properties']}</td></tr>";
    echo "<tr><td><strong>Last View:</strong></td><td>{$stats['last_view']}</td></tr>";
    echo "</table>";
} else {
    echo "<div class='fail'><strong>✗ MISSING:</strong> browsing_history table</div>";
}
echo "</div>";

// 5. Check tenant_preferences
echo "<div class='section'>";
echo "<h2>5. Tenant Preferences Check</h2>";
$result = $conn->query("SHOW TABLES LIKE 'tenant_preferences'");
if ($result->num_rows > 0) {
    $query = "SELECT COUNT(*) as total, COUNT(DISTINCT tenant_id) as unique_tenants FROM tenant_preferences";
    $result = $conn->query($query);
    $stats = $result->fetch_assoc();
    
    echo "<table>";
    echo "<tr><td><strong>Total Preferences:</strong></td><td class='count'>{$stats['total']}</td></tr>";
    echo "<tr><td><strong>Tenants with Preferences:</strong></td><td class='count'>{$stats['unique_tenants']}</td></tr>";
    echo "</table>";
    
    // Show sample preferences
    echo "<h3>Sample Preferences (Last 5):</h3>";
    $sampleQuery = "SELECT tp.*, t.user_id, u.email 
                   FROM tenant_preferences tp
                   LEFT JOIN tenants t ON t.id = tp.tenant_id
                   LEFT JOIN users u ON u.id = t.user_id
                   ORDER BY tp.updated_at DESC LIMIT 5";
    $samples = $conn->query($sampleQuery);
    
    if ($samples->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>User Email</th><th>Budget</th><th>Location</th><th>Property Type</th><th>Bedrooms</th></tr>";
        while ($row = $samples->fetch_assoc()) {
            // Use correct column names from tenant_preferences table
            $minRent = $row['min_rent'] ?? 0;
            $maxRent = $row['max_rent'] ?? 0;
            $location = $row['location_preference'] ?? 'Any';
            $propType = $row['property_type'] ?? 'Any';
            $bedrooms = $row['bedrooms'] ?? 'Any';
            
            echo "<tr>";
            echo "<td>{$row['email']}</td>";
            echo "<td>₱" . number_format($minRent) . " - ₱" . number_format($maxRent) . "</td>";
            echo "<td>$location</td>";
            echo "<td>$propType</td>";
            echo "<td>$bedrooms</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<div class='fail'><strong>✗ MISSING:</strong> tenant_preferences table</div>";
}
echo "</div>";

// 6. Check similarity_scores
echo "<div class='section'>";
echo "<h2>6. Similarity Scores Check</h2>";
$result = $conn->query("SHOW TABLES LIKE 'similarity_scores'");
if ($result->num_rows > 0) {
    $query = "SELECT COUNT(*) as total FROM similarity_scores";
    $result = $conn->query($query);
    $stats = $result->fetch_assoc();
    
    echo "<table>";
    echo "<tr><td><strong>Total Similarity Scores:</strong></td><td class='count'>{$stats['total']}</td></tr>";
    echo "</table>";
    
    if ($stats['total'] > 0) {
        echo "<h3>Sample Similarity Scores (Top 10):</h3>";
        $sampleQuery = "SELECT ss.*, 
                       p1.title as property1_title,
                       p2.title as property2_title
                       FROM similarity_scores ss
                       LEFT JOIN properties p1 ON p1.id = ss.property1_id
                       LEFT JOIN properties p2 ON p2.id = ss.property2_id
                       ORDER BY ss.similarity_score DESC LIMIT 10";
        $samples = $conn->query($sampleQuery);
        
        echo "<table>";
        echo "<tr><th>Property 1</th><th>Property 2</th><th>Score</th></tr>";
        while ($row = $samples->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['property1_title']}</td>";
            echo "<td>{$row['property2_title']}</td>";
            echo "<td>" . number_format($row['similarity_score'], 4) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<div class='warn'><strong>⚠ MISSING:</strong> similarity_scores table (optional for AI)</div>";
}
echo "</div>";

// 7. Check properties available for recommendations
echo "<div class='section'>";
echo "<h2>7. Properties Available for AI</h2>";
$query = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
    COUNT(CASE WHEN status = 'rented' THEN 1 END) as rented,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
FROM properties";
$result = $conn->query($query);
$stats = $result->fetch_assoc();

echo "<table>";
echo "<tr><td><strong>Total Properties:</strong></td><td class='count'>{$stats['total']}</td></tr>";
echo "<tr><td><strong>Available:</strong></td><td class='count' style='color:green'>{$stats['available']}</td></tr>";
echo "<tr><td><strong>Rented:</strong></td><td class='count' style='color:orange'>{$stats['rented']}</td></tr>";
echo "<tr><td><strong>Pending:</strong></td><td class='count' style='color:gray'>{$stats['pending']}</td></tr>";
echo "</table>";
echo "</div>";

// 8. Check users (tenants)
echo "<div class='section'>";
echo "<h2>8. Users Check</h2>";
$userQuery = "SELECT 
    COUNT(DISTINCT u.id) as total_users,
    COUNT(DISTINCT t.id) as tenants,
    COUNT(DISTINCT l.id) as landlords
FROM users u
LEFT JOIN tenants t ON u.id = t.user_id
LEFT JOIN landlords l ON u.id = l.user_id";
$result = $conn->query($userQuery);
$stats = $result->fetch_assoc();

echo "<table>";
echo "<tr><td><strong>Total Users:</strong></td><td class='count'>{$stats['total_users']}</td></tr>";
echo "<tr><td><strong>Tenants:</strong></td><td class='count'>{$stats['tenants']}</td></tr>";
echo "<tr><td><strong>Landlords:</strong></td><td class='count'>{$stats['landlords']}</td></tr>";
echo "</table>";
echo "</div>";

// 9. Diagnostic Summary
echo "<div class='section info'>";
echo "<h2>🔍 Diagnostic Summary</h2>";

$issues = [];
$warnings = [];

// Check recommendation_cache
$result = $conn->query("SHOW TABLES LIKE 'recommendation_cache'");
if ($result->num_rows == 0) {
    $issues[] = "recommendation_cache table is MISSING - AI recommendations will not work";
} else {
    $count = $conn->query("SELECT COUNT(*) as count FROM recommendation_cache WHERE is_valid = 1")->fetch_assoc()['count'];
    if ($count == 0) {
        $warnings[] = "No valid recommendations in cache - need to generate recommendations";
    }
}

// Check browsing_history
$result = $conn->query("SHOW TABLES LIKE 'browsing_history'");
if ($result->num_rows == 0) {
    $issues[] = "browsing_history table is MISSING - cannot track user behavior";
}

// Check tenant_preferences
$result = $conn->query("SHOW TABLES LIKE 'tenant_preferences'");
if ($result->num_rows == 0) {
    $warnings[] = "tenant_preferences table is MISSING - recommendations will be less accurate";
}

// Check properties
$propCount = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status = 'available'")->fetch_assoc()['count'];
if ($propCount < 3) {
    $warnings[] = "Only $propCount available properties - need more for good recommendations";
}

if (empty($issues) && empty($warnings)) {
    echo "<div class='pass'><strong>✓ ALL GOOD:</strong> Database structure looks healthy!</div>";
} else {
    if (!empty($issues)) {
        echo "<h3 style='color:red'>Critical Issues:</h3><ul>";
        foreach ($issues as $issue) {
            echo "<li style='color:red'>✗ $issue</li>";
        }
        echo "</ul>";
    }
    if (!empty($warnings)) {
        echo "<h3 style='color:orange'>Warnings:</h3><ul>";
        foreach ($warnings as $warning) {
            echo "<li style='color:orange'>⚠ $warning</li>";
        }
        echo "</ul>";
    }
}
echo "</div>";

// 10. Solutions
echo "<div class='section'>";
echo "<h2>💡 Solutions</h2>";
echo "<h3>If No Recommendations Are Showing:</h3>";
echo "<ol>";
echo "<li><strong>Check if recommendation_cache is empty:</strong> If yes, you need to generate recommendations using the AI service</li>";
echo "<li><strong>Run the AI recommendation script:</strong> Visit <code>ai-features.php</code> or run the Python AI service</li>";
echo "<li><strong>Check if AI service is running:</strong> The Flask AI service should be running on port 5000</li>";
echo "<li><strong>Manually test AI endpoint:</strong> Visit <code>http://localhost:5000/api/recommend/USER_ID</code></li>";
echo "<li><strong>Check browsing history:</strong> Users need to view properties first for personalized recommendations</li>";
echo "</ol>";

echo "<h3>To Generate Recommendations:</h3>";
echo "<pre>";
echo "# Option 1: Start AI service (if available)\n";
echo "cd ai_service\n";
echo "python app.py\n\n";
echo "# Option 2: Run batch recommendation script\n";
echo "Visit: http://localhost/HomeHub/generate_recommendations.php\n\n";
echo "# Option 3: Manually insert test data\n";
echo "INSERT INTO recommendation_cache (user_id, recommended_properties, algorithm_version, confidence_score, is_valid)\n";
echo "VALUES (1, '[1, 2, 3]', 'v1.0', 0.95, 1);\n";
echo "# Note: recommended_properties is a JSON array of property IDs\n";
echo "</pre>";
echo "</div>";

$conn->close();

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If tables are missing: Import the complete database schema</li>";
echo "<li>If tables are empty: Generate recommendations or add test data</li>";
echo "<li>If AI features page is blank: Check browser console (F12) for JavaScript errors</li>";
echo "<li>Check: <a href='ai-features.php'>ai-features.php</a> to see what's displayed</li>";
echo "</ul>";

echo "</body></html>";
?>
