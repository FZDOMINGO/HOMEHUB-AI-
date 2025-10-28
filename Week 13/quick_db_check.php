<?php
// Quick Database Check
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Database Status</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.status { background: white; padding: 20px; border-radius: 8px; margin: 10px 0; }
.success { border-left: 5px solid #4CAF50; }
.error { border-left: 5px solid #f44336; }
.warning { border-left: 5px solid #ff9800; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
th { background: #f9f9f9; }
.count { font-size: 24px; font-weight: bold; color: #2196F3; }
h1 { color: #333; }
h2 { color: #666; }
</style></head><body>";

echo "<h1>🗄️ Database Status Check</h1>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// 1. Test Connection
echo "<div class='status'>";
echo "<h2>1. Database Connection</h2>";
try {
    $conn = new mysqli('localhost', 'root', '', 'homehub');
    
    if ($conn->connect_error) {
        echo "<div class='status error'>";
        echo "❌ <strong>FAILED:</strong> " . $conn->connect_error;
        echo "</div></div></body></html>";
        exit;
    }
    
    echo "<div class='status success'>";
    echo "✅ <strong>CONNECTED</strong><br>";
    echo "Server: " . $conn->server_info . "<br>";
    echo "Host: " . $conn->host_info;
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='status error'>";
    echo "❌ <strong>ERROR:</strong> " . $e->getMessage();
    echo "</div></div></body></html>";
    exit;
}

// 2. List All Tables
echo "<h2>2. All Tables</h2>";
$result = $conn->query("SHOW TABLES");
$tables = [];
echo "<table>";
echo "<tr><th>#</th><th>Table Name</th><th>Row Count</th><th>Status</th></tr>";
$i = 1;
while ($row = $result->fetch_array()) {
    $tableName = $row[0];
    $tables[] = $tableName;
    
    $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
    $count = $countResult->fetch_assoc()['count'];
    
    $status = $count > 0 ? "✅ Has Data" : "⚠️ Empty";
    $color = $count > 0 ? "green" : "orange";
    
    echo "<tr>";
    echo "<td>$i</td>";
    echo "<td><strong>$tableName</strong></td>";
    echo "<td class='count' style='color:$color'>$count</td>";
    echo "<td style='color:$color'>$status</td>";
    echo "</tr>";
    $i++;
}
echo "</table>";
echo "<p><strong>Total Tables:</strong> " . count($tables) . "</p>";

// 3. Check Critical Tables
echo "<h2>3. Critical Tables Check</h2>";
$criticalTables = [
    'users' => 'User accounts',
    'tenants' => 'Tenant profiles',
    'landlords' => 'Landlord profiles',
    'properties' => 'Property listings',
    'saved_properties' => 'Saved properties',
    'browsing_history' => 'View history',
    'booking_visits' => 'Visit requests',
    'property_reservations' => 'Reservations',
    'notifications' => 'User notifications',
    'recommendation_cache' => 'AI recommendations',
    'tenant_preferences' => 'Tenant preferences'
];

echo "<table>";
echo "<tr><th>Table</th><th>Description</th><th>Status</th><th>Rows</th></tr>";

$allCriticalExist = true;
foreach ($criticalTables as $table => $description) {
    if (in_array($table, $tables)) {
        $countResult = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $countResult->fetch_assoc()['count'];
        $status = "✅ EXISTS";
        $color = "green";
    } else {
        $count = 0;
        $status = "❌ MISSING";
        $color = "red";
        $allCriticalExist = false;
    }
    
    echo "<tr>";
    echo "<td><strong>$table</strong></td>";
    echo "<td>$description</td>";
    echo "<td style='color:$color'>$status</td>";
    echo "<td class='count' style='color:$color'>$count</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Sample Data Check
echo "<h2>4. Sample Data</h2>";

// Check users
$result = $conn->query("SELECT COUNT(*) as count, 
    SUM(CASE WHEN role = 'tenant' THEN 1 ELSE 0 END) as tenants,
    SUM(CASE WHEN role = 'landlord' THEN 1 ELSE 0 END) as landlords,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins
    FROM users");
$users = $result->fetch_assoc();

echo "<table>";
echo "<tr><th>Category</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Total Users</td><td class='count'>" . $users['count'] . "</td><td>" . ($users['count'] > 0 ? "✅" : "⚠️") . "</td></tr>";
echo "<tr><td>Tenants</td><td class='count'>" . $users['tenants'] . "</td><td>" . ($users['tenants'] > 0 ? "✅" : "⚠️") . "</td></tr>";
echo "<tr><td>Landlords</td><td class='count'>" . $users['landlords'] . "</td><td>" . ($users['landlords'] > 0 ? "✅" : "⚠️") . "</td></tr>";
echo "<tr><td>Admins</td><td class='count'>" . $users['admins'] . "</td><td>" . ($users['admins'] > 0 ? "✅" : "⚠️") . "</td></tr>";
echo "</table>";

// Check properties
$result = $conn->query("SELECT COUNT(*) as total,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
    SUM(CASE WHEN status = 'rented' THEN 1 ELSE 0 END) as rented
    FROM properties");
$properties = $result->fetch_assoc();

echo "<table>";
echo "<tr><th>Property Status</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Total Properties</td><td class='count'>" . $properties['total'] . "</td><td>" . ($properties['total'] > 0 ? "✅" : "⚠️") . "</td></tr>";
echo "<tr><td>Available</td><td class='count'>" . $properties['available'] . "</td><td>" . ($properties['available'] > 0 ? "✅" : "⚠️") . "</td></tr>";
echo "<tr><td>Rented</td><td class='count'>" . $properties['rented'] . "</td><td>ℹ️</td></tr>";
echo "</table>";

// Check AI data
$result = $conn->query("SELECT 
    (SELECT COUNT(*) FROM browsing_history) as history,
    (SELECT COUNT(*) FROM recommendation_cache) as recommendations,
    (SELECT COUNT(*) FROM saved_properties) as saved,
    (SELECT COUNT(*) FROM tenant_preferences) as preferences");
$ai_data = $result->fetch_assoc();

echo "<table>";
echo "<tr><th>AI Feature Data</th><th>Count</th><th>Status</th></tr>";
echo "<tr><td>Browsing History</td><td class='count'>" . $ai_data['history'] . "</td><td>" . ($ai_data['history'] > 0 ? "✅" : "⚠️ Empty") . "</td></tr>";
echo "<tr><td>AI Recommendations</td><td class='count'>" . $ai_data['recommendations'] . "</td><td>" . ($ai_data['recommendations'] > 0 ? "✅" : "⚠️ Empty") . "</td></tr>";
echo "<tr><td>Saved Properties</td><td class='count'>" . $ai_data['saved'] . "</td><td>" . ($ai_data['saved'] > 0 ? "✅" : "⚠️ Empty") . "</td></tr>";
echo "<tr><td>Tenant Preferences</td><td class='count'>" . $ai_data['preferences'] . "</td><td>" . ($ai_data['preferences'] > 0 ? "✅" : "⚠️ Empty") . "</td></tr>";
echo "</table>";

// 5. Overall Status
echo "<h2>5. Overall Database Status</h2>";
echo "<div class='status success'>";
echo "<h3>✅ DATABASE IS WORKING!</h3>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>✅ Database connection: <strong>SUCCESS</strong></li>";
echo "<li>✅ Total tables: <strong>" . count($tables) . "</strong></li>";
echo "<li>" . ($allCriticalExist ? "✅" : "❌") . " Critical tables: <strong>" . ($allCriticalExist ? "ALL PRESENT" : "MISSING SOME") . "</strong></li>";
echo "<li>✅ User accounts: <strong>" . $users['count'] . "</strong></li>";
echo "<li>✅ Properties: <strong>" . $properties['total'] . "</strong></li>";
echo "<li>" . ($ai_data['recommendations'] > 0 ? "✅" : "⚠️") . " AI Recommendations: <strong>" . $ai_data['recommendations'] . "</strong></li>";
echo "</ul>";

if ($ai_data['recommendations'] == 0) {
    echo "<div class='status warning'>";
    echo "<h4>⚠️ Recommendation Cache is Empty</h4>";
    echo "<p>AI recommendations won't show until you generate them.</p>";
    echo "<p><strong>To fix:</strong> Run <a href='generate_ai_recommendations.php' style='color:#2196F3'>generate_ai_recommendations.php</a></p>";
    echo "</div>";
}

echo "</div>";

$conn->close();
echo "</div></body></html>";
?>
