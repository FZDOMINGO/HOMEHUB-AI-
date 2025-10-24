<?php
// Start session
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);

// Set JSON header
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
$reservationFee = filter_var($_POST['reservation_fee'] ?? 0, FILTER_VALIDATE_FLOAT);
$paymentMethod = filter_var($_POST['payment_method'] ?? '', FILTER_SANITIZE_STRING);
$employmentStatus = filter_var($_POST['employment_status'] ?? '', FILTER_SANITIZE_STRING);
$monthlyIncome = filter_var($_POST['monthly_income'] ?? 0, FILTER_VALIDATE_FLOAT);
$requirements = filter_var($_POST['requirements'] ?? '', FILTER_SANITIZE_STRING);
$agreeTerms = isset($_POST['agree_terms']);

// Validate inputs
if (!$propertyId || empty($moveInDate) || $leaseDuration < 1) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    ob_end_flush();
    exit;
}

// Validate reservation fee
if ($reservationFee < 1000) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Reservation fee must be at least ₱1,000.']);
    ob_end_flush();
    exit;
}

// Validate payment method
if (empty($paymentMethod)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Please select a payment method.']);
    ob_end_flush();
    exit;
}

// Validate employment status
if (empty($employmentStatus)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Please select your employment status.']);
    ob_end_flush();
    exit;
}

// Validate terms agreement
if (!$agreeTerms) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'You must agree to the terms and conditions.']);
    ob_end_flush();
    exit;
}

// Validate date (must be in the future)
$today = date('Y-m-d');
if ($moveInDate < $today) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Move-in date must be in the future.']);
    ob_end_flush();
    exit;
}

try {
    // Get tenant ID
    $stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Tenant profile not found.']);
        ob_end_flush();
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
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Property not found or not available for reservation.']);
        ob_end_flush();
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
    
    // Calculate holding period expiration (default: 7 days from submission)
    $holdingPeriodDays = 7;
    $expirationDate = date('Y-m-d', strtotime("+{$holdingPeriodDays} days"));
    
    // Set status to 'conflict' if there's a potential conflict, otherwise 'pending'
    $status = $potentialConflict ? 'conflict' : 'pending';
    
    // Save the reservation request
    $stmt = $conn->prepare("INSERT INTO property_reservations 
                          (property_id, tenant_id, move_in_date, lease_duration, reservation_fee, 
                           payment_method, employment_status, monthly_income, requirements, 
                           status, reservation_date, expiration_date) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
    
    if (!$stmt) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        ob_end_flush();
        exit;
    }
    
    $stmt->bind_param("iisidssdss", 
        $propertyId, $tenantId, $moveInDate, $leaseDuration, $reservationFee, 
        $paymentMethod, $employmentStatus, $monthlyIncome, $requirements, 
        $status, $expirationDate
    );
    
    if (!$stmt->execute()) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Error saving reservation: ' . $stmt->error]);
        ob_end_flush();
        exit;
    }
    
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
    $notificationMessage = "New reservation request for {$propertyTitle}: ₱" . number_format($reservationFee, 2) . " reservation fee, " . 
                          "move-in " . date('M j, Y', strtotime($moveInDate)) . " for {$leaseDuration} months. " .
                          "Employment: {$employmentStatus}, Income: ₱" . number_format($monthlyIncome, 2) . "/month";
    
    $stmt = $conn->prepare("INSERT INTO booking_notifications 
                          (user_id, booking_type, booking_id, message, is_read) 
                          VALUES (?, ?, ?, ?, 0)");
    $bookingType = 'reservation';
    $stmt->bind_param("isis", $landlordUserId, $bookingType, $reservationId, $notificationMessage);
    $stmt->execute();
    
    // Send back success response
    // Clear any unexpected output
    ob_clean();
    
    echo json_encode([
        'success' => true, 
        'message' => $potentialConflict ? 
            'Reservation request submitted. Note that there may be a scheduling conflict.' : 
            'Reservation request submitted successfully!',
        'reservation_id' => $reservationId
    ]);
    
} catch (Exception $e) {
    // Clear any unexpected output
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error processing your request: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    // Flush the output buffer
    ob_end_flush();
}