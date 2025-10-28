<?php
session_start();

// Set admin session for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_name'] = 'System Administrator';
$_SESSION['admin_role'] = 'super_admin';

echo "<!DOCTYPE html>";
echo "<html><head><title>Analytics - Real-Time Activity Updated</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";

echo "<div class='container mt-4'>";
echo "<div class='alert alert-success'>";
echo "<h4>✅ Real-Time Platform Activity Updated!</h4>";
echo "<p>Successfully removed 'Unread Reports' and 'Active Users' from the Real-Time Platform Activity section.</p>";
echo "</div>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header bg-danger text-white'>";
echo "<h5><i class='bi bi-trash'></i> Removed Metrics</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<ul class='list-group list-group-flush'>";
echo "<li class='list-group-item'><del>📢 Unread Reports</del> <span class='badge bg-danger'>Removed</span></li>";
echo "<li class='list-group-item'><del>👤 Active Users</del> <span class='badge bg-danger'>Removed</span></li>";
echo "</ul>";
echo "</div></div></div>";

echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-header bg-success text-white'>";
echo "<h5><i class='bi bi-check-circle'></i> Remaining Metrics</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<ul class='list-group list-group-flush'>";
echo "<li class='list-group-item'>👥 New Users Today <span class='badge bg-primary'>Active</span></li>";
echo "<li class='list-group-item'>🏠 Properties Added Today <span class='badge bg-success'>Active</span></li>";
echo "<li class='list-group-item'>👁️ Page Views Today <span class='badge bg-info'>Active</span></li>";
echo "</ul>";
echo "</div></div></div>";
echo "</div>";

echo "<div class='card mt-4'>";
echo "<div class='card-header'>";
echo "<h5><i class='bi bi-activity'></i> Real-Time Platform Activity Section</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<div class='alert alert-info'>";
echo "<h6>Updated Layout:</h6>";
echo "<p>The Real-Time Platform Activity section now displays a cleaner, more focused view with 3 key daily metrics:</p>";
echo "<div class='row text-center'>";
echo "<div class='col-4'>";
echo "<div class='border rounded p-3 bg-light'>";
echo "<div class='h5 text-primary mb-0'>X</div>";
echo "<small class='text-muted'>New Users Today</small>";
echo "</div></div>";
echo "<div class='col-4'>";
echo "<div class='border rounded p-3 bg-light'>";
echo "<div class='h5 text-success mb-0'>X</div>";
echo "<small class='text-muted'>Properties Added Today</small>";
echo "</div></div>";
echo "<div class='col-4'>";
echo "<div class='border rounded p-3 bg-light'>";
echo "<div class='h5 text-info mb-0'>X</div>";
echo "<small class='text-muted'>Page Views Today</small>";
echo "</div></div>";
echo "</div>";
echo "</div>";
echo "</div></div>";

echo "<div class='card mt-4'>";
echo "<div class='card-header'>";
echo "<h5><i class='bi bi-gear'></i> What Changed</h5>";
echo "</div>";
echo "<div class='card-body'>";
echo "<ul>";
echo "<li>🗑️ Removed 'Unread Reports' metric from Real-Time Platform Activity</li>";
echo "<li>🗑️ Removed 'Active Users' metric from Real-Time Platform Activity</li>";
echo "<li>📐 Reorganized layout to use 3-column grid instead of 5-column</li>";
echo "<li>🎨 Improved visual balance and spacing</li>";
echo "<li>🎯 Focused on core daily activity metrics</li>";
echo "</ul>";
echo "</div></div>";

echo "<div class='text-center mt-4'>";
echo "<a href='admin/analytics.php' class='btn btn-primary btn-lg' target='_blank'>";
echo "<i class='bi bi-graph-up'></i> View Updated Analytics Dashboard";
echo "</a>";
echo "</div>";

echo "</div>"; // container
echo "</body></html>";
?>