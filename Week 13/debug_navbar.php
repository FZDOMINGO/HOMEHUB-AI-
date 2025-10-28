<?php
session_start();

// Debug information
echo "<h2>Debug Information</h2>";
echo "<p><strong>Session Data:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p><strong>Current File:</strong> " . $_SERVER['PHP_SELF'] . "</p>";
echo "<p><strong>Current Directory:</strong> " . dirname($_SERVER['PHP_SELF']) . "</p>";

// Simulate navbar logic
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? ($_SESSION['user_type'] ?? 'guest') : 'guest';

// Set default path prefix if not already set
$currentFile = $_SERVER['PHP_SELF'];
$navPath = (strpos($currentFile, '/tenant/') !== false || strpos($currentFile, '/landlord/') !== false) ? '../' : '';

echo "<p><strong>Is Logged In:</strong> " . ($isLoggedIn ? 'Yes' : 'No') . "</p>";
echo "<p><strong>User Type:</strong> " . $userType . "</p>";
echo "<p><strong>Nav Path:</strong> " . $navPath . "</p>";

// Show what the Home URL would be
if ($isLoggedIn && $userType !== 'guest') {
    $homeUrl = $navPath . $userType . '/index.php';
} else {
    $homeUrl = $navPath . 'index.php';
}

echo "<p><strong>Home URL would be:</strong> " . $homeUrl . "</p>";
echo "<p><strong>Full Home URL:</strong> http://localhost/HomeHub/" . $homeUrl . "</p>";

// Test if the target file exists
$fullPath = $_SERVER['DOCUMENT_ROOT'] . '/HomeHub/' . $homeUrl;
echo "<p><strong>Target file path:</strong> " . $fullPath . "</p>";
echo "<p><strong>File exists:</strong> " . (file_exists($fullPath) ? 'Yes' : 'No') . "</p>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Navbar</title>
</head>
<body>
    <h1>Navbar Debug Page</h1>
    
    <h3>Test Links:</h3>
    <?php if ($isLoggedIn && $userType !== 'guest'): ?>
        <p><a href="<?php echo $navPath . $userType; ?>/index.php">Home (User-Specific)</a></p>
    <?php else: ?>
        <p><a href="<?php echo $navPath; ?>index.php">Home (General)</a></p>
    <?php endif; ?>
    
    <p><a href="tenant/index.php">Direct Tenant Home</a></p>
    <p><a href="landlord/index.php">Direct Landlord Home</a></p>
    
    <hr>
    <p>Click one of the test links above to see if they work</p>
</body>
</html>