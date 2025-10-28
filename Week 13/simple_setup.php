<?php
// Simple Admin Setup - Run this once to create admin system
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only respond to POST requests for setup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    header('Content-Type: application/json');
    
    try {
        // Include database connection
        require_once 'config/db_connect.php';
        $conn = getDbConnection();
        
        // Create admin_users table
        $adminUsersTable = "
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('super_admin', 'moderator', 'support') DEFAULT 'moderator',
            permissions JSON,
            is_active BOOLEAN DEFAULT TRUE,
            last_login TIMESTAMP NULL,
            failed_login_attempts INT DEFAULT 0,
            locked_until TIMESTAMP NULL,
            profile_image VARCHAR(255),
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by INT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($adminUsersTable)) {
            throw new Exception("Failed to create admin_users table: " . $conn->error);
        }
        
        // Create admin_activity_log table
        $activityLogTable = "
        CREATE TABLE IF NOT EXISTS admin_activity_log (
            id INT PRIMARY KEY AUTO_INCREMENT,
            admin_id INT,
            action VARCHAR(100) NOT NULL,
            target_type ENUM('user', 'property', 'booking', 'system', 'admin') NOT NULL,
            target_id INT,
            details JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($activityLogTable)) {
            throw new Exception("Failed to create admin_activity_log table: " . $conn->error);
        }
        
        // Create platform_settings table
        $settingsTable = "
        CREATE TABLE IF NOT EXISTS platform_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
            category VARCHAR(50) DEFAULT 'general',
            description TEXT,
            is_public BOOLEAN DEFAULT FALSE,
            updated_by INT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($settingsTable)) {
            throw new Exception("Failed to create platform_settings table: " . $conn->error);
        }
        
        // Create reported_content table
        $reportedTable = "
        CREATE TABLE IF NOT EXISTS reported_content (
            id INT PRIMARY KEY AUTO_INCREMENT,
            reporter_id INT NOT NULL,
            reporter_type ENUM('tenant', 'landlord') NOT NULL,
            target_type ENUM('property', 'user', 'message', 'review') NOT NULL,
            target_id INT NOT NULL,
            reason ENUM('spam', 'inappropriate', 'fraud', 'fake_listing', 'harassment', 'other') NOT NULL,
            description TEXT,
            evidence JSON,
            status ENUM('pending', 'under_review', 'resolved', 'dismissed') DEFAULT 'pending',
            admin_notes TEXT,
            resolved_by INT,
            resolved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($reportedTable)) {
            throw new Exception("Failed to create reported_content table: " . $conn->error);
        }
        
        // Check if default admin exists
        $adminCheck = $conn->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
        $adminExists = $adminCheck->fetch_assoc()['count'] > 0;
        
        if (!$adminExists) {
            // Create default admin user
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $permissions = json_encode([
                'manage_users' => true,
                'manage_properties' => true,
                'manage_bookings' => true,
                'view_analytics' => true,
                'manage_admins' => true,
                'system_settings' => true,
                'moderate_content' => true,
                'handle_reports' => true
            ]);
            
            $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password, full_name, role, permissions) VALUES (?, ?, ?, ?, ?, ?)");
            $username = 'admin';
            $email = 'admin@homehub.com';
            $fullName = 'System Administrator';
            $role = 'super_admin';
            $stmt->bind_param("ssssss", $username, $email, $hashedPassword, $fullName, $role, $permissions);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create default admin user: " . $stmt->error);
            }
            $stmt->close();
        }
        
        // Insert default platform settings
        $defaultSettings = [
            ['site_name', 'HomeHub', 'string', 'general', 'Name of the platform', 1],
            ['site_tagline', 'Find Your Perfect Home', 'string', 'general', 'Platform tagline', 1],
            ['maintenance_mode', 'false', 'boolean', 'system', 'Enable maintenance mode', 0],
            ['max_property_images', '10', 'number', 'properties', 'Maximum images per property', 0],
            ['support_email', 'support@homehub.com', 'string', 'contact', 'Support email address', 1]
        ];
        
        foreach ($defaultSettings as $setting) {
            $stmt = $conn->prepare("INSERT IGNORE INTO platform_settings (setting_key, setting_value, setting_type, category, description, is_public) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $setting[0], $setting[1], $setting[2], $setting[3], $setting[4], $setting[5]);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->close();
        
        ob_end_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Admin system setup completed successfully!',
            'admin_created' => !$adminExists
        ]);
        
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'file' => __FILE__,
            'line' => __LINE__
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeHub Admin Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .setup-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .setup-header {
            background: linear-gradient(45deg, #2c3e50, #34495e);
            color: white;
            padding: 2rem;
            text-align: center;
            border-radius: 15px 15px 0 0;
        }
        .log-box {
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            height: 300px;
            overflow-y: auto;
            font-size: 14px;
        }
        .btn-setup {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            padding: 12px 2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .btn-setup:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-card">
            <div class="setup-header">
                <i class="bi bi-gear-fill display-4 mb-3"></i>
                <h3 class="mb-2">HomeHub Admin Setup</h3>
                <p class="mb-0 opacity-75">Initialize the admin system for your platform</p>
            </div>
            
            <div class="p-4">
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle"></i>
                    <strong>What this does:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Creates admin database tables</li>
                        <li>Sets up default admin user</li>
                        <li>Configures platform settings</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning mb-4">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Default Admin Credentials:</strong><br>
                    Username: <code>admin</code><br>
                    Password: <code>admin123</code><br>
                    <small>⚠️ Change this password after first login!</small>
                </div>
                
                <div id="setup-log" class="log-box mb-4">
                    <div style="color: #00ffff;">HomeHub Admin Setup Console v2.0</div>
                    <div style="color: #ffff00;">=========================================</div>
                    <div style="color: #ffffff;">Ready to setup admin system...</div>
                </div>
                
                <div class="d-grid gap-2">
                    <button id="start-setup" class="btn btn-setup text-white btn-lg" onclick="runSetup()">
                        <i class="bi bi-rocket-takeoff"></i> Start Setup Process
                    </button>
                    <button id="go-admin" class="btn btn-success btn-lg" onclick="goToAdmin()" style="display: none;">
                        <i class="bi bi-shield-check"></i> Go to Admin Login
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function log(message, type = 'info') {
            const logBox = document.getElementById('setup-log');
            const timestamp = new Date().toLocaleTimeString();
            let color = '#ffffff';
            
            switch(type) {
                case 'error': color = '#ff4444'; break;
                case 'success': color = '#44ff44'; break;
                case 'warning': color = '#ffff44'; break;
                case 'info': color = '#44ffff'; break;
            }
            
            logBox.innerHTML += `<div style="color: ${color}">[${timestamp}] ${message}</div>`;
            logBox.scrollTop = logBox.scrollHeight;
        }

        function runSetup() {
            const startBtn = document.getElementById('start-setup');
            startBtn.disabled = true;
            startBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Setting up...';
            
            log('Initializing admin system setup...', 'info');
            log('Creating database tables...', 'info');
            
            fetch('simple_setup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    
                    if (data.success) {
                        log('✓ Database tables created successfully', 'success');
                        log('✓ Admin user configured', 'success');
                        log('✓ Platform settings initialized', 'success');
                        log('', 'info');
                        log('Setup completed successfully! 🎉', 'success');
                        log('', 'info');
                        log('Admin Login Credentials:', 'warning');
                        log('Username: admin', 'warning');
                        log('Password: admin123', 'warning');
                        log('', 'info');
                        log('Please change the password after first login!', 'error');
                        
                        document.getElementById('go-admin').style.display = 'block';
                    } else {
                        log('✗ Setup failed: ' + data.message, 'error');
                        if (data.file && data.line) {
                            log('Error in: ' + data.file + ':' + data.line, 'error');
                        }
                        startBtn.disabled = false;
                        startBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Setup';
                    }
                } catch (e) {
                    log('✗ Invalid response from server', 'error');
                    log('Response: ' + text.substring(0, 200), 'error');
                    startBtn.disabled = false;
                    startBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Setup';
                }
            })
            .catch(error => {
                log('✗ Network error: ' + error.message, 'error');
                startBtn.disabled = false;
                startBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Setup';
            });
        }
        
        function goToAdmin() {
            window.location.href = 'admin/login.php';
        }
    </script>
</body>
</html>