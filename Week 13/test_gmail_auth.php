<?php
require_once 'config/db_connect.php';
require_once 'includes/email_functions.php';

$conn = getDbConnection();
$config = $conn->query("SELECT * FROM email_config WHERE id = 1")->fetch_assoc();
$conn->close();

echo "<h2>Gmail SMTP Authentication Test</h2>";
echo "<p>Testing direct connection to Gmail...</p>";

if (!$config || $config['use_smtp'] != 1) {
    die("<p style='color: red;'>SMTP is not enabled in your configuration!</p>");
}

echo "<h3>Your Settings:</h3>";
echo "<pre>";
echo "Host: " . htmlspecialchars($config['smtp_host']) . "\n";
echo "Port: " . $config['smtp_port'] . "\n";
echo "Encryption: " . htmlspecialchars($config['smtp_encryption']) . "\n";
echo "Username: " . htmlspecialchars($config['smtp_username']) . "\n";
echo "Password Length: " . strlen($config['smtp_password']) . " characters\n";
echo "</pre>";

if (strlen($config['smtp_password']) != 16) {
    echo "<div style='background: #fee2e2; border: 2px solid #ef4444; padding: 20px; border-radius: 10px;'>";
    echo "<h3 style='color: #991b1b;'>⚠️ Password Problem Detected!</h3>";
    echo "<p><strong>Your password is " . strlen($config['smtp_password']) . " characters.</strong></p>";
    echo "<p>Gmail App Passwords must be <strong>exactly 16 characters</strong>.</p>";
    echo "<p><strong>Action Required:</strong></p>";
    echo "<ol>";
    echo "<li>Go to: <a href='https://myaccount.google.com/apppasswords' target='_blank'>Google App Passwords</a></li>";
    echo "<li>Generate a NEW app password</li>";
    echo "<li>Copy the 16-character code (remove spaces!)</li>";
    echo "<li>Paste it in <a href='admin/email-settings.php'>Email Settings</a></li>";
    echo "<li>Click Save Settings</li>";
    echo "</ol>";
    echo "</div>";
}

// Try to connect
echo "<h3>Testing Connection...</h3>";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 2; // Detailed debug output
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['smtp_port'];
    
    // Just test connection, don't send
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    $mail->smtpConnect();
    echo "</pre>";
    
    echo "<div style='background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
    echo "<h3 style='color: #065f46;'>✅ Connection Successful!</h3>";
    echo "<p>Gmail SMTP authentication is working correctly.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "</pre>";
    echo "<div style='background: #fee2e2; border: 2px solid #ef4444; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
    echo "<h3 style='color: #991b1b;'>❌ Connection Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (strpos($e->getMessage(), 'authenticate') !== false || strpos($e->getMessage(), 'Authentication') !== false) {
        echo "<h4>This is an authentication error!</h4>";
        echo "<p><strong>Solutions:</strong></p>";
        echo "<ol>";
        echo "<li><strong>Get a fresh Gmail App Password:</strong>";
        echo "<ul>";
        echo "<li>Go to: <a href='https://myaccount.google.com/apppasswords' target='_blank'>Google App Passwords</a></li>";
        echo "<li>Delete any old passwords for this app</li>";
        echo "<li>Create a NEW app password</li>";
        echo "<li>Make sure it's exactly 16 characters (remove spaces)</li>";
        echo "</ul>";
        echo "</li>";
        echo "<li>Make sure your Gmail account has 2-Step Verification enabled</li>";
        echo "<li>Check that you're using your full Gmail address: " . htmlspecialchars($config['smtp_username']) . "</li>";
        echo "</ol>";
    }
    echo "</div>";
}
?>
