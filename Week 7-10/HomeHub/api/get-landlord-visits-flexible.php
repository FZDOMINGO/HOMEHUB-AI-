<?php
// Save this as api/get-landlord-visits-flexible.php
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

// Get field names from booking_visits table to use the correct ones
$visitFields = [];
$result = $conn->query("SHOW COLUMNS FROM booking_visits");
while ($row = $result->fetch_assoc()) {
    $visitFields[] = $row['Field'];
}

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
    
    // Build the query based on available fields
    $selectFields = "bv.id";
    
    // Required fields
    if (in_array('property_id', $visitFields)) {
        $selectFields .= ", bv.property_id";
    }
    
    if (in_array('tenant_id', $visitFields)) {
        $selectFields .= ", bv.tenant_id";
    }
    
    // Date fields - try different possible names
    if (in_array('visit_date', $visitFields)) {
        $selectFields .= ", bv.visit_date";
    } else if (in_array('date', $visitFields)) {
        $selectFields .= ", bv.date as visit_date";
    }
    
    // Time fields - try different possible names
    if (in_array('visit_time', $visitFields)) {
        $selectFields .= ", bv.visit_time";
    } else if (in_array('time', $visitFields)) {
        $selectFields .= ", bv.time as visit_time";
    }
    
    // Status field
    if (in_array('status', $visitFields)) {
        $selectFields .= ", bv.status";
    }
    
    // Message field
    if (in_array('message', $visitFields)) {
        $selectFields .= ", bv.message";
    }
    
    // Other fields
    if (in_array('number_of_visitors', $visitFields)) {
        $selectFields .= ", bv.number_of_visitors";
    }
    
    if (in_array('phone_number', $visitFields)) {
        $selectFields .= ", bv.phone_number";
    }
    
    if (in_array('created_at', $visitFields)) {
        $selectFields .= ", bv.created_at";
    }
    
    // Get visit requests for properties owned by this landlord
    $query = "
        SELECT $selectFields,
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
            END";
            
    if (in_array('visit_date', $visitFields)) {
        $query .= ", bv.visit_date ASC";
    } else if (in_array('date', $visitFields)) {
        $query .= ", bv.date ASC";
    }
    
    $query .= ", bv.id DESC";
    
    $stmt = $conn->prepare($query);
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