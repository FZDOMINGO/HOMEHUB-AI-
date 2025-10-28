<?php
// Include environment configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Initialize session
initSession();

// Debug log
file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - Starting message process\n", FILE_APPEND);

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    redirect('login/login.html');
    exit;
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    redirect('index.php');
    exit;
}

$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$propertyId = filter_var($_POST['property_id'], FILTER_VALIDATE_INT);
$landlordId = filter_var($_POST['landlord_id'], FILTER_VALIDATE_INT);
$subject = htmlspecialchars(trim($_POST['subject']));
$messageContent = htmlspecialchars(trim($_POST['contact_message']));

// Log what we received
file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - Received data: userId=$userId, propertyId=$propertyId, landlordId=$landlordId\n", FILE_APPEND);

// Validate inputs
if (!$propertyId || !$landlordId || empty($subject) || empty($messageContent)) {
    $_SESSION['contact_error'] = "Please fill out all required fields.";
    header("Location: property-detail.php?id=" . $propertyId);
    exit;
}

try {
    // Get tenant ID
    $stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - No tenant found for user $userId\n", FILE_APPEND);
        $_SESSION['contact_error'] = "Tenant profile not found.";
        header("Location: property-detail.php?id=" . $propertyId);
        exit;
    }
    
    $tenant = $result->fetch_assoc();
    $tenantId = $tenant['id'];
    
    // Get landlord user_id (for receiver_id)
    $stmt = $conn->prepare("SELECT user_id FROM landlords WHERE id = ?");
    $stmt->bind_param("i", $landlordId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - No landlord found with ID $landlordId\n", FILE_APPEND);
        $_SESSION['contact_error'] = "Landlord not found.";
        header("Location: property-detail.php?id=" . $propertyId);
        exit;
    }
    
    $landlord = $result->fetch_assoc();
    $landlordUserId = $landlord['user_id'];
    
    // Prepare the insert query with all required fields
    $sql = "INSERT INTO messages (
                tenant_id, 
                landlord_id, 
                property_id, 
                subject, 
                message, 
                message_text, 
                sender_id, 
                receiver_id, 
                related_property_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    // Both message and message_text will contain the same content
    $stmt->bind_param("iiisssiis", 
        $tenantId,          // tenant_id
        $landlordId,        // landlord_id
        $propertyId,        // property_id
        $subject,           // subject
        $messageContent,    // message
        $messageContent,    // message_text (duplicate field)
        $userId,            // sender_id
        $landlordUserId,    // receiver_id
        $propertyId         // related_property_id
    );
    
    file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - Executing query: $sql\n", FILE_APPEND);
    file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - With params: tenantId=$tenantId, landlordId=$landlordId, propertyId=$propertyId, subject=$subject, senderId=$userId, receiverId=$landlordUserId\n", FILE_APPEND);
    
    if ($stmt->execute()) {
        $_SESSION['contact_success'] = "Message sent successfully!";
        file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - Message sent successfully\n", FILE_APPEND);
        
        // Create notification for landlord
        try {
            if ($conn->query("SHOW TABLES LIKE 'notifications'")->num_rows > 0) {
                // Get tenant's name
                $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $senderName = $user['first_name'] . ' ' . $user['last_name'];
                
                $notificationContent = "New message from $senderName: $subject";
                
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, content, created_at) VALUES (?, 'message', ?, NOW())");
                $stmt->bind_param("is", $landlordUserId, $notificationContent);
                $stmt->execute();
                file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - Notification created for landlord\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - Notification error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    } else {
        $_SESSION['contact_error'] = "Failed to send message. Please try again.";
        file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - Query error: " . $stmt->error . "\n", FILE_APPEND);
    }
} catch (Exception $e) {
    $_SESSION['contact_error'] = "Failed to send message: " . $e->getMessage();
    file_put_contents('contact_debug.log', date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
}

$conn->close();

// Redirect back to property page
header("Location: property-detail.php?id=" . $propertyId);
exit;
?>