<?php
// Include environment configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Start session
ob_start();

// Initialize session
initSession();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Debug logging - add this at the top
file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Request received. POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Auth error: User not logged in or not a tenant\n", FILE_APPEND);
    redirect('login/login.html');
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Method error: Not a POST request\n", FILE_APPEND);
    redirect('index.php');
    exit;
}

require_once __DIR__ . '/includes/email_functions.php';
$conn = getDbConnection();

// Log connection status
file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - DB connection established\n", FILE_APPEND);

$userId = $_SESSION['user_id'];
$propertyId = filter_var($_POST['property_id'], FILTER_VALIDATE_INT);
$visitDate = htmlspecialchars($_POST['visit_date'], ENT_QUOTES, 'UTF-8');
$visitTime = htmlspecialchars($_POST['visit_time'], ENT_QUOTES, 'UTF-8');
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
        $visitId = $conn->insert_id;
        file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Success: Booking request created, ID: {$visitId}\n", FILE_APPEND);
        
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
        
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $landlordUserId = $data['landlord_user_id'];
            $landlordEmail = $data['landlord_email'];
            $landlordName = $data['landlord_first_name'] . ' ' . $data['landlord_last_name'];
            $tenantName = $data['tenant_first_name'] . ' ' . $data['tenant_last_name'];
            $propertyTitle = $data['property_title'];
            
            // Create notification
            try {
                $notifContent = "New viewing request for {$propertyTitle} on {$visitDate}";
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, content, related_id, created_at) 
                                      VALUES (?, 'visit_request', ?, ?, NOW())");
                $stmt->bind_param("isi", $landlordUserId, $notifContent, $visitId);
                $stmt->execute();
                file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Notification created for landlord\n", FILE_APPEND);
            } catch (Exception $e) {
                file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Warning: Could not create notification: " . $e->getMessage() . "\n", FILE_APPEND);
            }
            
            // Send email to landlord
            file_put_contents('visit_email_debug.log', date('Y-m-d H:i:s') . " - PROCESS-BOOKING - Starting visit email send\n", FILE_APPEND);
            file_put_contents('visit_email_debug.log', "Landlord Email: " . $landlordEmail . "\n", FILE_APPEND);
            file_put_contents('visit_email_debug.log', "Landlord Name: " . $landlordName . "\n", FILE_APPEND);
            file_put_contents('visit_email_debug.log', "Tenant Name: " . $tenantName . "\n", FILE_APPEND);
            file_put_contents('visit_email_debug.log', "Property: " . $propertyTitle . "\n", FILE_APPEND);
            file_put_contents('visit_email_debug.log', "Visit Date: " . $visitDate . "\n", FILE_APPEND);
            file_put_contents('visit_email_debug.log', "Visit Time: " . $visitTime . "\n", FILE_APPEND);
            file_put_contents('visit_email_debug.log', "Visit ID: " . $visitId . "\n", FILE_APPEND);
            
            $emailResult = sendVisitRequestEmail($landlordEmail, $landlordName, $tenantName, $propertyTitle, $visitDate, $visitTime, $visitId);
            
            file_put_contents('visit_email_debug.log', "Email send result: " . ($emailResult ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
            file_put_contents('visit_email_debug.log', "---\n", FILE_APPEND);
        }
        
        $_SESSION['booking_success'] = "Viewing request sent successfully! The landlord will respond to your request shortly.";
    } else {
        file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Error: Failed to execute query: " . $stmt->error . "\n", FILE_APPEND);
        $_SESSION['booking_error'] = "Failed to create booking request. Please try again.";
    }

} catch (Exception $e) {
    file_put_contents('booking_debug.log', date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    $_SESSION['booking_error'] = "An error occurred: " . $e->getMessage();
}

$conn->close();

// Redirect back to property page
header("Location: property-detail.php?id=" . $propertyId);
exit;