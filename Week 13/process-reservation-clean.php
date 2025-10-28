<?php
// Include environment configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

ob_start();

// Initialize session
initSession();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/reservation_clean_error.log');

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

require_once __DIR__ . '/includes/email_functions.php';
$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$propertyId = filter_var($_POST['property_id'] ?? 0, FILTER_VALIDATE_INT);
$moveInDate = htmlspecialchars($_POST['move_in_date'] ?? '', ENT_QUOTES, 'UTF-8');
$leaseDuration = filter_var($_POST['lease_duration'] ?? 0, FILTER_VALIDATE_INT);
$reservationFee = filter_var($_POST['reservation_fee'] ?? 0, FILTER_VALIDATE_FLOAT);
$paymentMethod = htmlspecialchars($_POST['payment_method'] ?? '', ENT_QUOTES, 'UTF-8');
$employmentStatus = htmlspecialchars($_POST['employment_status'] ?? '', ENT_QUOTES, 'UTF-8');
$monthlyIncome = filter_var($_POST['monthly_income'] ?? 0, FILTER_VALIDATE_FLOAT);
$requirements = htmlspecialchars($_POST['requirements'] ?? '', ENT_QUOTES, 'UTF-8');
$agreeTerms = isset($_POST['agree_terms']);

// Validate inputs
if (!$propertyId || empty($moveInDate) || $leaseDuration < 1) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if ($reservationFee < 1000) {
    echo json_encode(['success' => false, 'message' => 'Reservation fee must be at least ₱1,000.']);
    exit;
}

if (empty($paymentMethod)) {
    echo json_encode(['success' => false, 'message' => 'Please select a payment method.']);
    exit;
}

if (empty($employmentStatus)) {
    echo json_encode(['success' => false, 'message' => 'Please select your employment status.']);
    exit;
}

if (!$agreeTerms) {
    echo json_encode(['success' => false, 'message' => 'You must agree to the terms and conditions.']);
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
    $stmt = $conn->prepare("SELECT id, landlord_id FROM properties WHERE id = ?");
    $stmt->bind_param("i", $propertyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Property not found.']);
        exit;
    }
    
    $property = $result->fetch_assoc();
    $landlordId = $property['landlord_id'];
    
    // Check for existing approved reservations with overlapping dates
    $stmt = $conn->prepare("SELECT id FROM property_reservations 
                          WHERE property_id = ? 
                          AND status = 'approved'");
    $stmt->bind_param("i", $propertyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
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
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    
    // Type string: i=int, s=string, d=double (11 parameters total)
    // property_id(i), tenant_id(i), move_in_date(s), lease_duration(i), reservation_fee(d),
    // payment_method(s), employment_status(s), monthly_income(d), requirements(s), status(s), expiration_date(s)
    $stmt->bind_param("iisidssdsss", 
        $propertyId, 
        $tenantId, 
        $moveInDate, 
        $leaseDuration, 
        $reservationFee, 
        $paymentMethod, 
        $employmentStatus, 
        $monthlyIncome, 
        $requirements, 
        $status, 
        $expirationDate
    );
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error saving reservation: ' . $stmt->error]);
        exit;
    }
    
    $reservationId = $conn->insert_id;
    
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
        $notificationData = $result->fetch_assoc();
        $landlordUserId = $notificationData['landlord_user_id'];
        $landlordEmail = $notificationData['landlord_email'];
        $landlordName = $notificationData['landlord_first_name'] . ' ' . $notificationData['landlord_last_name'];
        $tenantName = $notificationData['tenant_first_name'] . ' ' . $notificationData['tenant_last_name'];
        $propertyTitle = $notificationData['property_title'];
        
        // Insert notification
        $notificationContent = "New reservation request for {$propertyTitle}: ₱" . number_format($reservationFee, 2) . " reservation fee, " . 
                              "move-in " . date('M j, Y', strtotime($moveInDate)) . " for {$leaseDuration} months. " .
                              "Employment: {$employmentStatus}, Income: ₱" . number_format($monthlyIncome, 2) . "/month";
        $notificationType = 'reservation';
        
        $stmt = $conn->prepare("INSERT INTO notifications 
                              (user_id, type, content, related_id, is_read, created_at) 
                              VALUES (?, ?, ?, ?, 0, NOW())");
        $stmt->bind_param("issi", $landlordUserId, $notificationType, $notificationContent, $reservationId);
        $stmt->execute();
        
        // Send email notification to landlord
        file_put_contents(__DIR__ . '/booking_debug.log', date('Y-m-d H:i:s') . " - CLEAN FILE - Starting email send\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/booking_debug.log', "Landlord Email: " . $landlordEmail . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/booking_debug.log', "Landlord Name: " . $landlordName . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/booking_debug.log', "Tenant Name: " . $tenantName . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/booking_debug.log', "Property: " . $propertyTitle . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/booking_debug.log', "Move-in Date: " . $moveInDate . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/booking_debug.log', "Lease Duration: " . $leaseDuration . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/booking_debug.log', "Reservation ID: " . $reservationId . "\n", FILE_APPEND);
        
        // Send email
        $emailResult = sendBookingRequestEmail($landlordEmail, $landlordName, $tenantName, $propertyTitle, $moveInDate, $leaseDuration, $reservationId);
        
        file_put_contents(__DIR__ . '/booking_debug.log', "Email send result: " . ($emailResult ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
        file_put_contents(__DIR__ . '/booking_debug.log', "---\n", FILE_APPEND);
    }
    
    // Send back success response
    ob_clean();
    echo json_encode([
        'success' => true, 
        'message' => $potentialConflict ? 
            'Reservation request submitted. Note that there may be a scheduling conflict.' : 
            'Reservation request submitted successfully!',
        'reservation_id' => $reservationId
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
?>
