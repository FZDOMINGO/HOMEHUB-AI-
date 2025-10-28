<?php
// api/process-visit-request.php
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
    
    // Verify this visit is for a property owned by this landlord
    $stmt = $conn->prepare("
        SELECT bv.*, p.landlord_id, p.title AS property_title, 
               t.user_id AS tenant_user_id, u.email AS tenant_email,
               u.first_name AS tenant_first_name, u.last_name AS tenant_last_name,
               lu.phone AS landlord_contact
        FROM booking_visits bv
        JOIN properties p ON bv.property_id = p.id
        JOIN tenants t ON bv.tenant_id = t.id
        JOIN users u ON t.user_id = u.id
        JOIN landlords l ON p.landlord_id = l.id
        JOIN users lu ON l.user_id = lu.id
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
    $tenantEmail = $visit['tenant_email'];
    $tenantName = $visit['tenant_first_name'] . ' ' . $visit['tenant_last_name'];
    $propertyTitle = $visit['property_title'];
    $landlordContact = $visit['landlord_contact'] ?: 'Contact via HomeHub';
    
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
            error_log("Notification error: " . $e->getMessage());
        }
        
        // Send email notification to tenant when approved
        if ($action === 'approve') {
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', date('Y-m-d H:i:s') . " - VISIT APPROVED EMAIL\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Tenant Email: " . $tenantEmail . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Tenant Name: " . $tenantName . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Property: " . $propertyTitle . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Visit Date: " . $visit['visit_date'] . "\n", FILE_APPEND);
            
            error_log("=== VISIT APPROVED EMAIL DEBUG ===");
            error_log("Tenant Email: " . $tenantEmail);
            error_log("Tenant Name: " . $tenantName);
            error_log("Property: " . $propertyTitle);
            error_log("Visit Date: " . $visit['visit_date']);
            error_log("Visit Time: " . $visit['visit_time']);
            error_log("Landlord Contact: " . $landlordContact);
            
            $emailResult = sendVisitApprovedEmail($tenantEmail, $tenantName, $propertyTitle, $visit['visit_date'], $visit['visit_time'], $landlordContact);
            
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "Email send result: " . ($emailResult ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
            file_put_contents(__DIR__ . '/../tenant_notification_debug.log', "---\n", FILE_APPEND);
            
            error_log("Visit approval email send result: " . ($emailResult ? 'SUCCESS' : 'FAILED'));
            error_log("=== END VISIT APPROVED EMAIL DEBUG ===");
        }
        
        ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Visit request ' . $action . 'ed successfully.'
        ]);
    } else {
        ob_clean();
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update visit status: ' . $stmt->error
        ]);
    }
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error processing your request: ' . $e->getMessage()
    ]);
    error_log("Exception: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    ob_end_flush();
}
?>