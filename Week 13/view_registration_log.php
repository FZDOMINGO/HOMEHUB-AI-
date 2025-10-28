<?php
// View registration debug log
$logFile = __DIR__ . '/registration_debug.log';

echo "<h2>Registration Debug Log</h2>";
echo "<p>Log file: $logFile</p>";

if (file_exists($logFile)) {
    echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
    echo htmlspecialchars(file_get_contents($logFile));
    echo "</pre>";
    
    echo "<br><a href='?clear=1' style='background: #8b5cf6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Clear Log</a>";
    
    if (isset($_GET['clear'])) {
        file_put_contents($logFile, '');
        header('Location: view_registration_log.php');
        exit;
    }
} else {
    echo "<p style='color: red;'>Log file does not exist yet. Try registering a user first.</p>";
}

echo "<br><br><a href='login/register.html' style='background: #8b5cf6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Registration Page</a>";
?>
