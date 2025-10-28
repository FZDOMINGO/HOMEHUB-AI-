<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    redirect('login/login.html');
    exit;
}

// Get landlord data
$conn = getDbConnection();
$userId = $_SESSION['user_id'];

// Get landlord ID
$stmt = $conn->prepare("SELECT id FROM landlords WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$landlord = $result->fetch_assoc();
$landlordId = $landlord['id'];

// Get statistics
// Total properties
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM properties WHERE landlord_id = ?");
$stmt->bind_param("i", $landlordId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalProperties = $row['total'];

// Available properties
$stmt = $conn->prepare("SELECT COUNT(*) as available FROM properties WHERE landlord_id = ? AND status = 'available'");
$stmt->bind_param("i", $landlordId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$availableProperties = $row['available'];

// Total views this month
$firstDayOfMonth = date('Y-m-01');
$stmt = $conn->prepare("SELECT SUM(views) as total_views FROM property_views WHERE property_id IN (SELECT id FROM properties WHERE landlord_id = ?) AND view_date >= ?");
$stmt->bind_param("is", $landlordId, $firstDayOfMonth);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalViews = $row['total_views'] ?: 0;

// AI recommendations
$stmt = $conn->prepare("SELECT COUNT(*) as recommendations FROM ai_recommendations WHERE landlord_id = ?");
$stmt->bind_param("i", $landlordId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$aiRecommendations = $row['recommendations'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Landlord Dashboard - HomeHub AI</title>
  <link rel="stylesheet" href="dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <?php 
  $activePage = 'dashboard';
  $navPath = '../';
  include '../includes/navbar.php'; 
  ?>

  <!-- Main Content -->
  <main class="main-content">
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
          <a href="manage-properties.php" class="sidebar-item">
            <div class="sidebar-link">Manage Properties</div>
          </a>
          <a href="manage-availability.php" class="sidebar-item">
            <div class="sidebar-link">Manage Availability</div>
          </a>
          <a href="notifications.php" class="sidebar-item">
            <div class="sidebar-link">Notifications</div>
          </a>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-area">
          <div class="welcome-header">
            <h1>Landlord Dashboard</h1>
            <p>Manage your properties and track your rental business performance</p>
          </div>
          
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-home"></i>
              </div>
              <div class="stat-number"><?php echo $totalProperties; ?></div>
              <div class="stat-label">Total Properties</div>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="stat-number"><?php echo $availableProperties; ?></div>
              <div class="stat-label">Available Properties</div>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-chart-bar"></i>
              </div>
              <div class="stat-number"><?php echo $totalViews; ?></div>
              <div class="stat-label">Total Views This Month</div>
            </div>
            
            <div class="stat-card">
              <div class="stat-icon">
                <i class="fas fa-robot"></i>
              </div>
              <div class="stat-number"><?php echo $aiRecommendations; ?></div>
              <div class="stat-label">AI Recommendations</div>
            </div>
          </div>
          
          <!-- Performance Overview Section -->
          <div class="performance-section">
            <h2>Recent Activity</h2>
            <div class="activity-list">
              <div class="activity-item">
                <div class="activity-icon">
                  <i class="fas fa-eye"></i>
                </div>
                <div class="activity-details">
                  <p>"Modern Downtown Apartment" was viewed 45 times this month</p>
                  <small>Last viewed: Today, 10:30 AM</small>
                </div>
              </div>
              
              <div class="activity-item">
                <div class="activity-icon">
                  <i class="fas fa-calendar-check"></i>
                </div>
                <div class="activity-details">
                  <p>3 new booking requests received for "Modern Downtown Apartment"</p>
                  <small>Latest request: Yesterday, 3:45 PM</small>
                </div>
              </div>
              
              <div class="activity-item">
                <div class="activity-icon">
                  <i class="fas fa-home"></i>
                </div>
                <div class="activity-details">
                  <p>"Luxury Condo BGC" is now occupied by Maria Santos</p>
                  <small>Since: Oct 01, 2025</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="dashboard.js"></script>
</body>
</html>
