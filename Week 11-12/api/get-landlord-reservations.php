<?php
// api/get-landlord-reservations.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a landlord to view reservation requests.']);
    exit;
}

// Include database connection
require_once '../config/db_connect.php';
$conn = getDbConnection();

$userId = $_SESSION['user_id'];

try {
    // Get landlord ID
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
    
    // First, check what columns exist in properties table
    $checkColumns = $conn->query("SHOW COLUMNS FROM properties");
    $propertyColumns = [];
    while ($col = $checkColumns->fetch_assoc()) {
        $propertyColumns[] = $col['Field'];
    }
    
    // Determine which rent column to use
    $rentColumn = '0'; // default to 0 if no rent column found
    if (in_array('rent_amount', $propertyColumns)) {
        $rentColumn = 'p.rent_amount';
    } elseif (in_array('monthly_rent', $propertyColumns)) {
        $rentColumn = 'p.monthly_rent';
    } elseif (in_array('rent', $propertyColumns)) {
        $rentColumn = 'p.rent';
    } elseif (in_array('price', $propertyColumns)) {
        $rentColumn = 'p.price';
    } elseif (in_array('rental_price', $propertyColumns)) {
        $rentColumn = 'p.rental_price';
    }
    
    // Get reservation requests for properties owned by this landlord
    $query = "SELECT pr.id, pr.property_id, pr.tenant_id, pr.move_in_date, pr.lease_duration, 
              pr.reservation_fee, pr.payment_method, pr.employment_status, pr.monthly_income,
              pr.requirements, pr.status, pr.created_at, pr.reservation_date, pr.expiration_date,
              pr.approval_date, pr.documents_submitted, pr.lease_signed, pr.payment_confirmed,
              p.title AS property_title, p.address, p.city, 
              $rentColumn AS monthly_rent,
              CONCAT(u.first_name, ' ', u.last_name) AS tenant_name, 
              u.email AS tenant_email, u.phone AS tenant_phone
              FROM property_reservations pr
              JOIN properties p ON pr.property_id = p.id
              JOIN tenants t ON pr.tenant_id = t.id
              JOIN users u ON t.user_id = u.id
              WHERE p.landlord_id = ?
              ORDER BY 
                CASE 
                    WHEN pr.status = 'pending' THEN 1
                    WHEN pr.status = 'approved' THEN 2
                    WHEN pr.status = 'rejected' THEN 3
                    WHEN pr.status = 'expired' THEN 4
                    WHEN pr.status = 'completed' THEN 5
                    WHEN pr.status = 'cancelled' THEN 6
                    ELSE 7
                END,
                pr.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $landlordId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    
    echo json_encode(['success' => true, 'reservations' => $reservations]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing your request: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
