<?php
// Test if booking email sends for the latest reservation
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/email_test_log.txt');

require_once 'config/db_connect.php';
require_once 'includes/email_functions.php';

$conn = getDbConnection();

echo "<h1>Testing Booking Email for Latest Reservation</h1>";

// Get the latest reservation with all details
$result = $conn->query("
    SELECT 
        pr.id,
        pr.move_in_date,
        pr.lease_duration,
        p.title as property_title,
        l.user_id as landlord_user_id,
        t.user_id as tenant_user_id,
        lu.email as landlord_email,
        lu.first_name as landlord_first,
        lu.last_name as landlord_last,
        tu.first_name as tenant_first,
        tu.last_name as tenant_last
    FROM property_reservations pr
    JOIN properties p ON pr.property_id = p.id
    JOIN landlords l ON p.landlord_id = l.id
    JOIN tenants t ON pr.tenant_id = t.id
    JOIN users lu ON l.user_id = lu.id
    JOIN users tu ON t.user_id = tu.id
    ORDER BY pr.reservation_date DESC
    LIMIT 1
");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    
    echo "<h2>Reservation Details:</h2>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
    $landlordEmail = $data['landlord_email'];
    $landlordName = $data['landlord_first'] . ' ' . $data['landlord_last'];
    $tenantName = $data['tenant_first'] . ' ' . $data['tenant_last'];
    $propertyTitle = $data['property_title'];
    $moveInDate = $data['move_in_date'];
    $leaseDuration = $data['lease_duration'];
    $reservationId = $data['id'];
    
    echo "<h2>Email Details:</h2>";
    echo "To: " . htmlspecialchars($landlordEmail) . "<br>";
    echo "Landlord Name: " . htmlspecialchars($landlordName) . "<br>";
    echo "Tenant Name: " . htmlspecialchars($tenantName) . "<br>";
    echo "Property: " . htmlspecialchars($propertyTitle) . "<br>";
    echo "Move-in Date: " . $moveInDate . "<br>";
    echo "Lease Duration: " . $leaseDuration . " months<br>";
    echo "<br>";
    
    echo "<h2>Sending Email...</h2>";
    
    // Test if function exists
    if (function_exists('sendBookingRequestEmail')) {
        echo "✅ Function sendBookingRequestEmail exists<br><br>";
        
        // Send the email
        $result = sendBookingRequestEmail(
            $landlordEmail, 
            $landlordName, 
            $tenantName, 
            $propertyTitle, 
            $moveInDate, 
            $leaseDuration, 
            $reservationId
        );
        
        if ($result) {
            echo "<h3 style='color: green;'>✅ EMAIL SENT SUCCESSFULLY!</h3>";
            echo "Check the inbox for: " . htmlspecialchars($landlordEmail);
        } else {
            echo "<h3 style='color: red;'>❌ EMAIL SENDING FAILED</h3>";
            echo "Check email_test_log.txt for errors";
        }
    } else {
        echo "❌ Function sendBookingRequestEmail does NOT exist!<br>";
        echo "Check includes/email_functions.php<br>";
    }
    
} else {
    echo "<p>No reservations found in database.</p>";
    echo "<p>Make a reservation first, then run this test again.</p>";
}

echo "<hr>";
echo "<h2>Error Log:</h2>";
if (file_exists('email_test_log.txt')) {
    echo "<pre>";
    echo htmlspecialchars(file_get_contents('email_test_log.txt'));
    echo "</pre>";
} else {
    echo "No errors logged.";
}

$conn->close();
?>
