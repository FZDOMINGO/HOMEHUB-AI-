<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

require_once '../config/db_connect.php';

// Get admin info
$adminName = $_SESSION['admin_name'];
$adminRole = $_SESSION['admin_role'];
$adminPermissions = $_SESSION['admin_permissions'];

// Fetch dashboard statistics
$conn = getDbConnection();

// Get user counts
$tenantCount = $conn->query("SELECT COUNT(*) as count FROM tenants")->fetch_assoc()['count'];
$landlordCount = $conn->query("SELECT COUNT(*) as count FROM landlords")->fetch_assoc()['count'];

// Get property counts
$propertyCount = $conn->query("SELECT COUNT(*) as count FROM properties")->fetch_assoc()['count'];
$activeProperties = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status = 'available'")->fetch_assoc()['count'];
$suspendedProperties = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status = 'suspended'")->fetch_assoc()['count'];



// Get recent activity
$recentActivities = $conn->query("
    SELECT al.action, al.details, al.created_at, au.username 
    FROM admin_activity_log al 
    LEFT JOIN admin_users au ON al.admin_id = au.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HomeHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css" rel="stylesheet">
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
        
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .bg-primary-gradient { background: linear-gradient(45deg, #667eea, #764ba2); }
        .bg-success-gradient { background: linear-gradient(45deg, #56ab2f, #a8e6cf); }
        .bg-warning-gradient { background: linear-gradient(45deg, #f093fb, #f5576c); }
        .bg-info-gradient { background: linear-gradient(45deg, #4facfe, #00f2fe); }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .activity-item {
            padding: 0.75rem;
            border-left: 3px solid #667eea;
            background: white;
            border-radius: 0 10px 10px 0;
            margin-bottom: 0.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .navbar-brand {
            font-weight: 700;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
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
                    <small class="text-white-50">HomeHub Management</small>
                </div>
                
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
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
                <!-- Top Navigation -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
                    <div class="container-fluid">
                        <a class="navbar-brand" href="#">
                            <i class="bi bi-house-heart-fill"></i>
                            HomeHub Admin
                        </a>
                        
                        <div class="d-flex align-items-center">
                            <div class="admin-avatar me-3">
                                <?= strtoupper(substr($adminName, 0, 2)) ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($adminName) ?></div>
                                <small class="text-muted"><?= ucfirst($adminRole) ?></small>
                            </div>
                        </div>
                    </div>
                </nav>
                
                <div class="container-fluid">
                    <!-- Welcome Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="bg-white p-4 rounded-3 shadow-sm">
                                <h2 class="mb-1">Welcome back, <?= htmlspecialchars(explode(' ', $adminName)[0]) ?>! 👋</h2>
                                <p class="text-muted mb-0">Here's what's happening with HomeHub today.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-primary-gradient text-white me-3">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($tenantCount + $landlordCount) ?></h3>
                                        <small class="text-muted">Total Users</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-success-gradient text-white me-3">
                                        <i class="bi bi-house"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($propertyCount) ?></h3>
                                        <small class="text-muted">Total Properties</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="stats-icon bg-warning-gradient text-white me-3">
                                        <i class="bi bi-ban"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= number_format($suspendedProperties) ?></h3>
                                        <small class="text-muted">Suspended Properties</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts and Activities Row -->
                    <div class="row">
                        <!-- Quick Stats Chart -->
                        <div class="col-lg-8 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-bar-chart"></i> Platform Overview
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="overviewChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="col-lg-4 mb-4">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-activity"></i> Recent Activity
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="activity-list" style="max-height: 300px; overflow-y: auto;">
                                        <?php foreach ($recentActivities as $activity): ?>
                                            <div class="activity-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong><?= htmlspecialchars($activity['username'] ?? 'System') ?></strong>
                                                        <div class="small text-muted">
                                                            <?= ucfirst(str_replace('_', ' ', $activity['action'])) ?>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= date('M j, H:i', strtotime($activity['created_at'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-transparent">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-lightning"></i> Quick Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <a href="users.php" class="btn btn-outline-primary w-100 p-3 d-flex flex-column justify-content-center align-items-center" style="min-height: 80px;">
                                                <i class="bi bi-people d-block mb-1"></i>
                                                <small>Manage Users</small>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a href="properties.php" class="btn btn-outline-success w-100 p-3 d-flex flex-column justify-content-center align-items-center" style="min-height: 80px;">
                                                <i class="bi bi-house-add d-block mb-1"></i>
                                                <small>Manage Properties</small>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a href="analytics.php" class="btn btn-outline-info w-100 p-3 d-flex flex-column justify-content-center align-items-center" style="min-height: 80px;">
                                                <i class="bi bi-graph-up d-block mb-1"></i>
                                                <small>Analytics</small>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a href="settings.php" class="btn btn-outline-secondary w-100 p-3 d-flex flex-column justify-content-center align-items-center" style="min-height: 80px;">
                                                <i class="bi bi-gear d-block mb-1"></i>
                                                <small>Settings</small>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a href="#" onclick="showSystemInfo()" class="btn btn-outline-dark w-100 p-3 d-flex flex-column justify-content-center align-items-center" style="min-height: 80px;">
                                                <i class="bi bi-info-circle d-block mb-1"></i>
                                                <small>System Info</small>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Site Preview Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-eye"></i> Site Preview
                                        <small class="opacity-75 ms-2">View the site as a guest visitor</small>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row justify-content-center">
                                        <!-- Guest View -->
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 border-primary">
                                                <div class="card-body text-center">
                                                    <i class="bi bi-person-circle display-3 text-primary mb-3"></i>
                                                    <h4>Guest View</h4>
                                                    <p class="text-muted mb-4">See the site as a visitor without logging out of admin</p>
                                                    <a href="preview.php?type=guest&page=guest" class="btn btn-primary btn-lg">
                                                        <i class="bi bi-eye"></i> Preview Site as Guest
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Quick Links Row -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="bi bi-link-45deg"></i> Quick Preview Links
                                                    </h6>
                                                    <div class="row justify-content-center">
                                                        <div class="col-md-3 col-sm-6 mb-2">
                                                            <a href="preview.php?type=guest&page=properties" class="btn btn-sm btn-outline-primary w-100">
                                                                <i class="bi bi-house"></i> Browse Properties
                                                            </a>
                                                        </div>
                                                        <div class="col-md-3 col-sm-6 mb-2">
                                                            <a href="preview.php?type=guest&page=ai-features" class="btn btn-sm btn-outline-info w-100">
                                                                <i class="bi bi-robot"></i> AI Features
                                                            </a>
                                                        </div>
                                                        <div class="col-md-3 col-sm-6 mb-2">
                                                            <a href="preview.php?type=guest&page=guest" class="btn btn-sm btn-outline-success w-100">
                                                                <i class="bi bi-house-heart"></i> Landing Page
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Preview Instructions -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="alert alert-light border-info">
                                                <div class="d-flex">
                                                    <i class="bi bi-info-circle text-info me-2 mt-1"></i>
                                                    <div>
                                                        <strong>How Guest Preview Works:</strong>
                                                        <ul class="mb-0 mt-1">
                                                            <li>Click "Preview Site as Guest" to see the site as a visitor</li>
                                                            <li>A preview banner will appear showing you're in guest mode</li>
                                                            <li>Click "Exit Preview" or "Admin Dashboard" to return to admin panel</li>
                                                            <li>Your admin session remains secure throughout the preview</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <script>
        // Overview Chart
        const ctx = document.getElementById('overviewChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Tenants', 'Landlords', 'Active Properties', 'Suspended Properties'],
                datasets: [{
                    data: [<?= $tenantCount ?>, <?= $landlordCount ?>, <?= $activeProperties ?>, <?= $suspendedProperties ?>],
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#56ab2f',
                        '#ff6b6b'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
        
        // Logout function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('../api/admin/logout.php', {
                    method: 'POST'
                })
                .then(() => {
                    window.location.href = 'login.php';
                });
            }
        }
        
        // System info modal
        function showSystemInfo() {
            alert('HomeHub Admin Panel v2.0\nPHP Version: <?= phpversion() ?>\nMySQL Connection: Active\nAI Services: Running');
        }
    </script>
</body>
</html>