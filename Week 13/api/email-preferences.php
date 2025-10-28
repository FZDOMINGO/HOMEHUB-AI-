<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$conn = getDbConnection();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get email preferences
    $stmt = $conn->prepare("SELECT * FROM email_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'preferences' => $row]);
    } else {
        // Create default preferences if they don't exist
        $stmt = $conn->prepare("INSERT INTO email_preferences (user_id) VALUES (?)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Fetch the newly created preferences
        $stmt = $conn->prepare("SELECT * FROM email_preferences WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo json_encode(['success' => true, 'preferences' => $row]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update email preferences
    $data = json_decode(file_get_contents('php://input'), true);
    
    $receiveVisitRequests = isset($data['receive_visit_requests']) ? 1 : 0;
    $receiveBookingRequests = isset($data['receive_booking_requests']) ? 1 : 0;
    $receiveReservationUpdates = isset($data['receive_reservation_updates']) ? 1 : 0;
    $receiveVisitUpdates = isset($data['receive_visit_updates']) ? 1 : 0;
    $receivePropertyPerformance = isset($data['receive_property_performance']) ? 1 : 0;
    $receiveMessages = isset($data['receive_messages']) ? 1 : 0;
    $receiveSystemNotifications = isset($data['receive_system_notifications']) ? 1 : 0;
    $receiveMarketing = isset($data['receive_marketing']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO email_preferences 
        (user_id, receive_visit_requests, receive_booking_requests, receive_reservation_updates, 
         receive_visit_updates, receive_property_performance, receive_messages, 
         receive_system_notifications, receive_marketing) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        receive_visit_requests = VALUES(receive_visit_requests),
        receive_booking_requests = VALUES(receive_booking_requests),
        receive_reservation_updates = VALUES(receive_reservation_updates),
        receive_visit_updates = VALUES(receive_visit_updates),
        receive_property_performance = VALUES(receive_property_performance),
        receive_messages = VALUES(receive_messages),
        receive_system_notifications = VALUES(receive_system_notifications),
        receive_marketing = VALUES(receive_marketing)");
    
    $stmt->bind_param("iiiiiiiii", $userId, $receiveVisitRequests, $receiveBookingRequests, 
                      $receiveReservationUpdates, $receiveVisitUpdates, $receivePropertyPerformance,
                      $receiveMessages, $receiveSystemNotifications, $receiveMarketing);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Email preferences updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update preferences']);
    }
}

$conn->close();
?>
