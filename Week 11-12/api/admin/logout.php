<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Log logout activity if admin was logged in
    if (isset($_SESSION['admin_id'])) {
        $conn = getDbConnection();
        $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, target_type, ip_address, user_agent) VALUES (?, 'logout', 'system', ?, ?)");
        $stmt->bind_param("iss", $_SESSION['admin_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
    
    // Clear remember me cookie if it exists
    if (isset($_COOKIE['admin_remember_token'])) {
        setcookie('admin_remember_token', '', time() - 3600, "/");
    }
    
    // Destroy session
    session_destroy();
    
    echo json_encode(["status" => "success", "message" => "Logged out successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>