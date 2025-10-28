<?php
// Update Gmail App Password
require_once 'config/db_connect.php';

$conn = getDbConnection();
$password = 'byogeijvsbeptpah';

// Update the password
$stmt = $conn->prepare('UPDATE email_config SET smtp_password = ? WHERE id = 1');
$stmt->bind_param('s', $password);

if ($stmt->execute()) {
    echo "✅ Password updated successfully!\n";
    echo "Password length: " . strlen($password) . " characters\n\n";
    
    // Verify the configuration
    $result = $conn->query('SELECT use_smtp, smtp_host, smtp_port, smtp_username, smtp_encryption, LENGTH(smtp_password) as pass_len FROM email_config WHERE id = 1');
    $row = $result->fetch_assoc();
    
    echo "Current Configuration:\n";
    echo "- SMTP Enabled: " . ($row['use_smtp'] ? 'YES' : 'NO') . "\n";
    echo "- Host: " . $row['smtp_host'] . "\n";
    echo "- Port: " . $row['smtp_port'] . "\n";
    echo "- Username: " . $row['smtp_username'] . "\n";
    echo "- Encryption: " . $row['smtp_encryption'] . "\n";
    echo "- Password Length: " . $row['pass_len'] . " characters\n\n";
    
    echo "✅ Configuration is ready! Now test authentication at:\n";
    echo "http://localhost/HomeHub/test_gmail_auth.php\n";
} else {
    echo "❌ Error updating password: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
