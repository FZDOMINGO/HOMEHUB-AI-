<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

require_once '../config/db_connect.php';
$conn = getDbConnection();

// Get real-time analytics data
try {
    // User statistics
    $userQuery = "
        SELECT 
            COUNT(CASE WHEN EXISTS(SELECT 1 FROM tenants WHERE user_id = users.id) THEN 1 END) as tenants,
            COUNT(CASE WHEN EXISTS(SELECT 1 FROM landlords WHERE user_id = users.id) THEN 1 END) as landlords,
            COUNT(*) as total_users,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_week
        FROM users
    ";
    $userResult = $conn->query($userQuery);
    if (!$userResult) {
        throw new Exception("User stats query failed: " . $conn->error);
    }
    $userStats = $userResult->fetch_assoc();

    // Property statistics
    $propertyStats = $conn->query("
        SELECT 
            COUNT(*) as total_properties,
            COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
            COUNT(CASE WHEN status = 'rented' THEN 1 END) as rented,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COALESCE(AVG(CASE WHEN rent_amount > 0 THEN rent_amount END), 0) as avg_rent,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today
        FROM properties
    ")->fetch_assoc();

    // Activity statistics (notifications, views, messages)
    $activityStats = [];
    
    // Check for notifications
    $notifResult = $conn->query("SELECT COUNT(*) as table_exists FROM information_schema.tables WHERE table_schema = 'homehub' AND table_name = 'notifications'");
    if ($notifResult->fetch_assoc()['table_exists'] > 0) {
        $notifData = $conn->query("
            SELECT 
                COUNT(*) as total_notifications,
                COUNT(CASE WHEN is_read = 0 THEN 1 END) as unread_notifications,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_notifications
            FROM notifications
        ")->fetch_assoc();
        $activityStats['notifications'] = $notifData;
    } else {
        $activityStats['notifications'] = ['total_notifications' => 0, 'unread_notifications' => 0, 'today_notifications' => 0];
    }

    // Check for property views
    $viewResult = $conn->query("SELECT COUNT(*) as table_exists FROM information_schema.tables WHERE table_schema = 'homehub' AND table_name = 'property_views'");
    if ($viewResult->fetch_assoc()['table_exists'] > 0) {
        $viewData = $conn->query("
            SELECT 
                SUM(views) as total_views,
                SUM(CASE WHEN view_date = CURDATE() THEN views ELSE 0 END) as today_views,
                COUNT(DISTINCT property_id) as unique_properties_viewed
            FROM property_views
        ")->fetch_assoc();
        $activityStats['views'] = $viewData;
    } else {
        $activityStats['views'] = ['total_views' => 0, 'today_views' => 0, 'unique_properties_viewed' => 0];
    }

    // Check for messages
    $msgResult = $conn->query("SELECT COUNT(*) as table_exists FROM information_schema.tables WHERE table_schema = 'homehub' AND table_name = 'messages'");
    if ($msgResult->fetch_assoc()['table_exists'] > 0) {
        $msgData = $conn->query("
            SELECT 
                COUNT(*) as total_messages,
                COUNT(CASE WHEN DATE(sent_at) = CURDATE() THEN 1 END) as today_messages,
                COUNT(DISTINCT sender_id) as active_users
            FROM messages
        ")->fetch_assoc();
        $activityStats['messages'] = $msgData;
    } else {
        $activityStats['messages'] = ['total_messages' => 0, 'today_messages' => 0, 'active_users' => 0];
    }

    // Monthly growth data for users
    $monthlyData = $conn->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            DATE_FORMAT(created_at, '%M %Y') as month_name,
            COUNT(*) as count
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ")->fetch_all(MYSQLI_ASSOC);

    // Daily activity for the last 7 days
    $dailyActivity = $conn->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as user_registrations
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
    ")->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
    $userStats = ['tenants' => 0, 'landlords' => 0, 'total_users' => 0, 'new_today' => 0, 'new_week' => 0];
    $propertyStats = ['total_properties' => 0, 'available' => 0, 'rented' => 0, 'pending' => 0, 'avg_rent' => 0, 'new_today' => 0];
    $activityStats = [
        'notifications' => ['total_notifications' => 0, 'unread_notifications' => 0, 'today_notifications' => 0],
        'views' => ['total_views' => 0, 'today_views' => 0, 'unique_properties_viewed' => 0],
        'messages' => ['total_messages' => 0, 'today_messages' => 0, 'active_users' => 0]
    ];
    $monthlyData = [];
    $dailyActivity = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - HomeHub Admin</title>
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
        
        .metric-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            overflow: hidden;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .chart-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .bg-gradient-primary { background: linear-gradient(45deg, #667eea, #764ba2); }
        .bg-gradient-success { background: linear-gradient(45deg, #56ab2f, #a8e6cf); }
        .bg-gradient-warning { background: linear-gradient(45deg, #f093fb, #f5576c); }
        .bg-gradient-info { background: linear-gradient(45deg, #4facfe, #00f2fe); }
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
                        <a class="nav-link" href="properties.php">
                            <i class="bi bi-house"></i>Properties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="analytics.php">
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
                                <i class="bi bi-graph-up"></i> Analytics Dashboard
                            </h2>
                            <p class="text-muted mb-0">Platform insights and performance metrics</p>
                        </div>

                    </div>
                    
                    <!-- Key Metrics -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card metric-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="metric-icon bg-gradient-primary me-3">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0"><?= number_format($userStats['total_users']) ?></h3>
                                        <small class="text-muted">Total Users</small>
                                        <div class="d-flex mt-1">
                                            <small class="text-success me-2">
                                                <i class="bi bi-person"></i> <?= $userStats['tenants'] ?> Tenants
                                            </small>
                                            <small class="text-info">
                                                <i class="bi bi-house"></i> <?= $userStats['landlords'] ?> Landlords
                                            </small>
                                        </div>
                                        <?php if ($userStats['new_today'] > 0): ?>
                                            <div class="mt-1">
                                                <small class="text-primary">
                                                    <i class="bi bi-arrow-up"></i> <?= $userStats['new_today'] ?> new today
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card metric-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="metric-icon bg-gradient-success me-3">
                                        <i class="bi bi-house"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0"><?= number_format($propertyStats['total_properties']) ?></h3>
                                        <small class="text-muted">Properties Listed</small>
                                        <div class="d-flex mt-1">
                                            <small class="text-success me-2">
                                                <?= $propertyStats['available'] ?> Available
                                            </small>
                                            <small class="text-warning me-2">
                                                <?= $propertyStats['rented'] ?> Rented
                                            </small>
                                            <?php if ($propertyStats['pending'] > 0): ?>
                                                <small class="text-info">
                                                    <?= $propertyStats['pending'] ?> Pending
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($propertyStats['new_today'] > 0): ?>
                                            <div class="mt-1">
                                                <small class="text-primary">
                                                    <i class="bi bi-arrow-up"></i> <?= $propertyStats['new_today'] ?> new today
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card metric-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="metric-icon bg-gradient-warning me-3">
                                        <i class="bi bi-eye"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0"><?= number_format($activityStats['views']['total_views']) ?></h3>
                                        <small class="text-muted">Property Views</small>
                                        <div class="d-flex mt-1">
                                            <small class="text-success me-2">
                                                <?= $activityStats['views']['today_views'] ?> Today
                                            </small>
                                            <small class="text-info">
                                                <?= $activityStats['views']['unique_properties_viewed'] ?> Properties
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card metric-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="metric-icon bg-gradient-info me-3">
                                        <i class="bi bi-chat-dots"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0"><?= number_format($activityStats['messages']['total_messages']) ?></h3>
                                        <small class="text-muted">Messages</small>
                                        <div class="d-flex mt-1">
                                            <small class="text-success me-2">
                                                <?= $activityStats['messages']['today_messages'] ?> Today
                                            </small>
                                            <small class="text-info">
                                                <?= $activityStats['messages']['active_users'] ?> Active Users
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Metrics Row -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card metric-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="metric-icon bg-gradient-warning me-3">
                                        <i class="bi bi-bell"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0"><?= number_format($activityStats['notifications']['total_notifications']) ?></h3>
                                        <small class="text-muted">Notifications</small>
                                        <div class="d-flex mt-1">
                                            <small class="text-danger me-2">
                                                <?= $activityStats['notifications']['unread_notifications'] ?> Unread
                                            </small>
                                            <small class="text-success">
                                                <?= $activityStats['notifications']['today_notifications'] ?> Today
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card metric-card">
                                <div class="card-body d-flex align-items-center">
                                    <div class="metric-icon bg-gradient-primary me-3">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h3 class="mb-0"><?= number_format($userStats['new_week']) ?></h3>
                                        <small class="text-muted">New Users (7 days)</small>
                                        <div class="mt-1">
                                            <small class="text-info">
                                                <?php 
                                                $growth_rate = $userStats['total_users'] > 0 ? ($userStats['new_week'] / $userStats['total_users']) * 100 : 0;
                                                echo number_format($growth_rate, 1) . "% growth rate";
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-lg-8 mb-4">
                            <div class="card chart-card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-graph-up-arrow"></i> User Growth Trend
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="growthChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4 mb-4">
                            <div class="card chart-card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-pie-chart"></i> Property Status
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="propertyChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Tables -->
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-trophy"></i> Top Performers
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <h6 class="text-muted mb-3">Most Active Landlords</h6>
                                    <div class="placeholder-glow">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="placeholder col-4"></span>
                                            <span class="badge bg-success placeholder col-2"></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="placeholder col-5"></span>
                                            <span class="badge bg-primary placeholder col-2"></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="placeholder col-3"></span>
                                            <span class="badge bg-warning placeholder col-2"></span>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6 class="text-muted mb-3">Popular Property Types</h6>
                                    <div class="placeholder-glow">
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <span class="placeholder col-3"></span>
                                                <span class="placeholder col-2"></span>
                                            </div>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar bg-success" style="width: 65%"></div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <span class="placeholder col-4"></span>
                                                <span class="placeholder col-2"></span>
                                            </div>
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar bg-primary" style="width: 45%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-activity"></i> Real-Time Platform Activity
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-4 mb-3">
                                            <div class="border rounded p-3">
                                                <div class="h4 text-primary mb-0"><?= $userStats['new_today'] ?></div>
                                                <small class="text-muted">New Users Today</small>
                                            </div>
                                        </div>
                                        <div class="col-4 mb-3">
                                            <div class="border rounded p-3">
                                                <div class="h4 text-success mb-0"><?= $propertyStats['new_today'] ?></div>
                                                <small class="text-muted">Properties Added Today</small>
                                            </div>
                                        </div>
                                        <div class="col-4 mb-3">
                                            <div class="border rounded p-3">
                                                <div class="h4 text-info mb-0"><?= $activityStats['views']['today_views'] ?></div>
                                                <small class="text-muted">Page Views Today</small>
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
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('../api/admin/logout.php', { method: 'POST' })
                .then(() => window.location.href = 'login.php');
            }
        }
        
        // Growth Chart
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        const monthlyLabels = <?= json_encode(array_column($monthlyData, 'month_name')) ?>;
        const monthlyValues = <?= json_encode(array_column($monthlyData, 'count')) ?>;
        
        // If no data, show last 6 months with 0 values
        const chartLabels = monthlyLabels.length > 0 ? monthlyLabels : ['6 months ago', '5 months ago', '4 months ago', '3 months ago', '2 months ago', 'Last month'];
        const chartData = monthlyValues.length > 0 ? monthlyValues : [0, 0, 0, 0, 0, 0];
        
        new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'New User Registrations',
                    data: chartData,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'New Users: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Property Status Chart
        const propertyCtx = document.getElementById('propertyChart').getContext('2d');
        const availableCount = <?= $propertyStats['available'] ?? 0 ?>;
        const rentedCount = <?= $propertyStats['rented'] ?? 0 ?>;
        const pendingCount = <?= $propertyStats['pending'] ?? 0 ?>;
        const totalProperties = availableCount + rentedCount + pendingCount;
        
        // Show message if no properties
        if (totalProperties === 0) {
            document.getElementById('propertyChart').style.display = 'none';
            document.querySelector('#propertyChart').parentNode.innerHTML = '<div class="text-center py-4"><p class="text-muted">No properties listed yet</p></div>';
        } else {
            new Chart(propertyCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'Rented', 'Pending'],
                    datasets: [{
                        data: [availableCount, rentedCount, pendingCount],
                        backgroundColor: ['#28a745', '#ffc107', '#6c757d'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const percentage = ((value / totalProperties) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
        

    </script>
</body>
</html>