<?php
require_once dirname(__DIR__) . '/config/db_connect.php';

try {
    $conn = getDbConnection();
    
    // Check if status column already exists
    $checkResult = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
    
    if ($checkResult->num_rows === 0) {
        echo "Adding status column to users table...\n";
        
        $sql = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive', 'suspended') DEFAULT 'active' AFTER phone";
        
        if ($conn->query($sql) === TRUE) {
            echo "✅ Status column added successfully!\n";
            
            // Update existing users to have 'active' status
            $updateSql = "UPDATE users SET status = 'active' WHERE status IS NULL OR status = ''";
            if ($conn->query($updateSql) === TRUE) {
                echo "✅ Existing users updated with active status!\n";
            }
        } else {
            echo "❌ Error adding status column: " . $conn->error . "\n";
        }
    } else {
        echo "✅ Status column already exists.\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>