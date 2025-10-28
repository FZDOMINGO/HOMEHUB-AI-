<?php
// Function to get notification count
function getNotificationCount($userId, $conn) {
    $count = 0;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $count = $row['count'];
        }
    } catch (Exception $e) {
        error_log("Error getting notification count: " . $e->getMessage());
    }
    return $count;
}

// Get notification count if user is logged in
$notificationCount = 0;
if (isset($_SESSION['user_id'])) {
    $notificationCount = getNotificationCount($_SESSION['user_id'], $conn);
}
?>

<!-- Add this in the header of all pages -->
<script>
// Set the notification count when page loads
document.addEventListener('DOMContentLoaded', function() {
    const notificationBadge = document.getElementById('notificationBadge');
    if (notificationBadge) {
        const count = <?php echo $notificationCount; ?>;
        if (count > 0) {
            notificationBadge.textContent = count;
            notificationBadge.style.display = 'flex';
        } else {
            notificationBadge.style.display = 'none';
        }
    }
});
</script>