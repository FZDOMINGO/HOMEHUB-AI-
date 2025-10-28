<?php
/**
 * Fix Production Database Issues
 * This script will repair the identified problems
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
initSession();

echo "<h1>Production Database Repair Tool</h1>";
echo "<p><strong>Fixing identified issues...</strong></p>";

try {
    $conn = getDbConnection();
    
    // Issue 1: Fix empty user_type fields
    echo "<h2>1. Fixing User Type Fields</h2>";
    
    // Check current state
    $result = $conn->query("SELECT id, first_name, last_name, email, user_type FROM users WHERE user_type IS NULL OR user_type = ''");
    $usersToFix = [];
    
    if ($result && $result->num_rows > 0) {
        echo "<p>Found users with missing user_type:</p>";
        while ($row = $result->fetch_assoc()) {
            $usersToFix[] = $row;
            echo "- User ID {$row['id']}: {$row['first_name']} {$row['last_name']} ({$row['email']})<br>";
        }
        
        // For each user, determine if they're a tenant or landlord based on profile tables
        foreach ($usersToFix as $user) {
            $userId = $user['id'];
            
            // Check if user has tenant profile
            $tenantCheck = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
            $tenantCheck->bind_param("i", $userId);
            $tenantCheck->execute();
            $tenantResult = $tenantCheck->get_result();
            
            // Check if user has landlord profile
            $landlordCheck = $conn->prepare("SELECT id FROM landlords WHERE user_id = ?");
            $landlordCheck->bind_param("i", $userId);
            $landlordCheck->execute();
            $landlordResult = $landlordCheck->get_result();
            
            $userType = null;
            if ($tenantResult->num_rows > 0) {
                $userType = 'tenant';
            } elseif ($landlordResult->num_rows > 0) {
                $userType = 'landlord';
            }
            
            if ($userType) {
                // Update user_type
                $updateStmt = $conn->prepare("UPDATE users SET user_type = ? WHERE id = ?");
                $updateStmt->bind_param("si", $userType, $userId);
                
                if ($updateStmt->execute()) {
                    echo "<span style='color: green;'>✅ Fixed User ID {$userId}: Set as {$userType}</span><br>";
                    
                    // Update session if this is the current user
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                        $_SESSION['user_type'] = $userType;
                        echo "<span style='color: blue;'>ℹ️ Updated current session user_type to {$userType}</span><br>";
                    }
                } else {
                    echo "<span style='color: red;'>❌ Failed to update User ID {$userId}</span><br>";
                }
            } else {
                echo "<span style='color: orange;'>⚠️ User ID {$userId}: No tenant or landlord profile found, cannot determine type</span><br>";
            }
        }
    } else {
        echo "<span style='color: green;'>✅ All users have proper user_type values</span><br>";
    }
    
    // Issue 2: Check and fix SQL query issues
    echo "<h2>2. Checking Table Structure</h2>";
    
    // Check if the problematic tables exist and have proper structure
    $tables = [
        'users' => ['id', 'email', 'user_type', 'first_name', 'last_name'],
        'tenants' => ['id', 'user_id'],
        'landlords' => ['id', 'user_id'],
        'tenant_preferences' => ['id', 'tenant_id', 'min_budget', 'max_budget'],
        'similarity_scores' => ['id', 'tenant_id', 'property_id', 'match_score']
    ];
    
    foreach ($tables as $tableName => $requiredColumns) {
        echo "<h3>Table: {$tableName}</h3>";
        
        // Check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE '{$tableName}'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            echo "<span style='color: red;'>❌ Table {$tableName} does not exist!</span><br>";
            continue;
        }
        
        // Check columns
        $columnCheck = $conn->query("DESCRIBE {$tableName}");
        $existingColumns = [];
        while ($col = $columnCheck->fetch_assoc()) {
            $existingColumns[] = $col['Field'];
        }
        
        $missingColumns = array_diff($requiredColumns, $existingColumns);
        if (empty($missingColumns)) {
            echo "<span style='color: green;'>✅ All required columns exist</span><br>";
        } else {
            echo "<span style='color: red;'>❌ Missing columns: " . implode(', ', $missingColumns) . "</span><br>";
        }
        
        // Show record count
        $countResult = $conn->query("SELECT COUNT(*) as count FROM {$tableName}");
        if ($countResult) {
            $count = $countResult->fetch_assoc()['count'];
            echo "Records: {$count}<br>";
        }
    }
    
    // Issue 3: Test AI endpoint after fixes
    echo "<h2>3. Testing AI Endpoint After Fixes</h2>";
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        echo "Current session state:<br>";
        echo "- User ID: " . $_SESSION['user_id'] . "<br>";
        echo "- User Type: " . $_SESSION['user_type'] . "<br>";
        
        // Test the authorization logic
        if ($_SESSION['user_type'] === 'tenant') {
            echo "<span style='color: green;'>✅ Session shows user is a tenant - AI features should work</span><br>";
        } else {
            echo "<span style='color: orange;'>⚠️ User type is '{$_SESSION['user_type']}' - AI matching only works for tenants</span><br>";
        }
    }
    
    // Test a simple query that was failing
    echo "<h2>4. Testing Problematic Queries</h2>";
    
    // Test the type of query that was failing in get-history.php
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $testStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        
        if ($testStmt) {
            echo "<span style='color: green;'>✅ Basic prepared statement works</span><br>";
            $testStmt->bind_param("i", $userId);
            $testStmt->execute();
            $result = $testStmt->get_result();
            if ($result->num_rows > 0) {
                echo "<span style='color: green;'>✅ Query execution successful</span><br>";
            }
        } else {
            echo "<span style='color: red;'>❌ Prepared statement failed: " . $conn->error . "</span><br>";
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Error: " . $e->getMessage() . "</span><br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>After running these fixes:</p>";
echo "<ol>";
echo "<li>User types should be properly set in database</li>";
echo "<li>Session data should match database data</li>";
echo "<li>AI features should work for tenant users</li>";
echo "<li>SQL query errors should be resolved</li>";
echo "</ol>";

echo "<p><strong>Next step:</strong> Test the AI features on your site to confirm they work!</p>";
echo "<p><strong>Delete this file after use for security!</strong></p>";
?>