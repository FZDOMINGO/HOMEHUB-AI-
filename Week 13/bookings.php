<?php
// Include environment configuration
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Initialize session
initSession();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login/login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

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
    <?php 
    $activePage = 'bookings';
    $navPath = '';
    include 'includes/navbar.php'; 
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="bookings-header">
            <h1>Bookings</h1>
            <p class="bookings-subtitle">Manage your property reservations, schedule visits, and track booking status</p>
        </div>
        
        <div class="booking-options">
            <?php if($userType === 'tenant'): ?>
            <!-- Tenant Options -->
            <div class="booking-card" id="my-reservations-card">
                <div class="booking-card-content">
                    <img src="assets/images/reserve-icon.png" alt="Reservations" class="booking-icon">
                    <h2>My Reservations</h2>
                    <p>View and manage all your property reservation requests and their current status.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> Track reservation status</li>
                        <li><i class="fas fa-check-circle"></i> View landlord responses</li>
                        <li><i class="fas fa-check-circle"></i> See approved bookings</li>
                        <li><i class="fas fa-check-circle"></i> Manage pending requests</li>
                    </ul>
                    <div class="info-note">
                        <i class="fas fa-info-circle"></i> To reserve a property, browse the <a href="properties.php">Properties</a> page and click "Reserve" on your desired property.
                    </div>
                </div>
                <button class="booking-btn" id="myReservationsBtn">View My Reservations</button>
            </div>
            
            <div class="booking-card" id="my-visits-card">
                <div class="booking-card-content">
                    <img src="assets/images/calendar-icon.png" alt="Visits" class="booking-icon">
                    <h2>My Visit Requests</h2>
                    <p>Track all your property viewing requests and scheduled visit appointments.</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check-circle"></i> View scheduled visits</li>
                        <li><i class="fas fa-check-circle"></i> Check approval status</li>
                        <li><i class="fas fa-check-circle"></i> See visit dates & times</li>
                        <li><i class="fas fa-check-circle"></i> Get visit reminders</li>
                    </ul>
                    <div class="info-note">
                        <i class="fas fa-info-circle"></i> To schedule a visit, go to the <a href="properties.php">Properties</a> page and click "Schedule Visit" on any property.
                    </div>
                </div>
                <button class="booking-btn" id="myVisitsBtn">View My Visits</button>
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