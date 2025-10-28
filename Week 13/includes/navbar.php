<?php
/**
 * Standardized Navigation Bar Component
 * 
 * Usage: include 'includes/navbar.php';
 * 
 * Required session variables:
 * - $_SESSION['user_id'] (optional)
 * - $_SESSION['user_type'] (optional: 'tenant' or 'landlord')
 * - $_SESSION['user_name'] or first_name/last_name (optional)
 * 
 * Optional variables to set before including:
 * - $activePage: Current page identifier ('home', 'properties', 'dashboard', 'bookings', 'history', 'ai-features')
 * - $navPath: Path prefix for navigation links (default: '../' for subdirectories, '' for root)
 */

// Determine if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? ($_SESSION['user_type'] ?? 'guest') : 'guest';

// Get user's full name
$userName = '';
if ($isLoggedIn) {
    if (isset($_SESSION['user_name'])) {
        $userName = $_SESSION['user_name'];
    } elseif (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
        $userName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    } elseif (isset($_SESSION['first_name'])) {
        $userName = $_SESSION['first_name'];
    } else {
        $userName = 'User';
    }
}

// Set default path prefix if not already set
if (!isset($navPath)) {
    // Auto-detect if we're in a subdirectory
    $currentFile = $_SERVER['PHP_SELF'];
    $navPath = (strpos($currentFile, '/tenant/') !== false || strpos($currentFile, '/landlord/') !== false) ? '../' : '';
}

// Set default active page if not set
if (!isset($activePage)) {
    $activePage = '';
}
?>

<!-- Standardized Navigation Bar -->
<nav class="navbar">
    <div class="nav-container">
        <!-- Logo -->
        <div class="nav-logo">
            <?php if ($isLoggedIn && $userType !== 'guest'): ?>
                <a href="<?php echo $navPath . $userType; ?>/index.php" title="HomeHub AI - Home">
                    <img src="<?php echo $navPath; ?>assets/homehublogo.jpg" alt="HomeHub AI Logo" class="logo-img">
                </a>
            <?php else: ?>
                <a href="<?php echo $navPath; ?>index.php" title="HomeHub AI - Home">
                    <img src="<?php echo $navPath; ?>assets/homehublogo.jpg" alt="HomeHub AI Logo" class="logo-img">
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Desktop Navigation -->
        <div class="nav-center">
            <?php if ($isLoggedIn && $userType !== 'guest'): ?>
                <a href="<?php echo $navPath . $userType; ?>/index.php" class="nav-link <?php echo $activePage === 'home' ? 'active' : ''; ?>">Home</a>
            <?php else: ?>
                <a href="<?php echo $navPath; ?>index.php" class="nav-link <?php echo $activePage === 'home' ? 'active' : ''; ?>">Home</a>
            <?php endif; ?>
            <a href="<?php echo $navPath; ?>properties.php" class="nav-link <?php echo $activePage === 'properties' ? 'active' : ''; ?>">Properties</a>
            
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo $navPath . $userType; ?>/dashboard.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                <a href="<?php echo $navPath; ?>bookings.php" class="nav-link <?php echo $activePage === 'bookings' ? 'active' : ''; ?>">Bookings</a>
                <a href="<?php echo $navPath; ?>history.php" class="nav-link <?php echo $activePage === 'history' ? 'active' : ''; ?>">History</a>
                <a href="<?php echo $navPath; ?>ai-features.php" class="nav-link <?php echo $activePage === 'ai-features' ? 'active' : ''; ?>">AI Features</a>
            <?php else: ?>
                <a href="<?php echo $navPath; ?>login/login.html" class="nav-link">Dashboard</a>
                <a href="<?php echo $navPath; ?>bookings.php" class="nav-link <?php echo $activePage === 'bookings' ? 'active' : ''; ?>">Bookings</a>
                <a href="<?php echo $navPath; ?>login/login.html" class="nav-link">History</a>
                <a href="<?php echo $navPath; ?>ai-features.php" class="nav-link <?php echo $activePage === 'ai-features' ? 'active' : ''; ?>">AI Features</a>
            <?php endif; ?>
        </div>
        
        <!-- Desktop Right Section -->
        <div class="nav-right">
            <?php if ($isLoggedIn): ?>
                <span class="user-greeting">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                <a href="#" id="logoutBtn" class="btn-login">Logout</a>
            <?php else: ?>
                <a href="<?php echo $navPath; ?>login/login.html" class="btn-login">Login</a>
                <a href="<?php echo $navPath; ?>login/register.html" class="btn-signup">Sign Up</a>
            <?php endif; ?>
        </div>
        
        <!-- Mobile Navigation Buttons -->
        <div class="nav-buttons-mobile">
            <?php if ($isLoggedIn): ?>
                <a href="#" id="logoutBtnMobile" class="btn-login-mobile">Logout</a>
            <?php else: ?>
                <a href="<?php echo $navPath; ?>login/login.html" class="btn-login-mobile">Login</a>
            <?php endif; ?>
        </div>
        
        <!-- Hamburger Menu -->
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    
    <!-- Mobile Menu -->
    <div class="nav-menu-mobile" id="nav-menu-mobile">
        <?php if ($isLoggedIn && $userType !== 'guest'): ?>
            <a href="<?php echo $navPath . $userType; ?>/index.php" class="nav-link-mobile <?php echo $activePage === 'home' ? 'active' : ''; ?>">Home</a>
        <?php else: ?>
            <a href="<?php echo $navPath; ?>index.php" class="nav-link-mobile <?php echo $activePage === 'home' ? 'active' : ''; ?>">Home</a>
        <?php endif; ?>
        <a href="<?php echo $navPath; ?>properties.php" class="nav-link-mobile <?php echo $activePage === 'properties' ? 'active' : ''; ?>">Properties</a>
        
        <?php if ($isLoggedIn): ?>
            <a href="<?php echo $navPath . $userType; ?>/dashboard.php" class="nav-link-mobile <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="<?php echo $navPath; ?>bookings.php" class="nav-link-mobile <?php echo $activePage === 'bookings' ? 'active' : ''; ?>">Bookings</a>
            <a href="<?php echo $navPath; ?>history.php" class="nav-link-mobile <?php echo $activePage === 'history' ? 'active' : ''; ?>">History</a>
            <a href="<?php echo $navPath; ?>ai-features.php" class="nav-link-mobile <?php echo $activePage === 'ai-features' ? 'active' : ''; ?>">AI Features</a>
        <?php else: ?>
            <a href="<?php echo $navPath; ?>login/login.html" class="nav-link-mobile">Dashboard</a>
            <a href="<?php echo $navPath; ?>bookings.php" class="nav-link-mobile <?php echo $activePage === 'bookings' ? 'active' : ''; ?>">Bookings</a>
            <a href="<?php echo $navPath; ?>login/login.html" class="nav-link-mobile">History</a>
            <a href="<?php echo $navPath; ?>ai-features.php" class="nav-link-mobile <?php echo $activePage === 'ai-features' ? 'active' : ''; ?>">AI Features</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Logout Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Desktop logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '<?php echo $navPath; ?>api/logout.php';
            }
        });
    }
    
    // Mobile logout
    const logoutBtnMobile = document.getElementById('logoutBtnMobile');
    if (logoutBtnMobile) {
        logoutBtnMobile.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '<?php echo $navPath; ?>api/logout.php';
            }
        });
    }
    
    // Hamburger menu toggle
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
