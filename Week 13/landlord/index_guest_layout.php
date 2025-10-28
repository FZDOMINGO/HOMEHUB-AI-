<?php
/**
 * Landlord Homepage - HomeHub AI
 * Landing page for logged-in landlords
 */

session_start();

// Check if user is logged in as landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header('Location: ../login/login.html');
    exit;
}

// Database connection
require_once '../config/db_connect.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] ?? 'Landlord';

// Get landlord stats for homepage
$totalProperties = 0;
$activeProperties = 0;
$totalViews = 0;

try {
    $conn = getDbConnection();
    // Get total properties count
    $totalQuery = $conn->prepare("SELECT COUNT(*) as count FROM properties WHERE user_id = ?");
    $totalQuery->bind_param("i", $userId);
    $totalQuery->execute();
    $result = $totalQuery->get_result()->fetch_assoc();
    $totalProperties = $result ? $result['count'] : 0;

    // Get active properties count
    $activeQuery = $conn->prepare("SELECT COUNT(*) as count FROM properties WHERE user_id = ? AND status = 'available'");
    $activeQuery->bind_param("i", $userId);
    $activeQuery->execute();
    $result = $activeQuery->get_result()->fetch_assoc();
    $activeProperties = $result ? $result['count'] : 0;

    // Get total views count
    $viewsQuery = $conn->prepare("SELECT SUM(views) as total FROM properties WHERE user_id = ?");
    $viewsQuery->bind_param("i", $userId);
    $viewsQuery->execute();
    $result = $viewsQuery->get_result()->fetch_assoc();
    $totalViews = $result ? ($result['total'] ?? 0) : 0;

} catch (Exception $e) {
    error_log("Landlord homepage stats error: " . $e->getMessage());
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeHub AI - Landlord Dashboard</title>
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
                <a href="manage-properties.php" class="nav-link">Properties</a>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="add-property.php" class="nav-link">Add Property</a>
                <a href="../bookings.php" class="nav-link">Bookings</a>
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
            <a href="manage-properties.php" class="nav-link-mobile">Properties</a>
            <a href="dashboard.php" class="nav-link-mobile">Dashboard</a>
            <a href="add-property.php" class="nav-link-mobile">Add Property</a>
            <a href="../bookings.php" class="nav-link-mobile">Bookings</a>
            <a href="../ai-features.php" class="nav-link-mobile">AI Features</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="main-content">
        <section class="hero">
            <div class="hero-container">
                <div class="hero-content">
                    <h1 class="hero-title">Maximize Your Rental Success with AI</h1>
                    <p class="hero-subtitle">
                        HomeHub AI helps landlords optimize property performance, find quality tenants, 
                        and streamline rental management with intelligent insights.
                    </p>
                    <div class="hero-buttons">
                        <a href="add-property.php" class="btn-primary">Add Property</a>
                        <a href="dashboard.php" class="btn-secondary">View Dashboard</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <div class="features-container">
                <div class="feature-card">
                    <div class="feature-icon">🏠</div>
                    <h3 class="feature-title">Property Management</h3>
                    <p class="feature-description">
                        Effortlessly manage your rental portfolio with our comprehensive 
                        property management tools and analytics.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3 class="feature-title">Tenant Matching</h3>
                    <p class="feature-description">
                        AI-powered tenant matching connects you with qualified renters 
                        who meet your property requirements.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">Market Analytics</h3>
                    <p class="feature-description">
                        Get insights on market trends, optimal pricing strategies, 
                        and property performance optimization.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3 class="feature-title">Revenue Optimization</h3>
                    <p class="feature-description">
                        Maximize your rental income with AI-driven pricing recommendations 
                        and occupancy optimization strategies.
                    </p>
                </div>
            </div>
        </section>

        <!-- Landlord Stats Section -->
        <section class="stats">
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $totalProperties; ?></div>
                    <div class="stat-label">Total Properties</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $activeProperties; ?></div>
                    <div class="stat-label">Active Listings</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($totalViews); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">95%</div>
                    <div class="stat-label">Occupancy Rate</div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta">
            <div class="cta-container">
                <h2 class="cta-title">Ready to Optimize Your Rental Business?</h2>
                <p class="cta-subtitle">Join thousands of successful landlords using HomeHub AI</p>
                <div class="cta-buttons">
                    <a href="manage-properties.php" class="btn-cta-primary">Manage Properties</a>
                    <a href="profile.php" class="btn-cta-secondary">Account Settings</a>
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
                        <a href="manage-properties.php" class="footer-link">Properties</a>
                        <a href="../ai-features.php" class="footer-link">AI Features</a>
                        <a href="dashboard.php" class="footer-link">Dashboard</a>
                    </div>
                    <div class="footer-section">
                        <h4 class="footer-title">Management</h4>
                        <a href="add-property.php" class="footer-link">Add Property</a>
                        <a href="../bookings.php" class="footer-link">Bookings</a>
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