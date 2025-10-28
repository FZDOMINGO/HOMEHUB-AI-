<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to view booking status.']);
    exit;
}

$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

try {
    $bookings = [];
    
    if ($userType === 'tenant') {
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
        
        // Get visits
        $visitsQuery = "SELECT v.id, v.property_id, p.title AS property_title, v.visit_date AS date, 
                       v.visit_time AS time, v.status, v.created_at, 'visit' AS type,
                       (SELECT id FROM booking_visits WHERE property_id = v.property_id AND visit_date = v.visit_date AND status = 'approved' AND id != v.id LIMIT 1) AS conflict_id
                       FROM booking_visits v
                       JOIN properties p ON v.property_id = p.id
                       WHERE v.tenant_id = ?
                       ORDER BY v.visit_date DESC, v.visit_time DESC";
        
        $stmt = $conn->prepare($visitsQuery);
        $stmt->bind_param("i", $tenantId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        // Get reservations
        $reservationsQuery = "SELECT r.id, r.property_id, p.title AS property_title, r.move_in_date AS date, 
                            '' AS time, r.status, r.created_at, 'reservation' AS type,
                            (SELECT id FROM property_reservations WHERE property_id = r.property_id AND move_in_date = r.move_in_date AND status = 'approved' AND id != r.id LIMIT 1) AS conflict_id
                            FROM property_reservations r
                            JOIN properties p ON r.property_id = p.id
                            WHERE r.tenant_id = ?
                            ORDER BY r.move_in_date DESC";
        
        $stmt = $conn->prepare($reservationsQuery);
        $stmt->bind_param("i", $tenantId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    } else if ($userType === 'landlord') {
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
        
        // Get visits for properties owned by this landlord
        $visitsQuery = "SELECT v.id, v.property_id, p.title AS property_title, v.visit_date AS date, 
                       v.visit_time AS time, v.status, v.created_at, 'visit' AS type,
                       (SELECT id FROM booking_visits WHERE property_id = v.property_id AND visit_date = v.visit_date AND status = 'approved' AND id != v.id LIMIT 1) AS conflict_id
                       FROM booking_visits v
                       JOIN properties p ON v.property_id = p.id
                       WHERE p.landlord_id = ?
                       ORDER BY v.status = 'pending' DESC, v.visit_date DESC, v.visit_time DESC";
        
        $stmt = $conn->prepare($visitsQuery);
        $stmt->bind_param("i", $landlordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        // Get reservations for properties owned by this landlord
        $reservationsQuery = "SELECT r.id, r.property_id, p.title AS property_title, r.move_in_date AS date, 
                            '' AS time, r.status, r.created_at, 'reservation' AS type,
                            (SELECT id FROM property_reservations WHERE property_id = r.property_id AND move_in_date = r.move_in_date AND status = 'approved' AND id != r.id LIMIT 1) AS conflict_id
                            FROM property_reservations r
                            JOIN properties p ON r.property_id = p.id
                            WHERE p.landlord_id = ?
                            ORDER BY r.status = 'pending' DESC, r.move_in_date DESC";
        
        $stmt = $conn->prepare($reservationsQuery);
        $stmt->bind_param("i", $landlordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user type.']);
        exit;
    }
    
    echo json_encode(['success' => true, 'bookings' => $bookings]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing your request: ' . $e->getMessage()]);
} finally {
    $conn->close();
}