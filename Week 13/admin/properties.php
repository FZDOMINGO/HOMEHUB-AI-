<?php
// Include environment configuration
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// Initialize session
initSession();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    redirect('admin/login.php');
    exit;
}

$conn = getDbConnection();

// Handle property actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $propertyId = $_POST['property_id'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    
    // Debug logging
    error_log("Property action received: action=$action, propertyId=$propertyId, reason=$reason");
    
    switch ($action) {
        case 'suspend':
            handleSuspension($conn, $propertyId, $reason);
            break;
        case 'delete':
            handleDeletion($conn, $propertyId, $reason);
            break;
        case 'update_property':
            handlePropertyUpdate($conn, $propertyId, $_POST);
            break;
    }
    
    // Redirect to avoid form resubmission
    header('Location: properties.php?' . http_build_query($_GET));
    exit;
}


function handleSuspension($conn, $propertyId, $reason) {
    try {
        $stmt = $conn->prepare("UPDATE properties SET status = 'suspended' WHERE id = ?");
        $stmt->bind_param("i", $propertyId);
        
        if ($stmt->execute()) {
            error_log("Property $propertyId suspended successfully. Reason: $reason");
            
            // Send notification to landlord
            $message = "Your property listing has been suspended. Reason: " . $reason;
            sendNotificationToLandlord($conn, $propertyId, 'suspended', $message);
            
            return true;
        } else {
            error_log("Failed to suspend property $propertyId: " . $conn->error);
            return false;
        }
    } catch (Exception $e) {
        error_log("Exception suspending property $propertyId: " . $e->getMessage());
        return false;
    }
}

function handleDeletion($conn, $propertyId, $reason) {
    $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->bind_param("i", $propertyId);
    $stmt->execute();
    
    // Send notification to landlord
    $message = "Your property listing has been removed from the platform. Reason: " . $reason;
    sendNotificationToLandlord($conn, $propertyId, 'deleted', $message, true);
}



function handlePropertyUpdate($conn, $propertyId, $data) {
    $stmt = $conn->prepare("
        UPDATE properties 
        SET title = ?, description = ?, rent_amount = ?, bedrooms = ?, bathrooms = ?, 
            square_feet = ?, address = ?, city = ?, state = ?, zip_code = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param("ssiiiissssi", 
        $data['title'], $data['description'], $data['rent_amount'], 
        $data['bedrooms'], $data['bathrooms'], $data['square_feet'],
        $data['address'], $data['city'], $data['state'], $data['zip_code'],
        $propertyId
    );
    $stmt->execute();
}

function sendNotificationToLandlord($conn, $propertyId, $type, $message, $isDeleted = false) {
    // Get landlord user_id
    if ($isDeleted) {
        // For deleted properties, we need to store the landlord info separately
        return;
    }
    
    $stmt = $conn->prepare("
        SELECT l.user_id 
        FROM properties p 
        JOIN landlords l ON p.landlord_id = l.id 
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $propertyId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, content, related_id, status, is_read, created_at) 
            VALUES (?, ?, ?, ?, 'unread', 0, NOW())
        ");
        $notifStmt->bind_param("issi", $result['user_id'], $type, $message, $propertyId);
        $notifStmt->execute();
    }
}

// Get properties with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Debug: Log the received parameters
error_log("Admin Properties Filter Debug - Status: '$status', Search: '$search'");

// Build query
$whereClause = '';
$params = [];
$types = '';

if ($search) {
    $whereClause .= " WHERE (p.title LIKE ? OR p.description LIKE ? OR p.address LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = 'sss';
}

if ($status !== 'all') {
    if ($whereClause) {
        $whereClause .= " AND p.status = ?";
    } else {
        $whereClause .= " WHERE p.status = ?";
    }
    $params[] = $status;
    $types .= 's';
    error_log("Admin Properties Filter Debug - Adding status filter for: '$status'");
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM properties p $whereClause";
$countStmt = $conn->prepare($countQuery);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalProperties = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalProperties / $limit);

// Get properties data
$query = "
    SELECT p.*, u.first_name, u.last_name, u.email,
           (SELECT COUNT(*) FROM bookings b WHERE b.property_id = p.id) as booking_count
    FROM properties p 
    LEFT JOIN landlords l ON p.landlord_id = l.id 
    LEFT JOIN users u ON l.user_id = u.id
    $whereClause
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
";

error_log("Admin Properties Filter Debug - Final query: $query");
error_log("Admin Properties Filter Debug - Parameters: " . print_r($params, true));

$stmt = $conn->prepare($query);
$allParams = array_merge($params, [$limit, $offset]);
$allTypes = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$properties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get property statistics before closing connection
$statsQuery = "
    SELECT 
        COUNT(*) as total_properties,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_properties,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_properties
    FROM properties
";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties Management - HomeHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin: 0.2rem 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .property-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        
        .property-row-suspended {
            background-color: #f8d7da !important;
            border-left: 4px solid #dc3545;
        }
        
        .actions-dropdown {
            min-width: 200px;
        }
        
        .modal-header.bg-warning {
            background-color: #fff3cd !important;
        }
        
        .modal-header.bg-danger {
            background-color: #f8d7da !important;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar p-3">
                <div class="text-center mb-4">
                    <h4 class="text-white">
                        <i class="bi bi-shield-check"></i>
                        Admin Panel
                    </h4>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people"></i>Users Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="properties.php">
                            <i class="bi bi-house"></i>Properties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="analytics.php">
                            <i class="bi bi-graph-up"></i>Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="bi bi-gear"></i>Settings
                        </a>
                    </li>
                    
                    <hr class="my-3 text-white-50">
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="logout()">
                            <i class="bi bi-box-arrow-right"></i>Logout
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="container-fluid p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">
                                <i class="bi bi-house"></i> Properties Management
                            </h2>
                            <p class="text-muted mb-0">Manage and moderate property listings</p>
                        </div>
                        <div class="btn-group">
                        </div>
                    </div>
                    
                    <!-- Property Statistics -->
                    <div class="row mb-4">
                        
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?= number_format($stats['total_properties']) ?></h4>
                                            <p class="mb-0">Total Properties</p>
                                        </div>
                                        <i class="bi bi-house display-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?= number_format($stats['available_properties']) ?></h4>
                                            <p class="mb-0">Available</p>
                                        </div>
                                        <i class="bi bi-check-circle display-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0"><?= number_format($stats['suspended_properties']) ?></h4>
                                            <p class="mb-0">Suspended</p>
                                        </div>
                                        <i class="bi bi-ban display-6"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">Search Properties</label>
                                    <input type="text" class="form-control" name="search" 
                                           value="<?= htmlspecialchars($search) ?>" 
                                           placeholder="Title, description, or address...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Properties</option>
                                        <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option>
                                        <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                    <a href="properties.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Properties Table -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-list"></i> Properties List
                                    <span class="badge bg-primary ms-2"><?= number_format($totalProperties) ?></span>
                                </h5>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="select-all">
                                        </th>
                                        <th>Property</th>
                                        <th>Landlord</th>
                                        <th>Price</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Bookings</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($properties as $property): ?>
                                        <tr class="<?= $property['status'] === 'suspended' ? 'property-row-suspended' : '' ?>">
                                            <td>
                                                <input type="checkbox" class="property-checkbox" value="<?= $property['id'] ?>">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../uploads/<?= $property['image_url'] ?: 'default-property.jpg' ?>" 
                                                         class="property-image me-3" alt="Property">
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($property['title']) ?></div>
                                                        <small class="text-muted">
                                                            <?= $property['bedrooms'] ?>BR • <?= $property['bathrooms'] ?>BA • 
                                                            <?= number_format($property['square_feet']) ?> sq ft
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">
                                                        <?= htmlspecialchars($property['first_name'] . ' ' . $property['last_name']) ?>
                                                    </div>
                                                    <small class="text-muted"><?= htmlspecialchars($property['email']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success">
                                                    $<?= number_format($property['rent_amount']) ?>
                                                </span>
                                                <small class="text-muted d-block">/month</small>
                                            </td>
                                            <td>
                                                <div>
                                                    <div><?= htmlspecialchars($property['city']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($property['state']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'available' => 'success',
                                                    'suspended' => 'danger'
                                                ];
                                                $statusColor = $statusColors[$property['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $statusColor ?> status-badge">
                                                    <?= ucfirst($property['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?= $property['booking_count'] ?></span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($property['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="viewProperty(<?= $property['id'] ?>)" 
                                                            title="View Property">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-warning" onclick="editProperty(<?= $property['id'] ?>)" 
                                                            title="Edit Property">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>

                                                    <button class="btn btn-outline-danger" onclick="suspendProperty(<?= $property['id'] ?>)" 
                                                            title="Suspend Property">
                                                        <i class="bi bi-ban"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="deleteProperty(<?= $property['id'] ?>)" 
                                                            title="Delete Property">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($properties)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="bi bi-house display-4 text-muted"></i>
                                                <div class="mt-2 text-muted">No properties found</div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="card-footer bg-white">
                                <nav>
                                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Edit Property Modal -->
    <div class="modal fade" id="editPropertyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil"></i> Edit Property
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editPropertyForm" method="POST">
                    <input type="hidden" name="action" value="update_property">
                    <input type="hidden" name="property_id" id="editPropertyId">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Property Title</label>
                                <input type="text" class="form-control" name="title" id="editTitle" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Monthly Rent</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="rent_amount" id="editRentAmount" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="editDescription" rows="4" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Bedrooms</label>
                                <input type="number" class="form-control" name="bedrooms" id="editBedrooms" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Bathrooms</label>
                                <input type="number" class="form-control" name="bathrooms" id="editBathrooms" min="0" step="0.5" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Square Feet</label>
                                <input type="number" class="form-control" name="square_feet" id="editSquareFeet" min="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" id="editAddress" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" id="editCity" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="state" id="editState" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" name="zip_code" id="editZipCode" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Suspend Property Modal -->
    <div class="modal fade" id="suspendPropertyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark">
                        <i class="bi bi-ban"></i> Suspend Property
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="suspendPropertyForm" method="POST">
                    <input type="hidden" name="action" value="suspend">
                    <input type="hidden" name="property_id" id="suspendPropertyId">
                    
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            This will suspend the property listing and notify the landlord.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason for Suspension</label>
                            <select class="form-select" id="suspendReasonSelect" required>
                                <option value="">Select a reason...</option>
                                <option value="Inappropriate content">Inappropriate content</option>
                                <option value="Misleading information">Misleading information</option>
                                <option value="Poor quality photos">Poor quality photos</option>
                                <option value="Incomplete listing details">Incomplete listing details</option>
                                <option value="Suspected fraud">Suspected fraud</option>
                                <option value="Policy violation">Policy violation</option>
                                <option value="Duplicate listing">Duplicate listing</option>
                                <option value="Property no longer available">Property no longer available</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Additional Details (Optional)</label>
                            <textarea class="form-control" id="suspendAdditionalDetails" rows="3" 
                                      placeholder="Provide more specific details about the issue..."></textarea>
                        </div>
                        
                        <!-- Hidden field for the combined reason -->
                        <input type="hidden" name="reason" id="suspendCombinedReason">
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-ban"></i> Suspend Property
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Property Modal -->
    <div class="modal fade" id="deletePropertyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">
                        <i class="bi bi-trash"></i> Delete Property
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="deletePropertyForm" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="property_id" id="deletePropertyId">
                    
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Warning:</strong> This will permanently delete the property listing. This action cannot be undone.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason for Deletion</label>
                            <select class="form-select" name="reason" required>
                                <option value="">Select a reason...</option>
                                <option value="Inappropriate or offensive content">Inappropriate or offensive content</option>
                                <option value="Fraudulent listing">Fraudulent listing</option>
                                <option value="Spam or fake property">Spam or fake property</option>
                                <option value="Serious policy violation">Serious policy violation</option>
                                <option value="Legal issues">Legal issues</option>
                                <option value="Landlord request">Landlord request</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Additional Details</label>
                            <textarea class="form-control" name="additional_details" rows="3" required
                                      placeholder="Explain the specific reason for permanent deletion..."></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmDelete" required>
                            <label class="form-check-label" for="confirmDelete">
                                I understand this action is permanent and cannot be undone
                            </label>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete Property
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('../api/admin/logout.php', { method: 'POST' })
                .then(() => window.location.href = 'login.php');
            }
        }
        
        function viewProperty(propertyId) {
            console.log('View property called for ID:', propertyId);
            window.open('../property-detail.php?id=' + propertyId, '_blank');
        }
        
        function editProperty(propertyId) {
            console.log('Edit property called for ID:', propertyId);
            // Fetch property data and populate the edit modal
            fetch(`../api/get-property-details.php?id=${propertyId}`)
                .then(response => {
                    console.log('API response received:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('API data:', data);
                    if (data.success) {
                        const property = data.property;
                        document.getElementById('editPropertyId').value = property.id;
                        document.getElementById('editTitle').value = property.title;
                        document.getElementById('editDescription').value = property.description;
                        document.getElementById('editRentAmount').value = property.rent_amount;
                        document.getElementById('editBedrooms').value = property.bedrooms;
                        document.getElementById('editBathrooms').value = property.bathrooms;
                        document.getElementById('editSquareFeet').value = property.square_feet;
                        document.getElementById('editAddress').value = property.address;
                        document.getElementById('editCity').value = property.city;
                        document.getElementById('editState').value = property.state;
                        document.getElementById('editZipCode').value = property.zip_code;
                        
                        new bootstrap.Modal(document.getElementById('editPropertyModal')).show();
                    } else {
                        console.error('API error:', data.message);
                        alert('Error loading property details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading property details');
                });
        }
        

        
        function suspendProperty(propertyId) {
            console.log('Suspend property called for ID:', propertyId);
            document.getElementById('suspendPropertyId').value = propertyId;
            new bootstrap.Modal(document.getElementById('suspendPropertyModal')).show();
        }
        
        function deleteProperty(propertyId) {
            console.log('Delete property called for ID:', propertyId);
            document.getElementById('deletePropertyId').value = propertyId;
            new bootstrap.Modal(document.getElementById('deletePropertyModal')).show();
        }
        

        
        // Select all checkbox
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.property-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
        
        // Handle suspend form submission
        document.getElementById('suspendPropertyForm').addEventListener('submit', function(e) {
            console.log('Suspend form submitted');
            const reasonSelect = document.getElementById('suspendReasonSelect');
            const additionalDetails = document.getElementById('suspendAdditionalDetails').value;
            const combinedReasonField = document.getElementById('suspendCombinedReason');
            
            // Validate that a reason is selected
            if (!reasonSelect.value) {
                e.preventDefault();
                alert('Please select a reason for suspension');
                return false;
            }
            
            // Combine reason and additional details
            let finalReason = reasonSelect.value;
            if (additionalDetails.trim()) {
                finalReason += ': ' + additionalDetails.trim();
            }
            
            // Set the combined reason in the hidden field
            combinedReasonField.value = finalReason;
            console.log('Final reason:', finalReason);
        });
        
        // Handle delete form submission
        document.getElementById('deletePropertyForm').addEventListener('submit', function(e) {
            console.log('Delete form submitted');
            const reason = this.querySelector('select[name="reason"]').value;
            const additionalDetails = this.querySelector('textarea[name="additional_details"]').value;
            
            // Combine reason and additional details
            if (additionalDetails && reason) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'reason';
                hiddenInput.value = reason + ': ' + additionalDetails;
                this.appendChild(hiddenInput);
            }
        });
        
        // Reset forms when modals are closed
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('hidden.bs.modal', function() {
                const forms = this.querySelectorAll('form');
                forms.forEach(form => form.reset());
            });
        });
    </script>
</body>
</html>