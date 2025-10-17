<?php
// Start session
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a tenant to make a reservation.']);
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
$moveInDate = filter_var($_POST['move_in_date'] ?? '', FILTER_SANITIZE_STRING);
$leaseDuration = filter_var($_POST['lease_duration'] ?? 0, FILTER_VALIDATE_INT);
$requirements = filter_var($_POST['requirements'] ?? '', FILTER_SANITIZE_STRING);

// Validate inputs
if (!$propertyId || empty($moveInDate) || $leaseDuration < 1) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

// Validate date (must be in the future)
$today = date('Y-m-d');
if ($moveInDate < $today) {
    echo json_encode(['success' => false, 'message' => 'Move-in date must be in the future.']);
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
        echo json_encode(['success' => false, 'message' => 'Property not found or not available for reservation.']);
        exit;
    }
    
    $property = $result->fetch_assoc();
    $landlordId = $property['landlord_id'];
    
    // Check for existing approved reservations with overlapping dates
    $stmt = $conn->prepare("SELECT id FROM property_reservations 
                          WHERE property_id = ? 
                          AND move_in_date <= DATE_ADD(?, INTERVAL ? MONTH)
                          AND status = 'approved'");
    $stmt->bind_param("isi", $propertyId, $moveInDate, $leaseDuration);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // If there's already an approved reservation, we'll still allow the request but mark it for potential conflict
    $potentialConflict = ($result->num_rows > 0);
    
    // Save the reservation request
    $stmt = $conn->prepare("INSERT INTO property_reservations 
                          (property_id, tenant_id, move_in_date, lease_duration, requirements, status) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    
    // Set status to 'conflict' if there's a potential conflict, otherwise 'pending'
    $status = $potentialConflict ? 'conflict' : 'pending';
    
    $stmt->bind_param("iisiss", $propertyId, $tenantId, $moveInDate, $leaseDuration, $requirements, $status);
    $stmt->execute();
    $reservationId = $conn->insert_id;
    
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
    $notificationType = 'reservation_request';
    $notificationMessage = "New reservation request for {$propertyTitle} with move-in on " . date('M j, Y', strtotime($moveInDate)) . " for {$leaseDuration} months";
    
    $stmt = $conn->prepare("INSERT INTO booking_notifications 
                          (user_id, booking_type, booking_id, message, is_read) 
                          VALUES (?, ?, ?, ?, 0)");
    $bookingType = 'reservation';
    $stmt->bind_param("isis", $landlordUserId, $bookingType, $reservationId, $notificationMessage);
    $stmt->execute();
    
    // Send back success response
    echo json_encode([
        'success' => true, 
        'message' => $potentialConflict ? 
            'Reservation request submitted. Note that there may be a scheduling conflict.' : 
            'Reservation request submitted successfully!',
        'reservation_id' => $reservationId
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing your request: ' . $e->getMessage()]);
} finally {
    $conn->close();
}