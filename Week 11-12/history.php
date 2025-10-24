<?php
// Start session
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$userType = $isLoggedIn ? $_SESSION['user_type'] : 'guest';

// Redirect guests to login
if (!$isLoggedIn) {
    header("Location: login/login.html");
    exit;
}

// Include database connection
require_once 'config/db_connect.php';
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
  <title>History - HomeHub AI</title>
  <link rel="stylesheet" href="assets/css/history.css">
</head>
<body>
  <?php 
  $activePage = 'history';
  $navPath = '';
  include 'includes/navbar.php'; 
  ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="container">
      <!-- Header Section -->
      <div class="page-header">
        <div class="header-content">
          <h1>Activity History</h1>
          <p>Track your property searches, reservations, visits, and AI recommendations</p>
        </div>
        <div class="header-actions">
          <div class="filter-dropdown">
            <select id="activity-filter" class="filter-select">
              <option value="all">All Activities</option>
              <option value="reservations">Reservations</option>
              <option value="visits">Property Visits</option>
              <option value="searches">Property Searches</option>
              <option value="ai-activity">AI Activity</option>
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
      <div class="activity-timeline">
        <!-- Success Activity -->
        <div class="activity-item success" data-category="reservations" data-date="2025-09-22">
          <div class="activity-connector"></div>
          <div class="activity-icon success">
            <span>✅</span>
          </div>
          <div class="activity-content">
            <div class="activity-header">
              <h3>Property Reservation Confirmed</h3>
              <span class="activity-date">September 22, 2025</span>
            </div>
            <div class="activity-description">
              <p>Your reservation for the Modern 2BR Apartment in Makati has been approved by the landlord.</p>
            </div>
            <div class="activity-details">
              <div class="detail-item">
                <span class="detail-icon">🏠</span>
                <span class="detail-text">Modern 2BR Apartment</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">📍</span>
                <span class="detail-text">Makati City</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">💰</span>
                <span class="detail-text">₱35,000/month</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">👤</span>
                <span class="detail-text">Maria Santos (Landlord)</span>
              </div>
            </div>
            <div class="activity-actions">
              <button class="action-btn primary">View Details</button>
              <button class="action-btn secondary">Contact Landlord</button>
            </div>
          </div>
        </div>

        <!-- Visit Activity -->
        <div class="activity-item info" data-category="visits" data-date="2025-09-21">
          <div class="activity-connector"></div>
          <div class="activity-icon info">
            <span>📅</span>
          </div>
          <div class="activity-content">
            <div class="activity-header">
              <h3>Property Visit Scheduled</h3>
              <span class="activity-date">September 21, 2025</span>
            </div>
            <div class="activity-description">
              <p>Successfully scheduled a visit to Luxury Condo in BGC for September 23 at 2:00 PM.</p>
            </div>
            <div class="activity-details">
              <div class="detail-item">
                <span class="detail-icon">🏢</span>
                <span class="detail-text">Luxury Condo Unit</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">📍</span>
                <span class="detail-text">Bonifacio Global City</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">🕐</span>
                <span class="detail-text">Sep 23, 2:00 PM</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">🤖</span>
                <span class="detail-text">AI Matched - 94% Compatible</span>
              </div>
            </div>
            <div class="activity-actions">
              <button class="action-btn primary">View Property</button>
              <button class="action-btn secondary">Reschedule</button>
            </div>
          </div>
        </div>

        <!-- Search Activity -->
        <div class="activity-item neutral" data-category="searches" data-date="2025-09-20">
          <div class="activity-connector"></div>
          <div class="activity-icon neutral">
            <span>👁️</span>
          </div>
          <div class="activity-content">
            <div class="activity-header">
              <h3>Properties Viewed</h3>
              <span class="activity-date">September 20, 2025</span>
            </div>
            <div class="activity-description">
              <p>Viewed 8 properties matching your preferences. AI recommendations helped narrow down choices.</p>
            </div>
            <div class="activity-details">
              <div class="detail-item">
                <span class="detail-icon">🔢</span>
                <span class="detail-text">8 Properties Viewed</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">❤️</span>
                <span class="detail-text">3 Properties Saved</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">🎯</span>
                <span class="detail-text">AI Match Score: 87%</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">⭐</span>
                <span class="detail-text">Top Choice: BGC Condo</span>
              </div>
            </div>
            <div class="activity-actions">
              <button class="action-btn primary">View Saved Properties</button>
              <button class="action-btn secondary">New Search</button>
            </div>
          </div>
        </div>

        <!-- Cancelled Activity -->
        <div class="activity-item error" data-category="visits" data-date="2025-09-19">
          <div class="activity-connector"></div>
          <div class="activity-icon error">
            <span>❌</span>
          </div>
          <div class="activity-content">
            <div class="activity-header">
              <h3>Visit Cancelled</h3>
              <span class="activity-date">September 19, 2025</span>
            </div>
            <div class="activity-description">
              <p>Property visit to Studio Apartment was cancelled due to property being no longer available.</p>
            </div>
            <div class="activity-details">
              <div class="detail-item">
                <span class="detail-icon">🏠</span>
                <span class="detail-text">Studio Apartment</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">📍</span>
                <span class="detail-text">Quezon City</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">💔</span>
                <span class="detail-text">No longer available</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">🔄</span>
                <span class="detail-text">3 Alternatives suggested</span>
              </div>
            </div>
            <div class="activity-actions">
              <button class="action-btn primary">View Alternatives</button>
              <button class="action-btn secondary">Find Similar</button>
            </div>
          </div>
        </div>

        <!-- AI Activity -->
        <div class="activity-item ai" data-category="ai-activity" data-date="2025-09-18">
          <div class="activity-connector"></div>
          <div class="activity-icon ai">
            <span>🤖</span>
          </div>
          <div class="activity-content">
            <div class="activity-header">
              <h3>AI Recommendation Update</h3>
              <span class="activity-date">September 18, 2025</span>
            </div>
            <div class="activity-description">
              <p>AI analyzed your preferences and found 5 new properties that match your criteria with 95%+ compatibility.</p>
            </div>
            <div class="activity-details">
              <div class="detail-item">
                <span class="detail-icon">🎯</span>
                <span class="detail-text">5 New Matches Found</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">📊</span>
                <span class="detail-text">95%+ Compatibility Score</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">💡</span>
                <span class="detail-text">Budget-optimized suggestions</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">🔔</span>
                <span class="detail-text">Real-time notifications enabled</span>
              </div>
            </div>
            <div class="activity-actions">
              <button class="action-btn primary">View Recommendations</button>
              <button class="action-btn secondary">Update Preferences</button>
            </div>
          </div>
        </div>

        <!-- Application Activity -->
        <div class="activity-item warning" data-category="reservations" data-date="2025-09-17">
          <div class="activity-connector"></div>
          <div class="activity-icon warning">
            <span>⏳</span>
          </div>
          <div class="activity-content">
            <div class="activity-header">
              <h3>Application Under Review</h3>
              <span class="activity-date">September 17, 2025</span>
            </div>
            <div class="activity-description">
              <p>Your rental application for Premium Loft in Ortigas is currently under review by the landlord.</p>
            </div>
            <div class="activity-details">
              <div class="detail-item">
                <span class="detail-icon">🏢</span>
                <span class="detail-text">Premium Loft Unit</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">📍</span>
                <span class="detail-text">Ortigas Center</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">📄</span>
                <span class="detail-text">Documents submitted</span>
              </div>
              <div class="detail-item">
                <span class="detail-icon">⏱️</span>
                <span class="detail-text">Expected response: 2-3 days</span>
              </div>
            </div>
            <div class="activity-actions">
              <button class="action-btn primary">Check Status</button>
              <button class="action-btn secondary">Upload Documents</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Load More Button -->
      <div class="load-more-section">
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

  <script src="assets/js/history.js"></script>
</body>
</html>
