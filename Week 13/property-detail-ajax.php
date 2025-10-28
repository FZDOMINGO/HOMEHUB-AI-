<?php
// Include environment configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Initialize session
initSession();

// Check if property ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='error'>Invalid property request</div>";
    exit;
}

$propertyId = intval($_GET['id']);
$isLoggedIn = isset($_SESSION['user_id']);
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
$isSaved = false;

$conn = getDbConnection();

// Check if property is saved (if user is a tenant)
if ($isLoggedIn && $userType == 'tenant') {
    $userId = $_SESSION['user_id'];
    
    // Get tenant ID
    $stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $tenantResult = $stmt->get_result();
    
    if ($tenantResult->num_rows > 0) {
        $tenant = $tenantResult->fetch_assoc();
        $tenantId = $tenant['id'];
        
        // Check if property is saved
        $stmt = $conn->prepare("SELECT id FROM saved_properties WHERE tenant_id = ? AND property_id = ?");
        $stmt->bind_param("ii", $tenantId, $propertyId);
        $stmt->execute();
        $saveResult = $stmt->get_result();
        $isSaved = ($saveResult->num_rows > 0);
    }
}

// Get property details
$query = "SELECT p.*, 
         (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) AS primary_image,
         l.id AS landlord_id,
         CONCAT(u.first_name, ' ', u.last_name) AS owner_name,
         0 AS review_count,
         5.0 AS avg_rating
         FROM properties p
         JOIN landlords l ON p.landlord_id = l.id
         JOIN users u ON l.user_id = u.id
         WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='error'>Property not found</div>";
    exit;
}

$property = $result->fetch_assoc();

// Get property amenities
$stmt = $conn->prepare("SELECT amenity_name FROM property_amenities WHERE property_id = ?");
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$amenitiesResult = $stmt->get_result();
$amenities = [];

while ($amenity = $amenitiesResult->fetch_assoc()) {
    $amenities[] = $amenity['amenity_name'];
}

$conn->close();

// Calculate AI match percentage (mock data for now)
$matchPercentage = rand(70, 99);
?>

<!-- Property Detail View -->
<div class="property-detail-header">
    <img class="property-detail-image" src="<?php echo !empty($property['primary_image']) ? $property['primary_image'] : 'assets/images/no-image.jpg'; ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
    
    <?php if ($isLoggedIn): ?>
        <button class="bookmark-btn <?php echo $isSaved ? 'saved' : ''; ?>" data-logged-in>
            <i class="<?php echo $isSaved ? 'fas' : 'far'; ?> fa-heart"></i>
        </button>
    <?php endif; ?>
    
    <div class="property-detail-overlay">
        <h2 class="property-detail-title"><?php echo htmlspecialchars($property['title']); ?></h2>
        <p class="property-detail-location">
            <i class="fas fa-map-marker-alt"></i>
            <?php echo htmlspecialchars($property['city'] . ', ' . $property['state']); ?>
        </p>
        <div class="property-detail-price">₱<?php echo number_format($property['rent_amount']); ?>/month</div>
    </div>
</div>

<div class="property-detail-body">
    <?php if ($isLoggedIn && $userType == 'tenant'): ?>
        <div class="property-detail-match">
            <i class="fas fa-percentage"></i> <?php echo $matchPercentage; ?>% Match
        </div>
    <?php endif; ?>
    
    <div class="property-detail-features">
        <h3 class="features-title">
            <i class="fas fa-home"></i> Property Features
        </h3>
        
        <div class="features-grid">
            <div class="feature-item">
                <i class="fas fa-bed"></i>
                <span><?php echo $property['bedrooms']; ?> Bedrooms</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-bath"></i>
                <span><?php echo $property['bathrooms']; ?> Bathrooms</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-ruler-combined"></i>
                <span><?php echo $property['square_feet']; ?> sqm</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-building"></i>
                <span><?php echo ucfirst($property['property_type']); ?></span>
            </div>
            
            <?php foreach($amenities as $amenity): ?>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($amenity); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="property-description">
        <h3 class="description-title">
            <i class="fas fa-align-left"></i> Description
        </h3>
        <p class="description-text"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
    </div>
    
    <div class="property-owner-section">
        <div class="owner-image">
            <i class="fas fa-user"></i>
        </div>
        <div class="owner-details">
            <h4 class="owner-name"><?php echo htmlspecialchars($property['owner_name']); ?></h4>
            <div class="owner-rating">
                <?php
                $rating = round($property['avg_rating'] * 2) / 2;
                for($i = 1; $i <= 5; $i++):
                    if($i <= $rating):
                        echo '<i class="fas fa-star"></i>';
                    elseif($i - 0.5 == $rating):
                        echo '<i class="fas fa-star-half-alt"></i>';
                    else:
                        echo '<i class="far fa-star"></i>';
                    endif;
                endfor;
                ?>
                <span><?php echo number_format($property['avg_rating'], 1); ?> (<?php echo $property['review_count']; ?> reviews)</span>
            </div>
        </div>
        
        <?php if ($isLoggedIn && $userType == 'tenant'): ?>
            <a href="property-detail.php?id=<?php echo $propertyId; ?>#contact-form" class="btn-contact-owner">Contact</a>
        <?php endif; ?>
    </div>
    
    <?php if ($isLoggedIn && $userType == 'tenant'): ?>
        <div class="property-actions">
            <a href="property-detail.php?id=<?php echo $propertyId; ?>" class="btn-view-full">View Full Details</a>
            <a href="property-detail.php?id=<?php echo $propertyId; ?>#booking-form" class="btn-request-viewing">Request Viewing</a>
        </div>
    <?php elseif (!$isLoggedIn): ?>
        <div class="login-prompt">
            <p>Log in to request a viewing or contact the landlord</p>
            <a href="login/login.html" class="btn-login">Login Now</a>
        </div>
    <?php endif; ?>
</div>