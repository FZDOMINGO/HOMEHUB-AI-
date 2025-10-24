<?php
// api/process-visit-request.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a landlord to process visit requests.']);
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Debug log
file_put_contents('../visit_debug.log', date('Y-m-d H:i:s') . " - Starting process visit request with data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Get request data
$visitId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$visitId || !in_array($action, ['approve', 'reject', 'cancel'])) {
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
    
    // Verify this visit is for a property owned by this landlord
    $stmt = $conn->prepare("
        SELECT bv.*, p.landlord_id, t.user_id AS tenant_user_id 
        FROM booking_visits bv
        JOIN properties p ON bv.property_id = p.id
        JOIN tenants t ON bv.tenant_id = t.id
        WHERE bv.id = ? AND p.landlord_id = ?
    ");
    $stmt->bind_param("ii", $visitId, $landlordId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Visit request not found or not authorized.']);
        exit;
    }
    
    $visit = $result->fetch_assoc();
    $tenantUserId = $visit['tenant_user_id'];
    
    // Determine the new status based on the action
    $newStatus = '';
    switch ($action) {
        case 'approve':
            $newStatus = 'approved';
            break;
        case 'reject':
            $newStatus = 'rejected';
            break;
        case 'cancel':
            $newStatus = 'cancelled';
            break;
    }
    
    // Update the visit status
    $stmt = $conn->prepare("UPDATE booking_visits SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $visitId);
    
    if ($stmt->execute()) {
        // Create notification for tenant
        try {
            if ($conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0) {
                // Get property details
                $stmt = $conn->prepare("SELECT title FROM properties WHERE id = ?");
                $stmt->bind_param("i", $visit['property_id']);
                $stmt->execute();
                $propertyResult = $stmt->get_result();
                $property = $propertyResult->fetch_assoc();
                $propertyTitle = $property['title'];
                
                // Format date for notification
                $visitDate = date('M j, Y', strtotime($visit['visit_date']));
                $visitTime = date('g:i A', strtotime($visit['visit_time']));
                
                // Create notification message based on action
                switch ($action) {
                    case 'approve':
                        $notificationContent = "Your visit request for \"$propertyTitle\" on $visitDate at $visitTime has been approved!";
                        break;
                    case 'reject':
                        $notificationContent = "Your visit request for \"$propertyTitle\" on $visitDate at $visitTime has been rejected.";
                        break;
                    case 'cancel':
                        $notificationContent = "Your scheduled visit for \"$propertyTitle\" on $visitDate at $visitTime has been cancelled.";
                        break;
                }
                
                $notificationType = 'visit_update';
                
                $stmt = $conn->prepare("INSERT INTO notifications 
                                      (user_id, type, content, related_id, created_at) 
                                      VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("issi", $tenantUserId, $notificationType, $notificationContent, $visitId);
                $stmt->execute();
            }
        } catch (Exception $e) {
            // Just log notification errors, don't fail the whole process
            file_put_contents('../visit_debug.log', date('Y-m-d H:i:s') . " - Notification error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Visit request ' . $action . 'ed successfully.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update visit status: ' . $stmt->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing your request: ' . $e->getMessage()
    ]);
    file_put_contents('../visit_debug.log', date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
} finally {
    $conn->close();
}
?>