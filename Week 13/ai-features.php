<?php
// Include environment configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Initialize session
initSession();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;
$userType = $isLoggedIn ? $_SESSION['user_type'] : 'guest';

// Include database connection if logged in
if ($isLoggedIn) {
    $conn = getDbConnection();

    // Get user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Ensure session has user name for navbar
    if (!isset($_SESSION['user_name']) && $user) {
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
    }

    $conn->close();
}
?>
<script>
// Pass PHP session data to JavaScript
window.HomeHubUser = {
    isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
    userType: '<?php echo $userType; ?>',
    userId: <?php echo $userId ? $userId : 'null'; ?>
};
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Features - HomeHub AI</title>
    <link rel="stylesheet" href="assets/css/ai-features.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php 
    $activePage = 'ai-features';
    $navPath = '';
    include 'includes/navbar.php'; 
    
    // Include admin preview banner if in preview mode
    include 'includes/admin-preview-banner.php';
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero-section">
            <h1 class="hero-title">AI Features</h1>
            <p class="hero-subtitle">Intelligent tools that revolutionize how tenants find properties and landlords manage rentals</p>
        </section>

        <!-- Features Section -->
        <section class="features-container">
            <div class="features-grid">
                <?php if ($userType === 'tenant' || $userType === 'guest'): ?>
                <!-- Intelligent Tenant Matching -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <div class="icon-circle purple">
                            <i class="fas fa-brain"></i>
                        </div>
                    </div>
                    <div class="feature-content">
                        <h3>Intelligent Tenant Matching</h3>
                        <p>AI-powered algorithms that pair tenants with the most suitable properties, reducing mismatches and streamlining the rental process.</p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Advanced compatibility analysis</li>
                            <li><i class="fas fa-check"></i> Preference-based matching</li>
                            <li><i class="fas fa-check"></i> Reduced property mismatches</li>
                            <li><i class="fas fa-check"></i> Time-saving for both parties</li>
                        </ul>
                        <button class="btn-feature" onclick="openModal('tenant-matching')">Try AI Matching</button>
                    </div>
                </div>

                <!-- Smart Property Recommendations -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <div class="icon-circle pink">
                            <i class="fas fa-home"></i>
                        </div>
                    </div>
                    <div class="feature-content">
                        <h3>Smart Property Recommendations</h3>
                        <p>Personalized property suggestions based on browsing history, saved properties, and intelligent preference analysis.</p>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Browsing history analysis</li>
                            <li><i class="fas fa-check"></i> Personalized suggestions</li>
                            <li><i class="fas fa-check"></i> Behavioral pattern recognition</li>
                            <li><i class="fas fa-check"></i> Continuous learning algorithm</li>
                        </ul>
                        <button class="btn-feature" onclick="openModal('property-recommendations')">Get Recommendations</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($userType === 'landlord' || $userType === 'guest'): ?>
            <!-- Predictive Analytics for Landlords -->
            <div class="feature-card full-width">
                <div class="feature-icon">
                    <div class="icon-circle blue">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="feature-content">
                    <h3>Predictive Analytics for Landlords</h3>
                    <p>AI-powered insights that predict property demand, suggest optimal rental pricing, and provide market trend analysis.</p>
                    <ul class="feature-list horizontal">
                        <li><i class="fas fa-check"></i> Demand forecasting</li>
                        <li><i class="fas fa-check"></i> Optimal pricing suggestions</li>
                        <li><i class="fas fa-check"></i> Performance analytics</li>
                        <li><i class="fas fa-check"></i> Market trend insights</li>
                    </ul>
                    <button class="btn-feature" onclick="openModal('predictive-analytics')">View Analytics</button>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-header">
                <button class="modal-back" onclick="closeModal()">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h2 class="modal-title" id="modalTitle"></h2>
            </div>
            <div class="modal-content" id="modalContent">
                <!-- Content will be dynamically inserted -->
            </div>
        </div>
    </div>

    <script src="assets/js/ai-features.js"></script>
</body>
</html>
