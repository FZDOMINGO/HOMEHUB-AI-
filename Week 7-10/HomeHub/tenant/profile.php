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
$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$message = '';

// Handle form submission - Update profile
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $firstName = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
    $lastName = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['contact'], FILTER_SANITIZE_STRING);
    $budget = filter_var($_POST['budget'], FILTER_SANITIZE_STRING);
    $location = filter_var($_POST['location'], FILTER_SANITIZE_STRING);
    
    // Update users table
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $userId);
    $stmt->execute();
    
    // Update tenants table
    $stmt = $conn->prepare("UPDATE tenants SET preferred_location = ?, max_budget = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $location, $budget, $userId);
    $stmt->execute();
    
    $message = "Profile updated successfully!";
    
    // Update session data
    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    $_SESSION['user_email'] = $email;
}

// Get user data
$stmt = $conn->prepare("
    SELECT u.first_name, u.last_name, u.email, u.phone, u.profile_image, 
           t.preferred_location, t.max_budget
    FROM users u
    JOIN tenants t ON u.id = t.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile - HomeHub AI</title>
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="profile.css">
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
        <a href="bookings.php" class="nav-link">Bookings</a>
        <a href="history.php" class="nav-link">History</a>
        <a href="ai-features.php" class="nav-link">AI Features</a>
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
      <a href="dashboard.php" class="nav-link-mobile">Dashboard</a>
      <a href="bookings.php" class="nav-link-mobile">Bookings</a>
      <a href="history.php" class="nav-link-mobile">History</a>
      <a href="ai-features.php" class="nav-link-mobile">AI Features</a>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content">
    <div class="dashboard-container">
      <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
          <a href="dashboard.php" class="sidebar-item">
            <div class="sidebar-link">Dashboard Overview</div>
          </a>
          <a href="profile.php" class="sidebar-item active">
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
          <div class="profile-header">
            <h1>My Profile</h1>
            <p>Manage your personal information and rental preferences</p>
          </div>
          
          <?php if(!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
          <?php endif; ?>
          
          <div class="profile-content">
            <div class="profile-image-container">
              <?php if(!empty($user['profile_image'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="profile-image">
              <?php else: ?>
                <div class="profile-image-placeholder">
                  <i class="fas fa-user"></i>
                </div>
              <?php endif; ?>
              <div class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
            </div>
            
            <form class="profile-form" method="POST" action="profile.php">
              <div class="form-row">
                <div class="form-group">
                  <label for="first_name">First Name</label>
                  <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                
                <div class="form-group">
                  <label for="last_name">Last Name</label>
                  <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
              </div>
              
              <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              </div>
              
              <div class="form-group">
                <label for="contact">Contact Number</label>
                <input type="tel" id="contact" name="contact" value="<?php echo htmlspecialchars($user['phone']); ?>">
              </div>
              
              <div class="form-group">
                <label for="budget">Budget Range (optional)</label>
                <input type="text" id="budget" name="budget" value="<?php echo htmlspecialchars($user['max_budget']); ?>" placeholder="Enter your budget">
              </div>
              
              <div class="form-group">
                <label for="location">Preferred Location</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['preferred_location']); ?>">
              </div>
              
              <div class="form-actions">
                <button type="submit" name="update_profile" class="btn-update">Update Profile</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="dashboard.js"></script>
  <script>
    // Success alert auto-close
    document.addEventListener('DOMContentLoaded', function() {
      const alert = document.querySelector('.alert');
      if (alert) {
        setTimeout(() => {
          alert.style.opacity = '0';
          setTimeout(() => {
            alert.style.display = 'none';
          }, 500);
        }, 3000);
      }
    });
  </script>
</body>
</html>