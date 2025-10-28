<?php
// HomeHub Admin Database Setup Script
// Run this file once to create admin tables and default admin user

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomeHub Admin Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .setup-container { max-width: 800px; margin: 50px auto; }
        .log-box { background: #1e1e1e; color: #00ff00; padding: 20px; border-radius: 10px; font-family: monospace; height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container setup-container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0"><i class="bi bi-gear"></i> HomeHub Admin System Setup</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Setup Process:</strong> This will create admin tables and default admin user.
                    <br><strong>Default Login:</strong> admin / admin123 (Change after first login!)
                </div>
                
                <div id="setup-log" class="log-box mb-3">
                    <div>HomeHub Admin Setup Console</div>
                    <div>=====================================</div>
                </div>
                
                <button id="start-setup" class="btn btn-primary" onclick="startSetup()">
                    <i class="bi bi-play"></i> Start Setup
                </button>
                <button class="btn btn-success" onclick="window.location.href='admin/login.php'" style="display: none;" id="go-admin">
                    <i class="bi bi-arrow-right"></i> Go to Admin Login
                </button>
            </div>
        </div>
    </div>

    <script>
        function log(message, type = 'info') {
            const logBox = document.getElementById('setup-log');
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? '#ff0000' : type === 'success' ? '#00ff00' : '#ffffff';
            logBox.innerHTML += `<div style="color: ${color}">[${timestamp}] ${message}</div>`;
            logBox.scrollTop = logBox.scrollHeight;
        }

        function startSetup() {
            document.getElementById('start-setup').disabled = true;
            log('Starting setup process...');
            
            // Execute setup
            fetch('setup_admin_db.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    log('✓ Database tables created successfully', 'success');
                    log('✓ Default admin user created', 'success');
                    log('✓ Setup completed!', 'success');
                    log('', 'info');
                    log('You can now login with:', 'info');
                    log('Username: admin', 'info');
                    log('Password: admin123', 'info');
                    log('⚠️  Please change the password after first login!', 'error');
                    
                    document.getElementById('go-admin').style.display = 'inline-block';
                } else {
                    log('✗ Setup failed: ' + data.message, 'error');
                    document.getElementById('start-setup').disabled = false;
                }
            })
            .catch(error => {
                log('✗ Error: ' + error.message, 'error');
                document.getElementById('start-setup').disabled = false;
            });
        }
    </script>
</body>
</html>

<?php
// If this is a POST request, run the setup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any output buffer and set JSON header
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        require_once 'config/db_connect.php';
        $conn = getDbConnection();
        
        // Start transaction
        $conn->autocommit(false);
        
        // Admin users table
        $conn->query("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Admin activity log table
        $conn->query("
            CREATE TABLE IF NOT EXISTS admin_activity_log (
                id INT PRIMARY KEY AUTO_INCREMENT,
                admin_id INT,
                action VARCHAR(100) NOT NULL,
                target_type ENUM('user', 'property', 'booking', 'system', 'admin') NOT NULL,
                target_id INT,
                details JSON,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_action (admin_id, action),
                INDEX idx_target (target_type, target_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Platform settings table
        $conn->query("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Reported content table
        $conn->query("
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
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_target (target_type, target_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Check if admin user already exists
        $result = $conn->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
        $adminExists = $result->fetch_assoc()['count'] > 0;
        
        if (!$adminExists) {
            // Create default admin user
            $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
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
            
            $stmt = $conn->prepare("
                INSERT INTO admin_users (username, email, password, full_name, role, permissions) 
                VALUES ('admin', 'admin@homehub.com', ?, 'System Administrator', 'super_admin', ?)
            ");
            $stmt->bind_param("ss", $defaultPassword, $permissions);
            $stmt->execute();
        }
        
        // Insert default platform settings if they don't exist
        $settings = [
            ['site_name', 'HomeHub', 'string', 'general', 'Name of the platform', 1],
            ['site_tagline', 'Find Your Perfect Home', 'string', 'general', 'Platform tagline', 1],
            ['maintenance_mode', 'false', 'boolean', 'system', 'Enable maintenance mode', 0],
            ['max_property_images', '10', 'number', 'properties', 'Maximum images per property', 0],
            ['booking_commission', '5.0', 'number', 'financial', 'Platform commission percentage', 0],
            ['support_email', 'support@homehub.com', 'string', 'contact', 'Support email address', 1]
        ];
        
        foreach ($settings as $setting) {
            $stmt = $conn->prepare("
                INSERT IGNORE INTO platform_settings (setting_key, setting_value, setting_type, category, description, is_public) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssssi", $setting[0], $setting[1], $setting[2], $setting[3], $setting[4], $setting[5]);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        $conn->close();
        
        echo json_encode(['success' => true, 'message' => 'Admin system setup completed successfully']);
        
    } catch (Exception $e) {
        // Rollback on error
        if (isset($conn)) {
            $conn->rollback();
            $conn->close();
        }
        echo json_encode(['success' => false, 'message' => 'Setup failed: ' . $e->getMessage()]);
    }
    exit;
}
?>