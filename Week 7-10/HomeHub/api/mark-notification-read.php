<?php
// Start session
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Include database connection
require_once '../config/db_connect.php';
require_once '../includes/notification_functions.php';
$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

try {
    // Verify notification belongs to this user
    $stmt = $conn->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Notification not found.']);
        exit;
    }
    
    // Mark as read
    $success = markNotificationAsRead($notificationId, $conn);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Notification marked as read.' : 'Failed to mark notification as read.'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>