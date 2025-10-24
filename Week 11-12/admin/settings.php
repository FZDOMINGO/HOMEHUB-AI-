<?php
session_start();
require_once '../config/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Get database connection
$conn = getDbConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_settings') {
            // Update general settings
            $settings_to_update = [
                'site_name' => $_POST['setting_site_name'] ?? '',
                'site_tagline' => $_POST['setting_site_tagline'] ?? '',
                'contact_email' => $_POST['setting_contact_email'] ?? '',
                'support_phone' => $_POST['setting_support_phone'] ?? ''
            ];
            
            foreach ($settings_to_update as $key => $value) {
                if (!empty($value)) { // Only update non-empty values
                    $stmt = $conn->prepare("INSERT INTO platform_settings (setting_key, setting_value, setting_type, category, description, is_public, updated_at) VALUES (?, ?, 'string', 'general', ?, 1, NOW()) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
                    $description = ucfirst(str_replace('_', ' ', $key));
                    $stmt->bind_param("ssss", $key, $value, $description, $value);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            $success_message = "Settings updated successfully!";
        } elseif ($_POST['action'] === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = "All password fields are required.";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "New passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $error_message = "New password must be at least 6 characters long.";
            } else {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['admin_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $admin = $result->fetch_assoc();
                $stmt->close();
                
                if (!password_verify($current_password, $admin['password'])) {
                    $error_message = "Current password is incorrect.";
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admin_users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("si", $hashed_password, $_SESSION['admin_id']);
                    $stmt->execute();
                    $stmt->close();
                    
                    $success_message = "Password changed successfully!";
                }
            }
        }
    } catch (Exception $e) {
        $error_message = "Error updating settings: " . $e->getMessage();
    }
}

// Load current settings
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM platform_settings WHERE category = 'general' OR setting_key IN ('contact_email', 'support_phone')");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Get system statistics
$stats = [
    'total_users' => 0,
    'total_properties' => 0,
    'active_bookings' => 0
];

// Count users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $stats['total_users'] = $result->fetch_assoc()['count'];
}

// Count properties - use same logic as API
try {
    // Check if status column exists
    $statusExists = false;
    $result = $conn->query("SHOW COLUMNS FROM properties LIKE 'status'");
    if ($result && $result->num_rows > 0) {
        $statusExists = true;
    }
    
    if ($statusExists) {
        // Try different possible status values for "active" properties
        $possibleActiveStatuses = ['active', 'available', 'published', 'approved', 'enabled'];
        $totalActive = 0;
        
        foreach ($possibleActiveStatuses as $status) {
            $result = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status = '$status'");
            if ($result) {
                $count = (int)$result->fetch_assoc()['count'];
                $totalActive += $count;
            }
        }
        
        // If no properties found with common active statuses, count all properties
        if ($totalActive == 0) {
            $result = $conn->query("SELECT COUNT(*) as count FROM properties");
            if ($result) {
                $stats['total_properties'] = (int)$result->fetch_assoc()['count'];
            }
        } else {
            $stats['total_properties'] = $totalActive;
        }
    } else {
        // No status column, count all properties
        $result = $conn->query("SELECT COUNT(*) as count FROM properties");
        if ($result) {
            $stats['total_properties'] = (int)$result->fetch_assoc()['count'];
        }
    }
} catch (Exception $e) {
    // Fallback: count all properties
    $result = $conn->query("SELECT COUNT(*) as count FROM properties");
    if ($result) {
        $stats['total_properties'] = (int)$result->fetch_assoc()['count'];
    }
}

// Count active bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'");
if ($result) {
    $stats['active_bookings'] = $result->fetch_assoc()['count'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - HomeHub Admin</title>
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
        
        .settings-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .settings-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
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
                        <a class="nav-link" href="analytics.php">
                            <i class="bi bi-graph-up"></i>Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
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
                                <i class="bi bi-gear"></i> Settings
                            </h2>
                            <p class="text-muted mb-0">Configure your HomeHub platform</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <!-- General Settings -->
                        <div class="card settings-card">
                            <div class="card-header settings-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-globe"></i> General Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Site Name</label>
                                        <input type="text" class="form-control" name="setting_site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'HomeHub') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Site Tagline</label>
                                        <input type="text" class="form-control" name="setting_site_tagline" value="<?= htmlspecialchars($settings['site_tagline'] ?? 'Find Your Perfect Home') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Contact Email</label>
                                        <input type="email" class="form-control" name="setting_contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? 'contact@homehub.com') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Support Phone</label>
                                        <input type="tel" class="form-control" name="setting_support_phone" value="<?= htmlspecialchars($settings['support_phone'] ?? '+1 (555) 123-4567') ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Admin Management -->
                        <div class="card settings-card">
                            <div class="card-header settings-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-person-badge"></i> Admin Management
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6>Current Admin: <?= htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Unknown') ?></h6>
                                        <small class="text-muted">Role: <?= ucfirst($_SESSION['admin_role'] ?? 'Administrator') ?></small>
                                        <br><small class="text-muted">Last Login: <?= date('M j, Y H:i') ?></small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                            <i class="bi bi-key"></i> Change Password
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- System Information -->
                        <div class="card settings-card">
                            <div class="card-header settings-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-info-circle"></i> System Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Platform Version:</strong> HomeHub v1.0<br>
                                        <strong>Database:</strong> MySQL 8.0<br>
                                        <strong>PHP Version:</strong> <?= phpversion() ?><br>
                                        <strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Total Users:</strong> <span id="stat-total-users" class="badge bg-primary"><?= number_format($stats['total_users']) ?></span><br>
                                        <strong>Total Properties:</strong> <span id="stat-total-properties" class="badge bg-success"><?= number_format($stats['total_properties']) ?></span><br>
                                        <strong>Active Bookings:</strong> <span id="stat-active-bookings" class="badge bg-warning"><?= number_format($stats['active_bookings']) ?></span><br>
                                        <strong>System Status:</strong> <span id="system-status" class="badge bg-success">Online</span>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> Last updated: <span id="stats-updated">Just now</span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="refreshStats()">
                                                <i class="bi bi-arrow-clockwise"></i> Refresh
                                            </button>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Save Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="modal-header">
                        <h5 class="modal-title" id="changePasswordModalLabel">
                            <i class="bi bi-key"></i> Change Password
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let statsUpdateInterval;
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('../api/admin/logout.php', { method: 'POST' })
                .then(() => window.location.href = 'login.php')
                .catch(() => window.location.href = 'login.php');
            }
        }
        
        // Function to update statistics
        function updateStats(data) {
            document.getElementById('stat-total-users').textContent = new Intl.NumberFormat().format(data.total_users);
            document.getElementById('stat-total-properties').textContent = new Intl.NumberFormat().format(data.total_properties);
            document.getElementById('stat-active-bookings').textContent = new Intl.NumberFormat().format(data.active_bookings);
            
            // Update last updated time
            const now = new Date();
            document.getElementById('stats-updated').textContent = now.toLocaleTimeString();
            
            // Update system status based on successful data fetch
            const statusElement = document.getElementById('system-status');
            statusElement.className = 'badge bg-success';
            statusElement.textContent = 'Online';
        }
        
        // Function to fetch statistics from API
        function refreshStats() {
            const refreshBtn = document.querySelector('button[onclick="refreshStats()"]');
            const originalContent = refreshBtn.innerHTML;
            
            // Show loading state
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Updating...';
            refreshBtn.disabled = true;
            
            fetch('../api/admin/get-stats.php', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
                .then(response => {
                    console.log('Response status:', response.status); // Debug log
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text(); // Get as text first to debug
                })
                .then(text => {
                    console.log('Raw response:', text); // Debug log
                    try {
                        const data = JSON.parse(text);
                        console.log('Parsed data:', data); // Debug log
                        if (data.success) {
                            updateStats(data.data);
                        } else {
                            console.error('API returned error:', data.error);
                            // Fallback: reload the page to get fresh stats
                            if (confirm('Statistics update failed. Reload page to get fresh data?')) {
                                window.location.reload();
                            }
                        }
                    } catch (parseError) {
                        console.error('JSON parse error:', parseError, 'Raw text:', text);
                        // Update system status to show error
                        const statusElement = document.getElementById('system-status');
                        statusElement.className = 'badge bg-warning';
                        statusElement.textContent = 'Warning';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    // Update system status to show offline
                    const statusElement = document.getElementById('system-status');
                    statusElement.className = 'badge bg-danger';
                    statusElement.textContent = 'Offline';
                })
                .finally(() => {
                    // Restore button state
                    refreshBtn.innerHTML = originalContent;
                    refreshBtn.disabled = false;
                });
        }
        
        // Auto-refresh stats every 30 seconds
        function startStatsAutoRefresh() {
            statsUpdateInterval = setInterval(refreshStats, 30000); // 30 seconds
        }
        
        // Stop auto-refresh
        function stopStatsAutoRefresh() {
            if (statsUpdateInterval) {
                clearInterval(statsUpdateInterval);
                statsUpdateInterval = null;
            }
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Clear modal on close
        document.getElementById('changePasswordModal')?.addEventListener('hidden.bs.modal', function() {
            this.querySelector('form').reset();
            const inputs = this.querySelectorAll('input[type="password"]');
            inputs.forEach(input => input.setCustomValidity(''));
        });
        
        // Start auto-refresh when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startStatsAutoRefresh();
            
            // Add CSS for spinning animation
            const style = document.createElement('style');
            style.textContent = `
                .spin {
                    animation: spin 1s linear infinite;
                }
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        });
        
        // Stop auto-refresh when page is hidden/unfocused
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopStatsAutoRefresh();
            } else {
                startStatsAutoRefresh();
            }
        });
    </script>
</body>
</html>
