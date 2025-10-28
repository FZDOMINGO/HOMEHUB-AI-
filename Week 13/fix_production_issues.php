#!/usr/bin/env php
<?php
/**
 * HOMEHUB PRODUCTION FIXER
 * Automatically fixes critical production deployment issues
 * 
 * Usage: php fix_production_issues.php YOUR_DOMAIN
 * Example: php fix_production_issues.php https://mysite.com
 */

if ($argc < 2) {
    echo "ERROR: Please provide your production domain\n";
    echo "Usage: php fix_production_issues.php YOUR_DOMAIN\n";
    echo "Example: php fix_production_issues.php https://mysite.com\n";
    exit(1);
}

$productionDomain = rtrim($argv[1], '/');
echo "=== HOMEHUB PRODUCTION FIXER ===\n\n";
echo "Production Domain: $productionDomain\n\n";

$fixedFiles = [];
$errors = [];

// 1. FIX LOCALHOST URLs in email_functions.php
echo "1. Fixing localhost URLs in includes/email_functions.php...\n";
$file = 'includes/email_functions.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Replace localhost URLs
    $content = str_replace('http://localhost/HomeHub', $productionDomain, $content);
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixedFiles[] = $file;
        echo "   ✓ Fixed $file\n";
    } else {
        echo "   - No changes needed in $file\n";
    }
} else {
    $errors[] = "$file not found";
    echo "   ✗ ERROR: $file not found\n";
}

// 2. FIX LOCALHOST URLs in admin/email-preview.php
echo "\n2. Fixing localhost URLs in admin/email-preview.php...\n";
$file = 'admin/email-preview.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    $content = str_replace('http://localhost/HomeHub', $productionDomain, $content);
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixedFiles[] = $file;
        echo "   ✓ Fixed $file\n";
    } else {
        echo "   - No changes needed in $file\n";
    }
} else {
    echo "   ⚠ WARNING: $file not found (optional file)\n";
}

// 3. FIX LOCALHOST URLs in api/test-email.php
echo "\n3. Fixing localhost URLs in api/test-email.php...\n";
$file = 'api/test-email.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    $content = str_replace('http://localhost/HomeHub', $productionDomain, $content);
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $fixedFiles[] = $file;
        echo "   ✓ Fixed $file\n";
    } else {
        echo "   - No changes needed in $file\n";
    }
} else {
    echo "   ⚠ WARNING: $file not found (optional file)\n";
}

// 4. FIX DEPRECATED FILTER_SANITIZE_STRING
echo "\n4. Fixing deprecated FILTER_SANITIZE_STRING...\n";
$files_to_fix = [
    'process-booking.php',
    'process-reservation-clean.php',
    'process-reservation.php',
    'process-visit.php',
    'tenant/profile.php'
];

foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        // Replace FILTER_SANITIZE_STRING with htmlspecialchars
        $patterns = [
            '/filter_var\((\$_POST\[[^\]]+\](?:\s*\?\?\s*[^,]+)?),\s*FILTER_SANITIZE_STRING\)/i' => 'htmlspecialchars($1, ENT_QUOTES, \'UTF-8\')',
            '/filter_var\((\$_GET\[[^\]]+\](?:\s*\?\?\s*[^,]+)?),\s*FILTER_SANITIZE_STRING\)/i' => 'htmlspecialchars($1, ENT_QUOTES, \'UTF-8\')'
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        if ($content !== $originalContent && $content !== null) {
            file_put_contents($file, $content);
            $fixedFiles[] = $file;
            echo "   ✓ Fixed deprecated functions in $file\n";
        } else {
            echo "   - No changes needed in $file\n";
        }
    } else {
        echo "   ⚠ WARNING: $file not found\n";
    }
}

// 5. CREATE .htaccess FILE
echo "\n5. Creating .htaccess file...\n";
$htaccessContent = <<<HTACCESS
# HomeHub .htaccess

# Enable RewriteEngine
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Redirect root to index.php
    RewriteRule ^$ index.php [L]
</IfModule>

# Disable directory browsing
Options -Indexes

# Protect configuration files
<FilesMatch "^(db_connect\.php|email_config\.php)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect log files
<FilesMatch "\.(log|txt)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Disable error display (security)
php_flag display_errors Off
php_flag log_errors On

# Set default document
DirectoryIndex index.php index.html

# Enable compression (optional)
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Set proper MIME types
AddType application/javascript .js
AddType text/css .css
HTACCESS;

if (!file_exists('.htaccess')) {
    file_put_contents('.htaccess', $htaccessContent);
    $fixedFiles[] = '.htaccess (created)';
    echo "   ✓ Created .htaccess file\n";
} else {
    echo "   - .htaccess already exists (not overwriting)\n";
}

// 6. CREATE UPLOADS DIRECTORY
echo "\n6. Checking/creating necessary directories...\n";
$directories = ['uploads', 'logs'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "   ✓ Created $dir/ directory\n";
            $fixedFiles[] = "$dir/ (created)";
        } else {
            echo "   ✗ ERROR: Could not create $dir/ directory\n";
            $errors[] = "Failed to create $dir/";
        }
    } else {
        echo "   - $dir/ already exists\n";
    }
}

// 7. CREATE DATABASE CONFIG TEMPLATE
echo "\n7. Creating production database config template...\n";
$configTemplate = <<<'CONFIG'
<?php
/**
 * PRODUCTION DATABASE CONFIGURATION
 * Update these values with your hosting provider's credentials
 */

// TODO: Update these with your production database credentials
define('DB_SERVER', 'localhost');           // Change if different
define('DB_USERNAME', 'your_db_username');  // From hosting provider
define('DB_PASSWORD', 'your_db_password');  // From hosting provider
define('DB_NAME', 'your_db_name');          // From hosting provider

// Create database connection
function getDbConnection() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error instead of displaying
        error_log("Database connection failed: " . $conn->connect_error);
        die(json_encode([
            "status" => "error", 
            "message" => "Database connection failed. Please contact support."
        ]));
    }
    
    return $conn;
}
?>
CONFIG;

$configFile = 'config/db_connect.PRODUCTION.php';
if (!file_exists($configFile)) {
    file_put_contents($configFile, $configTemplate);
    echo "   ✓ Created $configFile template\n";
    echo "   ⚠ IMPORTANT: Update this file with your production credentials\n";
} else {
    echo "   - Production config template already exists\n";
}

// 8. CREATE README FOR DEPLOYMENT
echo "\n8. Creating deployment instructions...\n";
$readmeContent = <<<'README'
# PRODUCTION DEPLOYMENT INSTRUCTIONS

## Files Modified by fix_production_issues.php:
- includes/email_functions.php - Fixed localhost URLs
- admin/email-preview.php - Fixed localhost URLs
- api/test-email.php - Fixed localhost URLs
- Multiple PHP files - Fixed deprecated FILTER_SANITIZE_STRING
- .htaccess - Created with security settings

## NEXT STEPS:

### 1. Update Database Configuration
Edit `config/db_connect.php` with your production database credentials:
```php
define('DB_SERVER', 'your-server');
define('DB_USERNAME', 'your-username');
define('DB_PASSWORD', 'your-password');
define('DB_NAME', 'your-database');
```

Or copy `config/db_connect.PRODUCTION.php` to `config/db_connect.php` and update it.

### 2. Import Database
1. Export your local database from phpMyAdmin
2. Import to production database via your hosting control panel

### 3. Test the Application
Visit your production URL and test:
- Homepage loads
- User can register
- User can login
- Properties display
- Images load correctly

### 4. Configure Email
Login to admin panel and update SMTP settings:
- SMTP Host (from your hosting provider)
- SMTP Port (usually 587)
- SMTP Username and Password
- Test email sending

### 5. Remove Test Files (Optional but Recommended)
Delete or restrict access to:
- test_*.php files
- check_*.php files  
- debug_*.php files
- production_diagnostic.php
- fix_production_issues.php

## Troubleshooting:
- Database errors: Check db_connect.php credentials
- 500 errors: Check PHP error logs
- Images not loading: Check uploads/ directory permissions
- Emails not sending: Update SMTP settings in admin panel

README;

file_put_contents('DEPLOYMENT_README.txt', $readmeContent);
echo "   ✓ Created DEPLOYMENT_README.txt\n";

// SUMMARY
echo "\n" . str_repeat("=", 60) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 60) . "\n\n";

echo "✓ FILES FIXED/CREATED: " . count($fixedFiles) . "\n";
foreach ($fixedFiles as $file) {
    echo "  - $file\n";
}

if (!empty($errors)) {
    echo "\n✗ ERRORS: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "NEXT STEPS:\n";
echo str_repeat("=", 60) . "\n";
echo "1. Update config/db_connect.php with production database credentials\n";
echo "2. Upload all files to your production server\n";
echo "3. Import database SQL file\n";
echo "4. Test the application\n";
echo "5. Configure email settings via admin panel\n";
echo "\nSee DEPLOYMENT_README.txt for detailed instructions.\n";
echo str_repeat("=", 60) . "\n";

echo "\n✅ Production fixes complete!\n\n";

?>
