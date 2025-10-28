<?php
/**
 * Email Notification Functions for HomeHub
 * Sends email notifications for various events
 */

// Load environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Load PHPMailer
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email notification
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @param array $headers Additional email headers
 * @return bool Success or failure
 */
function sendEmail($to, $subject, $message, $headers = []) {
    // Get email configuration from database
    $conn = getDbConnection();
    $configResult = $conn->query("SELECT * FROM email_config WHERE id = 1");
    $config = $configResult ? $configResult->fetch_assoc() : null;
    
    // Debug logging
    error_log("Email Config Debug - use_smtp: " . ($config ? $config['use_smtp'] : 'NULL'));
    error_log("Email Config Debug - smtp_host: " . ($config ? $config['smtp_host'] : 'NULL'));
    
    // Check if we should use SMTP
    if ($config && $config['use_smtp'] == 1) {
        error_log("Using SMTP for email to: " . $to);
        // Use PHPMailer for SMTP
        return sendEmailViaSMTP($to, $subject, $message, $config);
    } else {
        error_log("Using PHP mail() for email to: " . $to . " (use_smtp=" . ($config ? $config['use_smtp'] : 'NULL') . ")");
        // Use PHP's mail() function
        return sendEmailViaMail($to, $subject, $message, $headers, $config);
    }
}

/**
 * Send email using SMTP (with PHPMailer)
 */
function sendEmailViaSMTP($to, $subject, $message, $config) {
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // PHPMailer not installed, try using ini_set for SMTP
        return sendEmailViaSMTPSimple($to, $subject, $message, $config);
    }
    
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        
        // Encryption
        if ($config['smtp_encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($config['smtp_encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        
        $mail->Port = $config['smtp_port'];
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to);
        $mail->addReplyTo($config['reply_to_email'], $config['from_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->CharSet = 'UTF-8';
        
        $mail->send();
        error_log("Email sent successfully via SMTP to: " . $to);
        return true;
    } catch (Exception $e) {
        error_log("SMTP Email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email using ini_set for SMTP (fallback when PHPMailer not available)
 */
function sendEmailViaSMTPSimple($to, $subject, $message, $config) {
    // Configure SMTP using ini_set
    ini_set('SMTP', $config['smtp_host']);
    ini_set('smtp_port', $config['smtp_port']);
    
    // Build headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
        'Reply-To: ' . $config['reply_to_email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Add authentication if needed (note: this is limited, PHPMailer is better)
    if (!empty($config['smtp_username']) && !empty($config['smtp_password'])) {
        ini_set('sendmail_from', $config['from_email']);
    }
    
    $headerString = implode("\r\n", $headers);
    
    $result = @mail($to, $subject, $message, $headerString);
    
    if ($result) {
        error_log("Email sent successfully via SMTP (ini_set) to: " . $to);
    } else {
        error_log("Failed to send email via SMTP (ini_set) to: " . $to);
    }
    
    return $result;
}

/**
 * Send email using PHP's mail() function
 */
function sendEmailViaMail($to, $subject, $message, $headers = [], $config = null) {
    // Default headers
    $fromEmail = $config ? $config['from_email'] : 'noreply@homehub.com';
    $fromName = $config ? $config['from_name'] : 'HomeHub';
    $replyTo = $config ? $config['reply_to_email'] : 'support@homehub.com';
    
    $defaultHeaders = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $replyTo,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Merge with custom headers
    $allHeaders = array_merge($defaultHeaders, $headers);
    $headerString = implode("\r\n", $allHeaders);
    
    // Send email (suppress warnings with @)
    try {
        $result = @mail($to, $subject, $message, $headerString);
        if ($result) {
            error_log("Email sent successfully via mail() to: " . $to);
        } else {
            error_log("Failed to send email via mail() to: " . $to);
        }
        return $result;
    } catch (Exception $e) {
        error_log("Email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get email template wrapper
 */
function getEmailTemplate($content, $title = 'HomeHub Notification') {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 28px;
            }
            .content {
                padding: 30px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background: #8b5cf6;
                color: white;
                text-decoration: none;
                border-radius: 25px;
                margin: 20px 0;
                font-weight: bold;
            }
            .footer {
                background: #f8f9fa;
                padding: 20px;
                text-align: center;
                font-size: 12px;
                color: #666;
            }
            .highlight {
                background: #f0e7ff;
                padding: 15px;
                border-left: 4px solid #8b5cf6;
                margin: 15px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🏠 HomeHub</h1>
            </div>
            <div class="content">
                ' . $content . '
            </div>
            <div class="footer">
                <p>This is an automated message from HomeHub. Please do not reply to this email.</p>
                <p>&copy; ' . date('Y') . ' HomeHub. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

/**
 * Send visit request email to landlord
 */
function sendVisitRequestEmail($landlordEmail, $landlordName, $tenantName, $propertyTitle, $visitDate, $visitTime, $visitId) {
    $formattedDate = date('l, F j, Y', strtotime($visitDate));
    $formattedTime = date('g:i A', strtotime($visitTime));
    
    $content = '
        <h2>New Visit Request</h2>
        <p>Hello ' . htmlspecialchars($landlordName) . ',</p>
        <p><strong>' . htmlspecialchars($tenantName) . '</strong> has requested to visit your property:</p>
        <div class="highlight">
            <p><strong>Property:</strong> ' . htmlspecialchars($propertyTitle) . '</p>
            <p><strong>Visit Date:</strong> ' . $formattedDate . '</p>
            <p><strong>Visit Time:</strong> ' . $formattedTime . '</p>
        </div>
        <p>Please log in to your HomeHub account to approve or decline this request.</p>
        <a href="' . APP_URL . '/landlord/bookings.php" class="button">View Request</a>
    ';
    
    $subject = "New Visit Request for " . $propertyTitle;
    $message = getEmailTemplate($content, $subject);
    
    return sendEmail($landlordEmail, $subject, $message);
}

/**
 * Send booking/reservation request email to landlord
 */
function sendBookingRequestEmail($landlordEmail, $landlordName, $tenantName, $propertyTitle, $moveInDate, $leaseDuration, $reservationId) {
    $formattedDate = date('F j, Y', strtotime($moveInDate));
    
    $content = '
        <h2>New Reservation Request</h2>
        <p>Hello ' . htmlspecialchars($landlordName) . ',</p>
        <p><strong>' . htmlspecialchars($tenantName) . '</strong> has requested to reserve your property:</p>
        <div class="highlight">
            <p><strong>Property:</strong> ' . htmlspecialchars($propertyTitle) . '</p>
            <p><strong>Move-in Date:</strong> ' . $formattedDate . '</p>
            <p><strong>Lease Duration:</strong> ' . $leaseDuration . ' months</p>
        </div>
        <p>Please log in to your HomeHub account to review and respond to this reservation request.</p>
        <a href="' . APP_URL . '/landlord/bookings.php" class="button">View Reservation</a>
    ';
    
    $subject = "New Reservation Request for " . $propertyTitle;
    $message = getEmailTemplate($content, $subject);
    
    return sendEmail($landlordEmail, $subject, $message);
}

/**
 * Send reservation approved email to tenant
 */
function sendReservationApprovedEmail($tenantEmail, $tenantName, $propertyTitle, $moveInDate, $rentAmount) {
    $formattedDate = date('F j, Y', strtotime($moveInDate));
    
    $content = '
        <h2>Reservation Approved! 🎉</h2>
        <p>Hello ' . htmlspecialchars($tenantName) . ',</p>
        <p>Great news! Your reservation has been approved:</p>
        <div class="highlight">
            <p><strong>Property:</strong> ' . htmlspecialchars($propertyTitle) . '</p>
            <p><strong>Move-in Date:</strong> ' . $formattedDate . '</p>
            <p><strong>Monthly Rent:</strong> ₱' . number_format($rentAmount, 2) . '</p>
        </div>
        <p>The landlord will contact you shortly with next steps for move-in arrangements.</p>
        <a href="' . APP_URL . '/tenant/bookings.php" class="button">View Details</a>
    ';
    
    $subject = "Reservation Approved: " . $propertyTitle;
    $message = getEmailTemplate($content, $subject);
    
    return sendEmail($tenantEmail, $subject, $message);
}

/**
 * Send visit approved email to tenant
 */
function sendVisitApprovedEmail($tenantEmail, $tenantName, $propertyTitle, $visitDate, $visitTime, $landlordContact) {
    $formattedDate = date('l, F j, Y', strtotime($visitDate));
    $formattedTime = date('g:i A', strtotime($visitTime));
    
    $content = '
        <h2>Visit Request Approved! ✅</h2>
        <p>Hello ' . htmlspecialchars($tenantName) . ',</p>
        <p>Your visit request has been approved:</p>
        <div class="highlight">
            <p><strong>Property:</strong> ' . htmlspecialchars($propertyTitle) . '</p>
            <p><strong>Visit Date:</strong> ' . $formattedDate . '</p>
            <p><strong>Visit Time:</strong> ' . $formattedTime . '</p>
            <p><strong>Contact:</strong> ' . htmlspecialchars($landlordContact) . '</p>
        </div>
        <p>Please arrive on time. Looking forward to showing you the property!</p>
        <a href="' . APP_URL . '/tenant/bookings.php" class="button">View Details</a>
    ';
    
    $subject = "Visit Approved: " . $propertyTitle;
    $message = getEmailTemplate($content, $subject);
    
    return sendEmail($tenantEmail, $subject, $message);
}

/**
 * Send property performance email to landlord
 */
function sendPropertyPerformanceEmail($landlordEmail, $landlordName, $propertyTitle, $viewCount, $savedCount) {
    $content = '
        <h2>Your Property is Trending! 📈</h2>
        <p>Hello ' . htmlspecialchars($landlordName) . ',</p>
        <p>Your property <strong>' . htmlspecialchars($propertyTitle) . '</strong> is getting attention!</p>
        <div class="highlight">
            <p><strong>Views Today:</strong> ' . $viewCount . '</p>
            <p><strong>Saves:</strong> ' . $savedCount . '</p>
        </div>
        <p>Great properties attract great tenants. Keep your listing updated for best results!</p>
        <a href="' . APP_URL . '/landlord/properties.php" class="button">Manage Property</a>
    ';
    
    $subject = "Your Property is Trending - " . $propertyTitle;
    $message = getEmailTemplate($content, $subject);
    
    return sendEmail($landlordEmail, $subject, $message);
}

/**
 * Send new message email notification
 */
function sendNewMessageEmail($recipientEmail, $recipientName, $senderName, $messagePreview) {
    $content = '
        <h2>New Message Received 💬</h2>
        <p>Hello ' . htmlspecialchars($recipientName) . ',</p>
        <p>You have received a new message from <strong>' . htmlspecialchars($senderName) . '</strong>:</p>
        <div class="highlight">
            <p>' . htmlspecialchars(substr($messagePreview, 0, 150)) . '...</p>
        </div>
        <a href="' . APP_URL . '/tenant/messages.php" class="button">Read Message</a>
    ';
    
    $subject = "New Message from " . $senderName;
    $message = getEmailTemplate($content, $subject);
    
    return sendEmail($recipientEmail, $subject, $message);
}

/**
 * Send welcome email to new user
 */
function sendWelcomeEmail($userEmail, $userName, $userType) {
    $typeLabel = $userType === 'landlord' ? 'Landlord' : 'Tenant';
    
    $content = '
        <h2>Welcome to HomeHub! 🏠</h2>
        <p>Hello ' . htmlspecialchars($userName) . ',</p>
        <p>Thank you for joining HomeHub as a <strong>' . $typeLabel . '</strong>!</p>
        <p>We\'re excited to help you ' . ($userType === 'landlord' ? 'find great tenants for your properties' : 'find your perfect home') . '.</p>
        <div class="highlight">
            <p><strong>Getting Started:</strong></p>
            <ul>
                <li>' . ($userType === 'landlord' ? 'List your first property' : 'Browse available properties') . '</li>
                <li>Complete your profile</li>
                <li>Explore our AI-powered recommendations</li>
            </ul>
        </div>
        <a href="' . APP_URL . '/' . $userType . '/dashboard.php" class="button">Go to Dashboard</a>
    ';
    
    $subject = "Welcome to HomeHub!";
    $message = getEmailTemplate($content, $subject);
    
    return sendEmail($userEmail, $subject, $message);
}

?>
