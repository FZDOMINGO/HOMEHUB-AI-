<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';

// Initialize session
initSession();

// Check if user is logged in as landlord or tenant (NOT admin)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    redirect('login/login.html');
    exit;
}

// Only allow landlords and tenants
if (!in_array($_SESSION['user_type'], ['landlord', 'tenant'])) {
    die("Email notifications are only available for landlords and tenants. Admins do not need email notifications.");
}

require_once __DIR__ . '/../includes/email_functions.php';

// Sample data for preview
$sampleData = [
    'landlordName' => 'John Doe',
    'tenantName' => 'Jane Smith',
    'propertyTitle' => 'Modern 2BR Apartment in Makati',
    'visitDate' => date('Y-m-d', strtotime('+7 days')),
    'visitTime' => '14:00:00',
    'moveInDate' => date('Y-m-d', strtotime('+30 days')),
    'leaseDuration' => 12,
    'rentAmount' => 35000,
    'viewCount' => 127,
    'savedCount' => 15,
    'senderName' => 'Mike Johnson',
    'messagePreview' => 'Hi, I am very interested in your property. Can we schedule a viewing?',
    'landlordContact' => '+63 912 345 6789'
];

$templateType = $_GET['template'] ?? 'visit_request';
$emailContent = '';

switch ($templateType) {
    case 'visit_request':
        $formattedDate = date('l, F j, Y', strtotime($sampleData['visitDate']));
        $formattedTime = date('g:i A', strtotime($sampleData['visitTime']));
        
        $emailContent = '
            <h2>New Visit Request</h2>
            <p>Hello ' . htmlspecialchars($sampleData['landlordName']) . ',</p>
            <p><strong>' . htmlspecialchars($sampleData['tenantName']) . '</strong> has requested to visit your property:</p>
            <div class="highlight">
                <p><strong>Property:</strong> ' . htmlspecialchars($sampleData['propertyTitle']) . '</p>
                <p><strong>Visit Date:</strong> ' . $formattedDate . '</p>
                <p><strong>Visit Time:</strong> ' . $formattedTime . '</p>
            </div>
            <p>Please log in to your HomeHub account to approve or decline this request.</p>
            <a href="https://homehubai.shop/landlord/bookings.php" class="button">View Request</a>
        ';
        break;
        
    case 'booking_request':
        $formattedDate = date('F j, Y', strtotime($sampleData['moveInDate']));
        
        $emailContent = '
            <h2>New Reservation Request</h2>
            <p>Hello ' . htmlspecialchars($sampleData['landlordName']) . ',</p>
            <p><strong>' . htmlspecialchars($sampleData['tenantName']) . '</strong> has requested to reserve your property:</p>
            <div class="highlight">
                <p><strong>Property:</strong> ' . htmlspecialchars($sampleData['propertyTitle']) . '</p>
                <p><strong>Move-in Date:</strong> ' . $formattedDate . '</p>
                <p><strong>Lease Duration:</strong> ' . $sampleData['leaseDuration'] . ' months</p>
            </div>
            <p>Please log in to your HomeHub account to review and respond to this reservation request.</p>
            <a href="https://homehubai.shop/landlord/bookings.php" class="button">View Reservation</a>
        ';
        break;
        
    case 'reservation_approved':
        $formattedDate = date('F j, Y', strtotime($sampleData['moveInDate']));
        
        $emailContent = '
            <h2>Reservation Approved! 🎉</h2>
            <p>Hello ' . htmlspecialchars($sampleData['tenantName']) . ',</p>
            <p>Great news! Your reservation has been approved:</p>
            <div class="highlight">
                <p><strong>Property:</strong> ' . htmlspecialchars($sampleData['propertyTitle']) . '</p>
                <p><strong>Move-in Date:</strong> ' . $formattedDate . '</p>
                <p><strong>Monthly Rent:</strong> ₱' . number_format($sampleData['rentAmount'], 2) . '</p>
            </div>
            <p>The landlord will contact you shortly with next steps for move-in arrangements.</p>
            <a href="https://homehubai.shop/tenant/bookings.php" class="button">View Details</a>
        ';
        break;
        
    case 'visit_approved':
        $formattedDate = date('l, F j, Y', strtotime($sampleData['visitDate']));
        $formattedTime = date('g:i A', strtotime($sampleData['visitTime']));
        
        $emailContent = '
            <h2>Visit Request Approved! ✅</h2>
            <p>Hello ' . htmlspecialchars($sampleData['tenantName']) . ',</p>
            <p>Your visit request has been approved:</p>
            <div class="highlight">
                <p><strong>Property:</strong> ' . htmlspecialchars($sampleData['propertyTitle']) . '</p>
                <p><strong>Visit Date:</strong> ' . $formattedDate . '</p>
                <p><strong>Visit Time:</strong> ' . $formattedTime . '</p>
                <p><strong>Contact:</strong> ' . htmlspecialchars($sampleData['landlordContact']) . '</p>
            </div>
            <p>Please arrive on time. Looking forward to showing you the property!</p>
            <a href="https://homehubai.shop/tenant/bookings.php" class="button">View Details</a>
        ';
        break;
        
    case 'property_performance':
        $emailContent = '
            <h2>Your Property is Trending! 📈</h2>
            <p>Hello ' . htmlspecialchars($sampleData['landlordName']) . ',</p>
            <p>Your property <strong>' . htmlspecialchars($sampleData['propertyTitle']) . '</strong> is getting attention!</p>
            <div class="highlight">
                <p><strong>Views Today:</strong> ' . $sampleData['viewCount'] . '</p>
                <p><strong>Saves:</strong> ' . $sampleData['savedCount'] . '</p>
            </div>
            <p>Great properties attract great tenants. Keep your listing updated for best results!</p>
            <a href="https://homehubai.shop/landlord/properties.php" class="button">Manage Property</a>
        ';
        break;
        
    case 'new_message':
        $emailContent = '
            <h2>New Message Received 💬</h2>
            <p>Hello ' . htmlspecialchars($sampleData['landlordName']) . ',</p>
            <p>You have received a new message from <strong>' . htmlspecialchars($sampleData['senderName']) . '</strong>:</p>
            <div class="highlight">
                <p>' . htmlspecialchars($sampleData['messagePreview']) . '</p>
            </div>
            <a href="https://homehubai.shop/tenant/messages.php" class="button">Read Message</a>
        ';
        break;
        
    case 'welcome':
        $emailContent = '
            <h2>Welcome to HomeHub! 🏠</h2>
            <p>Hello ' . htmlspecialchars($sampleData['tenantName']) . ',</p>
            <p>Thank you for joining HomeHub as a <strong>Tenant</strong>!</p>
            <p>We\'re excited to help you find your perfect home.</p>
            <div class="highlight">
                <p><strong>Getting Started:</strong></p>
                <ul>
                    <li>Browse available properties</li>
                    <li>Complete your profile</li>
                    <li>Explore our AI-powered recommendations</li>
                </ul>
            </div>
            <a href="https://homehubai.shop/tenant/dashboard.php" class="button">Go to Dashboard</a>
        ';
        break;
}

$fullEmail = getEmailTemplate($emailContent, 'HomeHub Email Preview');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates Preview - HomeHub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }
        
        .controls {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .controls h1 {
            margin: 0 0 15px 0;
            color: #8b5cf6;
        }
        
        .controls label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .controls select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .preview-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        iframe {
            width: 100%;
            min-height: 600px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="controls">
        <h1>📧 Email Template Preview</h1>
        <label for="template-select">Select Email Template:</label>
        <select id="template-select" onchange="loadTemplate()">
            <option value="visit_request" <?php echo $templateType === 'visit_request' ? 'selected' : ''; ?>>Visit Request (to Landlord)</option>
            <option value="booking_request" <?php echo $templateType === 'booking_request' ? 'selected' : ''; ?>>Booking Request (to Landlord)</option>
            <option value="reservation_approved" <?php echo $templateType === 'reservation_approved' ? 'selected' : ''; ?>>Reservation Approved (to Tenant)</option>
            <option value="visit_approved" <?php echo $templateType === 'visit_approved' ? 'selected' : ''; ?>>Visit Approved (to Tenant)</option>
            <option value="property_performance" <?php echo $templateType === 'property_performance' ? 'selected' : ''; ?>>Property Performance (to Landlord)</option>
            <option value="new_message" <?php echo $templateType === 'new_message' ? 'selected' : ''; ?>>New Message</option>
            <option value="welcome" <?php echo $templateType === 'welcome' ? 'selected' : ''; ?>>Welcome Email</option>
        </select>
    </div>
    
    <div class="preview-container">
        <iframe srcdoc="<?php echo htmlspecialchars($fullEmail); ?>"></iframe>
    </div>
    
    <script>
        function loadTemplate() {
            const template = document.getElementById('template-select').value;
            window.location.href = '?template=' + template;
        }
    </script>
</body>
</html>
