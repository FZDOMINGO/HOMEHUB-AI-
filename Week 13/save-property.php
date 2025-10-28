<?php
// Include environment configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Initialize session
initSession();

header('Content-Type: application/json');

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a tenant to save properties.']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Check if property ID and action are provided
if (!isset($_POST['property_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

$propertyId = intval($_POST['property_id']);
$action = $_POST['action'];
$userId = $_SESSION['user_id'];

$conn = getDbConnection();

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

// Verify that property exists
$stmt = $conn->prepare("SELECT id FROM properties WHERE id = ?");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Property not found.']);
    exit;
}

try {
    if ($action === 'save') {
        // Check if already saved
        $stmt = $conn->prepare("SELECT id FROM saved_properties WHERE tenant_id = ? AND property_id = ?");
        $stmt->bind_param("ii", $tenantId, $propertyId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Property already saved.']);
            exit;
        }
        
        // Save property
        $stmt = $conn->prepare("INSERT INTO saved_properties (tenant_id, property_id, saved_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $tenantId, $propertyId);
        $stmt->execute();
        
        // Track the save interaction
        $stmt = $conn->prepare("INSERT INTO user_interactions (user_id, property_id, interaction_type, weight, created_at) VALUES (?, ?, 'save', 0.8, NOW())");
        $stmt->bind_param("ii", $userId, $propertyId);
        $stmt->execute();
        
        // Update browsing_history to mark as saved
        $stmt = $conn->prepare("UPDATE browsing_history SET saved = 1 WHERE user_id = ? AND property_id = ? ORDER BY viewed_at DESC LIMIT 1");
        $stmt->bind_param("ii", $userId, $propertyId);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Property saved successfully.']);
    } else if ($action === 'unsave') {
        // Unsave property
        $stmt = $conn->prepare("DELETE FROM saved_properties WHERE tenant_id = ? AND property_id = ?");
        $stmt->bind_param("ii", $tenantId, $propertyId);
        $stmt->execute();
        
        // Track the unsave interaction
        $stmt = $conn->prepare("INSERT INTO user_interactions (user_id, property_id, interaction_type, weight, created_at) VALUES (?, ?, 'unsave', -0.5, NOW())");
        $stmt->bind_param("ii", $userId, $propertyId);
        $stmt->execute();
        
        // Update browsing_history to mark as unsaved
        $stmt = $conn->prepare("UPDATE browsing_history SET saved = 0 WHERE user_id = ? AND property_id = ? ORDER BY viewed_at DESC LIMIT 1");
        $stmt->bind_param("ii", $userId, $propertyId);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Property removed from saved list.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing your request: ' . $e->getMessage()]);
}

$conn->close();
?>