<?php
session_start();
require 'config/db_connect.php';

// Set session if not already set
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'tenant';
}

$conn = getDbConnection();
$userId = $_SESSION['user_id'];

echo "<h2>Activity Counts for User ID: $userId</h2>";

// Check tenant
$stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($tenant = $result->fetch_assoc()) {
    $tenantId = $tenant['id'];
    echo "<p><strong>Tenant ID:</strong> $tenantId</p>";
    
    // Count reservations
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM property_reservations WHERE tenant_id = ?");
    $stmt->bind_param("i", $tenantId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['cnt'];
    echo "<p><strong>Property Reservations:</strong> $count</p>";
    
    // Count visits
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM booking_visits WHERE tenant_id = ?");
    $stmt->bind_param("i", $tenantId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['cnt'];
    echo "<p><strong>Booking Visits:</strong> $count</p>";
    
} else {
    echo "<p style='color:red;'><strong>No tenant record found!</strong></p>";
}

// Count user interactions
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM user_interactions WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['cnt'];
echo "<p><strong>User Interactions:</strong> $count</p>";

// Count browsing history
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM browsing_history WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['cnt'];
echo "<p><strong>Browsing History:</strong> $count</p>";

// Count AI recommendations
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM similarity_scores WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['cnt'];
echo "<p><strong>AI Recommendations:</strong> $count</p>";

echo "<hr>";
echo "<h3>Total Activities That Should Show:</h3>";
echo "<p>If any of the above counts are greater than 0, activities should display on the history page.</p>";

$conn->close();
?>
