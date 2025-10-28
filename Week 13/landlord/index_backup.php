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
    <title>Home - HomeHub AI</title>
    <link rel="stylesheet" href="index.css">
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
                <h1 class="hero-title">Welcome Back, <?php echo htmlspecialchars($userName); ?>!</h1>
                <p class="hero-subtitle">Manage your properties efficiently with AI-powered insights and comprehensive rental management tools.</p>
                
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="stat-number"><?php echo $totalProperties; ?></div>
                        <div class="stat-label">Total Properties</div>
                    </div>
                    <div class="hero-stat">
                        <div class="stat-number"><?php echo $activeProperties; ?></div>
                        <div class="stat-label">Active Listings</div>
                    </div>
                    <div class="hero-stat">
                        <div class="stat-number"><?php echo $totalViews; ?></div>
                        <div class="stat-label">Total Views</div>
                    </div>
                </div>

                <div class="hero-actions">
                    <a href="add-property.php" class="btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Property
                    </a>
                    <a href="manage-properties.php" class="btn-secondary">
                        <i class="fas fa-cog"></i>
                        Manage Properties
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions Section -->
    <section class="quick-actions">
        <div class="container">
            <h2 class="section-title">Management Hub</h2>
            <div class="actions-grid">
                <a href="dashboard.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h3>Analytics Dashboard</h3>
                    <p>Monitor performance metrics and financial insights</p>
                </a>

                <a href="add-property.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h3>Add Property</h3>
                    <p>List new properties and expand your portfolio</p>
                </a>

                <a href="manage-properties.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Property Management</h3>
                    <p>Edit, update, and organize your listings</p>
                </a>

                <a href="tenants.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Tenant Relations</h3>
                    <p>Communicate and manage tenant interactions</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Landlord Benefits</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Analytics & Insights</h3>
                    <p>Get detailed analytics on property performance and tenant engagement with comprehensive reporting tools.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Quality Tenants</h3>
                    <p>Connect with verified, pre-screened tenants looking for quality rentals that match your property offerings.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Easy Management</h3>
                    <p>Manage all your properties from one convenient dashboard with automated workflows and notifications.</p>
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