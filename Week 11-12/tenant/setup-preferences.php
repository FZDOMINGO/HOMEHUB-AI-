<?php
// Start session
session_start();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    header("Location: ../login/login.html");
    exit;
}

require_once '../config/db_connect.php';
$conn = getDbConnection();

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Get tenant ID
$stmt = $conn->prepare("SELECT id FROM tenants WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();
$tenantId = $tenant['id'];

// Get current preferences if they exist
$stmt = $conn->prepare("SELECT * FROM tenant_preferences WHERE tenant_id = ?");
$stmt->bind_param("i", $tenantId);
$stmt->execute();
$result = $stmt->get_result();
$preferences = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_preferences'])) {
    $minBudget = floatval($_POST['min_budget']);
    $maxBudget = floatval($_POST['max_budget']);
    $preferredCity = isset($_POST['preferred_cities'][0]) ? $_POST['preferred_cities'][0] : null;
    $propertyType = isset($_POST['preferred_property_types'][0]) ? $_POST['preferred_property_types'][0] : null;
    $minBedrooms = intval($_POST['min_bedrooms']);
    $maxBedrooms = intval($_POST['max_bedrooms']);
    $minBathrooms = floatval($_POST['min_bathrooms']);
    $minSquareFeet = intval($_POST['min_square_feet'] ?? 0);
    
    // Lifestyle preferences (convert 1-10 scale to 0-1 decimal)
    $lifestyleQuiet = floatval($_POST['lifestyle_quiet_active']) / 10;
    $lifestyleSocial = 1 - $lifestyleQuiet;
    $lifestyleUrban = floatval($_POST['lifestyle_work_home']) / 10;
    $lifestyleFamily = floatval($_POST['lifestyle_family_single']) / 10;
    
    // Amenity preferences (convert 0-10 scale to 0-1 decimal)
    $wantsParking = floatval($_POST['amenity_parking'] ?? 5) / 10;
    $wantsGym = floatval($_POST['amenity_gym'] ?? 5) / 10;
    $wantsPool = floatval($_POST['amenity_pool'] ?? 5) / 10;
    $wantsLaundry = floatval($_POST['amenity_laundry'] ?? 5) / 10;
    $wantsAC = floatval($_POST['amenity_ac'] ?? 5) / 10;
    $wantsHeating = 0.5; // Default
    $wantsBalcony = floatval($_POST['amenity_balcony'] ?? 5) / 10;
    $wantsPetFriendly = floatval($_POST['amenity_pet_friendly'] ?? 5) / 10;
    $wantsFurnished = floatval($_POST['amenity_furnished'] ?? 5) / 10;
    $wantsSecurity = floatval($_POST['amenity_security'] ?? 5) / 10;
    
    $query = "
        INSERT INTO tenant_preferences 
        (tenant_id, min_budget, max_budget, preferred_city, property_type,
         min_bedrooms, max_bedrooms, min_bathrooms, min_square_feet,
         lifestyle_quiet, lifestyle_social, lifestyle_urban, lifestyle_family,
         wants_parking, wants_gym, wants_pool, wants_laundry, wants_air_conditioning,
         wants_heating, wants_balcony, wants_pet_friendly, wants_furnished, wants_security)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            min_budget = VALUES(min_budget),
            max_budget = VALUES(max_budget),
            preferred_city = VALUES(preferred_city),
            property_type = VALUES(property_type),
            min_bedrooms = VALUES(min_bedrooms),
            max_bedrooms = VALUES(max_bedrooms),
            min_bathrooms = VALUES(min_bathrooms),
            min_square_feet = VALUES(min_square_feet),
            lifestyle_quiet = VALUES(lifestyle_quiet),
            lifestyle_social = VALUES(lifestyle_social),
            lifestyle_urban = VALUES(lifestyle_urban),
            lifestyle_family = VALUES(lifestyle_family),
            wants_parking = VALUES(wants_parking),
            wants_gym = VALUES(wants_gym),
            wants_pool = VALUES(wants_pool),
            wants_laundry = VALUES(wants_laundry),
            wants_air_conditioning = VALUES(wants_air_conditioning),
            wants_heating = VALUES(wants_heating),
            wants_balcony = VALUES(wants_balcony),
            wants_pet_friendly = VALUES(wants_pet_friendly),
            wants_furnished = VALUES(wants_furnished),
            wants_security = VALUES(wants_security),
            updated_at = CURRENT_TIMESTAMP
    ";
    
    $stmt = $conn->prepare($query);
    
    // Type string: i=integer, d=decimal, s=string
    // tenant_id(i), min_budget(d), max_budget(d), preferred_city(s), property_type(s),
    // min_bedrooms(i), max_bedrooms(i), min_bathrooms(d), min_square_feet(i),
    // lifestyle_quiet(d), lifestyle_social(d), lifestyle_urban(d), lifestyle_family(d),
    // wants_parking(d), wants_gym(d), wants_pool(d), wants_laundry(d), wants_air_conditioning(d),
    // wants_heating(d), wants_balcony(d), wants_pet_friendly(d), wants_furnished(d), wants_security(d)
    // Total: 23 parameters
    
    $stmt->bind_param(
        "iddssiiiddddddddddddddd",
        $tenantId, $minBudget, $maxBudget, $preferredCity, $propertyType,
        $minBedrooms, $maxBedrooms, $minBathrooms, $minSquareFeet,
        $lifestyleQuiet, $lifestyleSocial, $lifestyleUrban, $lifestyleFamily,
        $wantsParking, $wantsGym, $wantsPool, $wantsLaundry, $wantsAC,
        $wantsHeating, $wantsBalcony, $wantsPetFriendly, $wantsFurnished, $wantsSecurity
    );
    
    if ($stmt->execute()) {
        $message = "Preferences saved successfully! Finding your perfect matches...";
        $messageType = "success";
        
        // Invalidate old similarity scores
        $conn->query("UPDATE similarity_scores SET is_valid = FALSE WHERE tenant_id = $tenantId");
        
        // Redirect to properties with AI matching
        header("Location: ../properties.php?ai_match=true");
        exit;
    } else {
        $message = "Error saving preferences. Please try again.";
        $messageType = "error";
    }
}

$conn->close();

// Parse existing preferences for form
$prefCities = [];
$prefTypes = [];
$amenityPrefs = [];

if ($preferences) {
    // Handle single city/type format
    if (!empty($preferences['preferred_city'])) {
        $prefCities = [$preferences['preferred_city']];
    }
    if (!empty($preferences['property_type'])) {
        $prefTypes = [$preferences['property_type']];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Your Preferences - HomeHub AI</title>
    <link rel="stylesheet" href="../assets/css/bookings.css">
    <link rel="stylesheet" href="profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .preference-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .preference-section h2 {
            color: #8b5cf6;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .slider-container {
            margin: 20px 0;
        }
        .slider-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        .slider {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(to right, #8b5cf6, #d4b5ff);
            outline: none;
        }
        .slider::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #8b5cf6;
            cursor: pointer;
        }
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .btn-save {
            background: #8b5cf6;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-save:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php 
    $navPath = '../';
    $activePage = '';
    include '../includes/navbar.php'; 
    ?>

    <main class="main-content" style="max-width: 1000px; margin: 100px auto; padding: 0 20px;">
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 style="color: #8b5cf6; font-size: 2.5rem;">🎯 Set Your Preferences</h1>
            <p style="color: #666; font-size: 1.1rem;">Help our AI find your perfect home!</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Budget Section -->
            <div class="preference-section">
                <h2><i class="fas fa-money-bill-wave"></i> Budget Range</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Minimum Budget (₱)</label>
                        <input type="number" name="min_budget" min="0" step="1000" 
                               value="<?php echo $preferences['min_budget'] ?? 10000; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Maximum Budget (₱)</label>
                        <input type="number" name="max_budget" min="0" step="1000" 
                               value="<?php echo $preferences['max_budget'] ?? 50000; ?>" required>
                    </div>
                </div>
            </div>

            <!-- Location & Property Type -->
            <div class="preference-section">
                <h2><i class="fas fa-map-marker-alt"></i> Location & Property Type</h2>
                <div class="form-group">
                    <label>Preferred Cities</label>
                    <div class="checkbox-group">
                        <?php
                        $cities = ['Manila', 'Quezon City', 'Makati', 'BGC', 'Pasig', 'Mandaluyong', 'Ortigas'];
                        foreach ($cities as $city):
                        ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="preferred_cities[]" value="<?php echo $city; ?>"
                                       <?php echo in_array($city, $prefCities) ? 'checked' : ''; ?>>
                                <label><?php echo $city; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label>Property Types</label>
                    <div class="checkbox-group">
                        <?php
                        $types = ['Apartment', 'House', 'Condo', 'Studio'];
                        foreach ($types as $type):
                        ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="preferred_property_types[]" value="<?php echo $type; ?>"
                                       <?php echo in_array($type, $prefTypes) ? 'checked' : ''; ?>>
                                <label><?php echo $type; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Size Requirements -->
            <div class="preference-section">
                <h2><i class="fas fa-ruler-combined"></i> Size Requirements</h2>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div class="form-group">
                        <label>Min Bedrooms</label>
                        <input type="number" name="min_bedrooms" min="0" max="10" 
                               value="<?php echo $preferences['min_bedrooms'] ?? 1; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Max Bedrooms</label>
                        <input type="number" name="max_bedrooms" min="0" max="10" 
                               value="<?php echo $preferences['max_bedrooms'] ?? 3; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Min Bathrooms</label>
                        <input type="number" name="min_bathrooms" min="0" max="10" step="0.5" 
                               value="<?php echo $preferences['min_bathrooms'] ?? 1; ?>" required>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <div class="form-group">
                        <label>Minimum Square Feet</label>
                        <input type="number" name="min_square_feet" min="0" step="50" 
                               value="<?php echo $preferences['min_square_feet'] ?? 0; ?>">
                    </div>
                </div>
            </div>

            <!-- Lifestyle Preferences -->
            <div class="preference-section">
                <h2><i class="fas fa-heart"></i> Lifestyle Preferences</h2>
                
                <div class="slider-container">
                    <div class="slider-label">
                        <span>Quiet Neighborhood</span>
                        <span id="quiet-value"><?php 
                            $quietVal = isset($preferences['lifestyle_quiet']) ? round($preferences['lifestyle_quiet'] * 10) : 5;
                            echo $quietVal; 
                        ?></span>
                        <span>Active/Urban Area</span>
                    </div>
                    <input type="range" name="lifestyle_quiet_active" class="slider" min="1" max="10" 
                           value="<?php echo $quietVal; ?>"
                           oninput="document.getElementById('quiet-value').textContent = this.value">
                </div>

                <div class="slider-container">
                    <div class="slider-label">
                        <span>Single/Couple</span>
                        <span id="family-value"><?php 
                            $familyVal = isset($preferences['lifestyle_family']) ? round($preferences['lifestyle_family'] * 10) : 5;
                            echo $familyVal; 
                        ?></span>
                        <span>Family-Friendly</span>
                    </div>
                    <input type="range" name="lifestyle_family_single" class="slider" min="1" max="10" 
                           value="<?php echo $familyVal; ?>"
                           oninput="document.getElementById('family-value').textContent = this.value">
                </div>

                <div class="slider-container">
                    <div class="slider-label">
                        <span>Office-Based</span>
                        <span id="work-value"><?php 
                            $workVal = isset($preferences['lifestyle_urban']) ? round($preferences['lifestyle_urban'] * 10) : 5;
                            echo $workVal; 
                        ?></span>
                        <span>Work From Home</span>
                    </div>
                    <input type="range" name="lifestyle_work_home" class="slider" min="1" max="10" 
                           value="<?php echo $workVal; ?>"
                           oninput="document.getElementById('work-value').textContent = this.value">
                </div>
            </div>

            <!-- Amenities Priority -->
            <div class="preference-section">
                <h2><i class="fas fa-star"></i> Amenity Priorities</h2>
                <p style="color: #666; margin-bottom: 20px;">Rate each amenity from 0 (not important) to 10 (very important)</p>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <?php
                    $amenities = [
                        'Parking' => ['icon' => 'car', 'db' => 'wants_parking'],
                        'Gym' => ['icon' => 'dumbbell', 'db' => 'wants_gym'],
                        'Pool' => ['icon' => 'swimming-pool', 'db' => 'wants_pool'],
                        'Laundry' => ['icon' => 'tshirt', 'db' => 'wants_laundry'],
                        'AC' => ['icon' => 'snowflake', 'db' => 'wants_air_conditioning'],
                        'Balcony' => ['icon' => 'building', 'db' => 'wants_balcony'],
                        'Pet-Friendly' => ['icon' => 'paw', 'db' => 'wants_pet_friendly'],
                        'Furnished' => ['icon' => 'couch', 'db' => 'wants_furnished'],
                        'Security' => ['icon' => 'shield-alt', 'db' => 'wants_security']
                    ];
                    foreach ($amenities as $name => $data):
                        $key = 'amenity_' . str_replace(['-', ' '], '_', strtolower($name));
                        $dbValue = isset($preferences[$data['db']]) ? $preferences[$data['db']] : 0.5;
                        $value = round($dbValue * 10); // Convert 0-1 to 0-10
                    ?>
                        <div class="slider-container">
                            <div class="slider-label">
                                <span><i class="fas fa-<?php echo $data['icon']; ?>"></i> <?php echo $name; ?></span>
                                <span id="<?php echo $key; ?>-value"><?php echo $value; ?></span>
                            </div>
                            <input type="range" name="<?php echo $key; ?>" class="slider" min="0" max="10" 
                                   value="<?php echo $value; ?>"
                                   oninput="document.getElementById('<?php echo $key; ?>-value').textContent = this.value">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="text-align: center; margin-top: 40px;">
                <button type="submit" name="save_preferences" class="btn-save">
                    <i class="fas fa-magic"></i> Save Preferences & Find Matches
                </button>
            </div>
        </form>
    </main>
</body>
</html>
