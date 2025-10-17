<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    header("Location: ../login/login.html");
    exit;
}

// Include database connection
require_once '../config/db_connect.php';
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

// Check if property ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage-properties.php");
    exit;
}

$propertyId = intval($_GET['id']);

// Verify the property belongs to this landlord
$stmt = $conn->prepare("SELECT * FROM properties WHERE id = ? AND landlord_id = ?");
$stmt->bind_param("ii", $propertyId, $landlordId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage-properties.php");
    exit;
}

$property = $result->fetch_assoc();

// Get property amenities
$amenities = [];
$stmt = $conn->prepare("SELECT amenity_name FROM property_amenities WHERE property_id = ?");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$amenitiesResult = $stmt->get_result();
while ($row = $amenitiesResult->fetch_assoc()) {
    $amenities[] = $row['amenity_name'];
}

// Get property images
$images = [];
$stmt = $conn->prepare("SELECT id, image_url, is_primary FROM property_images WHERE property_id = ? ORDER BY is_primary DESC");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$imagesResult = $stmt->get_result();
while ($row = $imagesResult->fetch_assoc()) {
    $images[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_property'])) {
    // Get form data
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
    $status = htmlspecialchars(trim($_POST['status'] ?? 'available'));
    
    // Amenities as an array
    $newAmenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
    
    // Images to delete
    $deleteImages = isset($_POST['delete_images']) ? $_POST['delete_images'] : [];
    
    // Start a transaction
    $conn->begin_transaction();
    
try {
    // Update property details
    $stmt = $conn->prepare("UPDATE properties SET 
                            title = ?, description = ?, address = ?, city = ?, state = ?, 
                            zip_code = ?, property_type = ?, bedrooms = ?, bathrooms = ?, 
                            square_feet = ?, rent_amount = ?, deposit_amount = ?, 
                            availability_date = ?, status = ?, updated_at = NOW() 
                            WHERE id = ? AND landlord_id = ?");
    
    // Corrected type definition string - status is a string (s), not integer (i)
    $stmt->bind_param("sssssssiddddssii", $title, $description, $address, $city, $state, 
                    $zipCode, $propertyType, $bedrooms, $bathrooms, $squareFeet, $rentAmount, 
                    $depositAmount, $availabilityDate, $status, $propertyId, $landlordId);
    
    $stmt->execute();
        
        // Update amenities - first delete existing ones
        $stmt = $conn->prepare("DELETE FROM property_amenities WHERE property_id = ?");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        
        // Insert new amenities
        if (!empty($newAmenities)) {
            $amenityStmt = $conn->prepare("INSERT INTO property_amenities (property_id, amenity_name) VALUES (?, ?)");
            foreach ($newAmenities as $amenity) {
                $amenityStmt->bind_param("is", $propertyId, $amenity);
                $amenityStmt->execute();
            }
        }
        
        // Delete selected images
        if (!empty($deleteImages)) {
            // First get the image URLs to delete the files
            $placeholders = str_repeat('?,', count($deleteImages) - 1) . '?';
            $types = str_repeat('i', count($deleteImages));
            
            $stmt = $conn->prepare("SELECT image_url FROM property_images WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$deleteImages);
            $stmt->execute();
            $deleteResult = $stmt->get_result();
            
            // Delete physical files
            while ($row = $deleteResult->fetch_assoc()) {
                $filePath = "../" . $row['image_url'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM property_images WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$deleteImages);
            $stmt->execute();
        }
        
        // Handle new image uploads
        if (!empty($_FILES['property_images']['name'][0])) {
            // Check if we need a new primary image (if the current primary was deleted)
            $needNewPrimary = false;
            if (!empty($deleteImages)) {
                $placeholders = str_repeat('?,', count($deleteImages) - 1) . '?';
                $types = str_repeat('i', count($deleteImages));
                
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM property_images 
                                        WHERE property_id = ? AND is_primary = 1 
                                        AND id NOT IN ($placeholders)");
                $bindParams = array_merge([$propertyId], $deleteImages);
                $stmt->bind_param('i' . $types, ...$bindParams);
                $stmt->execute();
                $checkResult = $stmt->get_result();
                $checkRow = $checkResult->fetch_assoc();
                $needNewPrimary = ($checkRow['count'] === 0);
            }
            
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
                        // If it's the first image and we need a new primary, or no images exist yet
                        $isPrimary = ($key === 0 && ($needNewPrimary || empty($images))) ? 1 : 0;
                        
                        $imageStmt->bind_param("isi", $propertyId, $imageUrl, $isPrimary);
                        $imageStmt->execute();
                    }
                }
            }
        }
        
        // Set primary image if needed and not already set
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM property_images WHERE property_id = ? AND is_primary = 1");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $primaryResult = $stmt->get_result();
        $primaryRow = $primaryResult->fetch_assoc();
        
        if ($primaryRow['count'] === 0) {
            // No primary image, set the first available image as primary
            $stmt = $conn->prepare("UPDATE property_images SET is_primary = 1 
                                   WHERE property_id = ? ORDER BY id ASC LIMIT 1");
            $stmt->bind_param("i", $propertyId);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        $message = "Property updated successfully!";
        $messageType = "success";
        
        // Refresh property data
        $stmt = $conn->prepare("SELECT * FROM properties WHERE id = ?");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $property = $result->fetch_assoc();
        
        // Refresh amenities
        $amenities = [];
        $stmt = $conn->prepare("SELECT amenity_name FROM property_amenities WHERE property_id = ?");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $amenitiesResult = $stmt->get_result();
        while ($row = $amenitiesResult->fetch_assoc()) {
            $amenities[] = $row['amenity_name'];
        }
        
        // Refresh images
        $images = [];
        $stmt = $conn->prepare("SELECT id, image_url, is_primary FROM property_images WHERE property_id = ? ORDER BY is_primary DESC");
        $stmt->bind_param("i", $propertyId);
        $stmt->execute();
        $imagesResult = $stmt->get_result();
        while ($row = $imagesResult->fetch_assoc()) {
            $images[] = $row;
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $message = "Error updating property: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Property - HomeHub AI</title>
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="add-property.css">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .current-images {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 15px;
    }
    
    .current-image-container {
      position: relative;
      width: 150px;
      height: 150px;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .current-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .image-options {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(0,0,0,0.6);
      padding: 5px;
      display: flex;
      justify-content: space-between;
    }
    
    .delete-image {
      color: #ff5252;
      cursor: pointer;
    }
    
    .primary-badge {
      position: absolute;
      top: 5px;
      left: 5px;
      background: #8b5cf6;
      color: white;
      padding: 2px 8px;
      font-size: 10px;
      border-radius: 10px;
      font-weight: 500;
    }
  </style>
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
<a href="../properties.php" class="nav-link">Properties</a>
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
<a href="../properties.php" class="nav-link">Properties</a>
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
            <h1>Edit Property</h1>
            <p>Update your property listing information</p>
          </div>
          
          <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
          <?php endif; ?>
          
          <form class="add-property-form" method="POST" action="edit-property.php?id=<?php echo $propertyId; ?>" enctype="multipart/form-data">
            <div class="form-section">
              <h2>Basic Information</h2>
              
              <div class="form-group">
                <label for="title">Property Title*</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($property['title']); ?>" required>
              </div>
              
              <div class="form-group">
                <label for="description">Description*</label>
                <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($property['description']); ?></textarea>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="property_type">Property Type*</label>
                  <select id="property_type" name="property_type" required>
                    <option value="apartment" <?php echo ($property['property_type'] == 'apartment') ? 'selected' : ''; ?>>Apartment</option>
                    <option value="house" <?php echo ($property['property_type'] == 'house') ? 'selected' : ''; ?>>House</option>
                    <option value="condo" <?php echo ($property['property_type'] == 'condo') ? 'selected' : ''; ?>>Condo</option>
                    <option value="room" <?php echo ($property['property_type'] == 'room') ? 'selected' : ''; ?>>Room</option>
                    <option value="commercial" <?php echo ($property['property_type'] == 'commercial') ? 'selected' : ''; ?>>Commercial</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="status">Status*</label>
                  <select id="status" name="status" required>
                    <option value="available" <?php echo ($property['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                    <option value="occupied" <?php echo ($property['status'] == 'occupied') ? 'selected' : ''; ?>>Occupied</option>
                    <option value="maintenance" <?php echo ($property['status'] == 'maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                  </select>
                </div>
              </div>
              
              <div class="form-group">
                <label for="availability_date">Availability Date*</label>
                <input type="date" id="availability_date" name="availability_date" value="<?php echo htmlspecialchars($property['availability_date']); ?>" required>
              </div>
            </div>
            
            <div class="form-section">
              <h2>Location</h2>
              
              <div class="form-group">
                <label for="address">Street Address*</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($property['address']); ?>" required>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="city">City*</label>
                  <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($property['city']); ?>" required>
                </div>
                
                <div class="form-group">
                  <label for="state">State/Province*</label>
                  <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($property['state']); ?>" required>
                </div>
              </div>
              
              <div class="form-group">
                <label for="zip_code">ZIP Code*</label>
                <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($property['zip_code']); ?>" required>
              </div>
            </div>
            
            <div class="form-section">
              <h2>Details</h2>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="bedrooms">Bedrooms*</label>
                  <input type="number" id="bedrooms" name="bedrooms" min="0" step="1" value="<?php echo $property['bedrooms']; ?>" required>
                </div>
                
                <div class="form-group">
                  <label for="bathrooms">Bathrooms*</label>
                  <input type="number" id="bathrooms" name="bathrooms" min="0" step="0.5" value="<?php echo $property['bathrooms']; ?>" required>
                </div>
              </div>
              
              <div class="form-group">
                <label for="square_feet">Square Feet</label>
                <input type="number" id="square_feet" name="square_feet" min="0" step="1" value="<?php echo $property['square_feet']; ?>">
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="rent_amount">Monthly Rent (₱)*</label>
                  <input type="number" id="rent_amount" name="rent_amount" min="0" step="0.01" value="<?php echo $property['rent_amount']; ?>" required>
                </div>
                
                <div class="form-group">
                  <label for="deposit_amount">Security Deposit (₱)*</label>
                  <input type="number" id="deposit_amount" name="deposit_amount" min="0" step="0.01" value="<?php echo $property['deposit_amount']; ?>" required>
                </div>
              </div>
            </div>
            
            <div class="form-section">
              <h2>Amenities</h2>
              
              <div class="amenities-grid">
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_wifi" name="amenities[]" value="Wi-Fi" <?php echo in_array('Wi-Fi', $amenities) ? 'checked' : ''; ?>>
                  <label for="amenity_wifi">Wi-Fi</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_ac" name="amenities[]" value="Air Conditioning" <?php echo in_array('Air Conditioning', $amenities) ? 'checked' : ''; ?>>
                  <label for="amenity_ac">Air Conditioning</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_parking" name="amenities[]" value="Parking" <?php echo in_array('Parking', $amenities) ? 'checked' : ''; ?>>
                  <label for="amenity_parking">Parking</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_laundry" name="amenities[]" value="Laundry" <?php echo in_array('Laundry', $amenities) ? 'checked' : ''; ?>>
                  <label for="amenity_laundry">Laundry</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_gym" name="amenities[]" value="Gym" <?php echo in_array('Gym', $amenities) ? 'checked' : ''; ?>>
                  <label for="amenity_gym">Gym</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_pool" name="amenities[]" value="Pool" <?php echo in_array('Pool', $amenities) ? 'checked' : ''; ?>>
                  <label for="amenity_pool">Swimming Pool</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_security" name="amenities[]" value="Security" <?php echo in_array('Security', $amenities) ? 'checked' : ''; ?>>
                  <label for="amenity_security">Security</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_pets" name="amenities[]" value="Pet Friendly" <?php echo in_array('Pet Friendly', $amenities) ? 'checked' : ''; ?>>
                  <label for="amenity_pets">Pet Friendly</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_furniture" name="amenities[]" value="Furnished" <?php echo in_array('Furnished', $amenities) ? 'checked' : ''; ?>>
                  <label for="amenity_furniture">Furnished</label>
                </div>
                
                <div class="amenity-item">
                  <input type="checkbox" id="amenity_balcony" name="amenities[]" value="Balcony" <?php echo in_array('Balcony', $amenities) ? 'checked' : ''; ?>>
                  <label for="amenity_balcony">Balcony</label>
                </div>
              </div>
            </div>
            
            <div class="form-section">
              <h2>Current Images</h2>
              
              <?php if (!empty($images)): ?>
                <p>Select images you want to delete:</p>
                <div class="current-images">
                  <?php foreach ($images as $image): ?>
                    <div class="current-image-container">
                      <?php if ($image['is_primary']): ?>
                        <div class="primary-badge">Primary</div>
                      <?php endif; ?>
                      <img src="../<?php echo htmlspecialchars($image['image_url']); ?>" alt="Property Image" class="current-image">
                      <div class="image-options">
                        <label class="delete-image">
                          <input type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>">
                          Delete
                        </label>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p>No images available for this property.</p>
              <?php endif; ?>
            </div>
            
            <div class="form-section">
              <h2>Upload New Images</h2>
              
              <div class="form-group">
                <label for="property_images">Add more images</label>
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
              <button type="submit" name="update_property" value="1" class="btn-submit">Update Property</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <script src="dashboard.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Image preview functionality
    const imageInput = document.getElementById('property_images');
    const imagePreview = document.getElementById('image-preview');
    
    if (imageInput) {
      imageInput.addEventListener('change', function() {
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
              previewItem.style.position = 'relative';
              previewItem.style.width = '100px';
              previewItem.style.height = '100px';
              previewItem.style.borderRadius = '8px';
              previewItem.style.overflow = 'hidden';
              previewItem.style.margin = '5px';
              previewItem.style.display = 'inline-block';
              
              const img = document.createElement('img');
              img.src = e.target.result;
              img.style.width = '100%';
              img.style.height = '100%';
              img.style.objectFit = 'cover';
              
              previewItem.appendChild(img);
              imagePreview.appendChild(previewItem);
            };
            
            reader.readAsDataURL(file);
          }
        }
      });
    }
    
    // Warn before deleting primary image
    const deleteCheckboxes = document.querySelectorAll('input[name="delete_images[]"]');
    deleteCheckboxes.forEach(function(checkbox) {
      checkbox.addEventListener('change', function() {
        const imageContainer = this.closest('.current-image-container');
        const isPrimary = imageContainer.querySelector('.primary-badge') !== null;
        
        if (this.checked && isPrimary) {
          alert('Warning: You are deleting the primary image. Please upload a new image to replace it or select another image as primary after updating.');
        }
      });
    });
  });
  </script>
</body>
</html>