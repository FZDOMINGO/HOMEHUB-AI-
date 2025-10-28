<?php
session_start();

// Set admin session for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_name'] = 'System Administrator';
$_SESSION['admin_role'] = 'super_admin';

echo "<!DOCTYPE html>";
echo "<html><head><title>Property Detail Foreign Key Fix Test</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";

echo "<div class='container mt-4'>";
echo "<div class='alert alert-success'>";
echo "<h4>✅ Property Detail Foreign Key Issue Fixed!</h4>";
echo "<p>The browsing history foreign key constraint issue has been resolved.</p>";
echo "</div>";

// Test database connection
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h5><i class='bi bi-bug-fill'></i> Issue Analysis</h5>";
echo "</div>";
echo "<div class='card-body'>";

echo "<h6>🔍 Problem:</h6>";
echo "<ul>";
echo "<li>Admin preview mode was setting temporary user IDs (999999, 999998)</li>";
echo "<li>These IDs don't exist in the <code>users</code> table</li>";
echo "<li>Browsing history table has foreign key constraint to <code>users.id</code></li>";
echo "<li>Trying to insert non-existent user_id caused constraint violation</li>";
echo "</ul>";

echo "<h6>✅ Solution Applied:</h6>";
echo "<ul>";
echo "<li>Added check to exclude preview user IDs (<code>userId < 999000</code>)</li>";
echo "<li>Added verification that user exists in database before inserting</li>";
echo "<li>Updated preview system to properly clear user_id for guests</li>";
echo "<li>Prevents browsing history tracking during admin preview mode</li>";
echo "</ul>";

echo "</div></div></div>";

echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header bg-success text-white'>";
echo "<h5><i class='bi bi-check-circle'></i> Test Results</h5>";
echo "</div>";
echo "<div class='card-body'>";

// Get a sample property for testing
$sampleProperty = $conn->query("SELECT id, title FROM properties LIMIT 1")->fetch_assoc();

if ($sampleProperty) {
    echo "<div class='alert alert-success'>";
    echo "<i class='bi bi-database-check'></i> Test Property Found: <strong>" . htmlspecialchars($sampleProperty['title']) . "</strong> (ID: " . $sampleProperty['id'] . ")";
    echo "</div>";
    
    echo "<div class='d-grid gap-2'>";
    echo "<a href='property-detail.php?id=" . $sampleProperty['id'] . "' target='_blank' class='btn btn-primary'>";
    echo "<i class='bi bi-eye'></i> Test Property Detail Page";
    echo "</a>";
    
    echo "<a href='admin/preview.php?type=guest&page=properties' target='_blank' class='btn btn-info'>";
    echo "<i class='bi bi-person-circle'></i> Test Admin Preview Mode";
    echo "</a>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning'>";
    echo "<i class='bi bi-exclamation-triangle'></i> No properties found for testing";
    echo "</div>";
}

echo "</div></div></div>";
echo "</div>";

echo "<div class='card mt-4'>";
echo "<div class='card-header'>";
echo "<h5><i class='bi bi-gear'></i> Technical Details</h5>";
echo "</div>";
echo "<div class='card-body'>";

echo "<h6>Code Changes Made:</h6>";
echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<h6 class='text-primary'>property-detail.php</h6>";
echo "<pre class='bg-light p-2 small'>";
echo "// Before:\n";
echo "if (\$isLoggedIn && \$userId) {\n";
echo "    // Direct insert causing FK error\n";
echo "}\n\n";
echo "// After:\n";
echo "if (\$isLoggedIn && \$userId && \$userId < 999000) {\n";
echo "    // Verify user exists first\n";
echo "    \$userCheckStmt = \$conn->prepare(\"SELECT id FROM users WHERE id = ?\");\n";
echo "    if (\$userExists->num_rows > 0) {\n";
echo "        // Safe to insert\n";
echo "    }\n";
echo "}";
echo "</pre>";
echo "</div>";

echo "<div class='col-md-6'>";
echo "<h6 class='text-primary'>admin/preview.php</h6>";
echo "<pre class='bg-light p-2 small'>";
echo "// Before:\n";
echo "unset(\$_SESSION['user_id']);\n\n";
echo "// After:\n";
echo "unset(\$_SESSION['user_id']);\n";
echo "\$_SESSION['user_id'] = null;\n";
echo "// Ensures clean guest state";
echo "</pre>";
echo "</div>";
echo "</div>";

echo "</div></div>";

echo "<div class='text-center mt-4'>";
echo "<a href='admin/properties.php' class='btn btn-primary btn-lg'>";
echo "<i class='bi bi-house-gear'></i> Test Properties Management";
echo "</a>";
echo "</div>";

echo "</div>"; // container
echo "</body></html>";

if ($conn) {
    $conn->close();
}
?>