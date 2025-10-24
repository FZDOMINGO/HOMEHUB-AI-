<?php
// api/process-reservation-request.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a landlord to process reservation requests.']);
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get request data
$reservationId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$reservationId || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
    exit;
}

// Include database connection
require_once '../config/db_connect.php';
$conn = getDbConnection();

$userId = $_SESSION['user_id'];

try {
    // Get landlord ID
    $stmt = $conn->prepare("SELECT id FROM landlords WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Landlord profile not found.']);
        exit;
    }
    
    $landlord = $result->fetch_assoc();
    $landlordId = $landlord['id'];
    
    // Verify this reservation is for a property owned by this landlord
    $stmt = $conn->prepare("
        SELECT pr.*, p.landlord_id, p.title AS property_title, t.user_id AS tenant_user_id 
        FROM property_reservations pr
        JOIN properties p ON pr.property_id = p.id
        JOIN tenants t ON pr.tenant_id = t.id
        WHERE pr.id = ? AND p.landlord_id = ?
    ");
    $stmt->bind_param("ii", $reservationId, $landlordId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Reservation request not found or not authorized.']);
        exit;
    }
    
    $reservation = $result->fetch_assoc();
    $tenantUserId = $reservation['tenant_user_id'];
    $propertyTitle = $reservation['property_title'];
    
    // Determine the new status based on the action
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
    
    // Update the reservation status
    if ($action === 'approve') {
        // When approving, set approval date and calculate expiration (14 days to complete requirements)
        $holdingPeriodDays = 14;
        $expirationDate = date('Y-m-d', strtotime("+{$holdingPeriodDays} days"));
        
        $stmt = $conn->prepare("UPDATE property_reservations 
                               SET status = ?, approval_date = NOW(), expiration_date = ?, updated_at = NOW() 
                               WHERE id = ?");
        $stmt->bind_param("ssi", $newStatus, $expirationDate, $reservationId);
    } else {
        // When rejecting, just update status
        $stmt = $conn->prepare("UPDATE property_reservations SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $reservationId);
    }
    
    if ($stmt->execute()) {
        // If approved, mark the property as reserved
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE properties SET status = 'reserved' WHERE id = ?");
            $stmt->bind_param("i", $reservation['property_id']);
            $stmt->execute();
        }
        
        // Create notification for tenant
        try {
            if ($conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0) {
                $notificationTitle = $action === 'approve' ? 'Reservation Approved! 🎉' : 'Reservation Update';
                
                if ($action === 'approve') {
                    $expirationDateFormatted = date('M j, Y', strtotime($expirationDate));
                    $notificationMessage = "Great news! Your reservation for '{$propertyTitle}' has been approved! " .
                                         "You have until {$expirationDateFormatted} to complete requirements (documents, lease signing, payment confirmation). " .
                                         "Please contact the landlord to proceed.";
                } else {
                    $notificationMessage = "Your reservation request for '{$propertyTitle}' has been declined by the landlord.";
                }
                
                $notificationType = $action === 'approve' ? 'reservation_approved' : 'reservation_rejected';
                $notificationLink = '/HomeHub/bookings.php';
                
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("issss", $tenantUserId, $notificationTitle, $notificationMessage, $notificationType, $notificationLink);
                $stmt->execute();
            }
        } catch (Exception $e) {
            // Notification creation failed, but main operation succeeded
            error_log("Failed to create notification: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "Reservation {$action}ed successfully.",
            'new_status' => $newStatus
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update reservation status.']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing your request: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
