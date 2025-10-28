<?php
/**
 * Simple Email Settings - No Admin Required
 * Use this for testing and configuration
 */
session_start();
require_once 'config/db_connect.php';
$conn = getDbConnection();

// Get current email configuration
$result = $conn->query("SELECT * FROM email_config WHERE id = 1");
$emailConfig = $result ? $result->fetch_assoc() : null;

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtpHost = $_POST['smtp_host'] ?? 'localhost';
    $smtpPort = intval($_POST['smtp_port'] ?? 25);
    $smtpUsername = $_POST['smtp_username'] ?? '';
    $smtpPassword = $_POST['smtp_password'] ?? '';
    $smtpEncryption = $_POST['smtp_encryption'] ?? 'none';
    $fromEmail = $_POST['from_email'] ?? 'noreply@homehub.com';
    $fromName = $_POST['from_name'] ?? 'HomeHub';
    $replyToEmail = $_POST['reply_to_email'] ?? 'support@homehub.com';
    $useSmtp = isset($_POST['use_smtp']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE email_config SET 
        smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?,
        smtp_encryption = ?, from_email = ?, from_name = ?, reply_to_email = ?,
        use_smtp = ? WHERE id = 1");
    
    $stmt->bind_param("sissssssi", $smtpHost, $smtpPort, $smtpUsername, $smtpPassword,
                      $smtpEncryption, $fromEmail, $fromName, $replyToEmail, $useSmtp);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">✅ Email settings updated successfully!</div>';
        // Refresh config
        $result = $conn->query("SELECT * FROM email_config WHERE id = 1");
        $emailConfig = $result->fetch_assoc();
    } else {
        $message = '<div class="alert alert-error">❌ Failed to update email settings.</div>';
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Settings - HomeHub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #8b5cf6;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #8b5cf6;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-save {
            background: #8b5cf6;
            color: white;
        }
        .btn-save:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }
        .btn-test {
            background: #10b981;
            color: white;
            margin-left: 10px;
        }
        .btn-test:hover {
            background: #059669;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #8b5cf6;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .info-box {
            background: #f0f9ff;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            display: flex;
            gap: 10px;
        }
        .links a {
            color: #8b5cf6;
            text-decoration: none;
            font-weight: 600;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Configuration</h1>
        <p class="subtitle">Configure email and SMTP settings for HomeHub notifications</p>
        
        <?php echo $message; ?>
        
        <form method="POST">
            <div class="section-title">SMTP Settings</div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" id="use_smtp" name="use_smtp" value="1" 
                       <?php echo ($emailConfig['use_smtp'] ?? 0) ? 'checked' : ''; ?>>
                <label for="use_smtp">Use SMTP (recommended for production)</label>
            </div>
            <p class="help-text">If unchecked, PHP's mail() function will be used</p>
            
            <div class="form-group">
                <label for="smtp_host">SMTP Host</label>
                <input type="text" id="smtp_host" name="smtp_host" 
                       value="<?php echo htmlspecialchars($emailConfig['smtp_host'] ?? 'localhost'); ?>"
                       placeholder="smtp.gmail.com">
                <p class="help-text">For Gmail: smtp.gmail.com</p>
            </div>
            
            <div class="form-group">
                <label for="smtp_port">SMTP Port</label>
                <input type="number" id="smtp_port" name="smtp_port" 
                       value="<?php echo htmlspecialchars($emailConfig['smtp_port'] ?? 25); ?>">
                <p class="help-text">Common: 587 (TLS), 465 (SSL), 25 (none)</p>
            </div>
            
            <div class="form-group">
                <label for="smtp_encryption">Encryption</label>
                <select id="smtp_encryption" name="smtp_encryption">
                    <option value="none" <?php echo ($emailConfig['smtp_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                    <option value="tls" <?php echo ($emailConfig['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                    <option value="ssl" <?php echo ($emailConfig['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="smtp_username">SMTP Username</label>
                <input type="text" id="smtp_username" name="smtp_username" 
                       value="<?php echo htmlspecialchars($emailConfig['smtp_username'] ?? ''); ?>"
                       placeholder="your-email@gmail.com">
            </div>
            
            <div class="form-group">
                <label for="smtp_password">SMTP Password</label>
                <input type="password" id="smtp_password" name="smtp_password" 
                       value="<?php echo htmlspecialchars($emailConfig['smtp_password'] ?? ''); ?>"
                       placeholder="Your SMTP password or App Password">
                <p class="help-text">⚠️ For Gmail, use an App Password (not your regular password)</p>
            </div>
            
            <div class="section-title">Email Identity</div>
            
            <div class="form-group">
                <label for="from_email">From Email</label>
                <input type="email" id="from_email" name="from_email" 
                       value="<?php echo htmlspecialchars($emailConfig['from_email'] ?? 'noreply@homehub.com'); ?>">
            </div>
            
            <div class="form-group">
                <label for="from_name">From Name</label>
                <input type="text" id="from_name" name="from_name" 
                       value="<?php echo htmlspecialchars($emailConfig['from_name'] ?? 'HomeHub'); ?>">
            </div>
            
            <div class="form-group">
                <label for="reply_to_email">Reply-To Email</label>
                <input type="email" id="reply_to_email" name="reply_to_email" 
                       value="<?php echo htmlspecialchars($emailConfig['reply_to_email'] ?? 'support@homehub.com'); ?>">
            </div>
            
            <div class="info-box">
                <strong>📖 Gmail App Password Setup:</strong>
                <ol style="margin: 10px 0 0 20px;">
                    <li>Go to your Google Account → Security</li>
                    <li>Enable 2-Step Verification (if not already)</li>
                    <li>Go to App Passwords</li>
                    <li>Generate new password for "Mail"</li>
                    <li>Copy the 16-character password and paste above</li>
                </ol>
            </div>
            
            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-save">💾 Save Settings</button>
                <button type="button" class="btn btn-test" onclick="testEmail()">📧 Send Test Email</button>
            </div>
        </form>
        
        <div class="links">
            <a href="test_email_notifications.php">← Back to Email Tests</a>
            <span style="color: #ddd;">|</span>
            <a href="admin/email-preview.php">Preview Email Templates</a>
            <span style="color: #ddd;">|</span>
            <a href="admin/login.php">Admin Login</a>
        </div>
    </div>
    
    <script>
        function testEmail() {
            if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                alert('Please log in first to send a test email');
                return;
            }
            
            if (confirm('This will send a test email to your registered email address. Continue?')) {
                fetch('api/test-email.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ Test email sent successfully! Check your inbox (and spam folder).');
                        } else {
                            alert('❌ Failed to send test email: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('❌ Error: ' + error.message);
                    });
            }
        }
    </script>
</body>
</html>
