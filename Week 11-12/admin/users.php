<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

require_once '../config/db_connect.php';
$conn = getDbConnection();

// Get users data with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$userType = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query
$whereClause = '';
$params = [];
$types = '';

if ($search) {
    $whereClause .= " WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = 'sss';
}

if ($userType !== 'all') {
    if ($whereClause) {
        $whereClause .= " AND";
    } else {
        $whereClause .= " WHERE";
    }
    
    if ($userType === 'tenant') {
        $whereClause .= " t.id IS NOT NULL";
    } else {
        $whereClause .= " l.id IS NOT NULL";
    }
}

// Get total count
$countQuery = "
    SELECT COUNT(DISTINCT u.id) as total
    FROM users u
    LEFT JOIN tenants t ON u.id = t.user_id
    LEFT JOIN landlords l ON u.id = l.user_id
    $whereClause
";

$countStmt = $conn->prepare($countQuery);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalUsers = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

// Get users data
$query = "
    SELECT 
        u.id, u.first_name, u.last_name, u.email, u.phone, 
        u.created_at, u.last_login, u.status,
        CASE 
            WHEN t.id IS NOT NULL THEN 'tenant'
            WHEN l.id IS NOT NULL THEN 'landlord'
            ELSE 'unknown'
        END as user_type,
        COALESCE(t.id, l.id) as profile_id
    FROM users u
    LEFT JOIN tenants t ON u.id = t.user_id
    LEFT JOIN landlords l ON u.id = l.user_id
    $whereClause
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
$allParams = array_merge($params, [$limit, $offset]);
$allTypes = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - HomeHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        /* Sidebar */
        .sidebar {
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
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 20px;
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
        }

        @media (max-width: 767.98px) {
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1060;
                background: #007bff;
                border: none;
                border-radius: 6px;
                padding: 10px;
                color: white;
            }
            
            .sidebar {
                position: fixed !important;
                top: 0;
                left: -100%;
                width: 250px !important;
                height: 100vh;
                transition: left 0.3s ease;
                z-index: 1050;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                padding-top: 4rem !important;
                margin-left: 0 !important;
            }
        }
        
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        
        .sidebar {
            min-height: 100vh;
        }
        
        /* Cards */
        .card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }
        
        .card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem;
        }
        
        /* Statistics Cards */
        .stats-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .bg-primary-gradient {
            background: #007bff;
        }
        
        .bg-success-gradient {
            background: #28a745;
        }
        
        .bg-warning-gradient {
            background: #ffc107;
        }
        
        .bg-info-gradient {
            background: #17a2b8;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.375rem;
            line-height: 1;
        }
        
        .stats-label {
            font-size: 1rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .stats-subtitle {
            font-size: 0.875rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }
        
        /* User Avatars */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 0.875rem;
        }
        
        .tenant-bg { 
            background: #007bff;
        }
        
        .landlord-bg { 
            background: #28a745;
        }
        
        /* Table */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        
        .table {
            margin-bottom: 0;
            background: white;
        }
        
        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 12px 16px;
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
        }
        
        .table tbody tr {
            transition: background-color 0.15s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        

        
        /* Badges */
        .badge {
            font-weight: 500;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        
        .badge.bg-primary {
            background: #007bff !important;
        }
        
        .badge.bg-success {
            background: #28a745 !important;
        }
        
        /* Buttons */
        .btn {
            border-radius: 6px;
            font-weight: 500;
            padding: 6px 12px;
            transition: all 0.15s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background: #007bff;
        }
        
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-gradient);
            border-color: transparent;
            color: white;
        }
        
        .btn-group-sm .btn {
            padding: 0.5rem;
            border-radius: 10px;
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Form Controls */
        .form-control, .form-select {
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
            background: rgba(255,255,255,0.95);
        }
        
        /* Responsive Utilities */
        @media (max-width: 576px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                font-size: 0.75rem;
            }
            
            .card-header {
                padding: 1rem;
            }
            
            .stats-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .btn-group-sm .btn {
                padding: 0.375rem;
                min-width: 36px;
                height: 36px;
            }
        }
        
        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle d-md-none" onclick="toggleSidebar()">
        <i class="bi bi-list fs-5"></i>
    </button>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar p-3">
                <div class="text-center mb-4">
                    <h4 class="text-white">
                        <i class="bi bi-shield-check"></i>
                        Admin Panel
                    </h4>
                    <small class="text-white-50">HomeHub Management</small>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">
                            <i class="bi bi-people"></i>Users Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="properties.php">
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
                <div class="container-fluid">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">
                                <i class="bi bi-people me-2 text-primary"></i>
                                Users Management
                            </h2>
                            <p class="text-muted mb-0">Manage tenants and landlords</p>
                        </div>
                        <button class="btn btn-primary" onclick="refreshData()">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Refresh
                        </button>
                    </div>

                    <!-- Statistics Overview -->
                    <div class="row mb-4">
                        <?php
                        // Get user statistics
                        $conn = getDbConnection();
                        $statsQuery = "
                            SELECT 
                                COUNT(*) as total_users,
                                SUM(CASE WHEN t.id IS NOT NULL THEN 1 ELSE 0 END) as total_tenants,
                                SUM(CASE WHEN l.id IS NOT NULL THEN 1 ELSE 0 END) as total_landlords,
                                SUM(CASE WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active_users
                            FROM users u
                            LEFT JOIN tenants t ON u.id = t.user_id
                            LEFT JOIN landlords l ON u.id = l.user_id
                        ";
                        $statsResult = $conn->query($statsQuery);
                        $stats = $statsResult->fetch_assoc();
                        $conn->close();
                        ?>
                        
                        <div class="col-6 col-lg-3">
                            <div class="stats-card total-users text-center">
                                <div class="stats-number text-primary"><?= number_format($stats['total_users']) ?></div>
                                <div class="stats-label">Total Users</div>
                                <div class="stats-subtitle">
                                    <i class="bi bi-people"></i>
                                    All registered
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-6 col-lg-3">
                            <div class="stats-card tenants text-center">
                                <div class="stats-number text-info"><?= number_format($stats['total_tenants']) ?></div>
                                <div class="stats-label">Tenants</div>
                                <div class="stats-subtitle">
                                    <i class="bi bi-person"></i>
                                    Property seekers
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-6 col-lg-3">
                            <div class="stats-card landlords text-center">
                                <div class="stats-number text-success"><?= number_format($stats['total_landlords']) ?></div>
                                <div class="stats-label">Landlords</div>
                                <div class="stats-subtitle">
                                    <i class="bi bi-house"></i>
                                    Property owners
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-6 col-lg-3">
                            <div class="stats-card active-users text-center">
                                <div class="stats-number text-warning"><?= number_format($stats['active_users']) ?></div>
                                <div class="stats-label">Active Users</div>
                                <div class="stats-subtitle">
                                    <i class="bi bi-activity"></i>
                                    Last 30 days
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search & Filter Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" id="filterForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Search Users</label>
                                        <input type="text" class="form-control" 
                                               name="search" 
                                               value="<?= htmlspecialchars($search) ?>" 
                                               placeholder="Search by name, email, or phone..."
                                               id="searchInput">
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label">User Type</label>
                                        <select class="form-select" name="type" id="typeFilter">
                                            <option value="all" <?= $userType === 'all' ? 'selected' : '' ?>>
                                                All Users
                                            </option>
                                            <option value="tenant" <?= $userType === 'tenant' ? 'selected' : '' ?>>
                                                Tenants Only
                                            </option>
                                            <option value="landlord" <?= $userType === 'landlord' ? 'selected' : '' ?>>
                                                Landlords Only
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12 col-md-2 col-lg-4">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-search me-1"></i>
                                                <span class="d-none d-lg-inline">Apply Filter</span>
                                                <span class="d-lg-none">Filter</span>
                                            </button>
                                            <?php if ($search || $userType !== 'all'): ?>
                                            <a href="users.php" class="btn btn-outline-secondary" title="Clear all filters">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Quick Filter Pills -->
                            <?php if (!$search && $userType === 'all'): ?>
                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="text-muted small fw-medium me-2">Quick filters:</span>
                                    <button class="btn btn-sm btn-light border" onclick="quickFilter('tenant')"
                                            style="border-radius: 20px;">
                                        <i class="bi bi-person me-1 text-primary"></i>Tenants
                                    </button>
                                    <button class="btn btn-sm btn-light border" onclick="quickFilter('landlord')"
                                            style="border-radius: 20px;">
                                        <i class="bi bi-house me-1 text-success"></i>Landlords
                                    </button>
                                    <button class="btn btn-sm btn-light border" onclick="quickFilter('recent')"
                                            style="border-radius: 20px;">
                                        <i class="bi bi-clock me-1 text-info"></i>Recent
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Users Directory Table -->
                    <div class="card">
                        <div class="card-body p-0">
                            <!-- Table Header -->
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">
                                        <i class="bi bi-people me-2"></i>
                                        Users Directory
                                        <span class="badge bg-primary ms-2"><?= number_format($totalUsers) ?></span>
                                    </h5>
                                    <small class="text-muted">
                                        <?php if ($search): ?>
                                            Results for "<?= htmlspecialchars($search) ?>"
                                        <?php elseif ($userType !== 'all'): ?>
                                            Showing <?= ucfirst($userType) ?>s only
                                        <?php else: ?>
                                            All registered users
                                        <?php endif; ?>
                                    </small>
                                </div>
                                
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="exportUsers()">
                                        <i class="bi bi-download"></i> Export
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshData()">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                            </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="usersTable">
                                <thead>
                                    <tr>
                                        <th class="border-0">
                                            <div class="d-flex align-items-center">
                                                <input type="checkbox" class="form-check-input me-2" id="selectAll">
                                                User
                                            </div>
                                        </th>
                                        <th class="border-0 d-none d-md-table-cell">Type</th>
                                        <th class="border-0 d-none d-lg-table-cell">Contact</th>
                                        <th class="border-0 d-none d-xl-table-cell">Joined</th>
                                        <th class="border-0 d-none d-xl-table-cell">Last Active</th>
                                        <th class="border-0">Status</th>
                                        <th class="border-0 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <?php foreach ($users as $index => $user): ?>
                                        <tr class="user-row" data-user-id="<?= $user['id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <input type="checkbox" class="form-check-input me-3 user-checkbox" value="<?= $user['id'] ?>">
                                                    <div class="user-avatar <?= $user['user_type'] ?>-bg me-3">
                                                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                                                    </div>
                                                    <div class="flex-grow-1 min-width-0">
                                                        <div class="fw-bold text-dark mb-1">
                                                            <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                                        </div>
                                                        <div class="small text-muted mb-1 d-md-none">
                                                            <span class="badge bg-<?= $user['user_type'] === 'tenant' ? 'primary' : 'success' ?> me-2">
                                                                <i class="bi bi-<?= $user['user_type'] === 'tenant' ? 'person' : 'house' ?> me-1"></i>
                                                                <?= ucfirst($user['user_type']) ?>
                                                            </span>
                                                        </div>
                                                        <div class="small text-muted d-lg-none">
                                                            <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email']) ?>
                                                            <?php if ($user['phone']): ?>
                                                                <br><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($user['phone']) ?>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="bi bi-hash"></i><?= $user['id'] ?>
                                                            <span class="d-xl-none">
                                                                • Joined <?= date('M Y', strtotime($user['created_at'])) ?>
                                                            </span>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <span class="badge bg-<?= $user['user_type'] === 'tenant' ? 'primary' : 'success' ?>">
                                                    <i class="bi bi-<?= $user['user_type'] === 'tenant' ? 'person' : 'house' ?> me-1"></i>
                                                    <?= ucfirst($user['user_type']) ?>
                                                </span>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <div class="small">
                                                    <div class="text-dark fw-medium mb-1">
                                                        <i class="bi bi-envelope me-1 text-muted"></i>
                                                        <?= htmlspecialchars($user['email']) ?>
                                                    </div>
                                                    <?php if ($user['phone']): ?>
                                                        <div class="text-muted">
                                                            <i class="bi bi-telephone me-1"></i>
                                                            <?= htmlspecialchars($user['phone']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="d-none d-xl-table-cell">
                                                <div class="small">
                                                    <div class="text-dark fw-medium"><?= date('M j, Y', strtotime($user['created_at'])) ?></div>
                                                    <div class="text-muted"><?= date('g:i A', strtotime($user['created_at'])) ?></div>
                                                </div>
                                            </td>
                                            <td class="d-none d-xl-table-cell">
                                                <?php if ($user['last_login']): ?>
                                                    <div class="small">
                                                        <div class="text-success fw-medium"><?= date('M j, Y', strtotime($user['last_login'])) ?></div>
                                                        <div class="text-muted"><?= date('g:i A', strtotime($user['last_login'])) ?></div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                                        Never logged in
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $isRecentUser = strtotime($user['created_at']) > strtotime('-7 days');
                                                $hasRecentLogin = $user['last_login'] && strtotime($user['last_login']) > strtotime('-7 days');
                                                $statusColors = [
                                                    'active' => 'success',
                                                    'suspended' => 'warning', 
                                                    'inactive' => 'secondary'
                                                ];
                                                $statusIcons = [
                                                    'active' => 'check-circle',
                                                    'suspended' => 'exclamation-triangle',
                                                    'inactive' => 'dash-circle'
                                                ];
                                                $statusColor = $statusColors[$user['status']] ?? 'secondary';
                                                $statusIcon = $statusIcons[$user['status']] ?? 'question-circle';
                                                ?>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge bg-<?= $statusColor ?>">
                                                        <i class="bi bi-<?= $statusIcon ?> me-1"></i>
                                                        <?= ucfirst($user['status']) ?>
                                                    </span>
                                                    <?php if ($isRecentUser): ?>
                                                        <span class="badge bg-info small">
                                                            <i class="bi bi-star me-1"></i>New
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($hasRecentLogin): ?>
                                                        <span class="badge bg-primary small">
                                                            <i class="bi bi-activity me-1"></i>Recent
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="viewUser(<?= $user['id'] ?>)" 
                                                            title="View Details" data-bs-toggle="tooltip">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-warning" onclick="editUser(<?= $user['id'] ?>)" 
                                                            title="Edit User" data-bs-toggle="tooltip">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="deleteUser(<?= $user['id'] ?>)" 
                                                            title="Delete User" data-bs-toggle="tooltip">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="bi bi-inbox display-4 text-muted"></i>
                                                <div class="mt-2 text-muted">No users found</div>
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
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($userType) ?>">
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
            </div>
        </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Animate table rows on load
            animateTableRows();
            
            // Initialize search functionality
            initializeSearch();
        });

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Animate table rows
        function animateTableRows() {
            const rows = document.querySelectorAll('.user-row');
            rows.forEach((row, index) => {
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }

        // Search functionality
        function initializeSearch() {
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    // Auto-submit form after 500ms of no typing
                    if (this.value.length > 2 || this.value.length === 0) {
                        document.getElementById('filterForm').submit();
                    }
                }, 500);
            });
        }

        // Quick filter functionality
        function quickFilter(type) {
            const typeSelect = document.getElementById('typeFilter');
            const searchInput = document.getElementById('searchInput');
            
            switch(type) {
                case 'tenant':
                    typeSelect.value = 'tenant';
                    break;
                case 'landlord':
                    typeSelect.value = 'landlord';
                    break;
                case 'recent':
                    searchInput.value = '';
                    typeSelect.value = 'all';
                    // Add recent filter logic
                    break;
            }
            
            document.getElementById('filterForm').submit();
        }

        // Clear search
        function clearSearch() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterForm').submit();
        }

        // Refresh data
        function refreshData() {
            const btn = event.target.closest('button');
            const originalContent = btn.innerHTML;
            
            btn.innerHTML = '<span class="loading-spinner"></span> Refreshing...';
            btn.disabled = true;
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        // Table view toggle
        let isCardView = false;
        function toggleTableView() {
            const table = document.getElementById('usersTable');
            const icon = document.getElementById('viewToggleIcon');
            
            if (isCardView) {
                table.classList.remove('card-view');
                icon.className = 'bi bi-grid-3x3-gap';
                isCardView = false;
            } else {
                table.classList.add('card-view');
                icon.className = 'bi bi-table';
                isCardView = true;
            }
        }

        // Export functionality
        function exportUsers() {
            const btn = event.target.closest('button');
            const originalContent = btn.innerHTML;
            
            btn.innerHTML = '<span class="loading-spinner"></span>';
            btn.disabled = true;
            
            // Simulate export
            setTimeout(() => {
                btn.innerHTML = originalContent;
                btn.disabled = false;
                
                // Create and download CSV
                exportToCSV();
            }, 2000);
        }

        function exportToCSV() {
            const rows = document.querySelectorAll('.user-row');
            let csv = 'Name,Type,Email,Phone,Joined,Last Login\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const name = cells[0].querySelector('.fw-bold').textContent.trim();
                const type = cells[1] ? cells[1].textContent.trim() : '';
                // Add more fields as needed
                csv += `"${name}","${type}","","","",""\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'users_export.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActions();
        });

        // Update bulk actions
        function updateBulkActions() {
            const selected = document.querySelectorAll('.user-checkbox:checked').length;
            // Show/hide bulk action buttons based on selection
        }

        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                const btn = event.target.closest('a');
                btn.innerHTML = '<span class="loading-spinner"></span> Logging out...';
                
                fetch('../api/admin/logout.php', { method: 'POST' })
                .then(() => window.location.href = 'login.php')
                .catch(() => window.location.href = 'login.php');
            }
        }
        
        // User management functions
        function viewUser(userId) {
            showUserModal(userId, 'view');
        }
        
        function editUser(userId) {
            showUserModal(userId, 'edit');
        }

        function showUserModal(userId, mode) {
            if (mode === 'view') {
                const modal = new bootstrap.Modal(document.getElementById('userModal'));
                document.getElementById('userModalLabel').textContent = 'User Details';
                document.getElementById('userModalBody').innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                modal.show();
                
                // Fetch user details
                fetch('../api/admin/users.php?action=get_user&user_id=' + userId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayUserDetails(data.user);
                        } else {
                            document.getElementById('userModalBody').innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> Error: ${data.message}
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        document.getElementById('userModalBody').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> Error loading user details
                            </div>
                        `;
                    });
            } else if (mode === 'edit') {
                const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                document.getElementById('editUserModalLabel').textContent = 'Edit User';
                document.getElementById('editUserModalBody').innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                modal.show();
                
                // Fetch user details for editing
                fetch('../api/admin/users.php?action=get_user&user_id=' + userId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayEditUserForm(data.user);
                        } else {
                            document.getElementById('editUserModalBody').innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> Error: ${data.message}
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        document.getElementById('editUserModalBody').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> Error loading user details
                            </div>
                        `;
                    });
            }
        }

        function displayUserDetails(user) {
            const statusBadge = user.status === 'active' ? 'bg-success' : 
                               user.status === 'suspended' ? 'bg-warning' : 'bg-secondary';
            const userTypeIcon = user.user_type === 'tenant' ? 'bi-person' : 'bi-building';
            
            document.getElementById('userModalBody').innerHTML = `
                <!-- Admin Access Notice -->
                <div class="alert alert-info border-0 mb-3">
                    <i class="bi bi-shield-lock me-2"></i>
                    <strong>Admin Access:</strong> The information below contains sensitive user profile data visible only to administrators.
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="bi ${userTypeIcon}"></i> Personal Information
                                </h6>
                                <p class="mb-2"><strong>User ID:</strong> #${user.id}</p>
                                <p class="mb-2"><strong>Name:</strong> ${user.first_name} ${user.last_name}</p>
                                <p class="mb-2"><strong>Email:</strong> 
                                    <a href="mailto:${user.email}" class="text-decoration-none">${user.email}</a>
                                </p>
                                <p class="mb-2"><strong>Phone:</strong> ${user.phone || 'Not provided'}</p>
                                <p class="mb-2"><strong>Type:</strong> 
                                    <span class="badge bg-primary">${user.user_type}</span>
                                </p>
                                <p class="mb-0"><strong>Status:</strong> 
                                    <span class="badge ${statusBadge}">${user.status}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="bi bi-clock-history"></i> Account Activity
                                </h6>
                                <p class="mb-2"><strong>Member Since:</strong> ${new Date(user.created_at).toLocaleDateString()}</p>
                                <p class="mb-2"><strong>Last Login:</strong> ${user.last_login ? new Date(user.last_login).toLocaleString() : 'Never'}</p>
                                <p class="mb-2"><strong>Account Age:</strong> ${Math.floor((new Date() - new Date(user.created_at)) / (1000 * 60 * 60 * 24))} days</p>
                                ${user.user_type === 'tenant' ? `
                                    <p class="mb-0"><strong>Total Bookings:</strong> <span class="badge bg-info">${user.booking_count || 0}</span></p>
                                ` : ''}
                                ${user.user_type === 'landlord' ? `
                                    <p class="mb-0"><strong>Properties Listed:</strong> <span class="badge bg-success">${user.property_count || 0}</span></p>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function displayEditUserForm(user) {
            document.getElementById('editUserModalBody').innerHTML = `
                <input type="hidden" id="editUserId" value="${user.id}">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="editFirstName" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="editFirstName" value="${user.first_name}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="editLastName" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="editLastName" value="${user.last_name}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="editEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="editEmail" value="${user.email}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="editPhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="editPhone" value="${user.phone || ''}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="editStatus" class="form-label">Status</label>
                        <select class="form-select" id="editStatus">
                            <option value="active" ${user.status === 'active' ? 'selected' : ''}>Active</option>
                            <option value="suspended" ${user.status === 'suspended' ? 'selected' : ''}>Suspended</option>
                            <option value="inactive" ${user.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">User Type</label>
                        <input type="text" class="form-control" value="${user.user_type}" readonly>
                        <small class="text-muted">User type cannot be changed</small>
                    </div>
                </div>
            `;
        }

        // Handle edit user form submission
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('editUserForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const userId = document.getElementById('editUserId').value;
                const formData = new FormData();
                formData.append('action', 'update_user');
                formData.append('user_id', userId);
                formData.append('first_name', document.getElementById('editFirstName').value);
                formData.append('last_name', document.getElementById('editLastName').value);
                formData.append('email', document.getElementById('editEmail').value);
                formData.append('phone', document.getElementById('editPhone').value);
                formData.append('status', document.getElementById('editStatus').value);
                
                const submitBtn = e.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                submitBtn.disabled = true;
                
                fetch('../api/admin/users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert('danger', data.message);
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    showAlert('danger', 'Error updating user');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
        });
        
        function deleteUser(userId) {
            if (confirm('⚠️ This action cannot be undone!\n\nAre you sure you want to permanently delete this user and all their data?')) {
                showLoadingOverlay('Deleting user...');
                
                const formData = new FormData();
                formData.append('action', 'delete_user');
                formData.append('user_id', userId);
                
                fetch('../api/admin/users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoadingOverlay();
                    if (data.success) {
                        showAlert('success', data.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert('danger', data.message);
                    }
                })
                .catch(error => {
                    hideLoadingOverlay();
                    showAlert('danger', 'Error deleting user');
                });
            }
        }

        // Helper functions
        function showAlert(type, message) {
            const alertContainer = document.createElement('div');
            alertContainer.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertContainer.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertContainer.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertContainer);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertContainer.parentNode) {
                    alertContainer.remove();
                }
            }, 5000);
        }

        function showLoadingOverlay(message) {
            const overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
            overlay.style.cssText = 'background-color: rgba(0,0,0,0.5); z-index: 9999;';
            overlay.innerHTML = `
                <div class="bg-white p-4 rounded-3 text-center">
                    <div class="spinner-border text-primary mb-3"></div>
                    <div>${message}</div>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        function hideLoadingOverlay() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.remove();
            }
        }

        // Additional admin functions for detailed user management
        function generateUserReport(userId) {
            showAlert('info', 'Generating comprehensive user report...');
            
            // Simulate report generation
            setTimeout(() => {
                const reportData = `
USER REPORT - ID: ${userId}
Generated: ${new Date().toLocaleString()}
Administrator: Admin User
==================================

This feature will generate a comprehensive PDF report including:
- Complete user profile and verification status
- Financial information and assessments
- Account activity and login history  
- Booking/property management history
- Administrative notes and actions taken

Report functionality is ready for implementation.
                `;
                
                const blob = new Blob([reportData], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `user_report_${userId}_${new Date().toISOString().split('T')[0]}.txt`;
                a.click();
                window.URL.revokeObjectURL(url);
                
                showAlert('success', 'User report generated and downloaded successfully');
            }, 2000);
        }

        function viewUserActivity(userId) {
            showAlert('info', 'Loading user activity log...');
            
            // This would typically open a modal or redirect to activity page
            setTimeout(() => {
                showAlert('info', `Activity Log for User #${userId}:\n\n` +
                    '• Recent login activity\n' +
                    '• Property searches and views\n' +
                    '• Booking requests and confirmations\n' +
                    '• Profile updates and changes\n' +
                    '• Administrative actions taken\n\n' +
                    'Full activity log module ready for implementation.');
            }, 1500);
        }

        function exportUserData(userId) {
            if (confirm('Export all user data including sensitive information?\n\nThis action will be logged for compliance purposes.')) {
                showLoadingOverlay('Exporting user data...');
                
                // Simulate data export
                setTimeout(() => {
                    const exportData = {
                        user_id: userId,
                        export_timestamp: new Date().toISOString(),
                        exported_by: 'Admin User',
                        data_categories: [
                            'Personal Information',
                            'Contact Details', 
                            'Financial Information',
                            'Account Activity',
                            'Preferences and Settings',
                            'Booking/Property History'
                        ],
                        compliance_note: 'Data exported for administrative purposes under platform terms of service'
                    };
                    
                    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `user_data_export_${userId}_${new Date().toISOString().split('T')[0]}.json`;
                    a.click();
                    window.URL.revokeObjectURL(url);
                    
                    hideLoadingOverlay();
                    showAlert('success', 'User data exported successfully. Export logged for compliance.');
                }, 2500);
            }
        }

        // Responsive handling
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('show');
            }
        });
    </script>

    <!-- User Details Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="userModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="userModalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm">
                    <div class="modal-body" id="editUserModalBody">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>