<?php
// Start session
session_start();
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

// Include database connection
require_once 'config/db_connect.php';
$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$propertyId = filter_var($_POST['property_id'] ?? 0, FILTER_VALIDATE_INT);
$visitDate = filter_var($_POST['visit_date'] ?? '', FILTER_SANITIZE_STRING);
$visitTime = filter_var($_POST['visit_time'] ?? '', FILTER_SANITIZE_STRING);
$visitors = filter_var($_POST['visitors'] ?? 1, FILTER_VALIDATE_INT);
$phone = filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING);
$message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_STRING);

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
    
    // Create a notification for the landlord
    $stmt = $conn->prepare("SELECT user_id FROM landlords WHERE id = ?");
    $stmt->bind_param("i", $landlordId);
    $stmt->execute();
    $result = $stmt->get_result();
    $landlordUser = $result->fetch_assoc();
    $landlordUserId = $landlordUser['user_id'];
    
    // Get property title for the notification
    $stmt = $conn->prepare("SELECT title FROM properties WHERE id = ?");
    $stmt->bind_param("i", $propertyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $propertyDetails = $result->fetch_assoc();
    $propertyTitle = $propertyDetails['title'];
    
    // Insert notification
    $notificationType = 'visit_request';
    $notificationMessage = "New visit request for {$propertyTitle} on " . date('M j, Y', strtotime($visitDate)) . " at " . date('g:i A', strtotime($visitTime));
    
    $stmt = $conn->prepare("INSERT INTO booking_notifications 
                          (user_id, booking_type, booking_id, message, is_read) 
                          VALUES (?, ?, ?, ?, 0)");
    $bookingType = 'visit';
    $stmt->bind_param("isis", $landlordUserId, $bookingType, $visitId, $notificationMessage);
    $stmt->execute();
    
    // Send back success response
    echo json_encode([
        'success' => true, 
        'message' => $potentialConflict ? 
            'Visit request submitted. Note that there may be a scheduling conflict.' : 
            'Visit request submitted successfully!',
        'visit_id' => $visitId
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing your request: ' . $e->getMessage()]);
} finally {
    $conn->close();
}

