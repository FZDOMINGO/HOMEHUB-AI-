<?php
header('Content-Type: application/json');

require_once '../config/db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    exit;
}

$conn = getDbConnection();
$propertyId = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT p.*, u.first_name, u.last_name, u.email
    FROM properties p 
    LEFT JOIN landlords l ON p.landlord_id = l.id 
    LEFT JOIN users u ON l.user_id = u.id
    WHERE p.id = ?
");

$stmt->bind_param("i", $propertyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Property not found']);
    exit;
}

$property = $result->fetch_assoc();
$conn->close();

echo json_encode([
    'success' => true,
    'property' => $property
]);
?>