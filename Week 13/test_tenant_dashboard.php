<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "=== TENANT DASHBOARD SYSTEM TEST ===\n\n";

$userId = 1; // Tenant Profile user

// 1. Get Tenant ID
$stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();
$tenantId = $tenant ? $tenant['id'] : 0;

echo "User ID: $userId\n";
echo "Tenant ID: $tenantId\n\n";

if ($tenantId > 0) {
    // 2. Test Saved Properties Count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM saved_properties WHERE tenant_id = ?");
    $stmt->bind_param("i", $tenantId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $savedCount = $result['count'];
    
    echo "✓ Saved Properties: $savedCount\n";
    
    // 3. Test Scheduled Visits Count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM booking_visits WHERE tenant_id = ? AND status IN ('pending', 'approved') AND visit_date >= CURDATE()");
    $stmt->bind_param("i", $tenantId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $visitsCount = $result['count'];
    
    echo "✓ Scheduled Visits: $visitsCount\n";
    
    // 4. Test Properties Viewed Count
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT property_id) as count FROM browsing_history WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $viewedCount = $result['count'];
    
    echo "✓ Properties Viewed: $viewedCount\n";
    
    // 5. Test AI Recommendations Count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM recommendation_cache WHERE user_id = ? AND is_valid = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $aiCount = $result['count'];
    
    echo "✓ AI Recommendations: $aiCount\n\n";
    
    // 6. Test Notifications Count
    $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread FROM notifications WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $totalNotifications = $result['total'];
    $unreadNotifications = $result['unread'];
    
    echo "✓ Total Notifications: $totalNotifications\n";
    echo "✓ Unread Notifications: $unreadNotifications\n\n";
    
    // 7. List Recent Saved Properties
    echo "=== RECENT SAVED PROPERTIES ===\n";
    $stmt = $conn->prepare("
        SELECT p.id, p.title, p.rent_amount, sp.saved_at
        FROM saved_properties sp
        JOIN properties p ON sp.property_id = p.id
        WHERE sp.tenant_id = ?
        ORDER BY sp.saved_at DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $tenantId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['title'] . " ($" . number_format($row['rent_amount'], 2) . ") - Saved: " . $row['saved_at'] . "\n";
        }
    } else {
        echo "No saved properties.\n";
    }
    
    echo "\n=== RECENT NOTIFICATIONS ===\n";
    $stmt = $conn->prepare("SELECT type, content, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $status = $row['is_read'] ? '[READ]' : '[UNREAD]';
            echo "$status " . $row['content'] . " (" . $row['created_at'] . ")\n";
        }
    } else {
        echo "No notifications.\n";
    }
    
    echo "\n✅ ALL SYSTEMS OPERATIONAL!\n";
    
} else {
    echo "❌ ERROR: Tenant ID not found for user ID $userId\n";
}
?>
