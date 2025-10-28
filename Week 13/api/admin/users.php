<?php
// Include environment configuration
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

// Initialize session
initSession();

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = getDbConnection();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = (int)($_POST['user_id'] ?? $_GET['user_id'] ?? 0);

switch ($action) {
    case 'get_user':
        if ($userId) {
            $query = "
                SELECT 
                    u.id, u.first_name, u.last_name, u.email, u.phone, 
                    u.created_at, u.last_login, u.status,
                    CASE 
                        WHEN t.id IS NOT NULL THEN 'tenant'
                        WHEN l.id IS NOT NULL THEN 'landlord'
                        ELSE 'unknown'
                    END as user_type,
                    t.id as tenant_id,
                    l.id as landlord_id,
                    t.occupation, t.income, t.preferred_location, t.max_budget, 
                    t.date_of_birth, t.move_in_date,
                    l.company_name, l.business_phone, l.verification_status,
                    l.company_address, l.tax_id
                FROM users u
                LEFT JOIN tenants t ON u.id = t.user_id
                LEFT JOIN landlords l ON u.id = l.user_id
                WHERE u.id = ?
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                // Get additional stats
                if ($user['user_type'] === 'tenant') {
                    $bookingQuery = "SELECT COUNT(*) as booking_count FROM property_reservations WHERE tenant_id = ?";
                    $bookingStmt = $conn->prepare($bookingQuery);
                    $bookingStmt->bind_param('i', $user['tenant_id']);
                    $bookingStmt->execute();
                    $user['booking_count'] = $bookingStmt->get_result()->fetch_assoc()['booking_count'];
                } else if ($user['user_type'] === 'landlord') {
                    $propertyQuery = "SELECT COUNT(*) as property_count FROM properties WHERE landlord_id = ?";
                    $propertyStmt = $conn->prepare($propertyQuery);
                    $propertyStmt->bind_param('i', $user['landlord_id']);
                    $propertyStmt->execute();
                    $user['property_count'] = $propertyStmt->get_result()->fetch_assoc()['property_count'];
                }
                
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        }
        break;
        
    case 'update_user':
        if ($userId && isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email'])) {
            $firstName = trim($_POST['first_name']);
            $lastName = trim($_POST['last_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone'] ?? '');
            $status = $_POST['status'] ?? 'active';
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                break;
            }
            
            // Check if email already exists for another user
            $checkQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param('si', $email, $userId);
            $checkStmt->execute();
            if ($checkStmt->get_result()->fetch_assoc()) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                break;
            }
            
            $updateQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('sssssi', $firstName, $lastName, $email, $phone, $status, $userId);
            
            if ($updateStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        }
        break;
        
    case 'delete_user':
        if ($userId) {
            $conn->begin_transaction();
            
            try {
                // First check what type of user this is
                $typeQuery = "
                    SELECT 
                        t.id as tenant_id,
                        l.id as landlord_id
                    FROM users u
                    LEFT JOIN tenants t ON u.id = t.user_id
                    LEFT JOIN landlords l ON u.id = l.user_id
                    WHERE u.id = ?
                ";
                $typeStmt = $conn->prepare($typeQuery);
                $typeStmt->bind_param('i', $userId);
                $typeStmt->execute();
                $userType = $typeStmt->get_result()->fetch_assoc();
                
                // Delete related records based on user type
                if ($userType['tenant_id']) {
                    // Delete tenant reservations
                    $deleteReservations = "DELETE FROM property_reservations WHERE tenant_id = ?";
                    $reservationStmt = $conn->prepare($deleteReservations);
                    $reservationStmt->bind_param('i', $userType['tenant_id']);
                    $reservationStmt->execute();
                    
                    // Delete tenant record
                    $deleteTenant = "DELETE FROM tenants WHERE id = ?";
                    $tenantStmt = $conn->prepare($deleteTenant);
                    $tenantStmt->bind_param('i', $userType['tenant_id']);
                    $tenantStmt->execute();
                }
                
                if ($userType['landlord_id']) {
                    // Delete properties and related data
                    $deleteProperties = "DELETE FROM properties WHERE landlord_id = ?";
                    $propertyStmt = $conn->prepare($deleteProperties);
                    $propertyStmt->bind_param('i', $userType['landlord_id']);
                    $propertyStmt->execute();
                    
                    // Delete landlord record
                    $deleteLandlord = "DELETE FROM landlords WHERE id = ?";
                    $landlordStmt = $conn->prepare($deleteLandlord);
                    $landlordStmt->bind_param('i', $userType['landlord_id']);
                    $landlordStmt->execute();
                }
                
                // Delete user notifications
                $deleteNotifications = "DELETE FROM notifications WHERE user_id = ?";
                $notificationStmt = $conn->prepare($deleteNotifications);
                $notificationStmt->bind_param('i', $userId);
                $notificationStmt->execute();
                
                // Finally delete the user
                $deleteUser = "DELETE FROM users WHERE id = ?";
                $userStmt = $conn->prepare($deleteUser);
                $userStmt->bind_param('i', $userId);
                $userStmt->execute();
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        }
        break;
        
    case 'suspend_user':
        if ($userId) {
            $updateQuery = "UPDATE users SET status = 'suspended' WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('i', $userId);
            
            if ($updateStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User suspended successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to suspend user']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        }
        break;
        
    case 'activate_user':
        if ($userId) {
            $updateQuery = "UPDATE users SET status = 'active' WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('i', $userId);
            
            if ($updateStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User activated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to activate user']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

$conn->close();
?>