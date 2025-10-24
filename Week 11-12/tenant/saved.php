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

// Handle property removal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_property'])) {
    $propertyId = filter_var($_POST['property_id'], FILTER_SANITIZE_NUMBER_INT);
    
    $stmt = $conn->prepare("DELETE FROM saved_properties WHERE tenant_id = ? AND property_id = ?");
    $stmt->bind_param("ii", $userId, $propertyId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $message = "Property removed from saved list.";
    }
}

// Get saved properties
$stmt = $conn->prepare("
    SELECT p.id, p.title, p.address, p.city, p.state, p.rent_amount, p.property_type,
           (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as image_url
    FROM saved_properties sp
    JOIN properties p ON sp.property_id = p.id
    WHERE sp.tenant_id = ?
    ORDER BY sp.saved_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$savedProperties = [];

while ($row = $result->fetch_assoc()) {
    $savedProperties[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Saved Rentals - HomeHub AI</title>
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="saved.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <?php 
  $navPath = '../';
  $activePage = '';
  include '../includes/navbar.php'; 
  ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="dashboard-container">
      <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
          <a href="dashboard.php" class="sidebar-item">
            <div class="sidebar-link">Dashboard Overview</div>
          </a>
          <a href="profile.php" class="sidebar-item">
            <div class="sidebar-link">My Profile</div>
          </a>
          <a href="saved.php" class="sidebar-item active">
            <div class="sidebar-link">Saved Rentals</div>
          </a>
          <a href="notifications.php" class="sidebar-item">
            <div class="sidebar-link">Notifications</div>
          </a>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-area">
          <div class="saved-header">
            <h1>Saved Rentals</h1>
            <p>Properties you've bookmarked for future reference</p>
          </div>
          
          <?php if(!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
          <?php endif; ?>
          
          <?php if(count($savedProperties) > 0): ?>
            <div class="saved-properties-grid">
              <?php foreach($savedProperties as $property): ?>
                <div class="property-card" data-property-id="<?php echo $property['id']; ?>">
                  <div class="property-icon">
                    <?php if($property['property_type'] == 'apartment' || $property['property_type'] == 'condo'): ?>
                      <i class="fas fa-building"></i>
                    <?php else: ?>
                      <i class="fas fa-home"></i>
                    <?php endif; ?>
                  </div>
                  <div class="property-details">
                    <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                    <p class="property-location">
                      <i class="fas fa-map-marker-alt"></i> 
                      <?php echo htmlspecialchars($property['city'] . ', ' . $property['state']); ?>
                    </p>
                    <p class="property-price">
                      ₱<?php echo number_format($property['rent_amount']); ?>/month
                    </p>
                    <div class="property-actions">
                      <a href="../properties.php?php echo $property['id']; ?>" class="btn-view">View</a>
                      <form method="POST" action="saved.php" class="remove-form">
                        <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                        <button type="submit" name="remove_property" class="btn-remove">Remove</button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="no-saved-properties">
              <i class="fas fa-heart-broken"></i>
              <p>You don't have any saved properties yet.</p>
              <a href="../properties.php" class="btn-browse">Browse Properties</a>
            </div>
          <?php endif; ?>
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
      
      // Confirm before removing
      const removeForms = document.querySelectorAll('.remove-form');
      removeForms.forEach(form => {
        form.addEventListener('submit', function(e) {
          if (!confirm('Are you sure you want to remove this property from your saved list?')) {
            e.preventDefault();
          }
        });
      });
    });
  </script>
</body>
</html>
