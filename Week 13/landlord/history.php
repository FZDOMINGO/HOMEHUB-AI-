<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

// Check if user is logged in and is a landlord
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$userType = $isLoggedIn ? $_SESSION['user_type'] : 'guest';

// Redirect if not landlord
if (!$isLoggedIn || $userType !== 'landlord') {
    redirect('login/login.html');
    exit;
}

$conn = getDbConnection();

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Ensure session has user name for navbar
if (!isset($_SESSION['user_name']) && $user) {
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activity History - HomeHub</title>
  <link rel="stylesheet" href="../assets/css/history.css">
</head>
<body>
  <?php 
  $activePage = 'history';
  $navPath = '../';
  include '../includes/navbar.php'; 
  ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="container">
      <!-- Header Section -->
      <div class="page-header">
        <div class="header-content">
          <h1>Landlord Activity History</h1>
          <p>Track reservations, visits, property views, and messages from tenants</p>
        </div>
        <div class="header-actions">
          <div class="filter-dropdown">
            <select id="activity-filter" class="filter-select">
              <option value="all">All Activities</option>
              <option value="reservations">Reservations</option>
              <option value="visits">Property Visits</option>
              <option value="searches">Property Views</option>
            </select>
          </div>
          <div class="date-filter">
            <input type="date" id="date-from" class="date-input">
            <span class="date-separator">to</span>
            <input type="date" id="date-to" class="date-input">
          </div>
        </div>
      </div>

      <!-- Activity Timeline -->
      <div class="activity-timeline" id="activity-timeline">
        <!-- Activities will be loaded dynamically -->
        <div class="loading-spinner" id="loading-spinner">
          <div class="spinner"></div>
          <p>Loading your activity history...</p>
        </div>
      </div>

      <!-- Load More Button -->
      <div class="load-more-section" id="load-more-section" style="display: none;">
        <button class="load-more-btn" id="load-more-btn">
          <span class="load-text">Load More Activities</span>
          <span class="load-icon">↓</span>
        </button>
      </div>

      <!-- Empty State (hidden by default) -->
      <div class="empty-state" id="empty-state" style="display: none;">
        <div class="empty-icon">📝</div>
        <h3>No Activities Found</h3>
        <p>No activities match your current filter criteria.</p>
        <button class="action-btn primary" onclick="clearFilters()">Clear Filters</button>
      </div>
    </div>
  </main>

  <script>
    // Pass user type to JavaScript
    const userType = '<?php echo $userType; ?>';
    const userId = <?php echo $userId; ?>;
  </script>
  <script src="../assets/js/history.js"></script>
</body>
</html>
