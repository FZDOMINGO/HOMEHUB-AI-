<?php
/**
 * Add Missing user_type Column to Users Table
 * This is a critical fix for the production database
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
initSession();

echo "<h1>Add Missing user_type Column</h1>";
echo "<p><strong>Fixing critical database schema issue...</strong></p>";

try {
    $conn = getDbConnection();
    
    // Step 1: Check if user_type column exists
    echo "<h2>1. Checking user_type Column</h2>";
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'user_type'");
    
    if ($result->num_rows > 0) {
        echo "<span style='color: green;'>✅ user_type column already exists</span><br>";
    } else {
        echo "<span style='color: orange;'>⚠️ user_type column is missing - adding it now...</span><br>";
        
        // Add the column
        $alterQuery = "ALTER TABLE users ADD COLUMN user_type ENUM('tenant', 'landlord') DEFAULT NULL AFTER email";
        
        if ($conn->query($alterQuery)) {
            echo "<span style='color: green;'>✅ Successfully added user_type column!</span><br>";
        } else {
            echo "<span style='color: red;'>❌ Failed to add column: " . $conn->error . "</span><br>";
            throw new Exception("Could not add user_type column");
        }
    }
    
    // Step 2: Populate user_type for all users
    echo "<h2>2. Populating user_type Values</h2>";
    
    // Get all users
    $usersResult = $conn->query("SELECT id, first_name, last_name, email, user_type FROM users");
    
    if ($usersResult) {
        $updated = 0;
        $skipped = 0;
        
        while ($user = $usersResult->fetch_assoc()) {
            $userId = $user['id'];
            $currentType = $user['user_type'];
            
            // Skip if already has a type
            if (!empty($currentType)) {
                echo "<span style='color: blue;'>ℹ️ User {$userId} ({$user['first_name']}) already has type: {$currentType}</span><br>";
                $skipped++;
                continue;
            }
            
            // Check tenant profile
            $tenantCheck = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
            $tenantCheck->bind_param("i", $userId);
            $tenantCheck->execute();
            $tenantResult = $tenantCheck->get_result();
            
            // Check landlord profile
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
                    echo "<span style='color: green;'>✅ Updated User {$userId} ({$user['first_name']}): Set as {$userType}</span><br>";
                    $updated++;
                    
                    // Update session if this is the current user
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                        $_SESSION['user_type'] = $userType;
                        echo "<span style='color: blue;'>  ↳ Updated current session to {$userType}</span><br>";
                    }
                } else {
                    echo "<span style='color: red;'>❌ Failed to update User {$userId}</span><br>";
                }
            } else {
                echo "<span style='color: orange;'>⚠️ User {$userId} ({$user['first_name']}): No profile found - cannot determine type</span><br>";
            }
        }
        
        echo "<p><strong>Summary:</strong> Updated {$updated} users, skipped {$skipped} users</p>";
    }
    
    // Step 3: Verify the fix
    echo "<h2>3. Verification</h2>";
    
    $verifyResult = $conn->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
    echo "<strong>User distribution by type:</strong><br>";
    
    while ($row = $verifyResult->fetch_assoc()) {
        $type = $row['user_type'] ?: 'NULL';
        echo "- {$type}: {$row['count']} users<br>";
    }
    
    // Step 4: Test AI authorization again
    echo "<h2>4. Testing AI Authorization</h2>";
    
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        // Get fresh user data from database
        $stmt = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $dbUserType = $user['user_type'];
            $sessionUserType = $_SESSION['user_type'] ?? 'NOT SET';
            
            echo "<strong>Current user (ID: {$userId}):</strong><br>";
            echo "- Session user_type: {$sessionUserType}<br>";
            echo "- Database user_type: {$dbUserType}<br>";
            
            if ($dbUserType === $sessionUserType) {
                echo "<span style='color: green;'>✅ Session and database match!</span><br>";
                
                if ($dbUserType === 'tenant') {
                    echo "<span style='color: green;'>✅ User is a tenant - AI features should work</span><br>";
                } else {
                    echo "<span style='color: blue;'>ℹ️ User is a {$dbUserType} - AI tenant features won't be accessible</span><br>";
                }
            } else {
                echo "<span style='color: orange;'>⚠️ Session and database don't match - session may need refresh</span><br>";
                
                // Update session to match database
                if ($dbUserType) {
                    $_SESSION['user_type'] = $dbUserType;
                    echo "<span style='color: green;'>✅ Updated session to match database: {$dbUserType}</span><br>";
                }
            }
        }
    }
    
    $conn->close();
    
    echo "<hr>";
    echo "<div style='background: #e6ffe6; border: 2px solid green; padding: 15px; margin: 20px 0;'>";
    echo "<h2 style='color: green; margin-top: 0;'>✅ Database Schema Fixed!</h2>";
    echo "<p><strong>What was fixed:</strong></p>";
    echo "<ul>";
    echo "<li>Added missing user_type column to users table</li>";
    echo "<li>Populated user_type for all users based on their profiles</li>";
    echo "<li>Synchronized session data with database</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test the AI features page: <a href='/ai-features.php'>AI Features</a></li>";
    echo "<li>Try clicking on 'Try AI Matching' or 'Get Recommendations'</li>";
    echo "<li>Check if the features now work without errors</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; border: 2px solid red; padding: 15px;'>";
    echo "<h3 style='color: red;'>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p><strong style='color: red;'>IMPORTANT: Delete this file after use for security!</strong></p>";
?>