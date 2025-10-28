<?php
/**
 * HomeHub Database Connection
 * 
 * Unified database connection that works on both
 * localhost and production (Hostinger)
 * 
 * Uses environment configuration from env.php
 */

require_once __DIR__ . '/env.php';

/**
 * Get database connection
 * @return mysqli Database connection
 * @throws Exception if connection fails
 */
function getDbConnection() {
    static $conn = null;
    
    // Check if connection exists and is valid
    if ($conn !== null && $conn instanceof mysqli) {
        // Check if connection is still alive by checking thread_id
        // A closed connection will have thread_id = 0 or accessing it will fail
        try {
            $threadId = @$conn->thread_id;
            if ($threadId && @$conn->ping()) {
                return $conn;
            }
        } catch (Throwable $e) {
            // Connection is dead or closed, create new one
        }
        // If we get here, connection is invalid
        $conn = null;
    }
    
    try {
        // Create new connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check for connection errors
        if ($conn->connect_error) {
            $error = "Database connection failed: " . $conn->connect_error;
            logDebug($error);
            
            // In production, don't expose database details
            if (IS_PRODUCTION) {
                throw new Exception("Database connection failed. Please contact support.");
            } else {
                throw new Exception($error);
            }
        }
        
        // Set charset to UTF-8
        if (!$conn->set_charset("utf8mb4")) {
            logDebug("Error setting charset: " . $conn->error);
        }
        
        logDebug("Database connected successfully", [
            'host' => DB_HOST,
            'database' => DB_NAME
        ]);
        
        return $conn;
        
    } catch (Exception $e) {
        logDebug("Database connection exception: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Close database connection
 */
function closeDbConnection() {
    global $conn;
    if ($conn !== null) {
        // Check if connection is still valid before trying to close
        try {
            if ($conn instanceof mysqli) {
                @$conn->close(); // Suppress warnings if already closed
                logDebug("Database connection closed");
            }
        } catch (Exception $e) {
            // Connection already closed, ignore
            logDebug("Connection already closed or invalid: " . $e->getMessage());
        } finally {
            $conn = null;
        }
    }
}

/**
 * Execute a prepared statement safely
 * @param mysqli $conn Database connection
 * @param string $query SQL query with placeholders
 * @param string $types Parameter types (e.g., "ssi" for string, string, int)
 * @param array $params Parameters to bind
 * @return mysqli_result|bool Query result
 */
function executeQuery($conn, $query, $types = "", $params = []) {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        logDebug("Prepare failed: " . $conn->error, ['query' => $query]);
        throw new Exception("Query preparation failed");
    }
    
    if ($types && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        logDebug("Execute failed: " . $stmt->error, ['query' => $query]);
        $stmt->close();
        throw new Exception("Query execution failed");
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result;
}

/**
 * Fetch single row
 * @param mysqli $conn Database connection
 * @param string $query SQL query
 * @param string $types Parameter types
 * @param array $params Parameters
 * @return array|null Single row as associative array
 */
function fetchOne($conn, $query, $types = "", $params = []) {
    $result = executeQuery($conn, $query, $types, $params);
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Fetch all rows
 * @param mysqli $conn Database connection
 * @param string $query SQL query
 * @param string $types Parameter types
 * @param array $params Parameters
 * @return array Array of rows
 */
function fetchAll($conn, $query, $types = "", $params = []) {
    $result = executeQuery($conn, $query, $types, $params);
    $rows = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

/**
 * Check if database tables exist
 * @return array Array of missing tables
 */
function checkRequiredTables() {
    $required = [
        'users',
        'tenants',
        'landlords',
        'properties',
        'property_images',
        'tenant_preferences',
        'similarity_scores',
        'browsing_history',
        'property_reservations',
        'booking_visits',
        'saved_properties',
        'notifications',
        'recommendation_cache'
    ];
    
    $missing = [];
    
    try {
        $conn = getDbConnection();
        
        foreach ($required as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows === 0) {
                $missing[] = $table;
            }
        }
        
    } catch (Exception $e) {
        logDebug("Error checking tables: " . $e->getMessage());
    }
    
    return $missing;
}

// Note: Connection cleanup is handled by PHP's garbage collection
// Manual cleanup with closeDbConnection() is available if needed
?>
