<?php
session_start();

// Set admin session for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_name'] = 'System Administrator';
$_SESSION['admin_role'] = 'super_admin';

echo "<!DOCTYPE html>";
echo "<html><head><title>Admin Dashboard - Updates Summary</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";

echo "<div class='container mt-4'>";
echo "<div class='alert alert-success'>";
echo "<h4>✅ Admin Dashboard Updated Successfully!</h4>";
echo "<p>Fixed the suspended properties count and removed booking-related items.</p>";
echo "</div>";

// Test database connection and get real counts
require_once 'config/db_connect.php';
$conn = getDbConnection();

$tenantCount = $conn->query("SELECT COUNT(*) as count FROM tenants")->fetch_assoc()['count'] ?? 0;
$landlordCount = $conn->query("SELECT COUNT(*) as count FROM landlords")->fetch_assoc()['count'] ?? 0;
$propertyCount = $conn->query("SELECT COUNT(*) as count FROM properties")->fetch_assoc()['count'] ?? 0;
$activeProperties = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status = 'available'")->fetch_assoc()['count'] ?? 0;
$suspendedProperties = $conn->query("SELECT COUNT(*) as count FROM properties WHERE status = 'suspended'")->fetch_assoc()['count'] ?? 0;

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header bg-danger text-white'>";
echo "<h5><i class='bi bi-trash'></i> Removed Items</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<ul class='list-group list-group-flush'>";
echo "<li class='list-group-item'><del>📅 Total Bookings</del> <span class='badge bg-danger'>Removed</span></li>";
echo "<li class='list-group-item'><del>⏰ Pending Bookings</del> <span class='badge bg-danger'>Removed</span></li>";
echo "<li class='list-group-item'><del>Booking data from chart</del> <span class='badge bg-danger'>Removed</span></li>";
echo "</ul>";
echo "</div></div></div>";

echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header bg-success text-white'>";
echo "<h5><i class='bi bi-plus-circle'></i> Added/Fixed Items</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<ul class='list-group list-group-flush'>";
echo "<li class='list-group-item'>🚫 Suspended Properties <span class='badge bg-warning'>$suspendedProperties</span></li>";
echo "<li class='list-group-item'>📊 Updated chart data <span class='badge bg-success'>Fixed</span></li>";
echo "<li class='list-group-item'>🏠 Total Properties label <span class='badge bg-info'>Clarified</span></li>";
echo "</ul>";
echo "</div></div></div>";
echo "</div>";

echo "<div class='card mt-4'>";
echo "<div class='card-header'>";
echo "<h5><i class='bi bi-bar-chart'></i> Current Statistics</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<div class='row text-center'>";
echo "<div class='col-md-3'>";
echo "<div class='card border-primary'>";
echo "<div class='card-body'>";
echo "<i class='bi bi-people display-4 text-primary'></i>";
echo "<h3 class='mt-2'>$tenantCount</h3>";
echo "<p class='text-muted'>Tenants</p>";
echo "</div></div></div>";

echo "<div class='col-md-3'>";
echo "<div class='card border-secondary'>";
echo "<div class='card-body'>";
echo "<i class='bi bi-building display-4 text-secondary'></i>";
echo "<h3 class='mt-2'>$landlordCount</h3>";
echo "<p class='text-muted'>Landlords</p>";
echo "</div></div></div>";

echo "<div class='col-md-3'>";
echo "<div class='card border-success'>";
echo "<div class='card-body'>";
echo "<i class='bi bi-house-check display-4 text-success'></i>";
echo "<h3 class='mt-2'>$activeProperties</h3>";
echo "<p class='text-muted'>Active Properties</p>";
echo "</div></div></div>";

echo "<div class='col-md-3'>";
echo "<div class='card border-warning'>";
echo "<div class='card-body'>";
echo "<i class='bi bi-ban display-4 text-warning'></i>";
echo "<h3 class='mt-2'>$suspendedProperties</h3>";
echo "<p class='text-muted'>Suspended Properties</p>";
echo "</div></div></div>";
echo "</div>";
echo "</div></div>";

echo "<div class='card mt-4'>";
echo "<div class='card-header'>";
echo "<h5><i class='bi bi-info-circle'></i> What the Status Chart Shows</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<div class='alert alert-info'>";
echo "<h6><strong>Platform Overview Chart:</strong></h6>";
echo "<p>The doughnut chart now displays the following categories:</p>";
echo "<ul>";
echo "<li><strong>Tenants:</strong> Users registered as property seekers</li>";
echo "<li><strong>Landlords:</strong> Users registered as property owners</li>";
echo "<li><strong>Active Properties:</strong> Properties available for rent (status = 'available')</li>";
echo "<li><strong>Suspended Properties:</strong> Properties that have been suspended by admin (status = 'suspended')</li>";
echo "</ul>";
echo "<p class='mb-0'><em>Note: Booking-related data has been removed as requested.</em></p>";
echo "</div>";
echo "</div></div>";

echo "<div class='text-center mt-4'>";
echo "<a href='admin/dashboard.php' class='btn btn-primary btn-lg'>";
echo "<i class='bi bi-speedometer2'></i> View Updated Admin Dashboard";
echo "</a>";
echo "</div>";

echo "</div>"; // container
echo "</body></html>";

if ($conn) {
    $conn->close();
}
?>