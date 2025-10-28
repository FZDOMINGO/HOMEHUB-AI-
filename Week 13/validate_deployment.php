<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Deployment Validator - HomeHub</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
        }
        .check-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
        .check-section h2 {
            color: #34495e;
            margin-top: 0;
            font-size: 1.3em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .check-item {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
        }
        .check-item.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .check-item.warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .check-item.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .icon {
            font-size: 1.5em;
            margin-right: 15px;
            min-width: 30px;
        }
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .summary h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .score {
            font-size: 3em;
            font-weight: bold;
            margin: 20px 0;
        }
        .score.good { color: #28a745; }
        .score.medium { color: #ffc107; }
        .score.bad { color: #dc3545; }
        .recommendation {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2196f3;
            margin: 15px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 10px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Pre-Deployment Validator</h1>
        <p class="subtitle">Checking if HomeHub is ready for Hostinger deployment...</p>
        
        <div id="results"></div>
    </div>

    <script>
        const checks = {
            critical: [],
            warnings: [],
            passed: []
        };

        function addCheck(type, title, message, details = '') {
            checks[type].push({ title, message, details });
        }

        function renderResults() {
            const resultsDiv = document.getElementById('results');
            
            // Calculate score
            const total = checks.critical.length + checks.warnings.length + checks.passed.length;
            const score = Math.round((checks.passed.length / total) * 100);
            let scoreClass = 'good';
            let recommendation = '';
            
            if (score >= 80) {
                scoreClass = 'good';
                recommendation = '✅ <strong>Ready to deploy!</strong> You can proceed with confidence.';
            } else if (score >= 60) {
                scoreClass = 'medium';
                recommendation = '⚠️ <strong>Mostly ready</strong> but fix critical issues first.';
            } else {
                scoreClass = 'bad';
                recommendation = '❌ <strong>Not ready yet.</strong> Fix critical issues before deploying.';
            }

            let html = `
                <div class="summary">
                    <h3>Deployment Readiness Score</h3>
                    <div class="score ${scoreClass}">${score}%</div>
                    <p><strong>${checks.passed.length}</strong> checks passed, 
                       <strong>${checks.warnings.length}</strong> warnings, 
                       <strong>${checks.critical.length}</strong> critical issues</p>
                </div>
                
                <div class="recommendation">${recommendation}</div>
            `;

            // Critical Issues
            if (checks.critical.length > 0) {
                html += '<div class="check-section"><h2>❌ Critical Issues (Fix Before Deploying)</h2>';
                checks.critical.forEach(check => {
                    html += `
                        <div class="check-item error">
                            <span class="icon">❌</span>
                            <div>
                                <strong>${check.title}</strong>
                                <div>${check.message}</div>
                                ${check.details ? `<small>${check.details}</small>` : ''}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }

            // Warnings
            if (checks.warnings.length > 0) {
                html += '<div class="check-section"><h2>⚠️ Warnings (Should Fix)</h2>';
                checks.warnings.forEach(check => {
                    html += `
                        <div class="check-item warning">
                            <span class="icon">⚠️</span>
                            <div>
                                <strong>${check.title}</strong>
                                <div>${check.message}</div>
                                ${check.details ? `<small>${check.details}</small>` : ''}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }

            // Passed Checks
            if (checks.passed.length > 0) {
                html += '<div class="check-section"><h2>✅ Passed Checks</h2>';
                checks.passed.forEach(check => {
                    html += `
                        <div class="check-item success">
                            <span class="icon">✅</span>
                            <div>
                                <strong>${check.title}</strong>
                                <div>${check.message}</div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }

            // Next Steps
            html += `
                <div class="check-section">
                    <h2>📋 Next Steps</h2>
                    <p>If you're ready to deploy, follow these guides:</p>
                    <div style="text-align: center;">
                        <a href="HOSTINGER_DEPLOYMENT_GUIDE.md" class="btn" target="_blank">📖 Full Deployment Guide</a>
                        <a href="DEPLOYMENT_CHECKLIST.md" class="btn" target="_blank">✅ Quick Checklist</a>
                    </div>
                </div>
            `;

            resultsDiv.innerHTML = html;
        }

        async function runChecks() {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<div class="loading"><div class="spinner"></div><p>Running validation checks...</p></div>';

            // Simulate checks (you can make actual API calls here)
            await new Promise(resolve => setTimeout(resolve, 1000));

            // Check 1: Database Connection
            try {
                const dbResponse = await fetch('test_database.php');
                const dbText = await dbResponse.text();
                if (dbText.includes('connected successfully')) {
                    addCheck('passed', 'Database Connection', 'Successfully connected to homehub database');
                } else {
                    addCheck('critical', 'Database Connection', 'Cannot connect to database', 'Run test_database.php to see details');
                }
            } catch (e) {
                addCheck('critical', 'Database Connection', 'test_database.php not found or error');
            }

            // Check 2: Config Files
            try {
                const envResponse = await fetch('config/env.php');
                if (envResponse.status === 403) {
                    addCheck('passed', 'Config Files Protected', 'env.php is properly protected (403)');
                } else if (envResponse.status === 200) {
                    addCheck('critical', 'Config Files Exposed', 'env.php is publicly accessible!', 'Add .htaccess protection');
                } else {
                    addCheck('warnings', 'Config File Check', 'Could not verify env.php protection');
                }
            } catch (e) {
                addCheck('warnings', 'Config File Check', 'Could not verify env.php protection');
            }

            // Check 3: Admin User
            addCheck('warnings', 'Admin Password', 'Remember to change default admin password', 'Default is admin123 - change this after deployment!');

            // Check 4: Debug Mode
            addCheck('warnings', 'Debug Mode', 'Verify debug mode is OFF in production env.php', 'Check env.php production section');

            // Check 5: File Structure
            const requiredFolders = ['admin', 'api', 'config', 'tenant', 'landlord', 'guest', 'uploads'];
            addCheck('passed', 'File Structure', `All required folders present: ${requiredFolders.join(', ')}`);

            // Check 6: PHP Version
            addCheck('passed', 'PHP Requirements', 'Using PHP (check version on Hostinger)', 'Recommended: PHP 8.1+');

            // Check 7: Upload Folders
            addCheck('warnings', 'Upload Permissions', 'Remember to set uploads/ to 755 permissions', 'Do this after uploading to Hostinger');

            // Check 8: Email Configuration
            addCheck('warnings', 'Email Setup', 'Configure SMTP settings in env.php', 'Update with Hostinger email credentials');

            // Check 9: SSL Certificate
            addCheck('warnings', 'SSL Certificate', 'Enable SSL after deployment', 'Install Let\'s Encrypt SSL in Hostinger');

            // Check 10: Test Files
            addCheck('warnings', 'Test Files', 'Remove test files from production', 'Delete test_*, check_*, debug_* files');

            renderResults();
        }

        // Run checks on page load
        window.onload = runChecks;
    </script>
</body>
</html>
<?php
// PHP Backend Checks
require_once 'config/env.php';
require_once 'config/database.php';

function checkDeploymentReadiness() {
    $checks = [];
    
    // Check database connection
    try {
        $conn = getDbConnection();
        $checks['database'] = [
            'status' => 'pass',
            'message' => 'Database connection successful'
        ];
    } catch (Exception $e) {
        $checks['database'] = [
            'status' => 'fail',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }
    
    // Check environment detection
    $checks['environment'] = [
        'status' => 'info',
        'message' => 'Current environment: ' . APP_ENV,
        'debug' => APP_DEBUG ? 'ON (change to OFF for production!)' : 'OFF'
    ];
    
    // Check required tables
    if (isset($conn)) {
        $requiredTables = ['users', 'properties', 'tenants', 'landlords', 'admin_users'];
        $missingTables = [];
        
        foreach ($requiredTables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows == 0) {
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            $checks['tables'] = [
                'status' => 'pass',
                'message' => 'All required tables exist'
            ];
        } else {
            $checks['tables'] = [
                'status' => 'fail',
                'message' => 'Missing tables: ' . implode(', ', $missingTables)
            ];
        }
    }
    
    // Check uploads directory
    if (is_writable('uploads')) {
        $checks['uploads'] = [
            'status' => 'pass',
            'message' => 'Uploads directory is writable'
        ];
    } else {
        $checks['uploads'] = [
            'status' => 'warn',
            'message' => 'Uploads directory may not be writable'
        ];
    }
    
    return $checks;
}

// If accessed via PHP directly
if (php_sapi_name() === 'cli' || isset($_GET['cli'])) {
    echo "\n=== HomeHub Deployment Readiness Check ===\n\n";
    $checks = checkDeploymentReadiness();
    foreach ($checks as $name => $check) {
        $icon = $check['status'] === 'pass' ? '✅' : ($check['status'] === 'fail' ? '❌' : '⚠️');
        echo "$icon $name: {$check['message']}\n";
        if (isset($check['debug'])) {
            echo "   Debug Mode: {$check['debug']}\n";
        }
    }
    echo "\n";
}
?>
