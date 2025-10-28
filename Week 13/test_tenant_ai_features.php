<?php
/**
 * Test AI Features for Tenant Users
 * This page helps debug AI matching and recommendations
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

initSession();

echo "<h2>AI Features Tenant Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
    h3 { margin-top: 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
    button { padding: 10px 20px; margin: 5px; cursor: pointer; }
</style>";

// Check session
echo "<div class='section'>";
echo "<h3>1. Session Check</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✓ User is logged in</p>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>User Type: " . ($_SESSION['user_type'] ?? 'Not set') . "</p>";
    echo "<p>User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "</p>";
    
    if ($_SESSION['user_type'] !== 'tenant') {
        echo "<p class='error'>✗ ERROR: User type is not 'tenant'! AI features require tenant login.</p>";
        echo "<p class='info'>Please log in as a tenant to test AI features.</p>";
    }
} else {
    echo "<p class='error'>✗ User is not logged in</p>";
    echo "<p class='info'>Please <a href='login/'>login as a tenant</a> first.</p>";
}
echo "</div>";

// Only continue if logged in as tenant
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'tenant') {
    $conn = getDbConnection();
    $userId = $_SESSION['user_id'];
    
    // Check tenant profile
    echo "<div class='section'>";
    echo "<h3>2. Tenant Profile Check</h3>";
    $stmt = $conn->prepare("SELECT * FROM tenants WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $tenant = $result->fetch_assoc();
        echo "<p class='success'>✓ Tenant profile found</p>";
        echo "<p>Tenant ID: " . $tenant['id'] . "</p>";
        $tenantId = $tenant['id'];
    } else {
        echo "<p class='error'>✗ Tenant profile not found in database</p>";
        echo "<p class='info'>A tenant profile should be created automatically during registration.</p>";
        exit;
    }
    echo "</div>";
    
    // Check tenant preferences
    echo "<div class='section'>";
    echo "<h3>3. Tenant Preferences Check</h3>";
    $stmt = $conn->prepare("SELECT * FROM tenant_preferences WHERE tenant_id = ?");
    $stmt->bind_param("i", $tenantId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $preferences = $result->fetch_assoc();
        echo "<p class='success'>✓ Preferences found</p>";
        echo "<pre>";
        echo "Min Rent: ₱" . number_format($preferences['min_rent']) . "\n";
        echo "Max Rent: ₱" . number_format($preferences['max_rent']) . "\n";
        echo "Bedrooms: " . $preferences['bedrooms'] . "\n";
        echo "Bathrooms: " . $preferences['bathrooms'] . "\n";
        echo "Property Type: " . $preferences['property_type'] . "\n";
        echo "Location: " . $preferences['location_preference'] . "\n";
        echo "</pre>";
    } else {
        echo "<p class='error'>✗ No preferences set</p>";
        echo "<p class='info'>You need to set preferences first: <a href='tenant/setup-preferences.php'>Setup Preferences</a></p>";
    }
    echo "</div>";
    
    // Check available properties
    echo "<div class='section'>";
    echo "<h3>4. Available Properties Check</h3>";
    $result = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status = 'available'");
    $row = $result->fetch_assoc();
    $propertyCount = $row['count'];
    
    if ($propertyCount > 0) {
        echo "<p class='success'>✓ Found $propertyCount available properties</p>";
    } else {
        echo "<p class='error'>✗ No available properties in database</p>";
        echo "<p class='info'>AI features need properties to match against.</p>";
    }
    echo "</div>";
    
    // Check browsing history
    echo "<div class='section'>";
    echo "<h3>5. Browsing History Check</h3>";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM browsing_history WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $historyCount = $row['count'];
    
    if ($historyCount > 0) {
        echo "<p class='success'>✓ Found $historyCount browsing history entries</p>";
        echo "<p class='info'>This helps generate better recommendations</p>";
    } else {
        echo "<p class='info'>⚠ No browsing history yet</p>";
        echo "<p class='info'>Browse some properties to improve recommendations: <a href='properties.php'>Browse Properties</a></p>";
    }
    echo "</div>";
    
    // Test AI Matching API
    echo "<div class='section'>";
    echo "<h3>6. Test AI Matching API</h3>";
    echo "<button onclick='testMatching()'>Test AI Matching</button>";
    echo "<div id='matching-result'></div>";
    echo "</div>";
    
    // Test Recommendations API
    echo "<div class='section'>";
    echo "<h3>7. Test Recommendations API</h3>";
    echo "<button onclick='testRecommendations()'>Test Recommendations</button>";
    echo "<div id='recommendations-result'></div>";
    echo "</div>";
}

?>

<script>
async function testMatching() {
    const resultDiv = document.getElementById('matching-result');
    resultDiv.innerHTML = '<p style="color: blue;">⏳ Loading...</p>';
    
    try {
        const response = await fetch('api/ai/get-matches.php', {
            credentials: 'same-origin'
        });
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            resultDiv.innerHTML = '<p style="color: red;">✗ Invalid JSON response</p><pre>' + text.substring(0, 500) + '</pre>';
            return;
        }
        
        if (data.success) {
            resultDiv.innerHTML = '<p style="color: green;">✓ API Success!</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        } else {
            resultDiv.innerHTML = '<p style="color: orange;">⚠ API returned an error</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<p style="color: red;">✗ Error: ' + error.message + '</p>';
        console.error(error);
    }
}

async function testRecommendations() {
    const resultDiv = document.getElementById('recommendations-result');
    resultDiv.innerHTML = '<p style="color: blue;">⏳ Loading...</p>';
    
    try {
        const response = await fetch('api/ai/get-recommendations.php', {
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = '<p style="color: green;">✓ API Success!</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        } else {
            resultDiv.innerHTML = '<p style="color: orange;">⚠ API returned an error</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<p style="color: red;">✗ Error: ' + error.message + '</p>';
        console.error(error);
    }
}
</script>

<div class="section">
    <h3>Instructions</h3>
    <ol>
        <li>Make sure you're logged in as a <strong>tenant</strong></li>
        <li>Set your preferences if you haven't: <a href="tenant/setup-preferences.php">Setup Preferences</a></li>
        <li>Browse some properties to build history: <a href="properties.php">Browse Properties</a></li>
        <li>Click the test buttons above to verify AI features</li>
        <li>Then try the main AI features page: <a href="ai-features.php">AI Features</a></li>
    </ol>
</div>
