<?php
/**
 * Tenant Homepage - HomeHub AI
 * Landing page for logged-in tenants
 */

session_start();

// Check if user is logged in as tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header('Location: ../login/login.html');
    exit;
}

// Database connection
require_once '../config/db_connect.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] ?? 'Tenant';

// Get user stats for homepage
$savedProperties = 0;
$recentViews = 0;
$aiRecommendations = 0;

try {
    $conn = getDbConnection();
    // Get saved properties count
    $savedQuery = $conn->prepare("SELECT COUNT(*) as count FROM saved_properties WHERE user_id = ?");
    $savedQuery->bind_param("i", $userId);
    $savedQuery->execute();
    $result = $savedQuery->get_result()->fetch_assoc();
    $savedProperties = $result ? $result['count'] : 0;

    // Get recent views count (last 7 days)
    $viewedQuery = $conn->prepare("SELECT COUNT(*) as count FROM browsing_history WHERE user_id = ? AND viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $viewedQuery->bind_param("i", $userId);
    $viewedQuery->execute();
    $result = $viewedQuery->get_result()->fetch_assoc();
    $recentViews = $result ? $result['count'] : 0;

    // Get AI recommendations count
    $aiQuery = $conn->prepare("SELECT COUNT(*) as count FROM recommendation_cache WHERE user_id = ? AND is_valid = 1");
    $aiQuery->bind_param("i", $userId);
    $aiQuery->execute();
    $result = $aiQuery->get_result()->fetch_assoc();
    $aiRecommendations = $result ? $result['count'] : 0;

} catch (Exception $e) {
    error_log("Tenant homepage stats error: " . $e->getMessage());
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeHub AI - Find Your Perfect Home</title>
    <link rel="stylesheet" href="../guest/style.css">
</head>
<body>
    <!-- Navigation Header -->
    <nav class="navbar">
        <div class="nav-container">
            <!-- Logo -->
            <div class="nav-logo">
                <img src="../assets/homehublogo.jpg" alt="HomeHub AI Logo" class="logo-img">
            </div>
            
            <!-- Desktop Navigation (hidden on mobile) -->
            <div class="nav-center">
                <a href="index.php" class="nav-link active">Home</a>
                <a href="../properties.php" class="nav-link">Properties</a>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="../bookings.php" class="nav-link">Bookings</a>
                <a href="history.php" class="nav-link">History</a>
                <a href="../ai-features.php" class="nav-link">AI Features</a>
            </div>
            
            <!-- Desktop Buttons (hidden on mobile) -->
            <div class="nav-right">
                <span class="user-greeting">Welcome, <?php echo htmlspecialchars($userName); ?></span>
                <a href="../logout.php" class="btn-login" id="logoutBtn">Logout</a>
            </div>
            
            <!-- Mobile Navigation Buttons -->
            <div class="nav-buttons-mobile">
                <a href="../logout.php" class="btn-login-mobile" id="logoutBtnMobile">Logout</a>
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
            <a href="index.php" class="nav-link-mobile active">Home</a>
            <a href="../properties.php" class="nav-link-mobile">Properties</a>
            <a href="dashboard.php" class="nav-link-mobile">Dashboard</a>
            <a href="../bookings.php" class="nav-link-mobile">Bookings</a>
            <a href="history.php" class="nav-link-mobile">History</a>
            <a href="../ai-features.php" class="nav-link-mobile">AI Features</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="main-content">
        <section class="hero">
            <div class="hero-container">
                <div class="hero-content">
                    <h1 class="hero-title">Find Your Perfect Home with AI</h1>
                    <p class="hero-subtitle">
                        HomeHub AI connects tenants and landlords through intelligent
                        matching, making rental experiences seamless and personalized.
                    </p>
                    <div class="hero-buttons">
                        <a href="../properties.php" class="btn-primary">Browse Properties</a>
                        <a href="dashboard.php" class="btn-secondary">My Dashboard</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <div class="features-container">
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3 class="feature-title">AI-Powered Matching</h3>
                    <p class="feature-description">
                        HomeHub AI connects tenants and landlords through intelligent
                        matching, making rental experiences seamless and personalized.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3 class="feature-title">Smart Search</h3>
                    <p class="feature-description">
                        Advanced filters and personalized recommendations help you
                        discover properties you'll love.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">Predictive Analytics</h3>
                    <p class="feature-description">
                        Landlords get insights on demand forecasting, optimal pricing, and
                        property performance.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💬</div>
                    <h3 class="feature-title">Seamless Communication</h3>
                    <p class="feature-description">
                        Integrated messaging and booking system streamlines tenant-landlord
                        interactions.
                    </p>
                </div>
            </div>
        </section>

        <!-- User Stats Section -->
        <section class="stats">
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $savedProperties; ?></div>
                    <div class="stat-label">Saved Properties</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $recentViews; ?></div>
                    <div class="stat-label">Recent Views</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $aiRecommendations; ?></div>
                    <div class="stat-label">AI Suggestions</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Match Success</div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta">
            <div class="cta-container">
                <h2 class="cta-title">Ready to Find Your Perfect Match?</h2>
                <p class="cta-subtitle">Explore properties and manage your rental journey</p>
                <div class="cta-buttons">
                    <a href="saved.php" class="btn-cta-primary">View Saved Properties</a>
                    <a href="profile.php" class="btn-cta-secondary">Update Preferences</a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-container">
                <div class="footer-content">
                    <div class="footer-section">
                        <div class="footer-logo">
                            <span class="footer-logo-text">HomeHub AI</span>
                        </div>
                        <p class="footer-description">
                            Revolutionizing the rental market with AI-powered matching and seamless experiences.
                        </p>
                    </div>
                    <div class="footer-section">
                        <h4 class="footer-title">Platform</h4>
                        <a href="../properties.php" class="footer-link">Properties</a>
                        <a href="../ai-features.php" class="footer-link">AI Features</a>
                        <a href="dashboard.php" class="footer-link">Dashboard</a>
                    </div>
                    <div class="footer-section">
                        <h4 class="footer-title">My Account</h4>
                        <a href="saved.php" class="footer-link">Saved Properties</a>
                        <a href="../bookings.php" class="footer-link">My Bookings</a>
                        <a href="profile.php" class="footer-link">Profile Settings</a>
                    </div>
                    <div class="footer-section">
                        <h4 class="footer-title">Support</h4>
                        <a href="../guest/help.html" class="footer-link">Help Center</a>
                        <a href="../guest/contact.html" class="footer-link">Contact Us</a>
                        <a href="../guest/faq.html" class="footer-link">FAQ</a>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>&copy; 2025 HomeHub AI. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </main>

    <script src="../guest/script.js"></script>
    <script>
        // Logout functionality
        document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        });
        
        document.getElementById('logoutBtnMobile')?.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        });
    </script>
</body>
</html>