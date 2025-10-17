<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.html");
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Include database connection
require_once 'config/db_connect.php';
$conn = getDbConnection();

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - HomeHub AI</title>
    <link rel="stylesheet" href="assets/css/bookings.css">
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
                <a href="../HomeHub/guest/index.html" class="nav-link">Home</a>
                <a href="properties.php" class="nav-link">Properties</a>
                <a href="<?php echo $userType; ?>/dashboard.php" class="nav-link">Dashboard</a>
                <a href="bookings.php" class="nav-link active">Bookings</a>
                <a href="<?php echo $userType; ?>/history.html" class="nav-link">History</a>
                <a href="<?php echo $userType; ?>/ai-features.html" class="nav-link">AI Features</a>
            </div>
            
            <!-- Desktop Buttons -->
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
            <a href="index.php" class="nav-link-mobile">Home</a>
            <a href="properties.php" class="nav-link-mobile">Properties</a>
            <a href="<?php echo $userType; ?>/dashboard.php" class="nav-link-mobile">Dashboard</a>
            <a href="bookings.php" class="nav-link-mobile active">Bookings</a>
            <a href="<?php echo $userType; ?>/history.html">History</a>
            <a href="<?php echo $userType; ?>/ai-features.php" class="nav-link-mobile">AI Features</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="bookings-header">
            <h1>Bookings</h1>
            <p class="bookings-subtitle">Manage your property reservations, schedule visits, and track booking status</p>
        </div>
        
        <div class="booking-options">
            <?php if($userType === 'tenant'): ?>
            <!-- Tenant Options -->
            <div class="booking-card" id="reserve-property-card">
                <div class="booking-card-content">
                    <img src="assets/images/reserve-icon.png" alt="Reserve" class="booking-icon">
                    <h2>Reserve Property</h2>
                    <p>Lock in your desired property immediately and secure your rental before it's gone.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Instant property reservation</li>
                        <li><i class="fas fa-check-circle"></i> Secure your preferred listing</li>
                        <li><i class="fas fa-check-circle"></i> Reduce competition risk</li>
                        <li><i class="fas fa-check-circle"></i> Peace of mind guarantee</li>
                    </ul>
                </div>
                <button class="booking-btn" id="reserveBtn">Reserve Now</button>
            </div>
            
            <div class="booking-card" id="schedule-visit-card">
                <div class="booking-card-content">
                    <img src="assets/images/calendar-icon.png" alt="Schedule" class="booking-icon">
                    <h2>Schedule Visit</h2>
                    <p>Arrange in-person property visits with convenient scheduling that works for both tenants and landlords.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Flexible scheduling system</li>
                        <li><i class="fas fa-check-circle"></i> Landlord collaboration</li>
                        <li><i class="fas fa-check-circle"></i> Reduce miscommunication</li>
                        <li><i class="fas fa-check-circle"></i> Convenient visit planning</li>
                    </ul>
                </div>
                <button class="booking-btn" id="scheduleBtn">Schedule Visit</button>
            </div>
            <?php endif; ?>

            <?php if($userType === 'landlord'): ?>
            <!-- Landlord Options -->
            <div class="booking-card" id="manage-reservations-card">
                <div class="booking-card-content">
                    <img src="assets/images/manage-icon.png" alt="Manage" class="booking-icon">
                    <h2>Manage Reservations</h2>
                    <p>Review and respond to property reservation requests from potential tenants.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Review tenant applications</li>
                        <li><i class="fas fa-check-circle"></i> Approve qualified tenants</li>
                        <li><i class="fas fa-check-circle"></i> Manage property availability</li>
                        <li><i class="fas fa-check-circle"></i> Streamlined booking process</li>
                    </ul>
                </div>
                <button class="booking-btn" id="manageReservationsBtn">Manage Reservations</button>
            </div>
            
            <div class="booking-card" id="manage-visits-card">
                <div class="booking-card-content">
                    <img src="assets/images/visit-icon.png" alt="Visits" class="booking-icon">
                    <h2>Manage Visit Requests</h2>
                    <p>Coordinate property viewings with interested tenants and organize your schedule.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Approve viewing requests</li>
                        <li><i class="fas fa-check-circle"></i> Organize your calendar</li>
                        <li><i class="fas fa-check-circle"></i> Avoid scheduling conflicts</li>
                        <li><i class="fas fa-check-circle"></i> Track viewing history</li>
                    </ul>
                </div>
                <button class="booking-btn" id="manageVisitsBtn">Manage Visits</button>
            </div>
            <?php endif; ?>
            
            <!-- For both user types -->
            <div class="booking-card" id="status-card">
                <div class="booking-card-content">
                    <img src="assets/images/status-icon.png" alt="Status" class="booking-icon">
                    <h2>Booking Status</h2>
                    <p>Track your reservations, pending decisions, and approved bookings in real-time.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Real-time status updates</li>
                        <li><i class="fas fa-check-circle"></i> Transparent communication</li>
                        <li><i class="fas fa-check-circle"></i> Pending, Approved, Cancelled tracking</li>
                        <li><i class="fas fa-check-circle"></i> Maintain user confidence</li>
                    </ul>
                </div>
                <button class="booking-btn" id="statusBtn">Check Status</button>
            </div>
        </div>
    </main>
    
    <!-- Modal Containers -->
    <div id="reserveModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Reserve Property</h2>
            <form id="reservationForm" action="process-reservation.php" method="POST">
                <div class="form-group">
                    <label for="property_id">Property Address</label>
                    <select id="property_id" name="property_id" required>
                        <option value="">Select a property</option>
                        <!-- Options will be loaded via AJAX -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="move_in_date">Preferred Move-in Date</label>
                    <input type="date" id="move_in_date" name="move_in_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="lease_duration">Lease Duration (months)</label>
                    <input type="number" id="lease_duration" name="lease_duration" min="1" max="60" required>
                </div>
                
                <div class="form-group">
                    <label for="requirements">Additional Requirements or Notes</label>
                    <textarea id="requirements" name="requirements" rows="4"></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Submit Reservation</button>
            </form>
        </div>
    </div>
    
    <div id="visitModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Schedule Visit</h2>
            <form id="visitForm" action="process-visit.php" method="POST">
                <div class="form-group">
                    <label for="visit_property_id">Property to Visit</label>
                    <select id="visit_property_id" name="property_id" required>
                        <option value="">Select a property</option>
                        <!-- Options will be loaded via AJAX -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="visit_date">Preferred Date</label>
                    <input type="date" id="visit_date" name="visit_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="visit_time">Preferred Time</label>
                    <select id="visit_time" name="visit_time" required>
                        <option value="">Select a time</option>
                        <option value="09:00:00">9:00 AM</option>
                        <option value="10:00:00">10:00 AM</option>
                        <option value="11:00:00">11:00 AM</option>
                        <option value="13:00:00">1:00 PM</option>
                        <option value="14:00:00">2:00 PM</option>
                        <option value="15:00:00">3:00 PM</option>
                        <option value="16:00:00">4:00 PM</option>
                        <option value="17:00:00">5:00 PM</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="visitors">Number of Visitors</label>
                    <input type="number" id="visitors" name="visitors" min="1" max="10" value="1" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+63 9XX XXX XXXX" required>
                </div>
                
                <div class="form-group">
                    <label for="visit_message">Message to Landlord (Optional)</label>
                    <textarea id="visit_message" name="message" rows="3"></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Schedule Visit</button>
            </form>
        </div>
    </div>
    
    <div id="statusModal" class="modal">
        <div class="modal-content status-content">
            <span class="close-modal">&times;</span>
            <h2>Booking Status</h2>
            
            <!-- Status content will be loaded via AJAX -->
            <div id="status-container">
                <div class="loading">Loading your bookings...</div>
            </div>
        </div>
    </div>
    
    <!-- Landlord specific modals -->
    <div id="manageReservationsModal" class="modal">
        <div class="modal-content wide-modal">
            <span class="close-modal">&times;</span>
            <h2>Manage Property Reservations</h2>
            
            <!-- Reservations content will be loaded via AJAX -->
            <div id="reservations-container">
                <div class="loading">Loading reservation requests...</div>
            </div>
        </div>
    </div>
    
    <div id="manageVisitsModal" class="modal">
        <div class="modal-content wide-modal">
            <span class="close-modal">&times;</span>
            <h2>Manage Visit Requests</h2>
            
            <!-- Visits content will be loaded via AJAX -->
            <div id="visits-container">
                <div class="loading">Loading visit requests...</div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/bookings.js"></script>
</body>
</html>