<?php
/**
 * Tenant Homepage - HomeHub AI
 * Landing page for logged-in tenants
 */

// Load environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

// Check if user is logged in as tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    redirect('login/login.html');
}

// Get database connection
$conn = getDbConnection();

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
    if ($savedQuery === false) {
        error_log("Failed to prepare saved properties query: " . $conn->error);
    } else {
        $savedQuery->bind_param("i", $userId);
        $savedQuery->execute();
        $result = $savedQuery->get_result()->fetch_assoc();
        $savedProperties = $result ? $result['count'] : 0;
        $savedQuery->close();
    }

    // Get recent views count (last 7 days)
    $viewedQuery = $conn->prepare("SELECT COUNT(*) as count FROM browsing_history WHERE user_id = ? AND viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    if ($viewedQuery === false) {
        error_log("Failed to prepare browsing history query: " . $conn->error);
    } else {
        $viewedQuery->bind_param("i", $userId);
        $viewedQuery->execute();
        $result = $viewedQuery->get_result()->fetch_assoc();
        $recentViews = $result ? $result['count'] : 0;
        $viewedQuery->close();
    }

    // Get AI recommendations count
    $aiQuery = $conn->prepare("SELECT COUNT(*) as count FROM recommendation_cache WHERE user_id = ? AND is_valid = 1");
    if ($aiQuery === false) {
        error_log("Failed to prepare recommendations query: " . $conn->error);
    } else {
        $aiQuery->bind_param("i", $userId);
        $aiQuery->execute();
        $result = $aiQuery->get_result()->fetch_assoc();
        $aiRecommendations = $result ? $result['count'] : 0;
        $aiQuery->close();
    }

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
    <link rel="stylesheet" href="../assets/css/properties.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php 
    // Set active page for navigation
    $activePage = 'home';
    $navPath = '../'; // From tenant subdirectory
    include '../includes/navbar.php'; 
    ?>

    <!-- Main Content -->
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
</body>
</html>