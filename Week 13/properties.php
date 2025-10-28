<?php
// Include environment configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Initialize session
initSession();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;

$conn = getDbConnection();

// Get search parameters
$searchLocation = isset($_GET['location']) ? trim($_GET['location']) : '';
$priceRange = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$propertyType = isset($_GET['property_type']) ? $_GET['property_type'] : '';
$bedrooms = isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '';
$bathrooms = isset($_GET['bathrooms']) ? $_GET['bathrooms'] : '';

// Build the query with filters
$query = "SELECT p.*, 
         (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) AS primary_image,
         l.id AS landlord_id,
         CONCAT(u.first_name, ' ', u.last_name) AS owner_name,
         0 AS review_count,
         5.0 AS avg_rating
         FROM properties p
         JOIN landlords l ON p.landlord_id = l.id
         JOIN users u ON l.user_id = u.id
         WHERE p.status = 'available'";

$params = [];
$types = "";

// Add location filter
if (!empty($searchLocation)) {
    $query .= " AND (p.city LIKE ? OR p.address LIKE ?)";
    $searchTerm = "%$searchLocation%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Add price range filter
if (!empty($priceRange)) {
    $priceRangeParts = explode('-', $priceRange);
    if (count($priceRangeParts) == 2) {
        $minPrice = (int)$priceRangeParts[0];
        $maxPrice = (int)$priceRangeParts[1];
        $query .= " AND p.rent_amount >= ? AND p.rent_amount <= ?";
        $params[] = $minPrice;
        $params[] = $maxPrice;
        $types .= "ii";
    }
}

// Add property type filter
if (!empty($propertyType)) {
    $query .= " AND p.property_type = ?";
    $params[] = $propertyType;
    $types .= "s";
}

// Add bedrooms filter
if (!empty($bedrooms)) {
    if ($bedrooms === '4+') {
        $query .= " AND p.bedrooms >= 4";
    } elseif ($bedrooms === 'studio') {
        $query .= " AND (p.bedrooms = 0 OR p.property_type LIKE '%studio%')";
    } else {
        $query .= " AND p.bedrooms = ?";
        $params[] = (int)$bedrooms;
        $types .= "i";
    }
}

// Add bathrooms filter
if (!empty($bathrooms)) {
    if ($bathrooms === '3+') {
        $query .= " AND p.bathrooms >= 3";
    } else {
        $query .= " AND p.bathrooms = ?";
        $params[] = (int)$bathrooms;
        $types .= "i";
    }
}

$query .= " ORDER BY p.created_at DESC";

// Execute the query
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
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
    <?php 
    // Set active page for navigation
    $activePage = 'properties';
    $navPath = ''; // Root level
    include 'includes/navbar.php'; 
    
    // Include admin preview banner if in preview mode
    include 'includes/admin-preview-banner.php';
    ?>

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
                        <input type="text" id="location" name="location" placeholder="Enter city or area" 
                               value="<?php echo htmlspecialchars($searchLocation); ?>" list="location-list">
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
                            <option value="0-10000" <?php echo $priceRange === '0-10000' ? 'selected' : ''; ?>>Under ₱10,000</option>
                            <option value="10000-20000" <?php echo $priceRange === '10000-20000' ? 'selected' : ''; ?>>₱10,000 - ₱20,000</option>
                            <option value="20000-35000" <?php echo $priceRange === '20000-35000' ? 'selected' : ''; ?>>₱20,000 - ₱35,000</option>
                            <option value="35000-50000" <?php echo $priceRange === '35000-50000' ? 'selected' : ''; ?>>₱35,000 - ₱50,000</option>
                            <option value="50000-999999" <?php echo $priceRange === '50000-999999' ? 'selected' : ''; ?>>Over ₱50,000</option>
                        </select>
                    </div>
                    
                    <div class="search-group">
                        <label for="property-type">Property Type</label>
                        <select id="property-type" name="property_type">
                            <option value="">Any Type</option>
                            <?php foreach($propertyTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" 
                                        <?php echo $propertyType === $type ? 'selected' : ''; ?>>
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
                            <option value="studio" <?php echo $bedrooms === 'studio' ? 'selected' : ''; ?>>Studio</option>
                            <option value="1" <?php echo $bedrooms === '1' ? 'selected' : ''; ?>>1 Bedroom</option>
                            <option value="2" <?php echo $bedrooms === '2' ? 'selected' : ''; ?>>2 Bedrooms</option>
                            <option value="3" <?php echo $bedrooms === '3' ? 'selected' : ''; ?>>3 Bedrooms</option>
                            <option value="4+" <?php echo $bedrooms === '4+' ? 'selected' : ''; ?>>4+ Bedrooms</option>
                        </select>
                    </div>
                    
                    <div class="search-group">
                        <label for="bathrooms">Bathrooms</label>
                        <select id="bathrooms" name="bathrooms">
                            <option value="">Any</option>
                            <option value="1" <?php echo $bathrooms === '1' ? 'selected' : ''; ?>>1 Bathroom</option>
                            <option value="2" <?php echo $bathrooms === '2' ? 'selected' : ''; ?>>2 Bathrooms</option>
                            <option value="3+" <?php echo $bathrooms === '3+' ? 'selected' : ''; ?>>3+ Bathrooms</option>
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
            <div class="properties-header-section">
                <h2>Available Properties</h2>
                <div class="results-count">
                    <?php 
                    $propertyCount = count($properties);
                    if ($propertyCount > 0) {
                        echo "Showing $propertyCount " . ($propertyCount === 1 ? 'property' : 'properties');
                        // Show search criteria if any filters are applied
                        $filters = [];
                        if (!empty($searchLocation)) $filters[] = "in " . htmlspecialchars($searchLocation);
                        if (!empty($priceRange)) {
                            $ranges = ['0-10000' => 'Under ₱10,000', '10000-20000' => '₱10,000-₱20,000', 
                                      '20000-35000' => '₱20,000-₱35,000', '35000-50000' => '₱35,000-₱50,000', 
                                      '50000-999999' => 'Over ₱50,000'];
                            if (isset($ranges[$priceRange])) $filters[] = $ranges[$priceRange];
                        }
                        if (!empty($propertyType)) $filters[] = ucfirst($propertyType);
                        if (!empty($bedrooms)) $filters[] = ($bedrooms === 'studio' ? 'Studio' : $bedrooms . ' bedroom' . ($bedrooms !== '1' ? 's' : ''));
                        if (!empty($bathrooms)) $filters[] = $bathrooms . ' bathroom' . ($bathrooms !== '1' ? 's' : '');
                        
                        if (!empty($filters)) {
                            echo " " . implode(", ", $filters);
                        }
                    } else {
                        echo "No properties found";
                        if (!empty($searchLocation) || !empty($priceRange) || !empty($propertyType) || !empty($bedrooms) || !empty($bathrooms)) {
                            echo " matching your search criteria";
                        }
                    }
                    ?>
                </div>
            </div>
            
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
