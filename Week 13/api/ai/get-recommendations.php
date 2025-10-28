<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

initSession();
header('Content-Type: application/json');

// Enhanced error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'error' => 'not_logged_in',
            'message' => 'Please log in to get recommendations'
        ]);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'guest';
    
    // Only tenants get recommendations
    if ($userType !== 'tenant') {
        echo json_encode([
            'success' => false,
            'error' => 'invalid_user_type',
            'message' => 'Recommendations are only available for tenants'
        ]);
        exit;
    }
    
    $conn = getDbConnection();
    
    // Get tenant ID
    $stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenant = $result->fetch_assoc();
    $stmt->close();
    
    if (!$tenant) {
        $conn->close();
        echo json_encode([
            'success' => false,
            'error' => 'tenant_not_found',
            'message' => 'Tenant profile not found'
        ]);
        exit;
    }
    
    $tenantId = $tenant['id'];
    
    // Get recommendations based on browsing history and user interactions
    $recommendations = [];
    
    // 1. Get properties similar to recently viewed ones (same property type)
    $query = "
        SELECT DISTINCT p.*, 
               'Recently Viewed Similar' as recommendation_reason,
               (SELECT image_url FROM property_images 
                WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p
        WHERE p.property_type IN (
            SELECT DISTINCT p2.property_type 
            FROM browsing_history bh2 
            INNER JOIN properties p2 ON bh2.property_id = p2.id
            WHERE bh2.user_id = ?
        )
        AND p.id NOT IN (
            SELECT property_id FROM browsing_history WHERE user_id = ?
        )
        AND p.status = 'available'
        ORDER BY p.created_at DESC
        LIMIT 5
    ";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $recommendations[] = $row;
        }
        $stmt->close();
    }
    
    // 2. Get popular properties in the same price range as viewed properties
    if (count($recommendations) < 10) {
        $query = "
            SELECT p.*, 
                   COUNT(DISTINCT bh.user_id) as popularity_score,
                   'Popular in Your Budget' as recommendation_reason,
                   (SELECT image_url FROM property_images 
                    WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM properties p
            LEFT JOIN browsing_history bh ON p.id = bh.property_id
            WHERE p.rent_amount BETWEEN (
                SELECT COALESCE(MIN(p2.rent_amount) * 0.8, 0)
                FROM browsing_history bh2
                INNER JOIN properties p2 ON bh2.property_id = p2.id
                WHERE bh2.user_id = ?
            ) AND (
                SELECT COALESCE(MAX(p2.rent_amount) * 1.2, 999999)
                FROM browsing_history bh2
                INNER JOIN properties p2 ON bh2.property_id = p2.id
                WHERE bh2.user_id = ?
            )
            AND p.id NOT IN (
                SELECT property_id FROM browsing_history WHERE user_id = ?
            )
            AND p.status = 'available'
            GROUP BY p.id
            ORDER BY popularity_score DESC, p.created_at DESC
            LIMIT ?
        ";
        
        $limit = 10 - count($recommendations);
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("iiii", $userId, $userId, $userId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $recommendations[] = $row;
            }
            $stmt->close();
        }
    }
    
    // 3. If still not enough, get newest properties
    if (count($recommendations) < 10) {
        $query = "
            SELECT p.*, 
                   'New Listing' as recommendation_reason,
                   (SELECT image_url FROM property_images 
                    WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM properties p
            WHERE p.id NOT IN (
                SELECT property_id FROM browsing_history WHERE user_id = ?
            )
            AND p.status = 'available'
            ORDER BY p.created_at DESC
            LIMIT ?
        ";
        
        $limit = 10 - count($recommendations);
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ii", $userId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $recommendations[] = $row;
            }
            $stmt->close();
        }
    }
    
    // 4. If still no recommendations, show previously viewed properties as "You Might Like"
    if (count($recommendations) == 0) {
        $query = "
            SELECT p.*, 
                   bh.viewed_at,
                   (SELECT image_url FROM property_images 
                    WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
                   CASE 
                       WHEN bh.saved = 1 THEN 'You Saved This'
                       WHEN bh.contact_clicked = 1 THEN 'You Showed Interest'
                       ELSE 'Based on Your Browsing'
                   END as recommendation_reason
            FROM properties p
            INNER JOIN browsing_history bh ON p.id = bh.property_id
            WHERE bh.user_id = ?
            AND p.status = 'available'
            GROUP BY p.id
            ORDER BY bh.saved DESC, bh.contact_clicked DESC, bh.viewed_at DESC
            LIMIT 10
        ";
        
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $recommendations[] = $row;
            }
            $stmt->close();
        }
    }

    // Cache recommendations
    if (count($recommendations) > 0) {
        $cacheData = json_encode(array_map(function($rec) {
            return $rec['id'];
        }, $recommendations));
        
        $interactionCount = count($recommendations);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $stmt = $conn->prepare("
            INSERT INTO recommendation_cache (user_id, recommended_properties, algorithm_version, based_on_interactions, expires_at, is_valid, created_at)
            VALUES (?, ?, 'v1.0', ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE 
                recommended_properties = VALUES(recommended_properties),
                algorithm_version = VALUES(algorithm_version),
                based_on_interactions = VALUES(based_on_interactions),
                expires_at = VALUES(expires_at),
                created_at = NOW()
        ");
        
        if ($stmt) {
            $stmt->bind_param("isis", $userId, $cacheData, $interactionCount, $expiresAt);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'count' => count($recommendations)
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Recommendations Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'message' => 'An error occurred while fetching recommendations'
    ]);
}
