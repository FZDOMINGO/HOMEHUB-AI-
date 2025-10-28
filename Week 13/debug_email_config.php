<?php
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<h2>Email Configuration Debug</h2>";

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'email_config'");
if ($tableCheck->num_rows > 0) {
    echo "<p style='color: green;'>✅ email_config table exists</p>";
    
    // Get the configuration
    $result = $conn->query("SELECT * FROM email_config WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $config = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Configuration found</p>";
        echo "<pre>";
        echo "use_smtp: " . ($config['use_smtp'] ? 'YES (1)' : 'NO (0)') . "\n";
        echo "smtp_host: " . htmlspecialchars($config['smtp_host']) . "\n";
        echo "smtp_port: " . htmlspecialchars($config['smtp_port']) . "\n";
        echo "smtp_encryption: " . htmlspecialchars($config['smtp_encryption']) . "\n";
        echo "smtp_username: " . htmlspecialchars($config['smtp_username']) . "\n";
        echo "smtp_password: " . (strlen($config['smtp_password']) > 0 ? '***SET*** (length: ' . strlen($config['smtp_password']) . ')' : 'NOT SET') . "\n";
        echo "from_email: " . htmlspecialchars($config['from_email']) . "\n";
        echo "from_name: " . htmlspecialchars($config['from_name']) . "\n";
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ No configuration found (row with id=1 doesn't exist)</p>";
        echo "<p>Run this SQL to create default config:</p>";
        echo "<pre>INSERT INTO email_config (id, use_smtp) VALUES (1, 0);</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ email_config table does not exist</p>";
    echo "<p>Run: <a href='setup_email.php'>setup_email.php</a></p>";
}

$conn->close();
?>
