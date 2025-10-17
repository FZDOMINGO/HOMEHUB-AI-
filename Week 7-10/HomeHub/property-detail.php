<?php
// Start session
session_start();

// Include database connection
require_once 'config/db_connect.php';
$conn = getDbConnection();

// Check if property ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$propertyId = intval($_GET['id']);
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
$isLoggedIn = isset($_SESSION['user_id']);
$isSaved = false;

// Get property details
$stmt = $conn->prepare("SELECT p.*, 
                        l.id AS landlord_id,
                        u.first_name AS landlord_first_name, 
                        u.last_name AS landlord_last_name,
                        u.email AS landlord_email,
                        u.phone AS landlord_phone,
                        u.profile_image AS landlord_image
                        FROM properties p
                        JOIN landlords l ON p.landlord_id = l.id
                        JOIN users u ON l.user_id = u.id
                        WHERE p.id = ?");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Property not found
    header("Location: index.php");
    exit;
}

$property = $result->fetch_assoc();

// Get property images
$stmt = $conn->prepare("SELECT id, image_url, is_primary FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$imagesResult = $stmt->get_result();
$images = [];
while ($image = $imagesResult->fetch_assoc()) {
    $images[] = $image;
}

// Get property amenities
$stmt = $conn->prepare("SELECT amenity_name FROM property_amenities WHERE property_id = ?");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$amenitiesResult = $stmt->get_result();
$amenities = [];
while ($amenity = $amenitiesResult->fetch_assoc()) {
    $amenities[] = $amenity['amenity_name'];
}

// Check if property is saved (if user is a tenant)
if ($isLoggedIn && $userType == 'tenant') {
    // Get tenant ID
    $stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $tenantResult = $stmt->get_result();
    if ($tenantResult->num_rows > 0) {
        $tenant = $tenantResult->fetch_assoc();
        $tenantId = $tenant['id'];
        
        // Check if property is saved by this tenant
        $stmt = $conn->prepare("SELECT id FROM saved_properties WHERE tenant_id = ? AND property_id = ?");
        $stmt->bind_param("ii", $tenantId, $propertyId);
        $stmt->execute();
        $saveResult = $stmt->get_result();
        $isSaved = ($saveResult->num_rows > 0);
    }
}

// Handle property save/unsave action
if ($isLoggedIn && $userType == 'tenant' && isset($_POST['toggle_save'])) {
    // Get tenant ID
    $stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $tenantResult = $stmt->get_result();
    $tenant = $tenantResult->fetch_assoc();
    $tenantId = $tenant['id'];
    
    if ($isSaved) {
        // Unsave the property
        $stmt = $conn->prepare("DELETE FROM saved_properties WHERE tenant_id = ? AND property_id = ?");
        $stmt->bind_param("ii", $tenantId, $propertyId);
        $stmt->execute();
        $isSaved = false;
    } else {
        // Save the property
        $stmt = $conn->prepare("INSERT INTO saved_properties (tenant_id, property_id, saved_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $tenantId, $propertyId);
        $stmt->execute();
        $isSaved = true;
    }
}

// Get analytics data
$viewsCount = 0;
$savedCount = 0;
$inquiriesCount = 0;

// Total views
$stmt = $conn->prepare("SELECT SUM(views) as total_views FROM property_views WHERE property_id = ?");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$viewsResult = $stmt->get_result();
$viewsRow = $viewsResult->fetch_assoc();
$viewsCount = $viewsRow['total_views'] ?? 0;

// Total saves
$stmt = $conn->prepare("SELECT COUNT(*) as total_saves FROM saved_properties WHERE property_id = ?");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$savedResult = $stmt->get_result();
$savedRow = $savedResult->fetch_assoc();
$savedCount = $savedRow['total_saves'] ?? 0;

// Total inquiries/booking requests
$stmt = $conn->prepare("SELECT COUNT(*) as total_inquiries FROM booking_requests WHERE property_id = ?");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$inquiriesResult = $stmt->get_result();
$inquiriesRow = $inquiriesResult->fetch_assoc();
$inquiriesCount = $inquiriesRow['total_inquiries'] ?? 0;

// Record property view if user is logged in
if ($isLoggedIn) {
    $today = date('Y-m-d');
    
    // Check if already viewed today
    $stmt = $conn->prepare("SELECT id, views FROM property_views 
                          WHERE property_id = ? AND view_date = ?");
    $stmt->bind_param("is", $propertyId, $today);
    $stmt->execute();
    $viewCheckResult = $stmt->get_result();
    
    if ($viewCheckResult->num_rows > 0) {
        // Update existing view count
        $viewRecord = $viewCheckResult->fetch_assoc();
        $views = $viewRecord['views'] + 1;
        $viewId = $viewRecord['id'];
        
        $stmt = $conn->prepare("UPDATE property_views SET views = ? WHERE id = ?");
        $stmt->bind_param("ii", $views, $viewId);
        $stmt->execute();
    } else {
        // Insert new view record
        $views = 1;
        $stmt = $conn->prepare("INSERT INTO property_views (property_id, views, view_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $propertyId, $views, $today);
        $stmt->execute();
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> - HomeHub AI</title>
    <link rel="stylesheet" href="assets/css/property-detail.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation Header -->
    <nav class="navbar">
        <div class="nav-container">
            <!-- Logo -->
            <div class="nav-logo">
                <img src="assets/homehublogo.jpg" alt="HomeHub AI Logo" class="logo-img">
            </div>
            
            <!-- Desktop Navigation -->
            <div class="nav-center">
                <a href="index.php" class="nav-link">Home</a>
                <a href="properties.php" class="nav-link active">Properties</a>
                <?php if($isLoggedIn): ?>
                    <a href="<?php echo $userType; ?>/dashboard.php" class="nav-link">Dashboard</a>
                    <a href="<?php echo $userType; ?>/bookings.php" class="nav-link">Bookings</a>
                    <a href="<?php echo $userType; ?>/history.php" class="nav-link">History</a>
                    <a href="<?php echo $userType; ?>/ai-features.php" class="nav-link">AI Features</a>
                <?php endif; ?>
            </div>
            
            <!-- Desktop Buttons -->
            <div class="nav-right">
                <?php if($isLoggedIn): ?>
                    <span class="user-greeting">Welcome, Mayrielle</span>
                    <a href="#" id="logoutBtn" class="btn-login">Logout</a>
                <?php else: ?>
                    <a href="login/login.html" class="btn-login">Login</a>
                    <a href="login/signup.html" class="btn-signup">Sign Up</a>
                <?php endif; ?>
            </div>
            
            <!-- Mobile Navigation Buttons -->
            <div class="nav-buttons-mobile">
                <?php if($isLoggedIn): ?>
                    <a href="#" id="logoutBtnMobile" class="btn-login-mobile">Logout</a>
                <?php else: ?>
                    <a href="login/login.html" class="btn-login-mobile">Login</a>
                <?php endif; ?>
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
            <a href="index.php" class="nav-link-mobile">Home</a>
            <a href="properties.php" class="nav-link-mobile active">Properties</a>
            <?php if($isLoggedIn): ?>
                <a href="<?php echo $userType; ?>/dashboard.php" class="nav-link-mobile">Dashboard</a>
                <a href="<?php echo $userType; ?>/bookings.php" class="nav-link-mobile">Bookings</a>
                <a href="<?php echo $userType; ?>/history.php" class="nav-link-mobile">History</a>
                <a href="<?php echo $userType; ?>/ai-features.php" class="nav-link-mobile">AI Features</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="property-container">
            <!-- Property Images Slider -->
            <div class="property-gallery">
                <div class="gallery-main">
                    <?php if (!empty($images)): ?>
                        <div class="main-image-container">
                            <img id="mainImage" src="<?php echo !empty($images) ? $images[0]['image_url'] : 'assets/images/no-image.jpg'; ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" class="main-image">
                        </div>
                    <?php else: ?>
                        <div class="main-image-container">
                            <img src="assets/images/no-image.jpg" alt="No image available" class="main-image">
                        </div>
                    <?php endif; ?>
                    
                    <?php if (count($images) > 1): ?>
                        <div class="gallery-nav">
                            <button class="gallery-btn prev" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
                            <button class="gallery-btn next" id="nextBtn"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbnails">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                                <img src="<?php echo $image['image_url']; ?>" alt="Thumbnail">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add this after the header section -->
<?php if(isset($_SESSION['booking_success'])): ?>
    <div class="alert alert-success">
        <?php echo $_SESSION['booking_success']; unset($_SESSION['booking_success']); ?>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['booking_error'])): ?>
    <div class="alert alert-danger">
        <?php echo $_SESSION['booking_error']; unset($_SESSION['booking_error']); ?>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['contact_success'])): ?>
    <div class="alert alert-success">
        <?php echo $_SESSION['contact_success']; unset($_SESSION['contact_success']); ?>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['contact_error'])): ?>
    <div class="alert alert-danger">
        <?php echo $_SESSION['contact_error']; unset($_SESSION['contact_error']); ?>
    </div>
<?php endif; ?>
            
            <!-- Property Information -->
            <div class="property-details-container">
                <div class="property-header">
                    <div class="property-title-section">
                        <h1 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h1>
                        <p class="property-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($property['address'] . ', ' . $property['city'] . ', ' . $property['state']); ?>
                        </p>
                    </div>
                    
                    <div class="property-price">
                        <span class="price">₱<?php echo number_format($property['rent_amount'], 2); ?></span>
                        <span class="price-period">per month</span>
                    </div>
                </div>
                
                <div class="property-actions">
                    <?php if ($isLoggedIn && $userType == 'tenant'): ?>
                        <form method="POST" action="property-detail.php?id=<?php echo $propertyId; ?>">
                            <button type="submit" name="toggle_save" class="btn-action <?php echo $isSaved ? 'btn-saved' : ''; ?>">
                                <i class="<?php echo $isSaved ? 'fas' : 'far'; ?> fa-heart"></i>
                                <?php echo $isSaved ? 'Saved' : 'Save'; ?>
                            </button>
                        </form>
                        <a href="#booking-form" class="btn-action btn-request">
                            <i class="fas fa-calendar-check"></i>
                            Request Viewing
                        </a>
                        <a href="#contact-form" class="btn-action btn-contact">
                            <i class="fas fa-comment"></i>
                            Contact Landlord
                        </a>
                    <?php elseif (!$isLoggedIn): ?>
                        <a href="login/login.html" class="btn-action">
                            <i class="far fa-heart"></i>
                            Login to Save
                        </a>
                        <a href="login/login.html" class="btn-action">
                            <i class="fas fa-calendar-check"></i>
                            Login to Book
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="property-features">
                    <div class="feature">
                        <i class="fas fa-bed"></i>
                        <span><?php echo $property['bedrooms']; ?> Bedroom<?php echo $property['bedrooms'] != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-bath"></i>
                        <span><?php echo $property['bathrooms']; ?> Bathroom<?php echo $property['bathrooms'] != 1 ? 's' : ''; ?></span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-ruler-combined"></i>
                        <span><?php echo $property['square_feet']; ?> sq ft</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-home"></i>
                        <span><?php echo ucfirst($property['property_type']); ?></span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-calendar"></i>
                        <span>Available: <?php echo date('M j, Y', strtotime($property['availability_date'])); ?></span>
                    </div>
                </div>
                
                <div class="property-description">
                    <h2>Description</h2>
                    <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                </div>
                
                <div class="property-amenities">
                    <h2>Amenities</h2>
                    <?php if (!empty($amenities)): ?>
                        <div class="amenities-list">
                            <?php foreach ($amenities as $amenity): ?>
                                <div class="amenity">
                                    <i class="fas fa-check-circle"></i>
                                    <span><?php echo htmlspecialchars($amenity); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No amenities listed for this property.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Property Analytics Section (Only visible to landlords) -->
                <?php if ($isLoggedIn && $userType == 'landlord' && $property['landlord_id'] == $landlordId): ?>
                    <div class="property-analytics">
                        <h2>Property Analytics</h2>
                        <div class="analytics-grid">
                            <div class="analytics-card">
                                <div class="analytics-icon">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <div class="analytics-value"><?php echo $viewsCount; ?></div>
                                <div class="analytics-label">Total Views</div>
                            </div>
                            
                            <div class="analytics-card">
                                <div class="analytics-icon">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <div class="analytics-value"><?php echo $savedCount; ?></div>
                                <div class="analytics-label">Saved by Tenants</div>
                            </div>
                            
                            <div class="analytics-card">
                                <div class="analytics-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="analytics-value"><?php echo $inquiriesCount; ?></div>
                                <div class="analytics-label">Viewing Requests</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Landlord Information -->
                <div class="landlord-info">
                    <h2>Property Manager</h2>
                    <div class="landlord-details">
                        <div class="landlord-image">
                            <?php if (!empty($property['landlord_image'])): ?>
                                <img src="<?php echo $property['landlord_image']; ?>" alt="Landlord">
                            <?php else: ?>
                                <div class="landlord-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="landlord-contact">
                            <div class="landlord-name">
                                <?php echo htmlspecialchars($property['landlord_first_name'] . ' ' . $property['landlord_last_name']); ?>
                            </div>
                            <?php if ($isLoggedIn && $userType == 'tenant'): ?>
                                <div class="landlord-email">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($property['landlord_email']); ?></span>
                                </div>
                                <div class="landlord-phone">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($property['landlord_phone']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Booking Request Form (Only visible to logged-in tenants) -->
                <?php if ($isLoggedIn && $userType == 'tenant'): ?>
<div class="booking-form" id="booking-form">
    <h2>Request Property Viewing</h2>
    <form action="process-booking.php" method="POST">
        <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>">
        
        <div class="form-group">
            <label for="visit_date">Preferred Date*</label>
            <input type="date" id="visit_date" name="visit_date" min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="visit_time">Preferred Time*</label>
            <select id="visit_time" name="visit_time" required>
                <option value="">Select Time</option>
                <option value="09:00">9:00 AM</option>
                <option value="10:00">10:00 AM</option>
                <option value="11:00">11:00 AM</option>
                <option value="13:00">1:00 PM</option>
                <option value="14:00">2:00 PM</option>
                <option value="15:00">3:00 PM</option>
                <option value="16:00">4:00 PM</option>
                <option value="17:00">5:00 PM</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="message">Message to Landlord</label>
            <textarea id="message" name="message" rows="4" placeholder="Any questions or comments for the property manager?"></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-submit">Request Viewing</button>
        </div>
    </form>
</div>
                    
                    <!-- Contact Form -->
                    <div class="contact-form" id="contact-form">
                        <h2>Contact Landlord</h2>
                        <form action="process-contact.php" method="POST">
                            <input type="hidden" name="property_id" value="<?php echo $propertyId; ?>">
                            <input type="hidden" name="landlord_id" value="<?php echo $property['landlord_id']; ?>">
                            
                            <div class="form-group">
                                <label for="subject">Subject*</label>
                                <input type="text" id="subject" name="subject" required placeholder="e.g. Question about <?php echo htmlspecialchars($property['title']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_message">Message*</label>
                                <textarea id="contact_message" name="contact_message" rows="5" required placeholder="Type your message here..."></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-submit">Send Message</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Image gallery functionality
        const mainImage = document.getElementById('mainImage');
        const thumbnails = document.querySelectorAll('.thumbnail');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        let currentIndex = 0;
        const images = [
            <?php foreach ($images as $image): ?>
                "<?php echo $image['image_url']; ?>",
            <?php endforeach; ?>
        ];
        
        // Set the active thumbnail and update main image
        function setActiveImage(index) {
            if (index < 0) index = images.length - 1;
            if (index >= images.length) index = 0;
            
            currentIndex = index;
            mainImage.src = images[currentIndex];
            
            // Update active class on thumbnails
            thumbnails.forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnails[currentIndex].classList.add('active');
        }
        
        // Event listeners for navigation buttons
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', function() {
                setActiveImage(currentIndex - 1);
            });
            
            nextBtn.addEventListener('click', function() {
                setActiveImage(currentIndex + 1);
            });
        }
        
        // Event listeners for thumbnails
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                setActiveImage(index);
            });
        });
        
        // Mobile menu toggle
        const hamburger = document.getElementById('hamburger');
        const mobileMenu = document.getElementById('nav-menu-mobile');
        
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        });
        
        // Handle logout
        const logoutBtn = document.getElementById('logoutBtn');
        const logoutBtnMobile = document.getElementById('logoutBtnMobile');
        
        const handleLogout = function(e) {
            e.preventDefault();
            
            fetch('api/logout.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = data.redirect;
                    } else {
                        alert('Logout failed. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred during logout. Please try again.');
                });
        };
        
        if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);
        if (logoutBtnMobile) logoutBtnMobile.addEventListener('click', handleLogout);
    });
    </script>
</body>
</html>