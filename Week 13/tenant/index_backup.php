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
    <link rel="stylesheet" href="index_new.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php 
    $activePage = 'home';
    $navPath = '../';
    include '../includes/navbar.php'; 
    ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Find Your Perfect Home with AI</h1>
                <p class="hero-subtitle">HomeHub AI connects tenants and landlords through intelligent matching, making rental experiences seamless and personalized.</p>
                
                <div class="hero-actions">
                    <a href="../properties.php" class="btn-primary">
                        Browse Properties
                    </a>
                    <a href="../ai-features.php" class="btn-secondary">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid Section -->
    <section class="features-grid-section">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>AI-Powered Matching</h3>
                    <p>Intelligent AI connects tenants and landlords through intelligent matching, making rental experiences smooth and personalized.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Smart Search</h3>
                    <p>Advanced filters and personalized recommendations help you discover properties you'll love faster than ever.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Predictive Analytics</h3>
                    <p>Leverage big data to gain insights into market trends, optimal pricing, and property performance.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Seamless Communication</h3>
                    <p>Integrated messaging and booking system streamlines tenant-landlord interactions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- User Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="user-stats">
                <div class="stats-header">
                    <h2>Your Activity Dashboard</h2>
                    <p>Track your rental journey progress</p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-number"><?php echo $savedProperties; ?></div>
                        <div class="stat-label">Saved Properties</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-number"><?php echo $recentViews; ?></div>
                        <div class="stat-label">Recent Views</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <div class="stat-number"><?php echo $aiRecommendations; ?></div>
                        <div class="stat-label">AI Suggestions</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions Section -->
    <section class="quick-actions">
        <div class="container">
            <h2 class="section-title">Quick Actions</h2>
            <div class="actions-grid">
                <a href="dashboard.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3>Dashboard</h3>
                    <p>View your rental activity and statistics</p>
                </a>

                <a href="saved.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Saved Properties</h3>
                    <p>Manage your favorite rental listings</p>
                </a>

                <a href="../bookings.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>My Bookings</h3>
                    <p>Track your scheduled property visits</p>
                </a>

                <a href="profile.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3>Profile Settings</h3>
                    <p>Update your preferences and information</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="additional-features">
        <div class="container">
            <h2 class="section-title">Why Choose HomeHub AI?</h2>
            <div class="features-list">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div class="feature-content">
                        <h3>AI-Powered Matching</h3>
                        <p>Our advanced algorithms analyze your preferences to suggest perfect rental matches.</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-content">
                        <h3>Verified Properties</h3>
                        <p>All listings are verified by our team to ensure quality and authenticity.</p>
                    </div>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="feature-content">
                        <h3>24/7 Support</h3>
                        <p>Get assistance anytime with our round-the-clock customer support team.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

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