<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../../config/db_connect.php';

try {
    $conn = getDbConnection();
    
    $stats = [
        'total_users' => 0,
        'total_properties' => 0,
        'active_bookings' => 0
    ];
    
    // Count total users - with error handling
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $stats['total_users'] = (int)$result->fetch_assoc()['count'];
        }
    } catch (Exception $e) {
        error_log("Error counting users: " . $e->getMessage());
        // Keep default value of 0
    }
    
    // Count properties - check what status values actually exist
    try {
        // First, check if status column exists
        $statusExists = false;
        $result = $conn->query("SHOW COLUMNS FROM properties LIKE 'status'");
        if ($result && $result->num_rows > 0) {
            $statusExists = true;
        }
        
        if ($statusExists) {
            // Try different possible status values for "active" properties
            $possibleActiveStatuses = ['active', 'available', 'published', 'approved', 'enabled'];
            $totalActive = 0;
            
            foreach ($possibleActiveStatuses as $status) {
                $result = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status = '$status'");
                if ($result) {
                    $count = (int)$result->fetch_assoc()['count'];
                    $totalActive += $count;
                }
            }
            
            // If no properties found with common active statuses, count all properties
            if ($totalActive == 0) {
                $result = $conn->query("SELECT COUNT(*) as count FROM properties");
                if ($result) {
                    $stats['total_properties'] = (int)$result->fetch_assoc()['count'];
                }
            } else {
                $stats['total_properties'] = $totalActive;
            }
        } else {
            // No status column, count all properties
            $result = $conn->query("SELECT COUNT(*) as count FROM properties");
            if ($result) {
                $stats['total_properties'] = (int)$result->fetch_assoc()['count'];
            }
        }
    } catch (Exception $e) {
        error_log("Error counting properties: " . $e->getMessage());
        // Fallback: count all properties
        try {
            $result = $conn->query("SELECT COUNT(*) as count FROM properties");
            if ($result) {
                $stats['total_properties'] = (int)$result->fetch_assoc()['count'];
            }
        } catch (Exception $e2) {
            error_log("Error counting all properties: " . $e2->getMessage());
        }
    }
    
    // Count bookings - try confirmed first, then all
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'");
        if ($result) {
            $stats['active_bookings'] = (int)$result->fetch_assoc()['count'];
        } else {
            // Fallback: count all bookings if status column doesn't exist
            $result = $conn->query("SELECT COUNT(*) as count FROM bookings");
            if ($result) {
                $stats['active_bookings'] = (int)$result->fetch_assoc()['count'];
            }
        }
    } catch (Exception $e) {
        error_log("Error counting bookings: " . $e->getMessage());
        // Try without status filter
        try {
            $result = $conn->query("SELECT COUNT(*) as count FROM bookings");
            if ($result) {
                $stats['active_bookings'] = (int)$result->fetch_assoc()['count'];
            }
        } catch (Exception $e2) {
            error_log("Error counting all bookings: " . $e2->getMessage());
        }
    }
    
    // Add timestamp for freshness
    $stats['updated_at'] = date('Y-m-d H:i:s');
    $stats['server_time'] = time();
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Stats API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
}
?>