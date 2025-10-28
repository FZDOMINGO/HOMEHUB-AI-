<?php
// Setup email tables and default configuration
require_once 'config/db_connect.php';

$conn = getDbConnection();

// Read and execute SQL file
$sql = file_get_contents('sql/email_tables.sql');

// Split into individual queries
$queries = array_filter(array_map('trim', explode(';', $sql)));

$success = true;
$messages = [];

foreach ($queries as $query) {
    if (!empty($query)) {
        if ($conn->query($query)) {
            $messages[] = "✓ Query executed successfully";
        } else {
            $success = false;
            $messages[] = "✗ Error: " . $conn->error;
        }
    }
}

$conn->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Email System - HomeHub</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f4f4f4; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #8b5cf6; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .message { padding: 10px; margin: 5px 0; border-left: 4px solid #8b5cf6; background: #f8fafc; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email System Setup</h1>
        
        <?php if ($success): ?>
            <h2 class="success">✓ Email system setup completed successfully!</h2>
            <p>The following tables have been created:</p>
            <ul>
                <li><strong>email_preferences</strong> - User email notification preferences</li>
                <li><strong>email_config</strong> - SMTP and email configuration</li>
            </ul>
        <?php else: ?>
            <h2 class="error">✗ Setup encountered errors</h2>
        <?php endif; ?>
        
        <h3>Setup Log:</h3>
        <?php foreach ($messages as $message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endforeach; ?>
        
        <h3>Next Steps:</h3>
        <ol>
            <li>Configure SMTP settings at <a href="admin/email-settings.php">Admin > Email Settings</a></li>
            <li>Preview email templates at <a href="admin/email-preview.php">Admin > Email Preview</a></li>
            <li>Test email delivery using the "Send Test Email" button</li>
            <li>Users can manage their email preferences in their account settings</li>
        </ol>
        
        <div style="margin-top: 30px;">
            <a href="admin/email-settings.php" style="background: #8b5cf6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;">
                Go to Email Settings
            </a>
            <a href="admin/email-preview.php" style="background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block; margin-left: 10px;">
                Preview Email Templates
            </a>
        </div>
    </div>
</body>
</html>
