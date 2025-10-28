<?php
/**
 * Create a new notification for a user
 * 
 * @param int $userId ID of the user to receive the notification
 * @param string $type Type of notification (visit_request, booking_request, property_performance, message, system)
 * @param string $content Content of the notification message
 * @param int $relatedId ID related to the notification (e.g., property ID, booking ID)
 * @param object $conn Database connection
 * @return bool Success or failure
 */
function createNotification($userId, $type, $content, $relatedId = null, $conn) {
    try {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, content, related_id, is_read) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("issi", $userId, $type, $content, $relatedId);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a visit request notification
 */
function createVisitRequestNotification($landlordUserId, $tenantName, $propertyTitle, $visitDate, $visitTime, $visitId, $conn) {
    $formattedDate = date('l, F j', strtotime($visitDate));
    $formattedTime = date('g:i A', strtotime($visitTime));
    $content = "{$tenantName} wants to visit \"{$propertyTitle}\" on {$formattedDate} at {$formattedTime}";
    return createNotification($landlordUserId, 'visit_request', $content, $visitId, $conn);
}

/**
 * Create a booking request notification
 */
function createBookingRequestNotification($landlordUserId, $tenantName, $propertyTitle, $reservationId, $conn) {
    $content = "{$tenantName} wants to book \"{$propertyTitle}\"";
    return createNotification($landlordUserId, 'booking_request', $content, $reservationId, $conn);
}

/**
 * Create a property performance notification
 */
function createPropertyPerformanceNotification($landlordUserId, $propertyTitle, $viewCount, $propertyId, $conn) {
    $content = "Your \"{$propertyTitle}\" is trending - {$viewCount} views today!";
    return createNotification($landlordUserId, 'property_performance', $content, $propertyId, $conn);
}

/**
 * Create a message notification
 */
function createMessageNotification($userId, $senderName, $subject, $messageId, $conn) {
    $content = "New message from {$senderName}: {$subject}";
    return createNotification($userId, 'message', $content, $messageId, $conn);
}

/**
 * Create a system notification
 */
function createSystemNotification($userId, $content, $conn) {
    return createNotification($userId, 'system', $content, null, $conn);
}

/**
 * Mark a notification as read
 */
function markNotificationAsRead($notificationId, $conn) {
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $notificationId);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 */
function markAllNotificationsAsRead($userId, $conn) {
    try {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}
?>