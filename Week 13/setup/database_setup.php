<?php
/**
 * Database Setup and Migration Script
 * 
 * Creates all required tables with proper structure
 * Safe to run multiple times - won't duplicate tables
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Database Setup</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
h1 { color: #333; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #007bff; }
pre { background: #eee; padding: 10px; border-radius: 4px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
th { background: #007bff; color: white; }
</style></head><body><div class='container'>";

echo "<h1>🗄️ HomeHub Database Setup</h1>";
echo "<p><strong>Environment:</strong> " . (IS_PRODUCTION ? "PRODUCTION" : "DEVELOPMENT") . "</p>";
echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
echo "<hr>";

$errors = [];
$created = [];
$exists = [];

try {
    $conn = getDbConnection();
    echo "<div class='section success'>✅ Database connection successful!</div>";
    
    // Define all tables with their CREATE statements
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `password` varchar(255) NOT NULL,
            `first_name` varchar(100) NOT NULL,
            `last_name` varchar(100) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `user_type` enum('tenant','landlord','admin') NOT NULL,
            `status` enum('active','suspended','pending') DEFAULT 'active',
            `email_verified` tinyint(1) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`),
            KEY `user_type` (`user_type`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'tenants' => "CREATE TABLE IF NOT EXISTS `tenants` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `bio` text DEFAULT NULL,
            `occupation` varchar(100) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`),
            CONSTRAINT `tenants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'landlords' => "CREATE TABLE IF NOT EXISTS `landlords` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `company_name` varchar(255) DEFAULT NULL,
            `bio` text DEFAULT NULL,
            `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`),
            CONSTRAINT `landlords_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'properties' => "CREATE TABLE IF NOT EXISTS `properties` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `landlord_id` int(11) DEFAULT NULL,
            `user_id` int(11) DEFAULT NULL,
            `title` varchar(255) NOT NULL,
            `description` text NOT NULL,
            `address` varchar(500) NOT NULL,
            `city` varchar(100) NOT NULL,
            `state` varchar(100) DEFAULT NULL,
            `zip_code` varchar(20) DEFAULT NULL,
            `property_type` enum('apartment','house','condo','commercial','studio','townhouse') NOT NULL,
            `bedrooms` int(11) NOT NULL DEFAULT 1,
            `bathrooms` decimal(3,1) NOT NULL DEFAULT 1.0,
            `square_feet` int(11) DEFAULT NULL,
            `rent_amount` decimal(10,2) NOT NULL,
            `deposit_amount` decimal(10,2) DEFAULT NULL,
            `available_from` date DEFAULT NULL,
            `lease_term` varchar(50) DEFAULT NULL,
            `status` enum('available','occupied','maintenance','suspended') DEFAULT 'available',
            `features` text DEFAULT NULL,
            `rules` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `landlord_id` (`landlord_id`),
            KEY `user_id` (`user_id`),
            KEY `status` (`status`),
            KEY `property_type` (`property_type`),
            KEY `city` (`city`),
            CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `landlords` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'property_images' => "CREATE TABLE IF NOT EXISTS `property_images` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `property_id` int(11) NOT NULL,
            `image_url` varchar(500) NOT NULL,
            `is_primary` tinyint(1) DEFAULT 0,
            `display_order` int(11) DEFAULT 0,
            `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `property_id` (`property_id`),
            KEY `is_primary` (`is_primary`),
            CONSTRAINT `property_images_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'tenant_preferences' => "CREATE TABLE IF NOT EXISTS `tenant_preferences` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `min_budget` decimal(10,2) DEFAULT NULL,
            `max_budget` decimal(10,2) DEFAULT NULL,
            `preferred_city` varchar(100) DEFAULT NULL,
            `property_type` enum('apartment','house','condo','commercial','studio','townhouse') DEFAULT NULL,
            `min_bedrooms` int(11) DEFAULT 1,
            `min_bathrooms` decimal(3,1) DEFAULT 1.0,
            `min_square_feet` int(11) DEFAULT NULL,
            `must_have_features` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `tenant_id` (`tenant_id`),
            CONSTRAINT `tenant_preferences_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'similarity_scores' => "CREATE TABLE IF NOT EXISTS `similarity_scores` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tenant_id` int(11) NOT NULL,
            `property_id` int(11) NOT NULL,
            `match_score` decimal(5,2) NOT NULL,
            `feature_breakdown` text DEFAULT NULL,
            `is_valid` tinyint(1) DEFAULT 1,
            `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `tenant_property_unique` (`tenant_id`,`property_id`),
            KEY `property_id` (`property_id`),
            KEY `match_score` (`match_score`),
            CONSTRAINT `similarity_scores_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
            CONSTRAINT `similarity_scores_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'browsing_history' => "CREATE TABLE IF NOT EXISTS `browsing_history` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `property_id` int(11) NOT NULL,
            `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `duration_seconds` int(11) DEFAULT NULL,
            `saved` tinyint(1) DEFAULT 0,
            `contact_clicked` tinyint(1) DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `property_id` (`property_id`),
            KEY `viewed_at` (`viewed_at`),
            CONSTRAINT `browsing_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `browsing_history_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'property_reservations' => "CREATE TABLE IF NOT EXISTS `property_reservations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `property_id` int(11) NOT NULL,
            `tenant_id` int(11) NOT NULL,
            `tenant_name` varchar(255) DEFAULT NULL,
            `tenant_email` varchar(255) DEFAULT NULL,
            `tenant_phone` varchar(20) DEFAULT NULL,
            `check_in_date` date NOT NULL,
            `check_out_date` date DEFAULT NULL,
            `guests` int(11) DEFAULT 1,
            `message` text DEFAULT NULL,
            `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `property_id` (`property_id`),
            KEY `tenant_id` (`tenant_id`),
            KEY `status` (`status`),
            CONSTRAINT `property_reservations_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
            CONSTRAINT `property_reservations_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'booking_visits' => "CREATE TABLE IF NOT EXISTS `booking_visits` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `property_id` int(11) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `tenant_name` varchar(255) NOT NULL,
            `tenant_email` varchar(255) NOT NULL,
            `tenant_phone` varchar(20) DEFAULT NULL,
            `visit_date` date NOT NULL,
            `visit_time` time NOT NULL,
            `message` text DEFAULT NULL,
            `status` enum('pending','approved','rejected','completed','cancelled') DEFAULT 'pending',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `property_id` (`property_id`),
            KEY `user_id` (`user_id`),
            KEY `status` (`status`),
            CONSTRAINT `booking_visits_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
            CONSTRAINT `booking_visits_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'saved_properties' => "CREATE TABLE IF NOT EXISTS `saved_properties` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `property_id` int(11) NOT NULL,
            `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_property_unique` (`user_id`,`property_id`),
            KEY `property_id` (`property_id`),
            CONSTRAINT `saved_properties_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            CONSTRAINT `saved_properties_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'notifications' => "CREATE TABLE IF NOT EXISTS `notifications` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `title` varchar(255) NOT NULL,
            `message` text NOT NULL,
            `type` enum('info','success','warning','error') DEFAULT 'info',
            `link` varchar(500) DEFAULT NULL,
            `is_read` tinyint(1) DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `is_read` (`is_read`),
            CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'recommendation_cache' => "CREATE TABLE IF NOT EXISTS `recommendation_cache` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `recommended_properties` text DEFAULT NULL,
            `algorithm_version` varchar(20) DEFAULT NULL,
            `based_on_interactions` int(11) DEFAULT 0,
            `expires_at` timestamp NULL DEFAULT NULL,
            `is_valid` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`),
            KEY `expires_at` (`expires_at`),
            CONSTRAINT `recommendation_cache_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    echo "<h2>📋 Creating Tables</h2>";
    echo "<table><tr><th>Table</th><th>Status</th></tr>";
    
    foreach ($tables as $table => $sql) {
        try {
            // Check if table exists
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            
            if ($result->num_rows > 0) {
                echo "<tr><td>$table</td><td class='warning'>⚠️ Already exists</td></tr>";
                $exists[] = $table;
            } else {
                // Create table
                if ($conn->query($sql)) {
                    echo "<tr><td>$table</td><td class='success'>✅ Created</td></tr>";
                    $created[] = $table;
                } else {
                    echo "<tr><td>$table</td><td class='error'>❌ Failed: " . htmlspecialchars($conn->error) . "</td></tr>";
                    $errors[] = "Failed to create table: $table";
                }
            }
        } catch (Exception $e) {
            echo "<tr><td>$table</td><td class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
            $errors[] = "Error with table $table: " . $e->getMessage();
        }
    }
    
    echo "</table>";
    
    // Summary
    echo "<div class='section'>";
    echo "<h2>📊 Summary</h2>";
    echo "<p><strong>Total Tables:</strong> " . count($tables) . "</p>";
    echo "<p><strong>Created:</strong> " . count($created) . "</p>";
    echo "<p><strong>Already Existed:</strong> " . count($exists) . "</p>";
    echo "<p><strong>Errors:</strong> " . count($errors) . "</p>";
    
    if (count($created) > 0) {
        echo "<div class='success'>";
        echo "<h3>✅ Successfully Created:</h3>";
        echo "<ul>";
        foreach ($created as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul></div>";
    }
    
    if (count($exists) > 0) {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Already Existed:</h3>";
        echo "<ul>";
        foreach ($exists as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul></div>";
    }
    
    if (count($errors) > 0) {
        echo "<div class='error'>";
        echo "<h3>❌ Errors:</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul></div>";
    }
    
    if (count($errors) === 0) {
        echo "<div class='success'><h2>🎉 Database setup complete!</h2>";
        echo "<p>All required tables are ready to use.</p></div>";
    }
    
    echo "</div>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Check:</strong></p>";
    echo "<ul>";
    echo "<li>Database credentials in config/env.php</li>";
    echo "<li>Database server is running</li>";
    echo "<li>Database '" . DB_NAME . "' exists</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>Environment: " . APP_ENV . " | Database: " . DB_NAME . " | Time: " . date('Y-m-d H:i:s') . "</small></p>";
echo "</div></body></html>";
?>
