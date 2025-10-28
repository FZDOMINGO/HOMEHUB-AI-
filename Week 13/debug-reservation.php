<?php
// Debug file to check what's being sent
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session' => [
        'user_id' => $_SESSION['user_id'] ?? 'not set',
        'user_type' => $_SESSION['user_type'] ?? 'not set'
    ],
    'post_data' => [
        'property_id' => $_POST['property_id'] ?? 'not set',
        'move_in_date' => $_POST['move_in_date'] ?? 'not set',
        'lease_duration' => $_POST['lease_duration'] ?? 'not set',
        'reservation_fee' => $_POST['reservation_fee'] ?? 'not set',
        'payment_method' => $_POST['payment_method'] ?? 'not set',
        'employment_status' => $_POST['employment_status'] ?? 'not set',
        'monthly_income' => $_POST['monthly_income'] ?? 'not set',
        'requirements' => $_POST['requirements'] ?? 'not set',
        'agree_terms' => isset($_POST['agree_terms']) ? 'checked' : 'not checked'
    ],
    'all_post' => $_POST
]);
?>
