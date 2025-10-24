<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Test endpoint working',
    'session_id' => session_id(),
    'has_user_id' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_type' => $_SESSION['user_type'] ?? null,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown'
]);
?>
