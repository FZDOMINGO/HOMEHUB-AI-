<!DOCTYPE html>
<html>
<head>
    <title>Test Email API Debug</title>
</head>
<body>
    <h1>Email API Debug Tool</h1>
    <button onclick="testAPI()">Test API Call</button>
    <div id="result"></div>
    
    <script>
        function testAPI() {
            document.getElementById('result').innerHTML = 'Testing...';
            
            fetch('api/test-email.php')
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.text(); // Get as text first to see what's being returned
                })
                .then(text => {
                    document.getElementById('result').innerHTML = '<h3>Raw Response:</h3><pre>' + text + '</pre>';
                    console.log('Raw response:', text);
                    
                    // Try to parse as JSON
                    try {
                        const data = JSON.parse(text);
                        document.getElementById('result').innerHTML += '<h3>Parsed JSON:</h3><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    } catch (e) {
                        document.getElementById('result').innerHTML += '<h3 style="color: red;">JSON Parse Error:</h3><pre>' + e.message + '</pre>';
                    }
                })
                .catch(error => {
                    document.getElementById('result').innerHTML = '<h3 style="color: red;">Fetch Error:</h3><pre>' + error.message + '</pre>';
                });
        }
    </script>
</body>
</html>
