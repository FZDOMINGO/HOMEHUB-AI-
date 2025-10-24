-- HomeHub Admin System Database Schema
-- Creates admin functionality for platform management

-- =====================================================
-- 1. ADMIN USERS TABLE
-- Stores admin user credentials and permissions
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    
    -- Permission levels
    role ENUM('super_admin', 'moderator', 'support') DEFAULT 'moderator',
    permissions JSON COMMENT 'Specific permissions array',
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    
    -- Profile
    profile_image VARCHAR(255),
    phone VARCHAR(20),
    
    -- Audit
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT REFERENCES admin_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. ADMIN ACTIVITY LOG TABLE
-- Tracks all admin actions for audit purposes
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type ENUM('user', 'property', 'booking', 'system', 'admin') NOT NULL,
    target_id INT,
    details JSON COMMENT 'Action details and changes',
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_admin_action (admin_id, action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. PLATFORM SETTINGS TABLE
-- Stores configurable platform settings
-- =====================================================
CREATE TABLE IF NOT EXISTS platform_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    category VARCHAR(50) DEFAULT 'general',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE COMMENT 'Can be accessed by non-admin users',
    
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. REPORTED CONTENT TABLE
-- Stores user reports about properties, users, etc.
-- =====================================================
CREATE TABLE IF NOT EXISTS reported_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reporter_id INT NOT NULL COMMENT 'User who reported',
    reporter_type ENUM('tenant', 'landlord') NOT NULL,
    
    target_type ENUM('property', 'user', 'message', 'review') NOT NULL,
    target_id INT NOT NULL,
    
    reason ENUM('spam', 'inappropriate', 'fraud', 'fake_listing', 'harassment', 'other') NOT NULL,
    description TEXT,
    evidence JSON COMMENT 'Screenshots, links, etc.',
    
    status ENUM('pending', 'under_review', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_notes TEXT,
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (resolved_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. SYSTEM NOTIFICATIONS TABLE
-- For platform-wide announcements and maintenance notices
-- =====================================================
CREATE TABLE IF NOT EXISTS system_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error', 'maintenance') DEFAULT 'info',
    
    -- Targeting
    target_audience ENUM('all', 'tenants', 'landlords', 'specific') DEFAULT 'all',
    target_users JSON COMMENT 'Specific user IDs if target_audience is specific',
    
    -- Display settings
    is_active BOOLEAN DEFAULT TRUE,
    is_dismissible BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 1 COMMENT '1=low, 5=critical',
    
    -- Scheduling
    show_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    show_until TIMESTAMP NULL,
    
    -- Audit
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert default admin user (username: admin, password: admin123)
-- CHANGE THIS PASSWORD IN PRODUCTION!
-- =====================================================
INSERT INTO admin_users (username, email, password, full_name, role, permissions) VALUES 
('admin', 'admin@homehub.com', '$2y$10$8K1p/a9Y8/b7fUCmsDWdk.VvmHeDGoRqQ8JiMl7Qa5m8k3u2lqfse', 'System Administrator', 'super_admin', 
 JSON_OBJECT(
     'manage_users', true,
     'manage_properties', true,
     'manage_bookings', true,
     'view_analytics', true,
     'manage_admins', true,
     'system_settings', true,
     'moderate_content', true,
     'handle_reports', true
 ));

-- =====================================================
-- Insert default platform settings
-- =====================================================
INSERT INTO platform_settings (setting_key, setting_value, setting_type, category, description, is_public) VALUES
('site_name', 'HomeHub', 'string', 'general', 'Name of the platform', true),
('site_tagline', 'Find Your Perfect Home', 'string', 'general', 'Platform tagline', true),
('maintenance_mode', 'false', 'boolean', 'system', 'Enable maintenance mode', false),
('max_property_images', '10', 'number', 'properties', 'Maximum images per property', false),
('booking_commission', '5.0', 'number', 'financial', 'Platform commission percentage', false),
('auto_approve_properties', 'false', 'boolean', 'moderation', 'Auto-approve new properties', false),
('support_email', 'support@homehub.com', 'string', 'contact', 'Support email address', true),
('max_file_size', '5242880', 'number', 'uploads', 'Max file size in bytes (5MB)', false);