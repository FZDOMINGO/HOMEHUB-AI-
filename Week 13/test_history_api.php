<!DOCTYPE html>
<html>
<head>
    <title>Test History API</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>History API Test</h1>
    
    <div class="section">
        <h2>Test Instructions</h2>
        <p>This page tests the history API. You must be logged in to see results.</p>
        <p>Current Session Status: 
            <?php
            session_start();
            if (isset($_SESSION['user_id'])) {
                echo "✅ Logged in as User ID: " . $_SESSION['user_id'] . " (" . $_SESSION['user_type'] . ")";
            } else {
                echo "❌ Not logged in";
            }
            ?>
        </p>
    </div>
    
    <div class="section">
        <h2>API Response</h2>
        <button onclick="testAPI()">Test History API</button>
        <div id="result"></div>
    </div>
    
    <script>
        async function testAPI() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p>Loading...</p>';
            
            try {
                const response = await fetch('api/get-history.php?category=all&limit=5');
                const data = await response.json();
                
                resultDiv.innerHTML = '<h3>Response:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
            } catch (error) {
                resultDiv.innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
            }
        }
    </script>
</body>
</html>
