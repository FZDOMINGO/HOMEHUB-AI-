<?php
// Test email function directly
require_once 'includes/email_functions.php';

echo "<h2>Direct Email Function Test</h2>";

$testEmail = "zachdomingojavellana@gmail.com";
$subject = "Direct Test - " . date('H:i:s');
$message = getEmailTemplate('<h2>Direct Test</h2><p>Testing email directly from script.</p>', $subject);

echo "<p>Attempting to send email to: <strong>$testEmail</strong></p>";
echo "<p>Check the log messages below for debug info...</p>";
echo "<hr>";

// Capture error log output
$result = sendEmail($testEmail, $subject, $message);

echo "<hr>";
echo "<h3>Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</h3>";

// Show last few lines of error log
$errorLog = 'C:\xampp\apache\logs\error.log';
if (file_exists($errorLog)) {
    $lines = file($errorLog);
    $lastLines = array_slice($lines, -10);
    echo "<h3>Last 10 Error Log Lines:</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
    foreach ($lastLines as $line) {
        if (strpos($line, 'Email Config Debug') !== false || strpos($line, 'Using SMTP') !== false || strpos($line, 'Using PHP mail') !== false) {
            echo "<strong style='color: blue;'>" . htmlspecialchars($line) . "</strong>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
}
?>
