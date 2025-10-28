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

// Handle test suspend action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'test_suspend') {
    $propertyId = $_POST['property_id'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    
    echo "<div class='alert alert-info'>";
    echo "<h5>🧪 Test Suspend Action Received:</h5>";
    echo "<ul>";
    echo "<li><strong>Action:</strong> " . htmlspecialchars($_POST['action']) . "</li>";
    echo "<li><strong>Property ID:</strong> " . htmlspecialchars($propertyId) . "</li>";
    echo "<li><strong>Reason:</strong> " . htmlspecialchars($reason) . "</li>";
    echo "</ul>";
    echo "</div>";
    
    // Test the actual suspend
    if ($propertyId && $reason) {
        $stmt = $conn->prepare("UPDATE properties SET status = 'suspended' WHERE id = ?");
        $stmt->bind_param("i", $propertyId);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>";
            echo "<i class='bi bi-check-circle'></i> Property successfully suspended in database!";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<i class='bi bi-x-circle'></i> Database error: " . $conn->error;
            echo "</div>";
        }
    }
}

// Get a sample property for testing
$sampleProperty = $conn->query("SELECT * FROM properties WHERE status != 'suspended' LIMIT 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suspend Property Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h4><i class="bi bi-ban"></i> Suspend Property Functionality Test</h4>
            </div>
            <div class="card-body">
                <?php if ($sampleProperty): ?>
                    <h5>Testing with Property: <?= htmlspecialchars($sampleProperty['title']) ?> (ID: <?= $sampleProperty['id'] ?>)</h5>
                    <p><strong>Current Status:</strong> <span class="badge bg-<?= $sampleProperty['status'] === 'available' ? 'success' : 'secondary' ?>"><?= ucfirst($sampleProperty['status']) ?></span></p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Test Suspend Form</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="testSuspendForm">
                                        <input type="hidden" name="action" value="test_suspend">
                                        <input type="hidden" name="property_id" value="<?= $sampleProperty['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Reason for Suspension</label>
                                            <select class="form-select" id="testReasonSelect" required>
                                                <option value="">Select a reason...</option>
                                                <option value="Test suspension">Test suspension</option>
                                                <option value="Inappropriate content">Inappropriate content</option>
                                                <option value="Policy violation">Policy violation</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Additional Details</label>
                                            <textarea class="form-control" id="testAdditionalDetails" rows="3" 
                                                      placeholder="Optional additional details..."></textarea>
                                        </div>
                                        
                                        <input type="hidden" name="reason" id="testCombinedReason">
                                        
                                        <button type="submit" class="btn btn-warning">
                                            <i class="bi bi-ban"></i> Test Suspend Property
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Debug Information</h6>
                                </div>
                                <div class="card-body">
                                    <div id="debug-output" class="bg-dark text-light p-3 rounded" style="height: 250px; overflow-y: scroll; font-family: monospace;">
                                        Debug output will appear here...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Comparison Test:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <button class="btn btn-outline-warning w-100" onclick="testDirectSuspend(<?= $sampleProperty['id'] ?>)">
                                    <i class="bi bi-gear"></i> Test Direct JavaScript Submit
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="admin/properties.php" target="_blank" class="btn btn-primary w-100">
                                    <i class="bi bi-house-gear"></i> Test Real Properties Page
                                </a>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> No unsuspended properties found for testing.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function logDebug(message) {
            const debugOutput = document.getElementById('debug-output');
            const timestamp = new Date().toLocaleTimeString();
            debugOutput.innerHTML += `[${timestamp}] ${message}\n`;
            debugOutput.scrollTop = debugOutput.scrollHeight;
            console.log(message);
        }

        logDebug('Test page loaded');

        // Handle test form submission
        document.getElementById('testSuspendForm').addEventListener('submit', function(e) {
            logDebug('Test suspend form submitted');
            
            const reasonSelect = document.getElementById('testReasonSelect');
            const additionalDetails = document.getElementById('testAdditionalDetails').value;
            const combinedReasonField = document.getElementById('testCombinedReason');
            
            if (!reasonSelect.value) {
                e.preventDefault();
                alert('Please select a reason');
                logDebug('ERROR: No reason selected');
                return false;
            }
            
            let finalReason = reasonSelect.value;
            if (additionalDetails.trim()) {
                finalReason += ': ' + additionalDetails.trim();
            }
            
            combinedReasonField.value = finalReason;
            logDebug('Combined reason: ' + finalReason);
            logDebug('Form submitting...');
        });

        function testDirectSuspend(propertyId) {
            logDebug('Testing direct suspend for property ID: ' + propertyId);
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="test_suspend">
                <input type="hidden" name="property_id" value="${propertyId}">
                <input type="hidden" name="reason" value="Direct JavaScript test suspension">
            `;
            
            document.body.appendChild(form);
            logDebug('Direct form created and submitting...');
            form.submit();
        }
    </script>
</body>
</html>