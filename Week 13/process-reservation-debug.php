<?php
// Simplified test version of process-reservation.php
session_start();

// Start output buffering
ob_start();

header('Content-Type: application/json');

// Log to a file for debugging
$logFile = __DIR__ . '/reservation_debug.log';
file_put_contents($logFile, "=== New Request at " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

// Check session
file_put_contents($logFile, "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n", FILE_APPEND);
file_put_contents($logFile, "Session user_type: " . ($_SESSION['user_type'] ?? 'NOT SET') . "\n", FILE_APPEND);

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    file_put_contents($logFile, "ERROR: Not logged in as tenant\n", FILE_APPEND);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a tenant to make a reservation.']);
    ob_end_flush();
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    file_put_contents($logFile, "ERROR: Not POST request\n", FILE_APPEND);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    ob_end_flush();
    exit;
}

// Log POST data
file_put_contents($logFile, "POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Include database connection
try {
    require_once 'config/db_connect.php';
    $conn = getDbConnection();
    file_put_contents($logFile, "Database connected successfully\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($logFile, "ERROR: Database connection failed: " . $e->getMessage() . "\n", FILE_APPEND);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    ob_end_flush();
    exit;
}

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

file_put_contents($logFile, "Parsed propertyId: $propertyId\n", FILE_APPEND);
file_put_contents($logFile, "Parsed moveInDate: $moveInDate\n", FILE_APPEND);
file_put_contents($logFile, "Parsed leaseDuration: $leaseDuration\n", FILE_APPEND);
file_put_contents($logFile, "Parsed reservationFee: $reservationFee\n", FILE_APPEND);
file_put_contents($logFile, "Parsed agreeTerms: " . ($agreeTerms ? 'YES' : 'NO') . "\n", FILE_APPEND);

// Validate inputs
if (!$propertyId || empty($moveInDate) || $leaseDuration < 1) {
    file_put_contents($logFile, "ERROR: Missing required fields\n", FILE_APPEND);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    ob_end_flush();
    exit;
}

if ($reservationFee < 1000) {
    file_put_contents($logFile, "ERROR: Reservation fee too low\n", FILE_APPEND);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Reservation fee must be at least ₱1,000.']);
    ob_end_flush();
    exit;
}

if (empty($paymentMethod)) {
    file_put_contents($logFile, "ERROR: Payment method not selected\n", FILE_APPEND);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Please select a payment method.']);
    ob_end_flush();
    exit;
}

if (empty($employmentStatus)) {
    file_put_contents($logFile, "ERROR: Employment status not selected\n", FILE_APPEND);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Please select your employment status.']);
    ob_end_flush();
    exit;
}

if (!$agreeTerms) {
    file_put_contents($logFile, "ERROR: Terms not agreed\n", FILE_APPEND);
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'You must agree to the terms and conditions.']);
    ob_end_flush();
    exit;
}

file_put_contents($logFile, "All validations passed\n", FILE_APPEND);
file_put_contents($logFile, "SUCCESS: About to send success response\n", FILE_APPEND);

ob_clean();
echo json_encode([
    'success' => true, 
    'message' => 'TEST: Validation passed. Real insertion not performed yet.',
    'debug' => [
        'propertyId' => $propertyId,
        'moveInDate' => $moveInDate,
        'fee' => $reservationFee
    ]
]);

file_put_contents($logFile, "Response sent\n", FILE_APPEND);

ob_end_flush();
$conn->close();
?>
