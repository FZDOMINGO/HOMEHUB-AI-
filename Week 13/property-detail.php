<?php
// Include environment configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Initialize session
initSession();

$conn = getDbConnection();

// Check if property ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('index.php');
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

// Get user details if logged in
if ($isLoggedIn) {
    $userStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();
    
    // Ensure session has user name for navbar
    if (!isset($_SESSION['user_name']) && $user) {
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
    }
    $userStmt->close();
}

// Get current landlord ID if they are a landlord
$landlordId = null;
if ($isLoggedIn && $userType === 'landlord') {
    $landlordStmt = $conn->prepare("SELECT id FROM landlords WHERE user_id = ?");
    $landlordStmt->bind_param("i", $userId);
    $landlordStmt->execute();
    $landlordResult = $landlordStmt->get_result();
    if ($landlordResult->num_rows > 0) {
        $landlordData = $landlordResult->fetch_assoc();
        $landlordId = $landlordData['id'];
    }
}

// Track browsing history for logged-in users (excluding admin preview mode)
if ($isLoggedIn && $userId && $userId < 999000) { // Exclude preview IDs (999xxx)
    // Check if this user already viewed this property today to avoid duplicates
    $checkStmt = $conn->prepare("SELECT id FROM browsing_history WHERE user_id = ? AND property_id = ? AND DATE(viewed_at) = CURDATE()");
    $checkStmt->bind_param("ii", $userId, $propertyId);
    $checkStmt->execute();
    $existingView = $checkStmt->get_result();
    
    if ($existingView->num_rows === 0) {
        // Verify the user actually exists in the users table
        $userCheckStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $userCheckStmt->bind_param("i", $userId);
        $userCheckStmt->execute();
        $userExists = $userCheckStmt->get_result();
        
        if ($userExists->num_rows > 0) {
            // Record new view with basic device detection
            $deviceType = 'desktop'; // Default
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
                if (preg_match('/mobile|android|iphone|ipad/i', $userAgent)) {
                    $deviceType = preg_match('/ipad/i', $userAgent) ? 'tablet' : 'mobile';
                }
            }
            
            $trackStmt = $conn->prepare("INSERT INTO browsing_history (user_id, property_id, device_type, viewed_at) VALUES (?, ?, ?, NOW())");
            $trackStmt->bind_param("iis", $userId, $propertyId, $deviceType);
            $trackStmt->execute();
            $trackStmt->close();
        }
        $userCheckStmt->close();
    }
    $checkStmt->close();
}

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php 
    $activePage = 'properties';
    $navPath = '';
    include 'includes/navbar.php'; 
    ?>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="property-container">
            <!-- Property Images Slider -->
            <div class="property-gallery">
                <div class="gallery-main">
                    <?php if (!empty($images)): ?>
                        <div class="main-image-container">
                            <!-- Match Percentage Badge -->
                            <div class="match-percentage">
                                <i class="fas fa-check-circle"></i>
                                79% Match
                            </div>
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

            <!-- Success/Error Alerts -->
            <?php if(isset($_SESSION['booking_success'])): ?>
                <div class="alert alert-success" id="successAlert">
                    <?php echo $_SESSION['booking_success']; unset($_SESSION['booking_success']); ?>
                    <button class="alert-close" onclick="closeAlert('successAlert')">&times;</button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['booking_error'])): ?>
                <div class="alert alert-danger" id="errorAlert">
                    <?php echo $_SESSION['booking_error']; unset($_SESSION['booking_error']); ?>
                    <button class="alert-close" onclick="closeAlert('errorAlert')">&times;</button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['contact_success'])): ?>
                <div class="alert alert-success" id="contactSuccessAlert">
                    <?php echo $_SESSION['contact_success']; unset($_SESSION['contact_success']); ?>
                    <button class="alert-close" onclick="closeAlert('contactSuccessAlert')">&times;</button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['contact_error'])): ?>
                <div class="alert alert-danger" id="contactErrorAlert">
                    <?php echo $_SESSION['contact_error']; unset($_SESSION['contact_error']); ?>
                    <button class="alert-close" onclick="closeAlert('contactErrorAlert')">&times;</button>
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
                        <button type="button" id="reservePropertyBtn" class="btn-action btn-primary" data-property-id="<?php echo $propertyId; ?>">
                            <i class="fas fa-home"></i>
                            Reserve Property
                        </button>
                        <form method="POST" action="property-detail.php?id=<?php echo $propertyId; ?>">
                            <button type="submit" name="toggle_save" class="btn-action <?php echo $isSaved ? 'btn-saved' : ''; ?>">
                                <i class="<?php echo $isSaved ? 'fas' : 'far'; ?> fa-heart"></i>
                                <?php echo $isSaved ? 'Saved' : 'Save'; ?>
                            </button>
                        </form>
                        <a href="#contact-form" class="btn-action btn-contact">
                            <i class="fas fa-comment"></i>
                            Contact Landlord
                        </a>
                    <?php elseif (!$isLoggedIn): ?>
                        <a href="login/login.html" class="btn-action btn-primary">
                            <i class="fas fa-home"></i>
                            Login to Reserve
                        </a>
                        <a href="login/login.html" class="btn-action">
                            <i class="far fa-heart"></i>
                            Login to Save
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
        // Alert management
        function closeAlert(alertId) {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.classList.add('alert-hide');
                setTimeout(() => {
                    alert.remove();
                }, 400);
            }
        }
        
        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                if (alert && !alert.classList.contains('alert-hide')) {
                    alert.classList.add('alert-hide');
                    setTimeout(() => {
                        alert.remove();
                    }, 400);
                }
            }, 5000);
        });
        
        // Make closeAlert function globally available
        window.closeAlert = closeAlert;
        
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
        
        // Reserve Property functionality
        const reservePropertyBtn = document.getElementById('reservePropertyBtn');
        
        if (reservePropertyBtn) {
            reservePropertyBtn.addEventListener('click', function() {
                const propertyId = this.getAttribute('data-property-id');
                const reserveModal = document.getElementById('reserveModal');
                document.getElementById('reserve_property_id').value = propertyId;
                reserveModal.classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        }
        
        // Close modals
        document.querySelectorAll('.close-modal').forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                modal.classList.remove('show');
                document.body.style.overflow = 'auto';
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });
        
        // Handle reservation form submission
        const reservationForm = document.getElementById('reservationForm');
        if (reservationForm) {
            reservationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Submitting...';
                submitBtn.disabled = true;
                
                fetch('process-reservation-clean.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Log the raw response for debugging
                    console.log('Response status:', response.status);
                    return response.text().then(text => {
                        console.log('Raw response:', text);
                        console.log('Response length:', text.length);
                        
                        // Show the full response in alert for debugging
                        if (!text || text.trim() === '') {
                            throw new Error('Server returned empty response');
                        }
                        
                        try {
                            return JSON.parse(text);
                        } catch(e) {
                            console.error('JSON parse error:', e);
                            console.error('Full response text:', text);
                            alert('Server response (check console for full text): ' + text.substring(0, 500));
                            throw new Error('Server returned invalid JSON. Check console for details.');
                        }
                    });
                })
                .then(data => {
                    console.log('Parsed data:', data);
                    if (data.success) {
                        alert('Reservation request submitted successfully! The landlord will review your request.');
                        this.reset();
                        document.getElementById('reserveModal').classList.remove('show');
                        document.body.style.overflow = 'auto';
                    } else {
                        alert('Error: ' + data.message);
                        console.error('Server error:', data.message);
                    }
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred: ' + error.message);
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
            });
        }
    });
    </script>
    
    <!-- Reserve Property Modal -->
    <div id="reserveModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Reserve Property</h2>
            <form id="reservationForm" method="POST">
                <input type="hidden" id="reserve_property_id" name="property_id" value="<?php echo $propertyId; ?>">
                
                <div class="reservation-info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Reservation Process:</strong>
                        <p>Submit a reservation fee to hold this property. The landlord will review and approve your request. You'll have a specified period to complete requirements and sign the lease.</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="move_in_date">Preferred Move-in Date</label>
                    <input type="date" id="move_in_date" name="move_in_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="lease_duration">Lease Duration (months)</label>
                    <input type="number" id="lease_duration" name="lease_duration" min="1" max="60" value="12" required>
                </div>
                
                <div class="form-group">
                    <label for="reservation_fee">Reservation Fee Amount (₱)</label>
                    <input type="number" id="reservation_fee" name="reservation_fee" min="1000" step="100" placeholder="e.g., 5000" required>
                    <small class="form-hint">Typical reservation fee: 1-2 months' rent or as agreed with landlord</small>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Select payment method</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                        <option value="cash">Cash (In-person)</option>
                        <option value="check">Check</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="employment_status">Employment Status</label>
                    <select id="employment_status" name="employment_status" required>
                        <option value="">Select status</option>
                        <option value="employed">Employed</option>
                        <option value="self_employed">Self-Employed</option>
                        <option value="student">Student</option>
                        <option value="retired">Retired</option>
                        <option value="unemployed">Unemployed</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="monthly_income">Monthly Income (₱)</label>
                    <input type="number" id="monthly_income" name="monthly_income" min="0" step="1000" placeholder="e.g., 30000">
                    <small class="form-hint">This helps landlords assess your application</small>
                </div>
                
                <div class="form-group">
                    <label for="requirements">Additional Notes / Special Requests</label>
                    <textarea id="requirements" name="requirements" rows="3" placeholder="E.g., Need parking space, pet-friendly, etc..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="agree_terms" name="agree_terms" required>
                        <span>I understand that the reservation fee may be non-refundable if I cancel or fail to complete the requirements within the agreed period.</span>
                    </label>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-shield-alt"></i> Submit Reservation Request
                </button>
            </form>
        </div>
    </div>
</body>
</html>