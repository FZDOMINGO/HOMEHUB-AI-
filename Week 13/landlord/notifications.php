<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

// Check if user is logged in and is a landlord
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'landlord') {
    redirect('login/login.html');
    exit;
}

$conn = getDbConnection();

$userId = $_SESSION['user_id'];

// Get all notifications for this landlord
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Mark all notifications as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $userId);
$stmt->execute();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - HomeHub AI</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="notifications.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php 
    $navPath = '../';
    $activePage = '';
    include '../includes/navbar.php'; 
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-container">
            <div class="dashboard-layout">
                <!-- Sidebar -->
                <div class="sidebar">
                    <a href="dashboard.php" class="sidebar-item">
                        <div class="sidebar-link">Dashboard Overview</div>
                    </a>
                    <a href="profile.php" class="sidebar-item">
                        <div class="sidebar-link">My Profile</div>
                    </a>
                    <a href="manage-properties.php" class="sidebar-item">
                        <div class="sidebar-link">Manage Properties</div>
                    </a>
                    <a href="manage-availability.php" class="sidebar-item">
                        <div class="sidebar-link">Manage Availability</div>
                    </a>
                    <a href="notifications.php" class="sidebar-item active">
                        <div class="sidebar-link">Notifications</div>
                    </a>
                </div>
                
                <!-- Main Content Area -->
                <div class="main-area">
                    <div class="notifications-header">
                        <h1>Notifications</h1>
                        <p>Stay updated on tenant inquiries, bookings, and property activities</p>
                    </div>
                    
                    <div class="notifications-container">
                        <h2>Recent Notifications</h2>
                        
                        <div class="notifications-list">
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                        <div class="notification-indicator">
                                            <?php
                                            $iconClass = '';
                                            switch ($notification['type']) {
                                                case 'visit_request':
                                                    $iconClass = 'fa-calendar-check';
                                                    break;
                                                case 'booking_request':
                                                    $iconClass = 'fa-file-contract';
                                                    break;
                                                case 'property_performance':
                                                    $iconClass = 'fa-chart-line';
                                                    break;
                                                case 'message':
                                                    $iconClass = 'fa-envelope';
                                                    break;
                                                case 'system':
                                                    $iconClass = 'fa-bell';
                                                    break;
                                                default:
                                                    $iconClass = 'fa-circle-info';
                                            }
                                            ?>
                                            <i class="fas <?php echo $iconClass; ?>"></i>
                                        </div>
                                        
                                        <div class="notification-content">
                                            <div class="notification-title">
                                                <?php echo getNotificationTitle($notification['type']); ?>
                                            </div>
                                            <div class="notification-message">
                                                <?php echo htmlspecialchars($notification['content']); ?>
                                            </div>
                                            <div class="notification-time">
                                                <?php echo formatTimeAgo($notification['created_at']); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($notification['related_id']): ?>
                                            <div class="notification-actions">
                                                <?php echo getActionButton($notification['type'], $notification['related_id']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-notifications">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>You have no notifications at the moment.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="dashboard.js"></script>
    <script>
    // Notification action handling
    document.addEventListener('DOMContentLoaded', function() {
        // Handle notification action buttons
        document.querySelectorAll('.notification-action-btn').forEach(button => {
            button.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                const id = this.getAttribute('data-id');
                
                switch (type) {
                    case 'visit_request':
                    case 'booking_request':
                        window.location.href = '../bookings.php?view=' + type + '&id=' + id;
                        break;
                    case 'property_performance':
                        window.location.href = 'manage-properties.php?id=' + id;
                        break;
                    case 'message':
                        window.location.href = 'messages.php?message=' + id;
                        break;
                }
            });
        });
    });
    </script>
</body>
</html>

<?php
// Helper functions
function formatTimeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);    // Value 60 is seconds
    $hours = round($seconds / 3600);    // Value 3600 is 60 minutes * 60 seconds
    $days = round($seconds / 86400);    // 86400 = 24 * 60 * 60
    $weeks = round($seconds / 604800);  // 7 * 24 * 60 * 60
    $months = round($seconds / 2629440); // ((365+365+365+365+366)/5/12) * 24 * 60 * 60
    
    if ($seconds <= 60) {
        return "just now";
    } else if ($minutes <= 60) {
        if ($minutes == 1) {
            return "1 minute ago";
        } else {
            return "$minutes minutes ago";
        }
    } else if ($hours <= 24) {
        if ($hours == 1) {
            return "1 hour ago";
        } else {
            return "$hours hours ago";
        }
    } else if ($days <= 7) {
        if ($days == 1) {
            return "yesterday";
        } else {
            return "$days days ago";
        }
    } else if ($weeks <= 4.3) {
        if ($weeks == 1) {
            return "1 week ago";
        } else {
            return "$weeks weeks ago";
        }
    } else if ($months <= 12) {
        if ($months == 1) {
            return "1 month ago";
        } else {
            return "$months months ago";
        }
    } else {
        return date('M j, Y', $time_ago);
    }
}

function getNotificationTitle($type) {
    switch ($type) {
        case 'visit_request':
            return 'New Visit Request';
        case 'booking_request':
            return 'Booking Request';
        case 'property_performance':
            return 'Property Performance';
        case 'message':
            return 'New Message';
        case 'system':
            return 'System Notification';
        default:
            return 'Notification';
    }
}

function getActionButton($type, $id) {
    switch ($type) {
        case 'visit_request':
            return '<button class="notification-action-btn" data-type="visit_request" data-id="'.$id.'">View Request</button>';
        case 'booking_request':
            return '<button class="notification-action-btn" data-type="booking_request" data-id="'.$id.'">Review Booking</button>';
        case 'property_performance':
            return '<button class="notification-action-btn" data-type="property_performance" data-id="'.$id.'">View Details</button>';
        case 'message':
            return '<button class="notification-action-btn" data-type="message" data-id="'.$id.'">Read Message</button>';
        default:
            return '';
    }
}
?>
