<?php
/**
 * AI Features Specific Debug Script
 * Tests the exact AI endpoints that are failing
 */

// Include required files
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
initSession();

header('Content-Type: text/html; charset=utf-8');

echo "<h1>AI Features Debug Test</h1>";
echo "<p><strong>Testing the exact endpoints that should work...</strong></p>";

// Test user authentication state
echo "<h2>1. User Authentication Status</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<span style='color: green;'>✅ User logged in</span><br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "User Type: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'NOT SET') . "<br>";
    echo "User Name: " . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'NOT SET') . "<br>";
} else {
    echo "<span style='color: red;'>❌ User not logged in</span><br>";
}

// Test database connection
echo "<h2>2. Database Connection Test</h2>";
try {
    $conn = getDbConnection();
    if ($conn) {
        echo "<span style='color: green;'>✅ Database connected</span><br>";
        
        // Check if user exists in database
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo "<span style='color: green;'>✅ User found in database</span><br>";
                echo "User Type: " . $user['user_type'] . "<br>";
                echo "Name: " . $user['first_name'] . ' ' . $user['last_name'] . "<br>";
                
                // Check tenant/landlord profile
                if ($user['user_type'] === 'tenant') {
                    $stmt = $conn->prepare("SELECT * FROM tenants WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $tenant = $result->fetch_assoc();
                        echo "<span style='color: green;'>✅ Tenant profile found</span><br>";
                        echo "Tenant ID: " . $tenant['id'] . "<br>";
                        
                        // Check preferences
                        $stmt = $conn->prepare("SELECT * FROM tenant_preferences WHERE tenant_id = ?");
                        $stmt->bind_param("i", $tenant['id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $prefs = $result->fetch_assoc();
                            echo "<span style='color: green;'>✅ Tenant preferences found</span><br>";
                            echo "Budget: $" . $prefs['min_budget'] . " - $" . $prefs['max_budget'] . "<br>";
                            echo "City: " . $prefs['preferred_city'] . "<br>";
                        } else {
                            echo "<span style='color: orange;'>⚠️ No tenant preferences set</span><br>";
                        }
                    } else {
                        echo "<span style='color: red;'>❌ Tenant profile not found</span><br>";
                    }
                } elseif ($user['user_type'] === 'landlord') {
                    echo "<span style='color: blue;'>ℹ️ User is a landlord</span><br>";
                }
            } else {
                echo "<span style='color: red;'>❌ User not found in database</span><br>";
            }
        }
    } else {
        echo "<span style='color: red;'>❌ Database connection failed</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Database error: " . $e->getMessage() . "</span><br>";
}

// Test AI endpoints directly
echo "<h2>3. Direct AI Endpoint Tests</h2>";

// Test 1: AI Matches endpoint
echo "<h3>Testing: /api/ai/get-matches.php</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://homehubai.shop/api/ai/get-matches.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, ''); // Use existing session
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Add session cookie if available
if (session_id()) {
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<span style='color: red;'>❌ cURL error: $error</span><br>";
} else {
    echo "HTTP Status: $httpCode<br>";
    if ($httpCode === 200) {
        echo "<span style='color: green;'>✅ API endpoint responding</span><br>";
        // Extract JSON from response
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        echo "<strong>Response:</strong><br>";
        echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow: auto;'>";
        echo htmlspecialchars($body);
        echo "</pre>";
    } else {
        echo "<span style='color: red;'>❌ API endpoint error (HTTP $httpCode)</span><br>";
        echo "<strong>Response:</strong><br>";
        echo "<pre style='background: #ffe6e6; padding: 10px; max-height: 300px; overflow: auto;'>";
        echo htmlspecialchars($response);
        echo "</pre>";
    }
}

// Test PHP error logs
echo "<h2>4. Recent PHP Errors</h2>";
$errorLogPath = __DIR__ . '/error_log.txt';
if (file_exists($errorLogPath)) {
    $errors = file_get_contents($errorLogPath);
    $lines = explode("\n", $errors);
    $recentErrors = array_slice($lines, -20); // Last 20 lines
    
    echo "<strong>Last 20 error log entries:</strong><br>";
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow: auto;'>";
    echo htmlspecialchars(implode("\n", $recentErrors));
    echo "</pre>";
} else {
    echo "<span style='color: orange;'>⚠️ Error log file not found at: $errorLogPath</span><br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>This script tests the AI features more specifically than the general health check.</p>";
echo "<p><strong>Delete this file after testing for security!</strong></p>";
?>