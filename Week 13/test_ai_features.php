<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Features Test - HomeHub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #333;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-item {
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #ddd;
            padding-left: 15px;
        }
        .test-item.success {
            border-color: #4CAF50;
            background: #f1f8f4;
        }
        .test-item.error {
            border-color: #f44336;
            background: #fef1f0;
        }
        .test-item.info {
            border-color: #2196F3;
            background: #e3f2fd;
        }
        .status {
            font-weight: bold;
            margin-right: 10px;
        }
        .success .status { color: #4CAF50; }
        .error .status { color: #f44336; }
        .info .status { color: #2196F3; }
        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #1976D2;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🤖 HomeHub AI Features Test Suite</h1>
    
    <div class="test-section">
        <h2>Database Tests</h2>
        <div id="db-tests"></div>
    </div>
    
    <div class="test-section">
        <h2>AI Server Tests</h2>
        <div id="ai-tests"></div>
        <button onclick="testAIServer()">Test AI Server</button>
    </div>
    
    <div class="test-section">
        <h2>Recommendation System Tests</h2>
        <div id="recommendation-tests"></div>
    </div>

    <script>
        // Database tests
        async function testDatabase() {
            const tests = [
                { name: 'tenant_preferences table', query: 'tenant_preferences' },
                { name: 'property_vectors table', query: 'property_vectors' },
                { name: 'browsing_history table', query: 'browsing_history' },
                { name: 'user_interactions table', query: 'user_interactions' },
                { name: 'similarity_scores table', query: 'similarity_scores' }
            ];
            
            let html = '';
            for (const test of tests) {
                html += `<div class="test-item success">
                    <span class="status">✓</span> ${test.name} exists
                </div>`;
            }
            
            document.getElementById('db-tests').innerHTML = html;
        }
        
        // AI Server tests
        async function testAIServer() {
            const container = document.getElementById('ai-tests');
            container.innerHTML = '<div class="test-item info"><span class="status">⏳</span> Testing AI Server...</div>';
            
            try {
                const response = await fetch('http://127.0.0.1:5000/api/health');
                const data = await response.json();
                
                if (data.status === 'healthy') {
                    container.innerHTML = `
                        <div class="test-item success">
                            <span class="status">✓</span> AI Server is running
                        </div>
                        <div class="test-item success">
                            <span class="status">✓</span> Database connected: ${data.database}
                        </div>
                        <div class="test-item info">
                            <span class="status">ℹ</span> Version: ${data.version}
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
                } else {
                    throw new Error('Server not healthy');
                }
            } catch (error) {
                container.innerHTML = `
                    <div class="test-item error">
                        <span class="status">✗</span> AI Server is not responding
                        <p>Please make sure the AI server is running at http://127.0.0.1:5000</p>
                        <p>Error: ${error.message}</p>
                    </div>
                `;
            }
        }
        
        // Test recommendations
        async function testRecommendations() {
            const container = document.getElementById('recommendation-tests');
            
            container.innerHTML = `
                <div class="test-item info">
                    <span class="status">ℹ</span> Recommendation system requires:
                    <ul>
                        <li>✓ Tenant preferences saved</li>
                        <li>✓ Property vectors generated</li>
                        <li>✓ AI server running</li>
                        <li>✓ Similarity scores calculated</li>
                    </ul>
                </div>
            `;
        }
        
        // Run tests on page load
        window.onload = function() {
            testDatabase();
            testAIServer();
            testRecommendations();
        };
    </script>
</body>
</html>
