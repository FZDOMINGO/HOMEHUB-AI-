<?php
// Start session
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;

// Include database connection
require_once 'config/db_connect.php';
$conn = getDbConnection();

// Fetch all available properties
$query = "SELECT p.*, 
         (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) AS primary_image,
         l.id AS landlord_id,
         CONCAT(u.first_name, ' ', u.last_name) AS owner_name,
         0 AS review_count,
         5.0 AS avg_rating
         FROM properties p
         JOIN landlords l ON p.landlord_id = l.id
         JOIN users u ON l.user_id = u.id
         WHERE p.status = 'available'
         ORDER BY p.created_at DESC";

$result = $conn->query($query);
$properties = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $properties[] = $row;
    }
}

// Get all distinct cities for location filter
$cityQuery = "SELECT DISTINCT city FROM properties WHERE status = 'available' ORDER BY city";
$cityResult = $conn->query($cityQuery);
$cities = [];

if ($cityResult->num_rows > 0) {
    while($row = $cityResult->fetch_assoc()) {
        $cities[] = $row['city'];
    }
}

// Get all property types
$typeQuery = "SELECT DISTINCT property_type FROM properties WHERE status = 'available' ORDER BY property_type";
$typeResult = $conn->query($typeQuery);
$propertyTypes = [];

if ($typeResult->num_rows > 0) {
    while($row = $typeResult->fetch_assoc()) {
        $propertyTypes[] = $row['property_type'];
    }
}

// Get common amenities
$amenitiesQuery = "SELECT DISTINCT amenity_name FROM property_amenities 
                  GROUP BY amenity_name 
                  ORDER BY COUNT(*) DESC LIMIT 10";
$amenitiesResult = $conn->query($amenitiesQuery);
$amenities = [];

if ($amenitiesResult->num_rows > 0) {
    while($row = $amenitiesResult->fetch_assoc()) {
        $amenities[] = $row['amenity_name'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties - HomeHub AI</title>
    <link rel="stylesheet" href="assets/css/properties.css">
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
    <a href="guest/index.html" class="nav-link">Home</a>
    <a href="properties.php" class="nav-link active">Properties</a>
    
   <?php if($isLoggedIn): ?>
    <a href="<?php echo $userType; ?>/dashboard.php" class="nav-link">Dashboard</a>
    <a href= "bookings.php" class="nav-link">Bookings</a>
    <a href="<?php echo $userType; ?>/history.html" class="nav-link">History</a>
    <a href="<?php echo $userType; ?>/ai-features.html" class="nav-link">AI Features</a>
<?php else: ?>
    <a href="login/login.html" class="nav-link">Dashboard</a>
    <a href="bookings.php" class="nav-link">Bookings</a>
    <a href="login/login.html" class="nav-link">History</a>
    <a href="guest/ai-features.html" class="nav-link">AI Features</a>
<?php endif; ?>
            
            <!-- Desktop Buttons -->
            <div class="nav-right">
                <?php if($isLoggedIn): ?>
                    <span class="user-greeting">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
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
        <div class="properties-header">
            <h1>Properties</h1>
            <div class="header-content">
                <p class="header-text">Discover your perfect rental home with AI-powered recommendations</p>
                
                <div class="tab-buttons">
                    <button class="tab-btn active" data-tab="search">Search Filters</button>
                    <button class="tab-btn" data-tab="browse">Browse Listings</button>
                    <button class="tab-btn" data-tab="smart">Smart Recommendations</button>
                </div>
            </div>
        </div>
        
        <!-- Search Filters Section -->
        <div class="search-section tab-content active" id="search-tab">
            <h2>Search Properties</h2>
            
            <form id="property-search-form" class="search-form">
                <div class="search-row">
                    <div class="search-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" placeholder="Enter city or area" list="location-list">
                        <datalist id="location-list">
                            <?php foreach($cities as $city): ?>
                                <option value="<?php echo htmlspecialchars($city); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="search-group">
                        <label for="price-range">Price Range</label>
                        <select id="price-range" name="price_range">
                            <option value="">Any Price</option>
                            <option value="0-10000">Under ₱10,000</option>
                            <option value="10000-20000">₱10,000 - ₱20,000</option>
                            <option value="20000-35000">₱20,000 - ₱35,000</option>
                            <option value="35000-50000">₱35,000 - ₱50,000</option>
                            <option value="50000-999999">Over ₱50,000</option>
                        </select>
                    </div>
                    
                    <div class="search-group">
                        <label for="property-type">Property Type</label>
                        <select id="property-type" name="property_type">
                            <option value="">Any Type</option>
                            <?php foreach($propertyTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>">
                                    <?php echo ucfirst(htmlspecialchars($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="search-row">
                    <div class="search-group">
                        <label for="bedrooms">Bedroom</label>
                        <select id="bedrooms" name="bedrooms">
                            <option value="">Any</option>
                            <option value="studio">Studio</option>
                            <option value="1">1 Bedroom</option>
                            <option value="2">2 Bedrooms</option>
                            <option value="3">3 Bedrooms</option>
                            <option value="4+">4+ Bedrooms</option>
                        </select>
                    </div>
                    
                    <div class="search-group">
                        <label for="amenities">Amenities</label>
                        <select id="amenities" name="amenities">
                            <option value="">Any Amenities</option>
                            <?php foreach($amenities as $amenity): ?>
                                <option value="<?php echo htmlspecialchars($amenity); ?>">
                                    <?php echo htmlspecialchars($amenity); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="search-btn-container">
                    <button type="submit" class="search-btn">Search Properties</button>
                </div>
            </form>
        </div>
        
        <!-- Browse Listings Section -->
        <div class="tab-content" id="browse-tab">
            <!-- Content will be loaded dynamically -->
        </div>
        
        <!-- Smart Recommendations Section -->
        <div class="tab-content" id="smart-tab">
            <!-- Content will be loaded dynamically if user is logged in -->
            <?php if(!$isLoggedIn): ?>
                <div class="login-prompt">
                    <h3>Get personalized property recommendations</h3>
                    <p>Login to access AI-powered property recommendations tailored to your preferences.</p>
                    <a href="login/login.html" class="btn-login">Login Now</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Property Listings Section -->
        <div class="properties-container">
            <h2>Available Properties</h2>
            
            <div class="properties-grid" id="properties-grid">
                <?php if(count($properties) > 0): ?>
                    <?php foreach($properties as $property): ?>
                        <div class="property-card" data-property-id="<?php echo $property['id']; ?>">
                            <div class="property-image">
                                <?php if(!empty($property['primary_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($property['primary_image']); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                                <?php else: ?>
                                    <img src="assets/images/no-image.jpg" alt="No image available">
                                <?php endif; ?>
                                <div class="property-type"><?php echo ucfirst(htmlspecialchars($property['property_type'])); ?></div>
                            </div>
                            
                            <div class="property-details">
                                <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                                <p class="property-owner">by <?php echo htmlspecialchars($property['owner_name']); ?></p>
                                
                                <div class="property-rating">
                                    <?php
                                    $rating = round($property['avg_rating'] * 2) / 2; // Round to nearest 0.5
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
                                
                                <div class="property-meta">
                                    <div class="property-size">
                                        <?php echo $property['square_feet']; ?> sqm (<?php echo date('Y', strtotime($property['created_at'])); ?>)
                                    </div>
                                    <div class="property-price">
                                        ₱<?php echo number_format($property['rent_amount']); ?>/mo
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-properties">
                        <p>No properties available at the moment. Please check back later.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Property Detail Modal -->
    <div id="property-modal" class="property-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="property-detail-content" class="property-detail-content">
                <!-- Property detail will be loaded here dynamically -->
            </div>
        </div>
    </div>
    
    <script src="assets/js/properties.js"></script>
</body>
</html>