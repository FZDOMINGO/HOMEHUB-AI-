<?php
/**
 * Admin Preview Mode Indicator - Simple Floating Button
 * Shows a minimal floating button when admin is previewing the site as a guest
 */

// Check if in preview mode
if (isset($_SESSION['preview_mode']) && $_SESSION['preview_mode']['active']) {
    $adminName = $_SESSION['preview_mode']['admin_name'];
    
    // Simple relative path to admin
    $currentPath = $_SERVER['PHP_SELF'];
    if (strpos($currentPath, '/guest/') !== false) {
        $adminPath = '../admin/dashboard.php';
    } else {
        $adminPath = 'admin/dashboard.php';
    }
    ?>
    
    <!-- Simple floating admin button -->
    <div id="admin-preview-btn" style="
        position: fixed; 
        top: 20px; 
        right: 20px; 
        z-index: 9999;
        background: #007bff;
        color: white;
        padding: 8px 16px;
        border-radius: 25px;
        font-size: 14px;
        font-family: Arial, sans-serif;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        border: none;
        transition: all 0.3s ease;
    ">
        <a href="<?= $adminPath ?>" style="color: white; text-decoration: none; font-weight: 500;">
            ← Return to Admin Dashboard
        </a>
    </div>
    
    <style>
        #admin-preview-btn:hover {
            background: #0056b3 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
    </style>
    
    <script>
        // Simple session cleanup when returning to admin
        document.getElementById('admin-preview-btn').addEventListener('click', function() {
            // Optional: Add any cleanup here
            return true; // Allow navigation
        });
    </script>
    
    <?php
}
?>