<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to view available properties.']);
    exit;
}

$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

try {
    if ($userType === 'tenant') {
        // For tenants, get all available properties
        $query = "SELECT p.id, p.title, p.address, p.city FROM properties p WHERE p.status = 'available' ORDER BY p.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
    } else if ($userType === 'landlord') {
        // For landlords, get only their properties
        $query = "SELECT p.id, p.title, p.address, p.city 
                 FROM properties p 
                 JOIN landlords l ON p.landlord_id = l.id 
                 WHERE l.user_id = ? ORDER BY p.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user type.']);
        exit;
    }
    
    $result = $stmt->get_result();
    $properties = [];
    
    while ($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
    
    echo json_encode(['success' => true, 'properties' => $properties]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing your request: ' . $e->getMessage()]);
} finally {
    $conn->close();
}