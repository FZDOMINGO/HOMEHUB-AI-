<?php
// Include environment configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Start output buffering to prevent any output issues
ob_start();

// Initialize session
initSession();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

header('Content-Type: application/json');

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a tenant to schedule a visit.']);
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/includes/email_functions.php';
$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$propertyId = filter_var($_POST['property_id'] ?? 0, FILTER_VALIDATE_INT);
$visitDate = htmlspecialchars($_POST['visit_date'] ?? '', ENT_QUOTES, 'UTF-8');
$visitTime = htmlspecialchars($_POST['visit_time'] ?? '', ENT_QUOTES, 'UTF-8');
$visitors = filter_var($_POST['visitors'] ?? 1, FILTER_VALIDATE_INT);
$phone = htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');

// Validate inputs
if (!$propertyId || empty($visitDate) || empty($visitTime) || $visitors < 1 || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

// Validate date (must be in the future)
$today = date('Y-m-d');
if ($visitDate < $today) {
    echo json_encode(['success' => false, 'message' => 'Visit date must be in the future.']);
    exit;
}


try {
    // Get tenant ID
    $stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Tenant profile not found.']);
        exit;
    }
    
    $tenant = $result->fetch_assoc();
    $tenantId = $tenant['id'];
    
    // Verify the property exists and is available
    $stmt = $conn->prepare("SELECT id, landlord_id FROM properties WHERE id = ? AND status = 'available'");
    $stmt->bind_param("i", $propertyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Property not found or not available for booking.']);
        exit;
    }
    
    $property = $result->fetch_assoc();
    $landlordId = $property['landlord_id'];
    
    // Check for existing approved visits at the same date and time
    $stmt = $conn->prepare("SELECT id FROM booking_visits 
                          WHERE property_id = ? 
                          AND visit_date = ? 
                          AND visit_time = ? 
                          AND status = 'approved'");
    $stmt->bind_param("iss", $propertyId, $visitDate, $visitTime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If there's already an approved visit, we'll still allow the request but mark it for potential conflict
    $potentialConflict = ($result->num_rows > 0);
    
    // Save the visit request
    $stmt = $conn->prepare("INSERT INTO booking_visits 
                          (property_id, tenant_id, visit_date, visit_time, number_of_visitors, 
                           phone_number, message, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Set status to 'conflict' if there's a potential conflict, otherwise 'pending'
    $status = $potentialConflict ? 'conflict' : 'pending';
    
    $stmt->bind_param("iississ", $propertyId, $tenantId, $visitDate, $visitTime, $visitors, $phone, $message, $status);
    $stmt->execute();
    $visitId = $conn->insert_id;
    
    // Get all data needed for notification and email in one query
    $stmt = $conn->prepare("
        SELECT 
            p.title as property_title,
            l.user_id as landlord_user_id,
            lu.email as landlord_email,
            lu.first_name as landlord_first_name,
            lu.last_name as landlord_last_name,
            tu.first_name as tenant_first_name,
            tu.last_name as tenant_last_name
        FROM properties p
        JOIN landlords l ON p.landlord_id = l.id
        JOIN users lu ON l.user_id = lu.id
        CROSS JOIN users tu
        WHERE p.id = ? AND tu.id = ?
    ");
    $stmt->bind_param("ii", $propertyId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("ERROR: Could not fetch property/user data for email notification");
        ob_clean();
        echo json_encode(['success' => true, 'message' => 'Visit request submitted but notification failed.']);
        ob_end_flush();
        exit;
    }
    
    $notificationData = $result->fetch_assoc();
    $landlordUserId = $notificationData['landlord_user_id'];
    $landlordEmail = $notificationData['landlord_email'];
    $landlordName = $notificationData['landlord_first_name'] . ' ' . $notificationData['landlord_last_name'];
    $tenantName = $notificationData['tenant_first_name'] . ' ' . $notificationData['tenant_last_name'];
    $propertyTitle = $notificationData['property_title'];
    
    // Insert notification
    $notificationType = 'visit_request';
    $notificationMessage = "New visit request for {$propertyTitle} on " . date('M j, Y', strtotime($visitDate)) . " at " . date('g:i A', strtotime($visitTime));
    
    $stmt = $conn->prepare("INSERT INTO booking_notifications 
                          (user_id, booking_type, booking_id, message, is_read) 
                          VALUES (?, ?, ?, ?, 0)");
    $bookingType = 'visit';
    $stmt->bind_param("isis", $landlordUserId, $bookingType, $visitId, $notificationMessage);
    $stmt->execute();
    
    // Send email notification to landlord
    file_put_contents(__DIR__ . '/visit_email_debug.log', date('Y-m-d H:i:s') . " - Starting visit email send\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/visit_email_debug.log', "Landlord Email: " . $landlordEmail . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/visit_email_debug.log', "Landlord Name: " . $landlordName . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/visit_email_debug.log', "Tenant Name: " . $tenantName . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/visit_email_debug.log', "Property: " . $propertyTitle . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/visit_email_debug.log', "Visit Date: " . $visitDate . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/visit_email_debug.log', "Visit Time: " . $visitTime . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/visit_email_debug.log', "Visit ID: " . $visitId . "\n", FILE_APPEND);
    
    error_log("=== VISIT EMAIL DEBUG ===");
    error_log("Landlord Email: " . $landlordEmail);
    error_log("Landlord Name: " . $landlordName);
    error_log("Tenant Name: " . $tenantName);
    error_log("Property: " . $propertyTitle);
    error_log("Visit Date: " . $visitDate);
    error_log("Visit Time: " . $visitTime);
    error_log("Visit ID: " . $visitId);
    
    // Send email - this WILL execute
    $emailResult = sendVisitRequestEmail($landlordEmail, $landlordName, $tenantName, $propertyTitle, $visitDate, $visitTime, $visitId);
    
    file_put_contents(__DIR__ . '/visit_email_debug.log', "Email send result: " . ($emailResult ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/visit_email_debug.log', "---\n", FILE_APPEND);
    
    error_log("Email send result: " . ($emailResult ? 'SUCCESS' : 'FAILED'));
    error_log("=== END VISIT EMAIL DEBUG ===");
    
    // Clear any unexpected output before sending JSON
    ob_clean();
    
    // Send back success response
    echo json_encode([
        'success' => true, 
        'message' => $potentialConflict ? 
            'Visit request submitted. Note that there may be a scheduling conflict.' : 
            'Visit request submitted successfully!',
        'visit_id' => $visitId
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error processing your request: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    ob_end_flush();
}

