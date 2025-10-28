<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

// Add debugging output to see what's being submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
}

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    redirect('login/login.html');
    exit;
}

// Test if PHP is working properly
if (isset($_GET['test'])) {
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 8px;'>
            PHP is working! Server time: " . date('Y-m-d H:i:s') . "
          </div>";
}

// Test upload directory
$uploadDir = "../uploads/properties/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
    echo "<div style='background-color: #cce5ff; color: #004085; padding: 15px; margin-bottom: 20px; border-radius: 8px;'>
            Upload directory created at: $uploadDir
          </div>";
}
else if (!is_writable($uploadDir)) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 8px;'>
            Warning: Upload directory exists but is not writable. Please check permissions.
          </div>";
}

$conn = getDbConnection();

$userId = $_SESSION['user_id'];

// Get landlord ID
$stmt = $conn->prepare("SELECT id FROM landlords WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$landlord = $result->fetch_assoc();
$landlordId = $landlord['id'];

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug information
    error_log("Form submitted: " . print_r($_POST, true));
    error_log("Files submitted: " . print_r($_FILES, true));
    
    // Look for any of our submission methods
    if (isset($_POST['add_property']) || isset($_POST['emergency_submit'])) {
        // Get form data - FIXED: Replace deprecated FILTER_SANITIZE_STRING
        $title = htmlspecialchars(trim($_POST['title'] ?? ''));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $address = htmlspecialchars(trim($_POST['address'] ?? ''));
        $city = htmlspecialchars(trim($_POST['city'] ?? ''));
        $state = htmlspecialchars(trim($_POST['state'] ?? ''));
        $zipCode = htmlspecialchars(trim($_POST['zip_code'] ?? ''));
        $propertyType = htmlspecialchars(trim($_POST['property_type'] ?? ''));
        $bedrooms = filter_var($_POST['bedrooms'] ?? 0, FILTER_VALIDATE_INT);
        $bathrooms = filter_var($_POST['bathrooms'] ?? 0, FILTER_VALIDATE_FLOAT);
        $squareFeet = filter_var($_POST['square_feet'] ?? 0, FILTER_VALIDATE_INT);
        $rentAmount = filter_var($_POST['rent_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
        $depositAmount = filter_var($_POST['deposit_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
        $availabilityDate = htmlspecialchars(trim($_POST['availability_date'] ?? date('Y-m-d')));
        
        // Amenities as an array
        $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
        
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // Insert into properties table
            $stmt = $conn->prepare("INSERT INTO properties (landlord_id, title, description, address, city, state, 
                                    zip_code, property_type, bedrooms, bathrooms, square_feet, rent_amount, 
                                    deposit_amount, availability_date, status, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())");
            
            // FIXED: Corrected bind_param - added 's' for availability_date
            $stmt->bind_param("isssssssidddds", $landlordId, $title, $description, $address, $city, $state, 
                            $zipCode, $propertyType, $bedrooms, $bathrooms, $squareFeet, $rentAmount, 
                            $depositAmount, $availabilityDate);
            
            $stmt->execute();
            $propertyId = $conn->insert_id;
            
            // Insert amenities
            if (!empty($amenities)) {
                $amenityStmt = $conn->prepare("INSERT INTO property_amenities (property_id, amenity_name) VALUES (?, ?)");
                foreach ($amenities as $amenity) {
                    $amenityStmt->bind_param("is", $propertyId, $amenity);
                    $amenityStmt->execute();
                }
            }
            
            // Handle image uploads
            if (!empty($_FILES['property_images']['name'][0])) {
                // Create directory for property images if it doesn't exist
                $uploadDir = "../uploads/properties/{$propertyId}/";
                if (!file_exists($uploadDir)) {
                    if (!mkdir($uploadDir, 0777, true)) {
                        throw new Exception("Failed to create upload directory: $uploadDir");
                    }
                }
                
                $imageStmt = $conn->prepare("INSERT INTO property_images (property_id, image_url, is_primary) VALUES (?, ?, ?)");
                
                foreach ($_FILES['property_images']['name'] as $key => $name) {
                    if ($_FILES['property_images']['error'][$key] != 0) {
                        // Log the error code
                        error_log("Upload error for file {$name}: " . $_FILES['property_images']['error'][$key]);
                        continue;
                    }
                    
                    $tmpName = $_FILES['property_images']['tmp_name'][$key];
                    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($extension, $allowedExtensions)) {
                        $newFileName = uniqid() . '.' . $extension;
                        $destination = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($tmpName, $destination)) {
                            $imageUrl = "uploads/properties/{$propertyId}/{$newFileName}";
                            $isPrimary = ($key === 0) ? 1 : 0; // First image is primary
                            
                            $imageStmt->bind_param("isi", $propertyId, $imageUrl, $isPrimary);
                            $imageStmt->execute();
                        } else {
                            error_log("Failed to move uploaded file from {$tmpName} to {$destination}");
                        }
                    } else {
                        error_log("Invalid file extension: {$extension}");
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            $message = "Property added successfully!";
            $messageType = "success";
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "Error adding property: " . $e->getMessage();
            $messageType = "danger";
            error_log("Exception: " . $e->getMessage());
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Property - HomeHub AI</title>
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="add-property.css">
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
          <a href="manage-properties.php" class="sidebar-item active">
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
          <div class="add-property-header">
            <h1>Add New Property</h1>
            <p>Fill in the details to create a new property listing</p>
          </div>
          
          <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
          <?php endif; ?>
          
          <form class="add-property-form" name="propertyForm" id="propertyForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
            <div class="form-section">
              <h2>Basic Information</h2>
              
              <div class="form-group">
                <label for="title">Property Title*</label>
                <input type="text" id="title" name="title" placeholder="e.g. Modern Downtown Apartment" required>
              </div>
              
              <div class="form-group">
                <label for="description">Description*</label>
                <textarea id="description" name="description" rows="4" placeholder="Describe your property..." required></textarea>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="property_type">Property Type*</label>
                  <select id="property_type" name="property_type" required>
                    <option value="">Select Type</option>
                    <option value="apartment">Apartment</option>
                    <option value="house">House</option>
                    <option value="condo">Condo</option>
                    <option value="room">Room</option>
                    <option value="commercial">Commercial</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="availability_date">Availability Date*</label>
                  <input type="date" id="availability_date" name="availability_date" required>
                </div>
              </div>
            </div>
            
            <div class="form-section">
              <h2>Location</h2>
              
              <div class="form-group">
                <label for="address">Street Address*</label>
                <input type="text" id="address" name="address" placeholder="Enter street address" required>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="city">City*</label>
                  <input type="text" id="city" name="city" placeholder="Enter city" required>
                </div>
                
                <div class="form-group">
                  <label for="state">State/Province*</label>
                  <input type="text" id="state" name="state" placeholder="Enter state" required>
                </div>
              </div>
              
              <div class="form-group">
                <label for="zip_code">ZIP Code*</label>
                <input type="text" id="zip_code" name="zip_code" placeholder="Enter ZIP code" required>
              </div>
            </div>
            
            <div class="form-section">
              <h2>Details</h2>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="bedrooms">Bedrooms*</label>
                  <input type="number" id="bedrooms" name="bedrooms" min="0" step="1" required>
                </div>
                
                <div class="form-group">
                  <label for="bathrooms">Bathrooms*</label>
                  <input type="number" id="bathrooms" name="bathrooms" min="0" step="0.5" required>
                </div>
              </div>
              
              <div class="form-group">
                <label for="square_feet">Square Feet</label>
                <input type="number" id="square_feet" name="square_feet" min="0" step="1">
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="rent_amount">Monthly Rent (₱)*</label>
                  <input type="number" id="rent_amount" name="rent_amount" min="0" step="0.01" required>
                </div>
                
                <div class="form-group">
                  <label for="deposit_amount">Security Deposit (₱)*</label>
                  <input type="number" id="deposit_amount" name="deposit_amount" min="0" step="0.01" required>
                </div>
              </div>
            </div>
            
            <div class="form-section">
              <h2>Amenities</h2>
              
              <div class="amenities-grid">
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_wifi" name="amenities[]" value="Wi-Fi">
                  <label for="amenity_wifi">Wi-Fi</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_ac" name="amenities[]" value="Air Conditioning">
                  <label for="amenity_ac">Air Conditioning</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_parking" name="amenities[]" value="Parking">
                  <label for="amenity_parking">Parking</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_laundry" name="amenities[]" value="Laundry">
                  <label for="amenity_laundry">Laundry</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_gym" name="amenities[]" value="Gym">
                  <label for="amenity_gym">Gym</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_pool" name="amenities[]" value="Pool">
                  <label for="amenity_pool">Swimming Pool</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_security" name="amenities[]" value="Security">
                  <label for="amenity_security">Security</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_pets" name="amenities[]" value="Pet Friendly">
                  <label for="amenity_pets">Pet Friendly</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_furniture" name="amenities[]" value="Furnished">
                  <label for="amenity_furniture">Furnished</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_balcony" name="amenities[]" value="Balcony">
                  <label for="amenity_balcony">Balcony</label>
                </div>
              </div>
            </div>
            
            <div class="form-section">
              <h2>Images</h2>
              
              <div class="form-group">
                <label for="property_images">Upload Images (First image will be the main photo)*</label>
                <div class="image-upload-container">
                  <input type="file" id="property_images" name="property_images[]" accept="image/*" multiple>
                  <label for="property_images" class="upload-btn">
                    <i class="fas fa-cloud-upload-alt"></i> Choose Images
                  </label>
                </div>
                <div id="image-preview" class="image-preview"></div>
              </div>
            </div>
            
            <div class="form-actions">
              <a href="manage-properties.php" class="btn-cancel">Cancel</a>
              <button type="submit" name="add_property" value="1" class="btn-submit">Add Property</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <script src="dashboard.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    console.log('Add property script loaded');
    
    // Image preview functionality only
    const imageInput = document.getElementById('property_images');
    const imagePreview = document.getElementById('image-preview');
    
    if (imageInput) {
      imageInput.addEventListener('change', function() {
        console.log('Images selected:', this.files.length);
        // Clear previous previews
        imagePreview.innerHTML = '';
        
        if (this.files) {
          // Create preview for each file
          for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            
            // Check if file is an image
            if (!file.type.match('image.*')) {
              continue;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
              const previewItem = document.createElement('div');
              previewItem.className = 'preview-item';
              
              const img = document.createElement('img');
              img.src = e.target.result;
              
              previewItem.appendChild(img);
              imagePreview.appendChild(previewItem);
              
              // Mark the first image as primary
              if (i === 0) {
                const primaryMarker = document.createElement('div');
                primaryMarker.className = 'primary-marker';
                primaryMarker.textContent = 'Primary';
                primaryMarker.style.position = 'absolute';
                primaryMarker.style.bottom = '0';
                primaryMarker.style.left = '0';
                primaryMarker.style.right = '0';
                primaryMarker.style.background = 'rgba(139, 92, 246, 0.8)';
                primaryMarker.style.color = 'white';
                primaryMarker.style.textAlign = 'center';
                primaryMarker.style.fontSize = '12px';
                primaryMarker.style.padding = '3px';
                previewItem.appendChild(primaryMarker);
              }
            };
            
            reader.readAsDataURL(file);
          }
        }
      });
    }
  });
  </script>
</body>
</html>
