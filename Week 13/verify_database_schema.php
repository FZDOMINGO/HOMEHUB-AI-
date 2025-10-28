<?php
/**
 * Database Schema Checker
 * Compares production schema with expected schema
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

echo "<h1>Database Schema Verification</h1>";
echo "<p>Checking for missing columns and schema issues...</p>";

try {
    $conn = getDbConnection();
    
    // Define expected schema
    $expectedSchema = [
        'users' => [
            'id', 'email', 'password', 'first_name', 'last_name', 
            'user_type', 'phone', 'created_at', 'updated_at'
        ],
        'tenants' => [
            'id', 'user_id', 'created_at'
        ],
        'landlords' => [
            'id', 'user_id', 'created_at'
        ],
        'properties' => [
            'id', 'landlord_id', 'title', 'description', 'address', 
            'city', 'state', 'zip_code', 'rent_amount', 'bedrooms', 
            'bathrooms', 'square_feet', 'property_type', 'status', 
            'created_at', 'updated_at'
        ],
        'tenant_preferences' => [
            'id', 'tenant_id', 'min_budget', 'max_budget', 
            'preferred_city', 'property_type', 'min_bedrooms', 
            'min_bathrooms', 'created_at', 'updated_at'
        ],
        'similarity_scores' => [
            'id', 'tenant_id', 'property_id', 'cosine_similarity', 
            'feature_breakdown', 'match_score', 'match_percentage', 
            'rank_for_tenant', 'calculated_at', 'is_valid'
        ],
        'browsing_history' => [
            'id', 'user_id', 'property_id', 'visited_at'
        ],
        'property_images' => [
            'id', 'property_id', 'image_url', 'is_primary', 'created_at'
        ]
    ];
    
    echo "<h2>Schema Comparison Results</h2>";
    
    $allGood = true;
    $missingColumns = [];
    
    foreach ($expectedSchema as $tableName => $expectedColumns) {
        echo "<h3>Table: {$tableName}</h3>";
        
        // Check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE '{$tableName}'");
        if (!$tableCheck || $tableCheck->num_rows === 0) {
            echo "<span style='color: red;'>❌ TABLE MISSING!</span><br>";
            $allGood = false;
            continue;
        }
        
        // Get actual columns
        $columnResult = $conn->query("DESCRIBE {$tableName}");
        $actualColumns = [];
        while ($col = $columnResult->fetch_assoc()) {
            $actualColumns[] = $col['Field'];
        }
        
        // Check for missing columns
        $missing = array_diff($expectedColumns, $actualColumns);
        $extra = array_diff($actualColumns, $expectedColumns);
        
        if (empty($missing) && empty($extra)) {
            echo "<span style='color: green;'>✅ Schema matches perfectly</span><br>";
        } else {
            if (!empty($missing)) {
                echo "<span style='color: red;'>❌ Missing columns: " . implode(', ', $missing) . "</span><br>";
                $missingColumns[$tableName] = $missing;
                $allGood = false;
            }
            if (!empty($extra)) {
                echo "<span style='color: blue;'>ℹ️ Extra columns: " . implode(', ', $extra) . "</span><br>";
            }
        }
        
        // Show record count
        $countResult = $conn->query("SELECT COUNT(*) as count FROM {$tableName}");
        $count = $countResult->fetch_assoc()['count'];
        echo "<span style='color: #666;'>Records: {$count}</span><br><br>";
    }
    
    // Generate fix SQL if needed
    if (!$allGood) {
        echo "<hr>";
        echo "<h2>Recommended Fixes</h2>";
        echo "<p>The following SQL commands should fix the schema issues:</p>";
        echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ccc;'>";
        
        foreach ($missingColumns as $tableName => $columns) {
            echo "-- Fix for table: {$tableName}\n";
            foreach ($columns as $column) {
                // Define appropriate column types
                $columnDef = match($column) {
                    'user_type' => "ENUM('tenant', 'landlord') DEFAULT NULL",
                    'phone' => "VARCHAR(20) DEFAULT NULL",
                    'cosine_similarity' => "DECIMAL(5,4) DEFAULT NULL",
                    'feature_breakdown' => "LONGTEXT DEFAULT NULL",
                    'match_score' => "DECIMAL(5,4) DEFAULT NULL",
                    'match_percentage' => "INT(11) DEFAULT NULL",
                    'rank_for_tenant' => "INT(11) DEFAULT NULL",
                    'is_valid' => "TINYINT(1) DEFAULT 1",
                    'is_primary' => "TINYINT(1) DEFAULT 0",
                    default => "VARCHAR(255) DEFAULT NULL"
                };
                
                echo "ALTER TABLE {$tableName} ADD COLUMN {$column} {$columnDef};\n";
            }
            echo "\n";
        }
        
        echo "</pre>";
        
        echo "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0;'>";
        echo "<h3>⚠️ Important</h3>";
        echo "<p>You have two options to fix the schema:</p>";
        echo "<ol>";
        echo "<li><strong>Run the SQL commands above</strong> in your database management tool (phpMyAdmin)</li>";
        echo "<li><strong>Import your complete local database</strong> to production to ensure all schema is correct</li>";
        echo "</ol>";
        echo "<p>Option 2 is recommended if you have many schema differences.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #e6ffe6; border: 2px solid green; padding: 15px;'>";
        echo "<h3 style='color: green;'>✅ All Good!</h3>";
        echo "<p>Your database schema matches the expected structure.</p>";
        echo "</div>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; border: 2px solid red; padding: 15px;'>";
    echo "<h3 style='color: red;'>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<p><strong>Delete this file after use for security!</strong></p>";
?>