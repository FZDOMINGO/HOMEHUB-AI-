<?php
// Start session
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header("Location: ../login/login.html");
    exit;
}

// Include database connection
require_once '../config/db_connect.php';

// Get tenant data
$conn = getDbConnection();
$userId = $_SESSION['user_id'];

// Initialize default values
$savedProperties = 0;
$scheduledVisits = 0;
$propertiesViewed = 0;
$aiRecommendations = 0;

try {
    // First, get the tenant_id from tenants table
    $tenantQuery = $conn->prepare("SELECT id as tenant_id FROM tenants WHERE user_id = ?");
    $tenantQuery->bind_param("i", $userId);
    $tenantQuery->execute();
    $tenantResult = $tenantQuery->get_result()->fetch_assoc();
    $tenantId = $tenantResult ? $tenantResult['tenant_id'] : 0;

    if ($tenantId > 0) {
        // 1. Saved Properties - from saved_properties table using tenant_id
        $savedQuery = $conn->prepare("SELECT COUNT(*) as count FROM saved_properties WHERE tenant_id = ?");
        $savedQuery->bind_param("i", $tenantId);
        $savedQuery->execute();
        $result = $savedQuery->get_result()->fetch_assoc();
        $savedProperties = $result ? $result['count'] : 0;

        // 2. Scheduled Visits - from booking_visits table (pending, approved, or confirmed visits)
        $visitsQuery = $conn->prepare("SELECT COUNT(*) as count FROM booking_visits WHERE tenant_id = ? AND status IN ('pending', 'approved') AND visit_date >= CURDATE()");
        $visitsQuery->bind_param("i", $tenantId);
        $visitsQuery->execute();
        $result = $visitsQuery->get_result()->fetch_assoc();
        $scheduledVisits = $result ? $result['count'] : 0;
    }

    // 3. Properties Viewed - from browsing_history table (distinct properties)
    $viewedQuery = $conn->prepare("SELECT COUNT(DISTINCT property_id) as count FROM browsing_history WHERE user_id = ?");
    $viewedQuery->bind_param("i", $userId);
    $viewedQuery->execute();
    $result = $viewedQuery->get_result()->fetch_assoc();
    $propertiesViewed = $result ? $result['count'] : 0;

    // 4. AI Recommendations - from recommendation_cache table (valid recommendations in last 30 days)
    $aiQuery = $conn->prepare("SELECT COUNT(*) as count FROM recommendation_cache WHERE user_id = ? AND is_valid = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $aiQuery->bind_param("i", $userId);
    $aiQuery->execute();
    $result = $aiQuery->get_result()->fetch_assoc();
    $aiRecommendations = $result ? $result['count'] : 0;

} catch (Exception $e) {
    // Log error but continue with default values
    error_log("Tenant dashboard stats error: " . $e->getMessage());
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tenant Dashboard - HomeHub AI</title>
  <link rel="stylesheet" href="dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <?php 
  // Set active page for navigation
  $activePage = 'dashboard';
  $navPath = '../'; // One level up from tenant folder
  include '../includes/navbar.php'; 
  ?>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Dashboard Content -->
    <div class="dashboard-container">
      <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
          <a href="dashboard.php" class="sidebar-item active">
            <div class="sidebar-link">Dashboard Overview</div>
          </a>
          <a href="profile.php" class="sidebar-item">
            <div class="sidebar-link">My Profile</div>
          </a>
          <a href="saved.php" class="sidebar-item">
            <div class="sidebar-link">Saved Rentals</div>
          </a>
          <a href="notifications.php" class="sidebar-item">
            <div class="sidebar-link">Notifications</div>
          </a>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-area">
          <div class="welcome-header">
            <h1>Tenant Dashboard</h1>
            <p>Welcome back! Here's what's happening with your rental journey.</p>
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
                <i class="fas fa-calendar-check"></i>
              </div>
              <div class="stat-number"><?php echo $scheduledVisits; ?></div>
              <div class="stat-label">Scheduled Visits</div>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-chart-bar"></i>
              </div>
              <div class="stat-number"><?php echo $propertiesViewed; ?></div>
              <div class="stat-label">Properties Viewed</div>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-robot"></i>
              </div>
              <div class="stat-number"><?php echo $aiRecommendations; ?></div>
              <div class="stat-label">AI Recommendations</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="dashboard.js"></script>
</body>
</html>
