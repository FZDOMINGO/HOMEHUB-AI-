<?php
// Database connection parameters for Railway
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // Parse Railway DATABASE_URL
    $db_parts = parse_url($database_url);
    define('DB_SERVER', $db_parts['host']);
    define('DB_USERNAME', $db_parts['user']);
    define('DB_PASSWORD', $db_parts['pass']);
    define('DB_NAME', ltrim($db_parts['path'], '/'));
    define('DB_PORT', $db_parts['port'] ?? 3306);
} else {
    // Fallback to environment variables or local defaults
    define('DB_SERVER', getenv('DB_HOST') ?: 'localhost');
    define('DB_USERNAME', getenv('DB_USER') ?: 'root');
    define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'homehub');
    define('DB_PORT', getenv('DB_PORT') ?: 3306);
}

// Create database connection - supports both mysqli and PDO
function getDbConnection() {
    // Try mysqli first (preferred)
    if (class_exists('mysqli')) {
        try {
            $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
            
            if ($conn->connect_error) {
                throw new Exception("mysqli connection failed: " . $conn->connect_error);
            }
            
            return $conn;
        } catch (Exception $e) {
            // Fall through to PDO if mysqli fails
        }
    }
    
    // Fallback to PDO
    try {
        $dsn = "mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Return PDO wrapped in mysqli-compatible class
        return new PDOMysqliAdapter($pdo);
    } catch (PDOException $e) {
        die(json_encode([
            "status" => "error",
            "message" => "Database connection failed: " . $e->getMessage()
        ]));
    }
}

// PDO to mysqli adapter class (only if needed)
if (!class_exists('PDOMysqliAdapter')) {
    class PDOMysqliAdapter {
        private $pdo;
        
        public function __construct($pdo) {
            $this->pdo = $pdo;
        }
        
        public function query($sql) {
            try {
                $stmt = $this->pdo->query($sql);
                return new PDOResultAdapter($stmt);
            } catch (PDOException $e) {
                return false;
            }
        }
        
        public function prepare($sql) {
            return $this->pdo->prepare($sql);
        }
        
        public function real_escape_string($string) {
            return trim($this->pdo->quote($string), "'");
        }
        
        public function close() {
            $this->pdo = null;
        }
        
        public function __get($name) {
            if ($name === 'insert_id') {
                return $this->pdo->lastInsertId();
            }
            return null;
        }
    }

    class PDOResultAdapter {
        private $stmt;
        public $num_rows = 0;
        
        public function __construct($stmt) {
            $this->stmt = $stmt;
            $this->num_rows = $stmt ? $stmt->rowCount() : 0;
        }
        
        public function fetch_assoc() {
            return $this->stmt ? $this->stmt->fetch(PDO::FETCH_ASSOC) : false;
        }
        
        public function fetch_all($mode = null) {
            return $this->stmt ? $this->stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    }
}
?>