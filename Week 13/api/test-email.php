<?php
// Suppress all output until we're ready to send JSON
ob_start();

session_start();

// Clear any output buffer and send JSON header
ob_end_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Only landlords and tenants can send test emails
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['landlord', 'tenant'])) {
    echo json_encode(['success' => false, 'message' => 'Only landlords and tenants can use email notifications']);
    exit;
}

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/email_functions.php';

$conn = getDbConnection();
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Get user email based on user type
if ($userType === 'landlord') {
    $stmt = $conn->prepare("SELECT u.email, u.first_name, u.last_name 
                           FROM landlords l 
                           JOIN users u ON l.user_id = u.id 
                           WHERE l.id = ?");
} else { // tenant
    $stmt = $conn->prepare("SELECT u.email, u.first_name, u.last_name 
                           FROM tenants t 
                           JOIN users u ON t.user_id = u.id 
                           WHERE t.id = ?");
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$userEmail = $user['email'];
$userName = $user['first_name'] . ' ' . $user['last_name'];

// Send test email
$content = '
    <h2>Test Email - System Working! ✅</h2>
    <p>Hello ' . htmlspecialchars($userName) . ',</p>
    <p>This is a test email to confirm that the HomeHub email notification system is working correctly.</p>
    <div class="highlight">
        <p><strong>Email Configuration:</strong> Active</p>
        <p><strong>Delivery Status:</strong> Successful</p>
        <p><strong>Timestamp:</strong> ' . date('F j, Y g:i A') . '</p>
        <p><strong>User Type:</strong> ' . ucfirst($userType) . '</p>
    </div>
    <p>If you received this email, your email notifications are configured correctly!</p>
    <a href="https://homehubai.shop/' . $userType . '/dashboard.php" class="button">Back to Dashboard</a>
';

$subject = "HomeHub Test Email - " . date('Y-m-d H:i:s');
$message = getEmailTemplate($content, $subject);

$result = sendEmail($userEmail, $subject, $message);

$conn->close();

// Provide helpful message about mail configuration
if (!$result) {
    // Check if SMTP is configured
    require_once __DIR__ . '/../config/db_connect.php';
    $conn2 = getDbConnection();
    $configResult = $conn2->query("SELECT use_smtp, smtp_host FROM email_config WHERE id = 1");
    $config = $configResult ? $configResult->fetch_assoc() : null;
    $conn2->close();
    
    if ($config && $config['use_smtp'] == 1) {
        echo json_encode([
            'success' => false,
            'message' => 'SMTP authentication failed. Please check your Gmail App Password (must be 16 characters). Current SMTP: ' . $config['smtp_host']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Email sending failed. Your local mail server is not configured. To actually send emails, configure Gmail SMTP in Email Settings.'
        ]);
    }
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent to ' . $userEmail . ' via SMTP!'
    ]);
}
?>
