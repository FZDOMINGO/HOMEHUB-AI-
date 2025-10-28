<!DOCTYPE html>
<html>
<head>
    <title>Navigation Bar Test</title>
    <link rel="stylesheet" href="assets/css/properties.css">
    <link rel="stylesheet" href="assets/css/ai-features.css">
    <style>
        body { padding: 20px; font-family: Arial; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .info { background: #d1ecf1; color: #0c5460; }
        button { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="test-section">
        <h1>🧪 Navigation Bar Test</h1>
        <p>This page tests the standardized navigation bar with different user types.</p>
    </div>

    <?php
    session_start();
    
    // Display current session
    echo '<div class="test-section">';
    echo '<h2>Current Session State:</h2>';
    
    if (isset($_SESSION['user_id'])) {
        echo '<div class="status success">';
        echo '<strong>✓ Logged In</strong><br>';
        echo 'User ID: ' . $_SESSION['user_id'] . '<br>';
        echo 'User Type: ' . ($_SESSION['user_type'] ?? 'Not set') . '<br>';
        echo 'User Name: ' . ($_SESSION['user_name'] ?? 'Not set') . '<br>';
        echo 'First Name: ' . ($_SESSION['first_name'] ?? 'Not set') . '<br>';
        echo 'Last Name: ' . ($_SESSION['last_name'] ?? 'Not set') . '<br>';
        echo '</div>';
    } else {
        echo '<div class="status info">';
        echo '<strong>ℹ Not Logged In</strong><br>';
        echo 'Session is empty or no user logged in.';
        echo '</div>';
    }
    echo '</div>';
    
    // Test buttons
    echo '<div class="test-section">';
    echo '<h2>Test Different States:</h2>';
    echo '<form method="POST" style="display: inline;">';
    echo '<button type="submit" name="test_tenant">Simulate Tenant Login</button>';
    echo '</form>';
    echo '<form method="POST" style="display: inline;">';
    echo '<button type="submit" name="test_landlord">Simulate Landlord Login</button>';
    echo '</form>';
    echo '<form method="POST" style="display: inline;">';
    echo '<button type="submit" name="test_logout">Simulate Logout</button>';
    echo '</form>';
    echo '</div>';
    
    // Handle test actions
    if (isset($_POST['test_tenant'])) {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_type'] = 'tenant';
        $_SESSION['user_name'] = 'John Tenant';
        $_SESSION['first_name'] = 'John';
        $_SESSION['last_name'] = 'Tenant';
        $_SESSION['user_email'] = 'tenant@test.com';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['test_landlord'])) {
        $_SESSION['user_id'] = 2;
        $_SESSION['user_type'] = 'landlord';
        $_SESSION['user_name'] = 'Jane Landlord';
        $_SESSION['first_name'] = 'Jane';
        $_SESSION['last_name'] = 'Landlord';
        $_SESSION['user_email'] = 'landlord@test.com';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    if (isset($_POST['test_logout'])) {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>

    <div class="test-section">
        <h2>Navigation Bar Preview:</h2>
        <p>Below is the actual navigation bar that would appear on pages:</p>
    </div>

    <?php
    // Include the standardized navbar
    $activePage = 'ai-features';
    $navPath = '';
    include 'includes/navbar.php';
    ?>

    <div class="test-section">
        <h2>Expected Behavior:</h2>
        <ul>
            <li><strong>Guest (Not Logged In):</strong> Should show "Login" and "Sign Up" buttons</li>
            <li><strong>Tenant:</strong> Should show "Welcome, John Tenant" and "Logout" button</li>
            <li><strong>Landlord:</strong> Should show "Welcome, Jane Landlord" and "Logout" button</li>
        </ul>
        
        <h3>Navigation Links:</h3>
        <ul>
            <li><strong>Dashboard link:</strong>
                <ul>
                    <li>Guest: Should redirect to login page</li>
                    <li>Tenant: Should go to tenant/dashboard.php</li>
                    <li>Landlord: Should go to landlord/dashboard.php</li>
                </ul>
            </li>
            <li><strong>AI Features:</strong> Should be highlighted (active) since this is the AI features page</li>
        </ul>
    </div>

    <script>
        // Add hamburger menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.getElementById('hamburger');
            const navMenuMobile = document.getElementById('nav-menu-mobile');
            
            if (hamburger && navMenuMobile) {
                hamburger.addEventListener('click', function() {
                    hamburger.classList.toggle('active');
                    navMenuMobile.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
