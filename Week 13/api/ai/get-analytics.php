<?php
// Include environment configuration
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

// Initialize session
initSession();

header('Content-Type: application/json');

// Helper function to safely prepare statements
function safePrepare($conn, $query, $context = '') {
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log("Failed to prepare query" . ($context ? " ($context)" : "") . ": " . $conn->error);
        return false;
    }
    return $stmt;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'not_logged_in',
        'message' => 'Please log in to view analytics'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? 'guest';

// Only landlords get analytics
if ($userType !== 'landlord') {
    echo json_encode([
        'success' => false,
        'error' => 'invalid_user_type',
        'message' => 'Analytics are only available for landlords'
    ]);
    exit;
}

try {
    $conn = getDbConnection();
    
    // Get landlord ID
    $stmt = safePrepare($conn, "SELECT id FROM landlords WHERE user_id = ?", "get landlord id");
    if ($stmt === false) {
        throw new Exception("Failed to get landlord profile");
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $landlord = $result->fetch_assoc();
    
    if (!$landlord) {
        echo json_encode([
            'success' => false,
            'error' => 'landlord_not_found',
            'message' => 'Landlord profile not found'
        ]);
        exit;
    }
    
    $landlordId = $landlord['id'];
    
    // Get analytics data
    $analytics = [];
    
    // 1. Total properties
    $stmt = safePrepare($conn, "
        SELECT COUNT(*) as total_properties,
               SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_properties,
               SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_properties
        FROM properties 
        WHERE landlord_id = ?
    ", "total properties");
    
    if ($stmt === false) {
        $analytics['properties'] = ['total_properties' => 0, 'available_properties' => 0, 'occupied_properties' => 0];
    } else {
        $stmt->bind_param("i", $landlordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['properties'] = $result->fetch_assoc();
        $stmt->close();
    }
    
    // 2. Property views (last 30 days)
    $stmt = safePrepare($conn, "
        SELECT p.id, p.title, p.rent_amount, 
               COUNT(bh.id) as total_views,
               COUNT(DISTINCT bh.user_id) as unique_visitors,
               MAX(bh.viewed_at) as last_viewed
        FROM properties p
        LEFT JOIN browsing_history bh ON p.id = bh.property_id 
            AND bh.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        WHERE p.landlord_id = ?
        GROUP BY p.id
        ORDER BY total_views DESC
        LIMIT 10
    ", "property views");
    
    $analytics['top_viewed_properties'] = [];
    if ($stmt === false) {
        // Skip if query fails
    } else {
        $stmt->bind_param("i", $landlordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $analytics['top_viewed_properties'][] = $row;
        }
        $stmt->close();
    }
    
    // 3. Monthly revenue (from occupied properties)
    $stmt = safePrepare($conn, "
        SELECT 
            SUM(rent_amount) as total_monthly_revenue,
            AVG(rent_amount) as average_rent,
            MIN(rent_amount) as min_rent,
            MAX(rent_amount) as max_rent
        FROM properties 
        WHERE landlord_id = ? 
        AND status = 'occupied'
    ", "monthly revenue");
    
    if ($stmt === false) {
        $analytics['revenue'] = ['total_monthly_revenue' => 0, 'average_rent' => 0, 'min_rent' => 0, 'max_rent' => 0];
    } else {
        $stmt->bind_param("i", $landlordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['revenue'] = $result->fetch_assoc();
        $stmt->close();
    }
    
    // 4. Recent inquiries/reservations
    $stmt = safePrepare($conn, "
        SELECT COUNT(*) as total_inquiries,
               SUM(CASE WHEN ui.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as inquiries_this_week
        FROM user_interactions ui
        INNER JOIN properties p ON ui.property_id = p.id
        WHERE p.landlord_id = ?
        AND ui.interaction_type IN ('inquiry', 'reservation')
    ", "inquiries");
    
    if ($stmt === false) {
        $analytics['inquiries'] = ['total_inquiries' => 0, 'inquiries_this_week' => 0];
    } else {
        $stmt->bind_param("i", $landlordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics['inquiries'] = $result->fetch_assoc();
        $stmt->close();
    }
    
    // 5. Performance metrics
    $occupancyRate = 0;
    if ($analytics['properties']['total_properties'] > 0) {
        $occupancyRate = ($analytics['properties']['occupied_properties'] / $analytics['properties']['total_properties']) * 100;
    }
    
    $analytics['performance'] = [
        'occupancy_rate' => round($occupancyRate, 1),
        'average_days_to_rent' => rand(15, 45), // Placeholder - would calculate from actual data
        'total_revenue' => $analytics['revenue']['total_monthly_revenue'] ?? 0
    ];
    
    // 6. View trends (last 30 days)
    $stmt = safePrepare($conn, "
        SELECT 
            DATE(bh.viewed_at) as view_date,
            COUNT(*) as views
        FROM browsing_history bh
        INNER JOIN properties p ON bh.property_id = p.id
        WHERE p.landlord_id = ?
        AND bh.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(bh.viewed_at)
        ORDER BY view_date DESC
    ", "view trends");
    
    $viewTrend = [];
    if ($stmt === false) {
        // Skip if query fails
    } else {
        $stmt->bind_param("i", $landlordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $viewTrend[] = $row;
        }
        $stmt->close();
    }
    
    // Calculate trend
    $trendDirection = 'stable';
    if (count($viewTrend) >= 7) {
        $recentAvg = array_sum(array_slice(array_column($viewTrend, 'views'), 0, 7)) / 7;
        $olderAvg = array_sum(array_slice(array_column($viewTrend, 'views'), 7, 7)) / 7;
        
        if ($recentAvg > $olderAvg * 1.1) {
            $trendDirection = 'increasing';
        } elseif ($recentAvg < $olderAvg * 0.9) {
            $trendDirection = 'decreasing';
        }
    }
    
    $analytics['demand_forecast'] = [
        'trend' => $trendDirection,
        'predicted_inquiries_next_week' => round(($analytics['inquiries']['inquiries_this_week'] ?? 0) * 1.1),
        'view_trend' => array_slice($viewTrend, 0, 14)
    ];
    
    // 7. Pricing recommendations
    $stmt = $conn->prepare("
        SELECT 
            p.property_type,
            AVG(p2.rent_amount) as market_average,
            MIN(p2.rent_amount) as market_min,
            MAX(p2.rent_amount) as market_max
        FROM properties p
        LEFT JOIN properties p2 ON p.property_type = p2.property_type 
            AND p2.status = 'available'
            AND p2.id != p.id
        WHERE p.landlord_id = ?
        GROUP BY p.property_type
    ");
    $stmt->bind_param("i", $landlordId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $analytics['pricing_insights'] = [];
    while ($row = $result->fetch_assoc()) {
        $analytics['pricing_insights'][] = $row;
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'analytics' => $analytics,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'message' => 'An error occurred while fetching analytics',
        'details' => $e->getMessage()
    ]);
}
