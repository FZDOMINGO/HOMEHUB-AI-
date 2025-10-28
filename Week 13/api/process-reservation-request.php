<?php
// api/process-reservation-request.php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

ob_start();

// Initialize session
initSession();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

require_once __DIR__ . '/../includes/email_functions.php';
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
        SELECT pr.*, p.landlord_id, p.title AS property_title, p.rent_amount, 
               t.user_id AS tenant_user_id, u.email AS tenant_email, 
               u.first_name AS tenant_first_name, u.last_name AS tenant_last_name
        FROM property_reservations pr
        JOIN properties p ON pr.property_id = p.id
        JOIN tenants t ON pr.tenant_id = t.id
        JOIN users u ON t.user_id = u.id
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
    $tenantEmail = $reservation['tenant_email'];
    $tenantName = $reservation['tenant_first_name'] . ' ' . $reservation['tenant_last_name'];
    $propertyTitle = $reservation['property_title'];
    $moveInDate = $reservation['move_in_date'];
    $rentAmount = $reservation['rent_amount'];
    
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
        
        // Send email notification to tenant
        if ($action === 'approve') {
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', date('Y-m-d H:i:s') . " - RESERVATION APPROVED EMAIL\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Tenant Email: " . $tenantEmail . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Tenant Name: " . $tenantName . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Property: " . $propertyTitle . "\n", FILE_APPEND);
            
            error_log("=== RESERVATION APPROVED EMAIL DEBUG ===");
            error_log("Tenant Email: " . $tenantEmail);
            error_log("Tenant Name: " . $tenantName);
            error_log("Property: " . $propertyTitle);
            error_log("Move-in Date: " . $moveInDate);
            error_log("Rent Amount: " . $rentAmount);
            
            $emailResult = sendReservationApprovedEmail($tenantEmail, $tenantName, $propertyTitle, $moveInDate, $rentAmount);
            
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Email send result: " . ($emailResult ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "---\n", FILE_APPEND);
            
            error_log("Approval email send result: " . ($emailResult ? 'SUCCESS' : 'FAILED'));
            error_log("=== END RESERVATION APPROVED EMAIL DEBUG ===");
        } else {
            // Send rejection email to tenant
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', date('Y-m-d H:i:s') . " - RESERVATION REJECTED EMAIL\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Tenant Email: " . $tenantEmail . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Property: " . $propertyTitle . "\n", FILE_APPEND);
            
            // You can create a rejection email function or send a simple notification
            // For now, just log it
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Action: REJECTED (no email function yet)\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "---\n", FILE_APPEND);
        }
        
        ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => "Reservation {$action}ed successfully.",
            'new_status' => $newStatus
        ]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to update reservation status.']);
    }
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error processing your request: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    ob_end_flush();
}
