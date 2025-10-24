<?php
// Start session
session_start();

// Debug logging - add this at the top
file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Request received. POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Auth error: User not logged in or not a tenant\n", FILE_APPEND);
    header("Location: login/login.html");
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Method error: Not a POST request\n", FILE_APPEND);
    header("Location: index.php");
    exit;
}

// Include database connection
require_once 'config/db_connect.php';
$conn = getDbConnection();

// Log connection status
file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - DB connection established\n", FILE_APPEND);

$userId = $_SESSION['user_id'];
$propertyId = filter_var($_POST['property_id'], FILTER_VALIDATE_INT);
$visitDate = filter_var($_POST['visit_date'], FILTER_SANITIZE_STRING);
$visitTime = filter_var($_POST['visit_time'], FILTER_SANITIZE_STRING);
$message = htmlspecialchars(trim($_POST['message'] ?? ''));

// Log sanitized inputs
file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Inputs: property_id={$propertyId}, visit_date={$visitDate}, visit_time={$visitTime}\n", FILE_APPEND);

// Validate inputs
if (!$propertyId || empty($visitDate) || empty($visitTime)) {
    file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Validation error: Missing required fields\n", FILE_APPEND);
    $_SESSION['booking_error'] = "Invalid booking details. Please try again.";
    header("Location: property-detail.php?id=" . $propertyId);
    exit;
}

// Format visit date and time
$visitDateTime = $visitDate . ' ' . $visitTime . ':00';
file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Visit datetime: {$visitDateTime}\n", FILE_APPEND);

// Get tenant ID
try {
    $stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Error: Tenant profile not found\n", FILE_APPEND);
        $_SESSION['booking_error'] = "Tenant profile not found.";
        header("Location: property-detail.php?id=" . $propertyId);
        exit;
    }

    $tenant = $result->fetch_assoc();
    $tenantId = $tenant['id'];
    file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Found tenant ID: {$tenantId}\n", FILE_APPEND);

    // Create booking request
$stmt = $conn->prepare("INSERT INTO booking_visits (property_id, tenant_id, visit_date, message, status) 
                      VALUES (?, ?, ?, ?, 'pending')");
$stmt->bind_param("iiss", $propertyId, $tenantId, $visitDateTime, $message);

    if ($stmt->execute()) {
        file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Success: Booking request created\n", FILE_APPEND);
        $_SESSION['booking_success'] = "Viewing request sent successfully! The landlord will respond to your request shortly.";
    } else {
        file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Error: Failed to execute query: " . $stmt->error . "\n", FILE_APPEND);
        $_SESSION['booking_error'] = "Failed to create booking request. Please try again.";
    }
    
    // Create notification for landlord
    try {
        // Get landlord ID
        $stmt = $conn->prepare("SELECT landlord_id FROM properties WHERE id = ?");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $propResult = $stmt->get_result();
        $property = $propResult->fetch_assoc();
        $landlordId = $property['landlord_id'];
        
        // Get landlord's user ID
        $stmt = $conn->prepare("SELECT user_id FROM landlords WHERE id = ?");
        $stmt->bind_param("i", $landlordId);
        $stmt->execute();
        $landlordResult = $stmt->get_result();
        $landlord = $landlordResult->fetch_assoc();
        $landlordUserId = $landlord['user_id'];
        
        // Create notification
        $notifContent = "New viewing request for your property on {$visitDate}";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, content, related_id, created_at) 
                              VALUES (?, 'visit_request', ?, ?, NOW())");
        $stmt->bind_param("isi", $landlordUserId, $notifContent, $propertyId);
        $stmt->execute();
        
        file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Notification created for landlord\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Warning: Could not create notification: " . $e->getMessage() . "\n", FILE_APPEND);
    }

} catch (Exception $e) {
    file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    $_SESSION['booking_error'] = "An error occurred: " . $e->getMessage();
}

$conn->close();

// Redirect back to property page
header("Location: property-detail.php?id=" . $propertyId);
exit;