<?php
/**
 * Email Notification Testing Tool
 * Complete testing suite for HomeHub email system
 * ONLY FOR LANDLORDS AND TENANTS
 */
session_start();
require_once 'config/db_connect.php';
require_once 'includes/email_functions.php';

$conn = getDbConnection();

// Check if user is logged in as landlord or tenant
$isAuthorized = false;
$userEmail = null;
$userName = null;

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'landlord' || $_SESSION['user_type'] === 'tenant') {
        $isAuthorized = true;
        $userType = $_SESSION['user_type'];
        $userId = $_SESSION['user_id'];
        
        // Get user details based on type
        if ($userType === 'landlord') {
            $stmt = $conn->prepare("SELECT u.email, u.first_name, u.last_name 
                                   FROM landlords l 
                                   JOIN users u ON l.user_id = u.id 
                                   WHERE l.id = ?");
        } else { // tenant
            $stmt = $conn->prepare("SELECT u.email, u.first_name, u.last_name 
                                   FROM tenants t 
                                   JOIN users u ON t.user_id = u.id 
                                   WHERE t.id = ?");
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            $userEmail = $user['email'];
            $userName = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $userEmail;
            $_SESSION['user_name'] = $user['first_name'];
        }
    }
}

$testResults = [];

// Handle test actions (only if authorized)
if ($isAuthorized && isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch($action) {
        case 'test_simple':
            // Simple test email
            $to = $_SESSION['user_email'] ?? 'test@example.com';
            $result = sendEmail(
                $to,
                "Simple Test Email - " . date('H:i:s'),
                getEmailTemplate('<h2>Simple Test</h2><p>This is a simple test email.</p>', 'Simple Test')
            );
            $testResults['simple'] = [
                'status' => $result ? 'success' : 'failed',
                'message' => $result ? "Test email sent to $to" : "Failed to send email"
            ];
            break;
            
        case 'test_visit_notification':
            // Test visit request notification
            $to = $_SESSION['user_email'] ?? 'test@example.com';
            $result = sendVisitRequestEmail(
                $to,
                $_SESSION['user_name'] ?? 'Test User',
                'John Tenant',
                'Modern 2BR Apartment in Manila',
                date('Y-m-d', strtotime('+3 days')),
                '14:00',
                1
            );
            $testResults['visit'] = [
                'status' => $result ? 'success' : 'failed',
                'message' => $result ? "Visit notification sent to $to" : "Failed to send visit notification"
            ];
            break;
            
        case 'test_booking_notification':
            // Test booking request notification
            $to = $_SESSION['user_email'] ?? 'test@example.com';
            $result = sendBookingRequestEmail(
                $to,
                $_SESSION['user_name'] ?? 'Test User',
                'Jane Tenant',
                'Cozy Studio in Makati',
                date('Y-m-d', strtotime('+1 month')),
                12,
                1
            );
            $testResults['booking'] = [
                'status' => $result ? 'success' : 'failed',
                'message' => $result ? "Booking notification sent to $to" : "Failed to send booking notification"
            ];
            break;
            
        case 'test_all_templates':
            // Test all email templates
            $to = $_SESSION['user_email'] ?? 'test@example.com';
            $userName = $_SESSION['user_name'] ?? 'Test User';
            $templates = [
                'visit_request' => sendVisitRequestEmail($to, $userName, 'John Tenant', 'Test Property', date('Y-m-d'), '14:00', 1),
                'booking_request' => sendBookingRequestEmail($to, $userName, 'Jane Tenant', 'Test Property', date('Y-m-d'), 12, 1),
                'reservation_approved' => sendReservationApprovedEmail($to, $userName, 'Test Property', date('Y-m-d'), 15000),
                'visit_approved' => sendVisitApprovedEmail($to, $userName, 'Test Property', date('Y-m-d'), '14:00', '09123456789'),
                'property_performance' => sendPropertyPerformanceEmail($to, $userName, 'Test Property', 25, 8),
                'new_message' => sendNewMessageEmail($to, $userName, 'John Doe', 'Hello, I am interested in your property...'),
                'welcome' => sendWelcomeEmail($to, $userName, 'tenant')
            ];
            $testResults['all_templates'] = [
                'status' => 'completed',
                'results' => $templates,
                'message' => 'Sent ' . count(array_filter($templates)) . ' out of ' . count($templates) . ' email templates successfully'
            ];
            break;
    }
}

// Check if email tables exist first
$tablesCheck = [
    'email_config' => $conn->query("SHOW TABLES LIKE 'email_config'")->num_rows > 0,
    'email_preferences' => $conn->query("SHOW TABLES LIKE 'email_preferences'")->num_rows > 0
];

// Check database configuration (only if table exists)
$emailConfig = null;
$emailConfigExists = false;
if ($tablesCheck['email_config']) {
    $result = $conn->query("SELECT * FROM email_config WHERE id = 1");
    if ($result) {
        $emailConfig = $result->fetch_assoc();
        $emailConfigExists = !empty($emailConfig);
    }
}

// Get sample users for testing
$sampleUsers = [];

// Get landlords
$landlordsResult = $conn->query("SELECT l.id, u.email, u.first_name, u.last_name, 'landlord' as user_type 
                                  FROM landlords l 
                                  JOIN users u ON l.user_id = u.id 
                                  LIMIT 3");
if ($landlordsResult) {
    while ($row = $landlordsResult->fetch_assoc()) {
        $sampleUsers[] = $row;
    }
}

// Get tenants
$tenantsResult = $conn->query("SELECT t.id, u.email, u.first_name, u.last_name, 'tenant' as user_type 
                                FROM tenants t 
                                JOIN users u ON t.user_id = u.id 
                                LIMIT 3");
if ($tenantsResult) {
    while ($row = $tenantsResult->fetch_assoc()) {
        $sampleUsers[] = $row;
    }
}

// Check PHP mail configuration
$mailFunction = function_exists('mail');
$mailConfigured = ini_get('SMTP') ? true : false;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Notifications Test - HomeHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            color: #8b5cf6;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #8b5cf6;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .status-card {
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ddd;
        }
        
        .status-card.success {
            background: #d1fae5;
            border-left-color: #10b981;
        }
        
        .status-card.warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }
        
        .status-card.error {
            background: #fee2e2;
            border-left-color: #ef4444;
        }
        
        .status-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .status-card .value {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .status-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .test-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #8b5cf6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #7c3aed;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
            transform: translateY(-2px);
        }
        
        .btn-info {
            background: #3b82f6;
            color: white;
        }
        
        .btn-info:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        
        .info-box {
            background: #f0f9ff;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .info-box strong {
            color: #1e40af;
        }
        
        .config-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .config-table th,
        .config-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .config-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .test-result {
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .test-result.success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
        }
        
        .test-result.failed {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
        
        .users-list {
            list-style: none;
        }
        
        .users-list li {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .users-list li:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Notifications Test Center</h1>
            <p>Test and verify your HomeHub email notification system</p>
        </div>
        
        <!-- Authorization Check -->
        <?php if (!$isAuthorized): ?>
        <div class="section" style="background: #fee2e2; border: 2px solid #ef4444;">
            <h2 class="section-title" style="color: #991b1b;">🔒 Login Required</h2>
            <div style="padding: 20px; background: white; border-radius: 10px; margin-top: 15px;">
                <h3 style="color: #dc2626; margin-bottom: 15px;">Please login as a Landlord or Tenant</h3>
                <p style="font-size: 16px; margin-bottom: 20px;">
                    Email notifications are only available for landlords and tenants.
                    Admins do not need email notifications.
                </p>
                <div style="display: flex; gap: 15px;">
                    <a href="login/login.html" class="btn btn-primary" style="font-size: 18px; padding: 15px 40px;">
                        🔑 Login as Landlord/Tenant
                    </a>
                    <a href="login/register.html" class="btn btn-success" style="font-size: 18px; padding: 15px 40px;">
                        📝 Register New Account
                    </a>
                </div>
            </div>
        </div>
        </div>
    </body>
    </html>
    <?php exit; ?>
    <?php endif; ?>
        
        <!-- Setup Required Warning -->
        <?php if (!$tablesCheck['email_config'] || !$tablesCheck['email_preferences']): ?>
        <div class="section" style="background: #fee2e2; border: 2px solid #ef4444;">
            <h2 class="section-title" style="color: #991b1b;">⚠️ Setup Required!</h2>
            <div style="padding: 20px; background: white; border-radius: 10px; margin-top: 15px;">
                <h3 style="color: #dc2626; margin-bottom: 15px;">Email tables are not set up yet.</h3>
                <p style="font-size: 16px; margin-bottom: 20px;">
                    The email notification system requires database tables to be created first.
                </p>
                <a href="setup_email.php" class="btn btn-primary" style="font-size: 18px; padding: 15px 40px;">
                    🔧 Run Email Setup Now
                </a>
                <p style="margin-top: 15px; color: #666;">
                    This will create the <code>email_config</code> and <code>email_preferences</code> tables.
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- System Status -->
        <div class="section">
            <h2 class="section-title">System Status</h2>
            <div class="status-grid">
                <div class="status-card <?php echo $mailFunction ? 'success' : 'error'; ?>">
                    <h3>PHP Mail Function</h3>
                    <div class="value">
                        <?php echo $mailFunction ? '✅ Available' : '❌ Not Available'; ?>
                    </div>
                </div>
                
                <div class="status-card <?php echo $emailConfigExists ? 'success' : 'error'; ?>">
                    <h3>Email Configuration</h3>
                    <div class="value">
                        <?php echo $emailConfigExists ? '✅ Configured' : '❌ Missing'; ?>
                    </div>
                </div>
                
                <div class="status-card <?php echo $tablesCheck['email_preferences'] ? 'success' : 'error'; ?>">
                    <h3>Email Preferences Table</h3>
                    <div class="value">
                        <?php echo $tablesCheck['email_preferences'] ? '✅ Exists' : '❌ Missing'; ?>
                    </div>
                </div>
                
                <div class="status-card <?php echo count($sampleUsers) > 0 ? 'success' : 'warning'; ?>">
                    <h3>Test Users Available</h3>
                    <div class="value">
                        <?php echo count($sampleUsers); ?> Users
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Current Configuration -->
        <?php if ($emailConfigExists): ?>
        <div class="section">
            <h2 class="section-title">Current Email Configuration</h2>
            <table class="config-table">
                <tr>
                    <th>Setting</th>
                    <th>Value</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td><strong>Use SMTP</strong></td>
                    <td><?php echo $emailConfig['use_smtp'] ? 'Yes' : 'No (PHP mail)'; ?></td>
                    <td>
                        <span class="badge <?php echo $emailConfig['use_smtp'] ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $emailConfig['use_smtp'] ? 'SMTP Mode' : 'Mail Mode'; ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>SMTP Host</strong></td>
                    <td><?php echo htmlspecialchars($emailConfig['smtp_host']); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td><strong>SMTP Port</strong></td>
                    <td><?php echo $emailConfig['smtp_port']; ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td><strong>Encryption</strong></td>
                    <td><?php echo strtoupper($emailConfig['smtp_encryption']); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td><strong>From Email</strong></td>
                    <td><?php echo htmlspecialchars($emailConfig['from_email']); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td><strong>From Name</strong></td>
                    <td><?php echo htmlspecialchars($emailConfig['from_name']); ?></td>
                    <td></td>
                </tr>
            </table>
            
            <div class="info-box">
                <strong>💡 Tip:</strong> You can modify these settings in the 
                <a href="admin/email-settings.php">Email Settings</a> page.
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Test Results -->
        <?php if (!empty($testResults)): ?>
        <div class="section">
            <h2 class="section-title">Test Results</h2>
            <?php foreach ($testResults as $test => $result): ?>
                <div class="test-result <?php echo $result['status']; ?>">
                    <strong><?php echo ucfirst($test); ?>:</strong> <?php echo $result['message']; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Quick Tests -->
        <div class="section">
            <h2 class="section-title">Quick Email Tests</h2>
            
            <?php if (!$tablesCheck['email_config']): ?>
                <div class="info-box" style="background: #fef3c7; border-color: #f59e0b;">
                    <strong>⚠️ Setup Required:</strong> Please run the email setup first before testing.
                    <br><br>
                    <a href="setup_email.php" class="btn btn-warning">Run Email Setup</a>
                </div>
            <?php else: ?>
                <p style="margin-bottom: 20px; color: #666;">
                    Run these tests to verify different types of email notifications are working correctly.
                </p>
                
                <div class="test-buttons">
                    <a href="?action=test_simple" class="btn btn-primary">
                        📨 Send Simple Test Email
                    </a>
                    
                    <a href="?action=test_visit_notification" class="btn btn-success">
                        🏠 Test Visit Notification
                    </a>
                    
                    <a href="?action=test_booking_notification" class="btn btn-warning">
                        📅 Test Booking Notification
                    </a>
                    
                    <a href="api/test-email.php" class="btn btn-info">
                        🔧 API Test Email
                    </a>
                    
                    <a href="?action=test_all_templates" class="btn btn-primary" style="grid-column: span 2;">
                        🎨 Test All Email Templates (7 emails)
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Available Users for Testing -->
        <?php if (!empty($sampleUsers)): ?>
        <div class="section">
            <h2 class="section-title">Available Test Users</h2>
            <ul class="users-list">
                <?php foreach ($sampleUsers as $user): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                        <br>
                        📧 <?php echo htmlspecialchars($user['email']); ?>
                        <br>
                        <span class="badge badge-success"><?php echo ucfirst($user['user_type']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Setup Guide -->
        <div class="section">
            <h2 class="section-title">Setup Checklist</h2>
            <div class="info-box">
                <h3 style="margin-bottom: 10px;">✅ Complete Setup Steps:</h3>
                <ol style="margin-left: 20px; line-height: 1.8;">
                    <li>Run <a href="setup_email.php"><code>setup_email.php</code></a> to create email tables</li>
                    <li>Configure SMTP settings in <a href="admin/email-settings.php">Email Settings</a></li>
                    <li>Send a test email using the "Send Test Email" button</li>
                    <li>Preview email templates at <a href="admin/email-preview.php">Email Preview</a></li>
                    <li>Test notifications by creating actual bookings/visits</li>
                </ol>
            </div>
            
            <div class="info-box" style="background: #fef3c7; border-color: #f59e0b; margin-top: 15px;">
                <strong>⚠️ Gmail Users:</strong> You need to use an App Password, not your regular Gmail password.
                <br>
                Go to Google Account → Security → 2-Step Verification → App Passwords
            </div>
        </div>
        
        <!-- Troubleshooting -->
        <div class="section">
            <h2 class="section-title">Troubleshooting</h2>
            <div style="line-height: 1.8;">
                <p><strong>Email not received?</strong></p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Check your spam/junk folder</li>
                    <li>Verify the recipient email address is correct</li>
                    <li>Check error_log.txt for error messages</li>
                    <li>Ensure PHP mail() is configured or SMTP settings are correct</li>
                    <li>For SMTP: Verify host, port, username, and password</li>
                    <li>For Gmail: Ensure you're using an App Password</li>
                </ul>
                
                <p style="margin-top: 15px;"><strong>Check PHP mail configuration:</strong></p>
                <pre style="background: #f3f4f6; padding: 10px; border-radius: 5px; overflow-x: auto;">
SMTP = <?php echo ini_get('SMTP') ?: 'Not set'; ?>

sendmail_path = <?php echo ini_get('sendmail_path') ?: 'Not set'; ?>
</pre>
            </div>
        </div>
        
        <!-- Links -->
        <div class="section">
            <h2 class="section-title">Related Links</h2>
            <div class="test-buttons">
                <?php if (isset($_SESSION['user_type'])): ?>
                    <a href="<?php echo $_SESSION['user_type']; ?>/email-settings.php" class="btn btn-primary">
                        ⚙️ Email Settings
                    </a>
                <?php else: ?>
                    <a href="email_settings_simple.php" class="btn btn-primary">
                        ⚙️ Email Settings
                    </a>
                <?php endif; ?>
                <a href="admin/email-preview.php" class="btn btn-success">
                    👁️ Email Templates
                </a>
                <a href="setup_email.php" class="btn btn-warning">
                    🔧 Run Setup
                </a>
                <a href="EMAIL_TESTING_GUIDE.md" class="btn btn-info">
                    📖 Documentation
                </a>
            </div>
        </div>
    </div>
</body>
</html>
