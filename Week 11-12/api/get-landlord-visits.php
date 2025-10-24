<?php
// Start session
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a landlord to view visit requests.']);
    exit;
}

// Include database connection
require_once '../config/db_connect.php';
$conn = getDbConnection();

$userId = $_SESSION['user_id'];

try {
    // Get landlord ID from user ID
    $stmt = $conn->prepare("SELECT id FROM landlords WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Landlord profile not found.']);
        exit;
    }
    
    $landlord = $result->fetch_assoc();
    $landlordId = $landlord['id'];
    
    // Get visit requests for properties owned by this landlord
    $stmt = $conn->prepare("
        SELECT bv.*, 
               p.title as property_title,
               CONCAT(u.first_name, ' ', u.last_name) as tenant_name,
               u.phone as tenant_phone,
               u.email as tenant_email
        FROM booking_visits bv
        JOIN properties p ON bv.property_id = p.id
        JOIN tenants t ON bv.tenant_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE p.landlord_id = ?
        ORDER BY 
            CASE 
                WHEN bv.status = 'pending' THEN 0
                WHEN bv.status = 'approved' THEN 1
                ELSE 2
            END,
            bv.visit_date ASC, 
            bv.id DESC
    ");
    
    $stmt->bind_param("i", $landlordId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $visits = [];
    while ($row = $result->fetch_assoc()) {
        $visits[] = $row;
    }
    
    echo json_encode(['success' => true, 'visits' => $visits]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>