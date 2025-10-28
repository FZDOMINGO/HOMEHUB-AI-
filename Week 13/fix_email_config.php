<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

// Update the configuration with your Gmail settings
$sql = "UPDATE email_config SET 
    use_smtp = 1,
    smtp_host = 'smtp.gmail.com',
    smtp_port = 587,
    smtp_encryption = 'tls',
    from_email = 'zachdomingojavellana@gmail.com',
    from_name = 'HomeHub',
    reply_to_email = 'zachdomingojavellana@gmail.com'
    WHERE id = 1";

if ($conn->query($sql)) {
    echo "<h2 style='color: green;'>✅ Email Configuration Updated!</h2>";
    echo "<p>SMTP is now enabled with Gmail settings.</p>";
    
    // Show the updated config
    $result = $conn->query("SELECT * FROM email_config WHERE id = 1");
    $config = $result->fetch_assoc();
    echo "<pre>";
    echo "use_smtp: " . ($config['use_smtp'] ? 'YES ✅' : 'NO') . "\n";
    echo "smtp_host: " . htmlspecialchars($config['smtp_host']) . "\n";
    echo "smtp_port: " . htmlspecialchars($config['smtp_port']) . "\n";
    echo "smtp_encryption: " . htmlspecialchars($config['smtp_encryption']) . "\n";
    echo "</pre>";
    
    echo "<h3>Now test your email:</h3>";
    echo "<a href='test_email_api_debug.php' style='padding: 10px 20px; background: green; color: white; text-decoration: none; border-radius: 5px;'>Test Email Now</a>";
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

$conn->close();
?>
