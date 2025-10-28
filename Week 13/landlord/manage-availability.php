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

$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Get landlord ID
$stmt = $conn->prepare("SELECT id FROM landlords WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$landlord = $result->fetch_assoc();
$landlordId = $landlord['id'];

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $propertyId = filter_var($_POST['property_id'], FILTER_SANITIZE_NUMBER_INT);
    $status = $_POST['status'] == '1' ? 'available' : 'occupied';
    
    $stmt = $conn->prepare("UPDATE properties SET status = ? WHERE id = ? AND landlord_id = ?");
    $stmt->bind_param("sii", $status, $propertyId, $landlordId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0 || $stmt->errno == 0) {
        $message = "Property status updated successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to update property status.";
        $messageType = "danger";
    }
}

// Get all properties for this landlord
$stmt = $conn->prepare("
    SELECT p.id, p.title, p.city, p.state, p.property_type, p.bedrooms, p.bathrooms, 
           p.rent_amount, p.status, 
           (SELECT SUM(views) FROM property_views WHERE property_id = p.id AND view_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)) as monthly_views,
           (SELECT COUNT(*) FROM booking_requests WHERE property_id = p.id AND status = 'pending') as pending_bookings,
           (SELECT CONCAT(first_name, ' ', last_name) FROM users u JOIN tenants t ON u.id = t.user_id JOIN bookings b ON t.id = b.tenant_id WHERE b.property_id = p.id AND b.status = 'active' LIMIT 1) as tenant_name
    FROM properties p
    WHERE p.landlord_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $landlordId);
$stmt->execute();
$result = $stmt->get_result();
$properties = [];

while ($row = $result->fetch_assoc()) {
    $properties[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Availability - HomeHub AI</title>
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="manage-availability.css">
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
          <a href="manage-properties.php" class="sidebar-item">
            <div class="sidebar-link">Manage Properties</div>
          </a>
          <a href="manage-availability.php" class="sidebar-item active">
            <div class="sidebar-link">Manage Availability</div>
          </a>
          <a href="notifications.php" class="sidebar-item">
            <div class="sidebar-link">Notifications</div>
          </a>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-area">
          <div class="availability-header">
            <h1>Manage Availability</h1>
            <p>Control the availability status of your properties</p>
          </div>
          
          <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
          <?php endif; ?>
          
          <?php if(count($properties) > 0): ?>
            <div class="properties-list">
              <?php foreach($properties as $property): ?>
                <div class="property-card">
                  <div class="property-header">
                    <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                    <div class="property-status <?php echo strtolower($property['status']); ?>">
                      <?php echo htmlspecialchars($property['status']); ?>
                    </div>
                  </div>
                  
                  <div class="property-info">
                    <div class="property-toggle">
                      <span class="toggle-label">Property Status:</span>
                      <form method="POST" action="manage-availability.php" class="toggle-form">
                        <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                        <label class="switch">
                          <input type="checkbox" name="status" value="1" <?php echo $property['status'] == 'available' ? 'checked' : ''; ?> 
                                 onchange="this.form.submit()">
                                                    <span class="slider round"></span>
                        </label>
                        <input type="hidden" name="update_status" value="1">
                      </form>
                    </div>
                    
                    <div class="property-location">
                      <i class="fas fa-map-marker-alt"></i> 
                      <?php echo htmlspecialchars($property['city'] . ', ' . $property['state']); ?>
                    </div>
                    
                    <div class="property-stats">
                      <span><i class="fas fa-eye"></i> <?php echo $property['monthly_views'] ? $property['monthly_views'] : 0; ?> views this month</span>
                      
                      <?php if($property['status'] == 'available' && $property['pending_bookings'] > 0): ?>
                        <span><i class="fas fa-calendar-check"></i> <?php echo $property['pending_bookings']; ?> bookings pending</span>
                      <?php endif; ?>
                      
                      <?php if($property['status'] == 'occupied' && !empty($property['tenant_name'])): ?>
                        <span><i class="fas fa-user"></i> Tenant: <?php echo htmlspecialchars($property['tenant_name']); ?></span>
                      <?php endif; ?>
                    </div>
                    
                    <div class="property-details">
                      <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> bedrooms</span>
                      <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> bathroom</span>
                      <span><i class="fas fa-money-bill-wave"></i> ₱<?php echo number_format($property['rent_amount']); ?>/month</span>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="no-properties">
              <i class="fas fa-home"></i>
              <p>You haven't added any properties yet.</p>
              <a href="add-property.php" class="btn-add">Add Your First Property</a>
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
    });
  </script>
</body>
</html>
