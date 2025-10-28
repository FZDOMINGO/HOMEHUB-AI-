<?php
session_start();

// Set admin session for testing
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';
$_SESSION['admin_name'] = 'System Administrator';
$_SESSION['admin_role'] = 'super_admin';

require_once 'config/db_connect.php';
$conn = getDbConnection();

// Handle property actions (same as admin/properties.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $propertyId = $_POST['property_id'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    
    echo "<div class='alert alert-info'>POST received: Action = $action, Property ID = $propertyId, Reason = $reason</div>";
    
    switch ($action) {
        case 'approve':
            $stmt = $conn->prepare("UPDATE properties SET status = 'available' WHERE id = ?");
            $stmt->bind_param("i", $propertyId);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Property $propertyId approved successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Error approving property: " . $conn->error . "</div>";
            }
            break;
        case 'suspend':
            $stmt = $conn->prepare("UPDATE properties SET status = 'suspended' WHERE id = ?");
            $stmt->bind_param("i", $propertyId);
            if ($stmt->execute()) {
                echo "<div class='alert alert-warning'>Property $propertyId suspended successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Error suspending property: " . $conn->error . "</div>";
            }
            break;
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
            $stmt->bind_param("i", $propertyId);
            if ($stmt->execute()) {
                echo "<div class='alert alert-danger'>Property $propertyId deleted successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Error deleting property: " . $conn->error . "</div>";
            }
            break;
    }
}

// Get a sample property for testing
$sampleProperty = $conn->query("SELECT * FROM properties LIMIT 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties Actions Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4><i class="bi bi-bug"></i> Properties Actions Test</h4>
            </div>
            <div class="card-body">
                <?php if ($sampleProperty): ?>
                    <h5>Testing with Property: <?= htmlspecialchars($sampleProperty['title']) ?> (ID: <?= $sampleProperty['id'] ?>)</h5>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Action Buttons Test</h6>
                                </div>
                                <div class="card-body">
                                    <div class="btn-group-vertical w-100" role="group">
                                        <button class="btn btn-outline-primary mb-2" onclick="viewProperty(<?= $sampleProperty['id'] ?>)">
                                            <i class="bi bi-eye"></i> View Property
                                        </button>
                                        <button class="btn btn-outline-secondary mb-2" onclick="editProperty(<?= $sampleProperty['id'] ?>)">
                                            <i class="bi bi-pencil"></i> Edit Property
                                        </button>
                                        <button class="btn btn-outline-success mb-2" onclick="approveProperty(<?= $sampleProperty['id'] ?>)">
                                            <i class="bi bi-check"></i> Approve Property
                                        </button>
                                        <button class="btn btn-outline-warning mb-2" onclick="suspendProperty(<?= $sampleProperty['id'] ?>)">
                                            <i class="bi bi-ban"></i> Suspend Property
                                        </button>
                                        <button class="btn btn-outline-danger mb-2" onclick="deleteProperty(<?= $sampleProperty['id'] ?>)">
                                            <i class="bi bi-trash"></i> Delete Property
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Console Output</h6>
                                </div>
                                <div class="card-body">
                                    <div id="console-output" class="bg-dark text-light p-3 rounded" style="height: 300px; overflow-y: scroll; font-family: monospace;">
                                        Console output will appear here...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No properties found in database. Please add some properties first.
                    </div>
                <?php endif; ?>
                
                <div class="mt-4 text-center">
                    <a href="admin/properties.php" class="btn btn-primary">
                        <i class="bi bi-house-gear"></i> Go to Real Properties Page
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Simplified modals for testing -->
    <div class="modal fade" id="suspendPropertyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Suspend Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="suspend">
                    <input type="hidden" name="property_id" id="suspendPropertyId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <select class="form-select" name="reason" required>
                                <option value="Test suspension">Test suspension</option>
                                <option value="Policy violation">Policy violation</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Suspend</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deletePropertyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Delete Property</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="property_id" id="deletePropertyId">
                    <div class="modal-body">
                        <div class="alert alert-danger">This will permanently delete the property!</div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <select class="form-select" name="reason" required>
                                <option value="Test deletion">Test deletion</option>
                                <option value="Spam">Spam</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Console logger
        function logToConsole(message) {
            const console_output = document.getElementById('console-output');
            const timestamp = new Date().toLocaleTimeString();
            console_output.innerHTML += `[${timestamp}] ${message}\n`;
            console_output.scrollTop = console_output.scrollHeight;
            console.log(message);
        }

        logToConsole('Test page loaded successfully');

        function viewProperty(propertyId) {
            logToConsole(`View property called for ID: ${propertyId}`);
            window.open('property-detail.php?id=' + propertyId, '_blank');
        }
        
        function editProperty(propertyId) {
            logToConsole(`Edit property called for ID: ${propertyId}`);
            fetch(`api/get-property-details.php?id=${propertyId}`)
                .then(response => {
                    logToConsole(`API response status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    logToConsole(`API data received: ${JSON.stringify(data)}`);
                    if (data.success) {
                        alert('Edit functionality would work! Property data loaded successfully.');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    logToConsole(`Fetch error: ${error}`);
                    alert('Error loading property details');
                });
        }
        
        function approveProperty(propertyId) {
            logToConsole(`Approve property called for ID: ${propertyId}`);
            if (confirm('Approve this property listing?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="property_id" value="${propertyId}">
                `;
                document.body.appendChild(form);
                logToConsole('Submitting approve form...');
                form.submit();
            }
        }
        
        function suspendProperty(propertyId) {
            logToConsole(`Suspend property called for ID: ${propertyId}`);
            document.getElementById('suspendPropertyId').value = propertyId;
            new bootstrap.Modal(document.getElementById('suspendPropertyModal')).show();
        }
        
        function deleteProperty(propertyId) {
            logToConsole(`Delete property called for ID: ${propertyId}`);
            document.getElementById('deletePropertyId').value = propertyId;
            new bootstrap.Modal(document.getElementById('deletePropertyModal')).show();
        }
    </script>
</body>
</html>