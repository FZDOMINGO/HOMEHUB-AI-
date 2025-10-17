<?php
// Start session
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to view notifications.']);
    exit;
}

// Include database connection
require_once '../config/db_connect.php';
$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5; // Default to 5 notifications

try {
    // Get recent notifications
    $stmt = $conn->prepare("SELECT * FROM notifications 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC 
                           LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    // Get unread count
    $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM notifications 
                           WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $countResult = $stmt->get_result();
    $countRow = $countResult->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $countRow['unread']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching notifications: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>