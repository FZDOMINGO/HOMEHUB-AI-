<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';

// Initialize session
initSession();

// Check if user is logged in as tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    echo "<p>You need to be logged in as a tenant to test this.</p>";
    echo "<p><a href='../login/login.html'>Login Here</a></p>";
} else {
    echo "<h2>Tenant Navbar Debug</h2>";
    
    // Debug information
    echo "<p><strong>Session Data:</strong></p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";

    echo "<p><strong>Current File:</strong> " . $_SERVER['PHP_SELF'] . "</p>";
    
    // Include the actual navbar and see what it generates
    $activePage = 'home';
    $navPath = '../';
    
    echo "<p><strong>Nav Path:</strong> " . $navPath . "</p>";
    echo "<p><strong>User Type:</strong> " . $_SESSION['user_type'] . "</p>";
    
    // Show what the navbar would generate
    $isLoggedIn = isset($_SESSION['user_id']);
    $userType = $isLoggedIn ? ($_SESSION['user_type'] ?? 'guest') : 'guest';
    
    if ($isLoggedIn && $userType !== 'guest') {
        $homeUrl = $navPath . $userType . '/index.php';
    } else {
        $homeUrl = $navPath . 'index.php';
    }
    
    echo "<p><strong>Generated Home URL:</strong> " . $homeUrl . "</p>";
    echo "<p><strong>Full URL:</strong> http://localhost/HomeHub/" . $homeUrl . "</p>";
    
    // Test if file exists
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/HomeHub/' . $homeUrl;
    echo "<p><strong>Target file:</strong> " . $fullPath . "</p>";
    echo "<p><strong>File exists:</strong> " . (file_exists($fullPath) ? 'Yes' : 'No') . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tenant Debug</title>
</head>
<body>
    <h1>Tenant Navbar Debug</h1>
    
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'tenant'): ?>
        <h3>Test Home Links:</h3>
        <p><a href="../tenant/index.php">Direct: ../tenant/index.php</a></p>
        <p><a href="index.php">Relative: index.php</a></p>
        <p><a href="<?php echo $navPath . $userType; ?>/index.php">Navbar Logic: <?php echo $navPath . $userType; ?>/index.php</a></p>
        
        <hr>
        <h3>Actual Navbar (will show real navbar):</h3>
        <?php include '../includes/navbar.php'; ?>
    <?php endif; ?>
</body>
</html>