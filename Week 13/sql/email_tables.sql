-- Email preferences table for HomeHub
CREATE TABLE IF NOT EXISTS email_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    receive_visit_requests TINYINT(1) DEFAULT 1,
    receive_booking_requests TINYINT(1) DEFAULT 1,
    receive_reservation_updates TINYINT(1) DEFAULT 1,
    receive_visit_updates TINYINT(1) DEFAULT 1,
    receive_property_performance TINYINT(1) DEFAULT 1,
    receive_messages TINYINT(1) DEFAULT 1,
    receive_system_notifications TINYINT(1) DEFAULT 1,
    receive_marketing TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMTP configuration table
CREATE TABLE IF NOT EXISTS email_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    smtp_host VARCHAR(255) DEFAULT 'localhost',
    smtp_port INT DEFAULT 25,
    smtp_username VARCHAR(255) DEFAULT '',
    smtp_password VARCHAR(255) DEFAULT '',
    smtp_encryption VARCHAR(10) DEFAULT 'none',
    from_email VARCHAR(255) DEFAULT 'noreply@homehub.com',
    from_name VARCHAR(255) DEFAULT 'HomeHub',
    reply_to_email VARCHAR(255) DEFAULT 'support@homehub.com',
    use_smtp TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default email config
INSERT INTO email_config (id, smtp_host, smtp_port, use_smtp) 
VALUES (1, 'localhost', 25, 0)
ON DUPLICATE KEY UPDATE id=id;
