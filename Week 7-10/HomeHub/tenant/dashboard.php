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

// Get tenant stats (placeholder values)
$savedProperties = 12;
$scheduledVisits = 3;
$propertiesViewed = 45;
$aiRecommendations = 8;

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
  <!-- Navigation Header -->
  <nav class="navbar">
    <div class="nav-container">
      <!-- Logo -->
      <div class="nav-logo">
        <img src="../assets/homehublogo.jpg" alt="HomeHub AI Logo" class="logo-img">
      </div>
      
      <!-- Desktop Navigation (hidden on mobile) -->
      <div class="nav-center">
        <a href="../guest/index.html" class="nav-link">Home</a>
        <a href="../properties.php" class="nav-link-mobile">Properties</a>
        <a href="dashboard.php" class="nav-link active">Dashboard</a>
        <a href="../bookings.php" class="nav-link">Bookings</a>
        <a href="history.html" class="nav-link">History</a>
        <a href="ai-features.html" class="nav-link">AI Features</a>
      </div>
      
      <!-- Desktop Buttons (hidden on mobile) -->
      <div class="nav-right">
        <span class="user-greeting">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="#" id="logoutBtn" class="btn-login">Logout</a>
      </div>
      
      <!-- Mobile Navigation Buttons -->
      <div class="nav-buttons-mobile">
        <a href="#" id="logoutBtnMobile" class="btn-login-mobile">Logout</a>
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
      <a href="../guest/index.html" class="nav-link-mobile">Home</a>
<a href="../properties.php" class="nav-link-mobile">Properties</a>
      <a href="dashboard.php" class="nav-link-mobile active">Dashboard</a>
      <a href="bookings.php" class="nav-link-mobile">Bookings</a>
      <a href="history.php" class="nav-link-mobile">History</a>
      <a href="ai-features.php" class="nav-link-mobile">AI Features</a>
    </div>
  </nav>

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