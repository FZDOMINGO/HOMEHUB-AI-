<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

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

$conn = getDbConnection();

// Get current email configuration
$result = $conn->query("SELECT * FROM email_config WHERE id = 1");
$emailConfig = $result->fetch_assoc();

// Handle form submission
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
        $successMessage = "Email settings updated successfully!";
        // Refresh config
        $result = $conn->query("SELECT * FROM email_config WHERE id = 1");
        $emailConfig = $result->fetch_assoc();
    } else {
        $errorMessage = "Failed to update email settings.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Settings - HomeHub Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .email-settings-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        .btn-save {
            background: #8b5cf6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }
        
        .btn-test {
            background: #10b981;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
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
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #8b5cf6;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="email-settings-container">
        <div style="margin-bottom: 20px;">
            <a href="javascript:history.back()" style="color: #8b5cf6; text-decoration: none; font-weight: 600;">
                ← Back
            </a>
        </div>
        <h1>Email Configuration</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
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
                <p class="help-text">e.g., smtp.gmail.com, smtp.office365.com, localhost</p>
            </div>
            
            <div class="form-group">
                <label for="smtp_port">SMTP Port</label>
                <input type="number" id="smtp_port" name="smtp_port" 
                       value="<?php echo htmlspecialchars($emailConfig['smtp_port'] ?? 25); ?>">
                <p class="help-text">Common ports: 25 (no encryption), 587 (TLS), 465 (SSL)</p>
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
                       placeholder="Your SMTP password">
                <p class="help-text">For Gmail, use an App Password instead of your regular password</p>
            </div>
            
            <div class="section-title" style="margin-top: 30px;">Email Identity</div>
            
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
            
            <div style="margin-top: 30px;">
                <button type="submit" class="btn-save">Save Settings</button>
                <button type="button" class="btn-test" onclick="testEmail()">Send Test Email</button>
            </div>
        </form>
    </div>
    
    <script>
        function testEmail() {
            if (confirm('This will send a test email to your registered email address. Continue?')) {
                fetch('../api/test-email.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Test email sent successfully! Check your inbox.');
                        } else {
                            alert('Failed to send test email: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                    });
            }
        }
    </script>
</body>
</html>
