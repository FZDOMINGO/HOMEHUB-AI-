<?php
/**
 * Landlord Homepage - HomeHub AI
 * Landing page for logged-in landlords
 */

// Load environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

// Check if user is logged in as landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    redirect('login/login.html');
}

// Get database connection
$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$userName = $_SESSION['first_name'] ?? 'Landlord';

// Get landlord stats for homepage
$totalProperties = 0;
$activeProperties = 0;
$totalViews = 0;

try {
    $conn = getDbConnection();
    
    // First, get landlord_id from landlords table
    $landlordIdQuery = $conn->prepare("SELECT id FROM landlords WHERE user_id = ?");
    if ($landlordIdQuery === false) {
        throw new Exception("Failed to prepare landlord query: " . $conn->error);
    }
    $landlordIdQuery->bind_param("i", $userId);
    $landlordIdQuery->execute();
    $landlordResult = $landlordIdQuery->get_result()->fetch_assoc();
    $landlordId = $landlordResult ? $landlordResult['id'] : null;
    $landlordIdQuery->close();
    
    if ($landlordId) {
        // Get total properties count
        $totalQuery = $conn->prepare("SELECT COUNT(*) as count FROM properties WHERE landlord_id = ?");
        if ($totalQuery === false) {
            error_log("Failed to prepare total properties query: " . $conn->error);
        } else {
            $totalQuery->bind_param("i", $landlordId);
            $totalQuery->execute();
            $result = $totalQuery->get_result()->fetch_assoc();
            $totalProperties = $result ? $result['count'] : 0;
            $totalQuery->close();
        }

        // Get active properties count
        $activeQuery = $conn->prepare("SELECT COUNT(*) as count FROM properties WHERE landlord_id = ? AND status = 'available'");
        if ($activeQuery === false) {
            error_log("Failed to prepare active properties query: " . $conn->error);
        } else {
            $activeQuery->bind_param("i", $landlordId);
            $activeQuery->execute();
            $result = $activeQuery->get_result()->fetch_assoc();
            $activeProperties = $result ? $result['count'] : 0;
            $activeQuery->close();
        }

        // Get total views count (if views column exists)
        $viewsQuery = $conn->prepare("SELECT COUNT(*) as property_count FROM properties WHERE landlord_id = ?");
        if ($viewsQuery === false) {
            error_log("Failed to prepare views query: " . $conn->error);
        } else {
            $viewsQuery->bind_param("i", $landlordId);
            $viewsQuery->execute();
            $result = $viewsQuery->get_result()->fetch_assoc();
            $totalViews = $result ? $result['property_count'] : 0;
            $viewsQuery->close();
        }
    }
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
    <link rel="stylesheet" href="../assets/css/properties.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php 
    // Set active page for navigation
    $activePage = 'home';
    $navPath = '../'; // From landlord subdirectory
    include '../includes/navbar.php'; 
    ?>

    <!-- Main Content -->
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
</body>
</html>