<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

// Check if user is logged in and is a tenant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'tenant') {
    redirect('login/login.html');
    exit;
}

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
    
    // Handle preferred cities as JSON
    $preferredCitiesArray = isset($_POST['preferred_cities']) ? $_POST['preferred_cities'] : [];
    $preferredCities = json_encode($preferredCitiesArray);
    
    // Handle property types as JSON
    $propertyTypesArray = isset($_POST['preferred_property_types']) ? $_POST['preferred_property_types'] : [];
    $propertyTypes = json_encode($propertyTypesArray);
    
    $minBedrooms = intval($_POST['min_bedrooms']);
    $maxBedrooms = intval($_POST['max_bedrooms']);
    $minBathrooms = floatval($_POST['min_bathrooms']);
    
    // Lifestyle preferences (keep as integers 1-10, not decimals)
    $lifestyleQuietActive = intval($_POST['lifestyle_quiet_active'] ?? 5);
    $lifestyleFamilySingle = intval($_POST['lifestyle_family_single'] ?? 5);
    $lifestyleWorkHome = intval($_POST['lifestyle_work_home'] ?? 5);
    
    // Pet friendly and furnished preferences
    $petFriendlyRequired = isset($_POST['pet_friendly_required']) ? 1 : 0;
    $furnishedPref = $_POST['furnished_preference'] ?? 'either';
    
    // Amenity preferences as JSON
    $amenitiesPrefs = json_encode([
        'parking' => floatval($_POST['amenity_parking'] ?? 5) / 10,
        'gym' => floatval($_POST['amenity_gym'] ?? 5) / 10,
        'pool' => floatval($_POST['amenity_pool'] ?? 5) / 10,
        'laundry' => floatval($_POST['amenity_laundry'] ?? 5) / 10,
        'air_conditioning' => floatval($_POST['amenity_ac'] ?? 5) / 10,
        'heating' => 0.5,
        'balcony' => floatval($_POST['amenity_balcony'] ?? 5) / 10,
        'pet_friendly' => floatval($_POST['amenity_pet_friendly'] ?? 5) / 10,
        'furnished' => floatval($_POST['amenity_furnished'] ?? 5) / 10,
        'security' => floatval($_POST['amenity_security'] ?? 5) / 10
    ]);
    
    // Parking and transport
    $parkingRequired = isset($_POST['parking_required']) ? 1 : 0;
    $nearPublicTransport = isset($_POST['near_public_transport']) ? 1 : 0;
    
    $query = "
        INSERT INTO tenant_preferences 
        (tenant_id, min_budget, max_budget, preferred_cities, preferred_property_types,
         min_bedrooms, max_bedrooms, min_bathrooms,
         lifestyle_quiet_active, lifestyle_family_single, lifestyle_work_home,
         pet_friendly_required, furnished_preference, amenities_preferences,
         parking_required, near_public_transport)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            min_budget = VALUES(min_budget),
            max_budget = VALUES(max_budget),
            preferred_cities = VALUES(preferred_cities),
            preferred_property_types = VALUES(preferred_property_types),
            min_bedrooms = VALUES(min_bedrooms),
            max_bedrooms = VALUES(max_bedrooms),
            min_bathrooms = VALUES(min_bathrooms),
            lifestyle_quiet_active = VALUES(lifestyle_quiet_active),
            lifestyle_family_single = VALUES(lifestyle_family_single),
            lifestyle_work_home = VALUES(lifestyle_work_home),
            pet_friendly_required = VALUES(pet_friendly_required),
            furnished_preference = VALUES(furnished_preference),
            amenities_preferences = VALUES(amenities_preferences),
            parking_required = VALUES(parking_required),
            near_public_transport = VALUES(near_public_transport),
            updated_at = CURRENT_TIMESTAMP
    ";
    
    $stmt = $conn->prepare($query);
    
    // Type string: i=integer, d=decimal, s=string
    // tenant_id(i), min_budget(d), max_budget(d), preferred_cities(s), preferred_property_types(s),
    // min_bedrooms(i), max_bedrooms(i), min_bathrooms(d),
    // lifestyle_quiet_active(i), lifestyle_family_single(i), lifestyle_work_home(i),
    // pet_friendly_required(i), furnished_preference(s), amenities_preferences(s),
    // parking_required(i), near_public_transport(i)
    // Total: 16 parameters
    
    $stmt->bind_param(
        "iddssiidiiiissii",
        $tenantId, $minBudget, $maxBudget, $preferredCities, $propertyTypes,
        $minBedrooms, $maxBedrooms, $minBathrooms,
        $lifestyleQuietActive, $lifestyleFamilySingle, $lifestyleWorkHome,
        $petFriendlyRequired, $furnishedPref, $amenitiesPrefs,
        $parkingRequired, $nearPublicTransport
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
    // Handle JSON city/type format
    if (!empty($preferences['preferred_cities'])) {
        $prefCities = json_decode($preferences['preferred_cities'], true) ?: [];
    }
    if (!empty($preferences['preferred_property_types'])) {
        $prefTypes = json_decode($preferences['preferred_property_types'], true) ?: [];
    }
    if (!empty($preferences['amenities_preferences'])) {
        $amenityPrefs = json_decode($preferences['amenities_preferences'], true) ?: [];
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
        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            font-family: 'Roboto', sans-serif;
            color: #2d3748;
            line-height: 1.6;
            min-height: 100vh;
        }

        .main-content {
            background: transparent;
            padding: 120px 20px 60px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
            animation: fadeInDown 0.6s ease;
        }

        .page-header h1 {
            color: #ffffff;
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .page-header p {
            color: #f3e8ff;
            font-size: 1.2rem;
            font-weight: 400;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        }

        .preference-section {
            background: rgba(255, 255, 255, 0.98);
            padding: 35px 40px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15), 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.5s ease;
            border: 1px solid rgba(139, 92, 246, 0.1);
        }

        .preference-section:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2), 0 4px 10px rgba(139, 92, 246, 0.2);
        }

        .preference-section h2 {
            color: #8b5cf6;
            margin-bottom: 25px;
            font-size: 1.6rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }

        .preference-section h2 i {
            font-size: 1.4rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Roboto', sans-serif;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-group input[type="number"]:focus,
        .form-group select:focus {
            outline: none;
            border-color: #8b5cf6;
            background: white;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        .input-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .slider-container {
            margin: 25px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .slider-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            color: #4a5568;
        }

        .slider-label span:nth-child(2) {
            background: #8b5cf6;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
        }

        .slider {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(to right, #e2e8f0, #cbd5e0);
            outline: none;
            appearance: none;
            cursor: pointer;
        }

        .slider::-webkit-slider-thumb {
            appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
            transition: all 0.3s ease;
        }

        .slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            box-shadow: 0 3px 12px rgba(139, 92, 246, 0.6);
        }

        .slider::-moz-range-thumb {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
            border: none;
        }

        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .checkbox-item:hover {
            background: #f0f4ff;
            border-color: #8b5cf6;
        }

        .checkbox-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #8b5cf6;
        }

        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
            color: #4a5568;
        }

        .section-description {
            color: #718096;
            font-size: 0.95rem;
            margin-bottom: 25px;
            padding: 12px 16px;
            background: #f0f4ff;
            border-left: 4px solid #8b5cf6;
            border-radius: 6px;
        }

        .btn-save {
            background: linear-gradient(135deg, #ffffff, #f3e8ff);
            color: #6d28d9;
            padding: 16px 50px;
            border: 3px solid #ffffff;
            border-radius: 12px;
            font-size: 1.15rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-save:hover {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #7c3aed;
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(253, 230, 138, 0.4), 0 4px 15px rgba(0, 0, 0, 0.2);
            border-color: #fef3c7;
        }

        .btn-save:active {
            transform: translateY(-1px);
        }

        .alert {
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeInDown 0.5s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert::before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 1.2rem;
        }

        .alert-success::before {
            content: '\f058';
        }

        .alert-error::before {
            content: '\f06a';
        }

        .save-section {
            text-align: center;
            margin-top: 50px;
            padding: 40px 20px;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15), 0 2px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.1);
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .preference-section {
                padding: 25px 20px;
            }

            .preference-section h2 {
                font-size: 1.3rem;
            }

            .input-group {
                grid-template-columns: 1fr;
            }

            .checkbox-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php 
    $navPath = '../';
    $activePage = '';
    include '../includes/navbar.php'; 
    ?>

    <main class="main-content" style="max-width: 1100px; margin: 0 auto;">
        <div class="page-header">
            <h1><i class="fas fa-magic"></i> Set Your Preferences</h1>
            <p>Help our AI find your perfect home match!</p>
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
                <p class="section-description">
                    Set your monthly rental budget range to filter properties within your price point.
                </p>
                <div class="input-group">
                    <div class="form-group">
                        <label>Minimum Budget (₱)</label>
                        <input type="number" name="min_budget" min="0" step="1000" 
                               value="<?php echo $preferences['min_budget'] ?? 10000; ?>" 
                               placeholder="e.g., 10,000" required>
                    </div>
                    <div class="form-group">
                        <label>Maximum Budget (₱)</label>
                        <input type="number" name="max_budget" min="0" step="1000" 
                               value="<?php echo $preferences['max_budget'] ?? 50000; ?>" 
                               placeholder="e.g., 50,000" required>
                    </div>
                </div>
            </div>

            <!-- Location & Property Type -->
            <div class="preference-section">
                <h2><i class="fas fa-map-marker-alt"></i> Location & Property Type</h2>
                <p class="section-description">
                    Choose your preferred cities and property types to narrow down your search.
                </p>
                
                <div class="form-group">
                    <label>Preferred Cities</label>
                    <div class="checkbox-group">
                        <?php
                        $cities = ['Manila', 'Quezon City', 'Makati', 'BGC', 'Pasig', 'Mandaluyong', 'Ortigas'];
                        foreach ($cities as $city):
                        ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="preferred_cities[]" value="<?php echo $city; ?>"
                                       id="city-<?php echo str_replace(' ', '-', strtolower($city)); ?>"
                                       <?php echo in_array($city, $prefCities) ? 'checked' : ''; ?>>
                                <label for="city-<?php echo str_replace(' ', '-', strtolower($city)); ?>">
                                    <?php echo $city; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 25px;">
                    <label>Property Types</label>
                    <div class="checkbox-group">
                        <?php
                        $types = ['Apartment', 'House', 'Condo', 'Studio'];
                        foreach ($types as $type):
                        ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="preferred_property_types[]" value="<?php echo $type; ?>"
                                       id="type-<?php echo strtolower($type); ?>"
                                       <?php echo in_array($type, $prefTypes) ? 'checked' : ''; ?>>
                                <label for="type-<?php echo strtolower($type); ?>">
                                    <?php echo $type; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Size Requirements -->
            <div class="preference-section">
                <h2><i class="fas fa-ruler-combined"></i> Size Requirements</h2>
                <p class="section-description">
                    Specify your ideal property size based on bedrooms, bathrooms, and square footage.
                </p>
                
                <div class="input-group">
                    <div class="form-group">
                        <label>Minimum Bedrooms</label>
                        <input type="number" name="min_bedrooms" min="0" max="10" 
                               value="<?php echo $preferences['min_bedrooms'] ?? 1; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Maximum Bedrooms</label>
                        <input type="number" name="max_bedrooms" min="0" max="10" 
                               value="<?php echo $preferences['max_bedrooms'] ?? 3; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Minimum Bathrooms</label>
                        <input type="number" name="min_bathrooms" min="0" max="10" step="0.5" 
                               value="<?php echo $preferences['min_bathrooms'] ?? 1; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Minimum Square Feet</label>
                        <input type="number" name="min_square_feet" min="0" step="50" 
                               value="<?php echo $preferences['min_square_feet'] ?? 0; ?>"
                               placeholder="Optional">
                    </div>
                </div>
            </div>

            <!-- Lifestyle Preferences -->
            <div class="preference-section">
                <h2><i class="fas fa-heart"></i> Lifestyle Preferences</h2>
                <p class="section-description">
                    Tell us about your lifestyle to help match you with the perfect neighborhood and amenities.
                </p>
                
                <div class="slider-container">
                    <div class="slider-label">
                        <span><i class="fas fa-volume-mute"></i> Quiet Neighborhood</span>
                        <span id="quiet-value"><?php 
                            $quietVal = isset($preferences['lifestyle_quiet_active']) ? $preferences['lifestyle_quiet_active'] : 5;
                            echo $quietVal; 
                        ?></span>
                        <span><i class="fas fa-city"></i> Active/Urban Area</span>
                    </div>
                    <input type="range" name="lifestyle_quiet_active" class="slider" min="1" max="10" 
                           value="<?php echo $quietVal; ?>"
                           oninput="document.getElementById('quiet-value').textContent = this.value">
                </div>

                <div class="slider-container">
                    <div class="slider-label">
                        <span><i class="fas fa-user"></i> Single/Couple</span>
                        <span id="family-value"><?php 
                            $familyVal = isset($preferences['lifestyle_family_single']) ? $preferences['lifestyle_family_single'] : 5;
                            echo $familyVal; 
                        ?></span>
                        <span><i class="fas fa-users"></i> Family-Friendly</span>
                    </div>
                    <input type="range" name="lifestyle_family_single" class="slider" min="1" max="10" 
                           value="<?php echo $familyVal; ?>"
                           oninput="document.getElementById('family-value').textContent = this.value">
                </div>

                <div class="slider-container">
                    <div class="slider-label">
                        <span><i class="fas fa-building"></i> Office-Based</span>
                        <span id="work-value"><?php 
                            $workVal = isset($preferences['lifestyle_work_home']) ? $preferences['lifestyle_work_home'] : 5;
                            echo $workVal; 
                        ?></span>
                        <span><i class="fas fa-home"></i> Work From Home</span>
                    </div>
                    <input type="range" name="lifestyle_work_home" class="slider" min="1" max="10" 
                           value="<?php echo $workVal; ?>"
                           oninput="document.getElementById('work-value').textContent = this.value">
                </div>
            </div>

            <!-- Amenities Priority -->
            <div class="preference-section">
                <h2><i class="fas fa-star"></i> Amenity Priorities</h2>
                <p class="section-description">
                    Rate each amenity from 0 (not important) to 10 (very important) to personalize your recommendations.
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    <?php
                    $amenities = [
                        'Parking' => ['icon' => 'car', 'db' => 'parking'],
                        'Gym' => ['icon' => 'dumbbell', 'db' => 'gym'],
                        'Pool' => ['icon' => 'swimming-pool', 'db' => 'pool'],
                        'Laundry' => ['icon' => 'tshirt', 'db' => 'laundry'],
                        'AC' => ['icon' => 'snowflake', 'db' => 'air_conditioning'],
                        'Balcony' => ['icon' => 'building', 'db' => 'balcony'],
                        'Pet-Friendly' => ['icon' => 'paw', 'db' => 'pet_friendly'],
                        'Furnished' => ['icon' => 'couch', 'db' => 'furnished'],
                        'Security' => ['icon' => 'shield-alt', 'db' => 'security']
                    ];
                    foreach ($amenities as $name => $data):
                        $key = 'amenity_' . str_replace(['-', ' '], '_', strtolower($name));
                        $dbValue = isset($amenityPrefs[$data['db']]) ? $amenityPrefs[$data['db']] : 0.5;
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

            <div class="save-section">
                <button type="submit" name="save_preferences" class="btn-save">
                    <i class="fas fa-magic"></i> Save Preferences & Find My Perfect Match
                </button>
            </div>
        </form>
    </main>
</body>
</html>
