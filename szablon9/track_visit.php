<?php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/visitor_tracking.log');

// Set CORS and JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Function to log errors with more detail
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' Context: ' . json_encode($context) : '';
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = isset($backtrace[1]) ? basename($backtrace[1]['file']) . ':' . $backtrace[1]['line'] : 'unknown';
    $logMessage = "[$timestamp] [$caller] $message$contextStr\n";
    error_log($logMessage, 3, __DIR__ . '/logs/visitor_tracking.log');
}

// Function to check and create tables
function ensureTablesExist($pdo) {
    try {
        // Create logs directory if it doesn't exist
        if (!file_exists(__DIR__ . '/logs')) {
            mkdir(__DIR__ . '/logs', 0755, true);
        }
        
        // Sprawdź czy połączenie z bazą danych istnieje
        if (!isset($pdo) || $pdo === null) {
            // Spróbuj ponownie nawiązać połączenie z bazą danych
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                logError("Utworzono nowe połączenie z bazą danych");
            } catch (PDOException $e) {
                logError("Błąd podczas próby ponownego nawiązania połączenia z bazą: " . $e->getMessage());
                throw $e;
            }
        }

        // Check if visitors table exists
        $visitorsTableExists = false;
        $sql = "SHOW TABLES LIKE 'visitors'";
        $result = $pdo->query($sql);
        $visitorsTableExists = $result->rowCount() > 0;
        
        if (!$visitorsTableExists) {
            // Create visitors table if it doesn't exist
            $sql = "CREATE TABLE IF NOT EXISTS visitors (
                id VARCHAR(50) PRIMARY KEY,
                page TEXT NOT NULL,
                referrer TEXT,
                user_agent VARCHAR(255),
                screen_resolution VARCHAR(20),
                language VARCHAR(10),
                ip_address VARCHAR(45),
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_last_activity (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            logError("Created visitors table successfully");
        }
        
        // Check if visitor_history table exists
        $historyTableExists = false;
        $sql = "SHOW TABLES LIKE 'visitor_history'";
        $result = $pdo->query($sql);
        $historyTableExists = $result->rowCount() > 0;
        
        if (!$historyTableExists) {
            // Create visitor_history table if it doesn't exist
            $sql = "CREATE TABLE IF NOT EXISTS visitor_history (
                id INT NOT NULL AUTO_INCREMENT,
                visitor_id VARCHAR(50) NOT NULL,
                page VARCHAR(255) NOT NULL,
                referrer TEXT,
                user_agent VARCHAR(255),
                screen_resolution VARCHAR(20),
                language VARCHAR(10),
                ip_address VARCHAR(45),
                visit_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_visitor_id (visitor_id),
                INDEX idx_visit_time (visit_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            logError("Created visitor_history table successfully");
        }
        
        return true;
    } catch (PDOException $e) {
        logError("Database error ensuring tables exist: " . $e->getMessage(), [
            'error_code' => $e->getCode(),
            'sql_state' => $e->errorInfo[0] ?? null
        ]);
        return false;
    } catch (Exception $e) {
        logError("Error ensuring tables exist: " . $e->getMessage());
        return false;
    }
}

// Function to clean old entries (older than 5 minutes)
function cleanOldVisitors($pdo) {
    try {
        $sql = "DELETE FROM visitors WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute();
        
        if ($result) {
            $rowCount = $stmt->rowCount();
            logError("Cleaned {$rowCount} old visitor records");
        }
        
        return true;
    } catch (PDOException $e) {
        logError("Failed to clean old visitors: " . $e->getMessage(), [
            'error_code' => $e->getCode(),
            'sql_state' => $e->errorInfo[0] ?? null
        ]);
        return false;
    }
}

// Function to get active visitors
function getVisitors($pdo) {
    try {
        // First check if tables exist
        if (!ensureTablesExist($pdo)) {
            throw new Exception("Visitors tables do not exist");
        }
        
        // Clean old entries
        cleanOldVisitors($pdo);
        
        // Get active visitors
        $sql = "SELECT * FROM visitors ORDER BY last_activity DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return [
            'status' => 'success',
            'visitors' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (Exception $e) {
        logError("Failed to get visitors: " . $e->getMessage());
        throw $e;
    }
}

// Function to get visitor history
function getVisitorHistory($pdo, $days = 7, $limit = 100) {
    try {
        // Check if tables exist
        if (!ensureTablesExist($pdo)) {
            throw new Exception("Visitor tables do not exist");
        }
        
        // Get visitor history for the specified time period
        $sql = "SELECT * FROM visitor_history 
                WHERE visit_time >= DATE_SUB(NOW(), INTERVAL :days DAY) 
                ORDER BY visit_time DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'status' => 'success',
            'history' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (Exception $e) {
        logError("Failed to get visitor history: " . $e->getMessage());
        throw $e;
    }
}

// Function to update visitor status and record history
function updateVisitor($pdo, $page) {
    try {
        // Check if tables exist
        if (!ensureTablesExist($pdo)) {
            throw new Exception("Visitor tables do not exist");
        }
        
        // Parse JSON input
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            throw new Exception('Invalid JSON data');
        }

        // Get visitor IP
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        // Generate unique visitor ID if not exists
        if (!isset($_COOKIE['visitor_id'])) {
            $visitor_id = uniqid('v_', true);
            setcookie('visitor_id', $visitor_id, time() + (86400 * 30), '/'); // Cookie valid for 30 days
        } else {
            $visitor_id = $_COOKIE['visitor_id'];
        }
        
        // Prepare visitor data
        $visitorData = [
            ':id' => $visitor_id,
            ':page' => $data['page_url'] ?? $page,
            ':referrer' => $data['referrer'] ?? null,
            ':user_agent' => $data['user_agent'] ?? null,
            ':screen_resolution' => $data['screen_resolution'] ?? null,
            ':language' => $data['language'] ?? null,
            ':ip_address' => $ip_address
        ];
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Update active visitors table
        $sql = "INSERT INTO visitors 
                (id, page, referrer, user_agent, screen_resolution, language, ip_address, last_activity)
                VALUES 
                (:id, :page, :referrer, :user_agent, :screen_resolution, :language, :ip_address, NOW())
                ON DUPLICATE KEY UPDATE 
                    page = VALUES(page),
                    referrer = VALUES(referrer),
                    user_agent = VALUES(user_agent),
                    screen_resolution = VALUES(screen_resolution),
                    language = VALUES(language),
                    ip_address = VALUES(ip_address),
                    last_activity = NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($visitorData);
        
        // Add to visitor history
        $sql = "INSERT INTO visitor_history 
                (visitor_id, page, referrer, user_agent, screen_resolution, language, ip_address)
                VALUES 
                (:id, :page, :referrer, :user_agent, :screen_resolution, :language, :ip_address)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($visitorData);
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'status' => 'success',
            'visitor_id' => $visitor_id
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        logError("Error updating visitor: " . $e->getMessage(), [
            'page' => $page ?? 'unknown'
        ]);
        throw $e;
    }
}

// Handle requests
try {
    // Test database connection
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get':
            echo json_encode(getVisitors($pdo));
            break;
            
        case 'history':
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            echo json_encode(getVisitorHistory($pdo, $days, $limit));
            break;
            
        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            $page = $data['page'] ?? '/';
            echo json_encode(updateVisitor($pdo, $page));
            break;
            
        default:
            throw new Exception('Invalid action specified. Expected "get", "history" or "update", got: ' . $action);
    }
} catch (PDOException $e) {
    logError("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'A database error occurred'
    ]);
} catch (Exception $e) {
    logError("API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 