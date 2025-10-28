<?php
/**
 * Test Booking Email Functionality
 * This will test if booking emails are being sent when you make a reservation
 */

require_once 'config/db_connect.php';
require_once 'includes/email_functions.php';

echo "<h1>Booking Email Test</h1>";

$conn = getDbConnection();

// Check email configuration
echo "<h2>1. Email Configuration Status</h2>";
$result = $conn->query("SELECT use_smtp, smtp_host, smtp_port, smtp_username, LENGTH(smtp_password) as pass_len FROM email_config WHERE id = 1");
if ($result && $row = $result->fetch_assoc()) {
    echo "✅ SMTP Enabled: " . ($row['use_smtp'] ? 'YES' : 'NO') . "<br>";
    echo "✅ SMTP Host: " . $row['smtp_host'] . "<br>";
    echo "✅ SMTP Port: " . $row['smtp_port'] . "<br>";
    echo "✅ SMTP Username: " . $row['smtp_username'] . "<br>";
    echo "✅ Password Length: " . $row['pass_len'] . " characters<br>";
} else {
    echo "❌ Email configuration not found!<br>";
}

// Check for recent reservations
echo "<h2>2. Recent Reservations (Last 5)</h2>";
$result = $conn->query("
    SELECT 
        pr.id, 
        pr.property_id,
        pr.tenant_id,
        pr.move_in_date,
        pr.lease_duration,
        pr.reservation_date,
        p.title as property_title,
        p.landlord_id,
        l.user_id as landlord_user_id,
        t.user_id as tenant_user_id
    FROM property_reservations pr
    JOIN properties p ON pr.property_id = p.id
    JOIN landlords l ON p.landlord_id = l.id
    JOIN tenants t ON pr.tenant_id = t.id
    ORDER BY pr.reservation_date DESC
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Property</th><th>Move-in Date</th><th>Duration</th><th>Reservation Date</th><th>Action</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['property_title']) . "</td>";
        echo "<td>" . date('M j, Y', strtotime($row['move_in_date'])) . "</td>";
        echo "<td>" . $row['lease_duration'] . " months</td>";
        echo "<td>" . date('M j, Y g:i A', strtotime($row['reservation_date'])) . "</td>";
        echo "<td><a href='?test_reservation=" . $row['id'] . "'>Test Email</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No reservations found yet. Make a reservation first!<br>";
}

// Test email for a specific reservation
if (isset($_GET['test_reservation'])) {
    $reservationId = intval($_GET['test_reservation']);
    
    echo "<h2>3. Testing Email for Reservation #$reservationId</h2>";
    
    // Get reservation details
    $stmt = $conn->prepare("
        SELECT 
            pr.id,
            pr.move_in_date,
            pr.lease_duration,
            p.title as property_title,
            p.landlord_id,
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
        WHERE pr.id = ?
    ");
    
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        echo "<strong>Reservation Details:</strong><br>";
        echo "Property: " . htmlspecialchars($data['property_title']) . "<br>";
        echo "Landlord: " . htmlspecialchars($data['landlord_first'] . ' ' . $data['landlord_last']) . " (" . $data['landlord_email'] . ")<br>";
        echo "Tenant: " . htmlspecialchars($data['tenant_first'] . ' ' . $data['tenant_last']) . "<br>";
        echo "Move-in Date: " . date('F j, Y', strtotime($data['move_in_date'])) . "<br>";
        echo "Lease Duration: " . $data['lease_duration'] . " months<br><br>";
        
        echo "<strong>Sending Test Email...</strong><br>";
        
        $landlordEmail = $data['landlord_email'];
        $landlordName = $data['landlord_first'] . ' ' . $data['landlord_last'];
        $tenantName = $data['tenant_first'] . ' ' . $data['tenant_last'];
        $propertyTitle = $data['property_title'];
        $moveInDate = $data['move_in_date'];
        $leaseDuration = $data['lease_duration'];
        
        // Send the email
        $result = sendBookingRequestEmail($landlordEmail, $landlordName, $tenantName, $propertyTitle, $moveInDate, $leaseDuration, $reservationId);
        
        if ($result) {
            echo "✅ <strong style='color: green;'>Email sent successfully to $landlordEmail!</strong><br>";
            echo "Check the landlord's inbox for the reservation notification.<br>";
        } else {
            echo "❌ <strong style='color: red;'>Email sending failed!</strong><br>";
            echo "Check error_log.txt for details.<br>";
        }
    } else {
        echo "❌ Reservation not found!<br>";
    }
}

// Check error log
echo "<h2>4. Recent Email Logs</h2>";
if (file_exists('error_log.txt')) {
    $logs = file('error_log.txt');
    $emailLogs = array_filter($logs, function($line) {
        return stripos($line, 'email') !== false || stripos($line, 'smtp') !== false || stripos($line, 'BOOKING EMAIL') !== false;
    });
    
    $recentLogs = array_slice(array_reverse($emailLogs), 0, 20);
    
    if (count($recentLogs) > 0) {
        echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: auto;'>";
        foreach ($recentLogs as $log) {
            echo htmlspecialchars($log);
        }
        echo "</pre>";
    } else {
        echo "No email-related logs found.<br>";
    }
} else {
    echo "Error log file not found.<br>";
}

echo "<hr>";
echo "<h2>How to Test:</h2>";
echo "<ol>";
echo "<li>Make a reservation on the website (as a tenant)</li>";
echo "<li>Reload this page to see the reservation appear</li>";
echo "<li>Click 'Test Email' next to the reservation</li>";
echo "<li>Check the landlord's email inbox</li>";
echo "<li>Check the logs below to see what happened</li>";
echo "</ol>";

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #333; }
    h2 { color: #666; margin-top: 30px; }
    table { border-collapse: collapse; margin-top: 10px; }
    th { background: #4CAF50; color: white; }
    td, th { padding: 8px; text-align: left; }
    tr:nth-child(even) { background: #f9f9f9; }
    a { color: #4CAF50; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
