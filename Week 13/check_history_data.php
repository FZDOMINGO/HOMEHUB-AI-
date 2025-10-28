<?php
session_start();

require_once 'config/db_connect.php';
$conn = getDbConnection();

// Check current session
echo "<h2>Session Info:</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "User Type: " . ($_SESSION['user_type'] ?? 'Not set') . "<br>";
echo "User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "<br><br>";

$userId = $_SESSION['user_id'] ?? 1;

// Check if tenant exists
$stmt = $conn->prepare("SELECT * FROM tenants WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Tenant Info:</h2>";
if ($tenant = $result->fetch_assoc()) {
    echo "Tenant ID: " . $tenant['id'] . "<br>";
    echo "User ID: " . $tenant['user_id'] . "<br><br>";
    $tenantId = $tenant['id'];
} else {
    echo "No tenant found for user_id $userId<br><br>";
    exit;
}

// Check user_interactions
$stmt = $conn->prepare("SELECT * FROM user_interactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>User Interactions (Last 5):</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Property ID</th><th>Type</th><th>Weight</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['property_id'] . "</td>";
        echo "<td>" . $row['interaction_type'] . "</td>";
        echo "<td>" . $row['weight'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "No user interactions found<br><br>";
}

// Check property_reservations
$stmt = $conn->prepare("SELECT * FROM property_reservations WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $tenantId);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Property Reservations (Last 5):</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Property ID</th><th>Status</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['property_id'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "No property reservations found<br><br>";
}

// Check booking_visits
$stmt = $conn->prepare("SELECT * FROM booking_visits WHERE tenant_id = ? ORDER BY visit_datetime DESC LIMIT 5");
$stmt->bind_param("i", $tenantId);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Booking Visits (Last 5):</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Property ID</th><th>Status</th><th>Visit DateTime</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['property_id'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['visit_datetime'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "No booking visits found<br><br>";
}

// Check browsing_history
$stmt = $conn->prepare("SELECT * FROM browsing_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Browsing History (Last 5):</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Property ID</th><th>Saved</th><th>Created At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['property_id'] . "</td>";
        echo "<td>" . ($row['saved'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "No browsing history found<br><br>";
}

// Check similarity_scores
$stmt = $conn->prepare("SELECT * FROM similarity_scores WHERE user_id = ? ORDER BY calculated_at DESC LIMIT 5");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>AI Recommendations (Last 5):</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Property ID</th><th>Score</th><th>Calculated At</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['property_id'] . "</td>";
        echo "<td>" . number_format($row['cosine_similarity'], 4) . "</td>";
        echo "<td>" . $row['calculated_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "No AI recommendations found<br><br>";
}

$conn->close();
?>
