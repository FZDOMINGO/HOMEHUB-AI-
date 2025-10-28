<?php
session_start();

// Set admin session for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_name'] = 'System Administrator';
$_SESSION['admin_role'] = 'super_admin';

echo "<!DOCTYPE html>";
echo "<html><head><title>Testing Admin Properties Actions</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";

echo "<div class='container mt-4'>";
echo "<div class='alert alert-info'>";
echo "<h4>🔧 Admin Properties Actions Diagnostic</h4>";
echo "<p>Testing the functionality of Properties Management page actions...</p>";
echo "</div>";

// Test database connection
require_once 'config/db_connect.php';
$conn = getDbConnection();

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header bg-success text-white'>";
echo "<h5><i class='bi bi-check-circle'></i> System Status</h5>";
echo "</div>";
echo "<div class='card-body'>";

// Check database connection
if ($conn) {
    echo "<div class='alert alert-success'><i class='bi bi-database-check'></i> Database connection: Working</div>";
} else {
    echo "<div class='alert alert-danger'><i class='bi bi-database-x'></i> Database connection: Failed</div>";
}

// Check if properties table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'properties'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    echo "<div class='alert alert-success'><i class='bi bi-table'></i> Properties table: Exists</div>";
} else {
    echo "<div class='alert alert-danger'><i class='bi bi-table'></i> Properties table: Missing</div>";
}

// Check API endpoint
if (file_exists('api/get-property-details.php')) {
    echo "<div class='alert alert-success'><i class='bi bi-file-check'></i> API endpoint: Found</div>";
} else {
    echo "<div class='alert alert-danger'><i class='bi bi-file-x'></i> API endpoint: Missing</div>";
}

echo "</div></div></div>";

echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header bg-primary text-white'>";
echo "<h5><i class='bi bi-bug'></i> Common Issues & Fixes</h5>";
echo "</div>";
echo "<div class='card-body'>";

echo "<h6>🔍 Likely Issues:</h6>";
echo "<ul>";
echo "<li><strong>API Path Error:</strong> Fixed - Database connection path corrected</li>";
echo "<li><strong>Missing Bootstrap JS:</strong> Required for modals</li>";
echo "<li><strong>JavaScript Errors:</strong> Check browser console for errors</li>";
echo "<li><strong>Form Submission Issues:</strong> Check POST handling</li>";
echo "</ul>";

echo "<h6>✅ Fixed Issues:</h6>";
echo "<ul>";
echo "<li>Updated API path from 'config/db_connect.php' to '../config/db_connect.php'</li>";
echo "</ul>";

echo "</div></div></div>";
echo "</div>";

// Test a sample property action
echo "<div class='card mt-4'>";
echo "<div class='card-header'>";
echo "<h5><i class='bi bi-play'></i> Action Test</h5>";
echo "</div>";
echo "<div class='card-body'>";

echo "<div class='alert alert-warning'>";
echo "<h6>Test the following actions on Properties Management page:</h6>";
echo "<ol>";
echo "<li><strong>View:</strong> Click the eye icon - should open property detail in new tab</li>";
echo "<li><strong>Edit:</strong> Click the pencil icon - should open edit modal</li>";
echo "<li><strong>Approve:</strong> Click the check icon - should show confirmation dialog</li>";
echo "<li><strong>Suspend:</strong> Click the ban icon - should open suspend modal</li>";
echo "<li><strong>Delete:</strong> Click the trash icon - should open delete modal</li>";
echo "</ol>";
echo "</div>";

// Show a sample properties action buttons for testing
echo "<div class='border rounded p-3 bg-light'>";
echo "<h6>Sample Action Buttons:</h6>";
echo "<div class='btn-group'>";
echo "<button class='btn btn-outline-primary' onclick='testView()' title='View Property'>";
echo "<i class='bi bi-eye'></i>";
echo "</button>";
echo "<button class='btn btn-outline-secondary' onclick='testEdit()' title='Edit Property'>";
echo "<i class='bi bi-pencil'></i>";
echo "</button>";
echo "<button class='btn btn-outline-success' onclick='testApprove()' title='Approve Property'>";
echo "<i class='bi bi-check'></i>";
echo "</button>";
echo "<button class='btn btn-outline-warning' onclick='testSuspend()' title='Suspend Property'>";
echo "<i class='bi bi-ban'></i>";
echo "</button>";
echo "<button class='btn btn-outline-danger' onclick='testDelete()' title='Delete Property'>";
echo "<i class='bi bi-trash'></i>";
echo "</button>";
echo "</div>";
echo "<div class='mt-2'>";
echo "<small class='text-muted'>Click these buttons to test if JavaScript functions work</small>";
echo "</div>";
echo "</div>";

echo "</div></div>";

echo "<div class='text-center mt-4'>";
echo "<a href='admin/properties.php' class='btn btn-primary btn-lg' target='_blank'>";
echo "<i class='bi bi-house-gear'></i> Open Properties Management";
echo "</a>";
echo "</div>";

echo "</div>"; // container

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>";
echo "<script>";
echo "function testView() { alert('View function called! This should open property details.'); }";
echo "function testEdit() { alert('Edit function called! This should open edit modal.'); }";
echo "function testApprove() { alert('Approve function called! This should show confirmation.'); }";
echo "function testSuspend() { alert('Suspend function called! This should open suspend modal.'); }";
echo "function testDelete() { alert('Delete function called! This should open delete modal.'); }";
echo "console.log('Admin Properties Test Page Loaded');";
echo "</script>";

echo "</body></html>";

if ($conn) {
    $conn->close();
}
?>