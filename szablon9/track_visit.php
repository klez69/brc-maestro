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

// Inicjalizacja połączenia z bazą danych
$pdo = null;
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    error_log("Nawiązano połączenie z bazą danych");
} catch (PDOException $e) {
    error_log("Błąd połączenia z bazą danych: " . $e->getMessage());
    // Nie rzucamy wyjątku tutaj, aby skrypt mógł kontynuować
    // Obsłużymy brak połączenia w odpowiednich miejscach
}

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
                is_logged_in TINYINT(1) DEFAULT 0,
                username VARCHAR(50) NULL,
                role VARCHAR(20) NULL,
                user_id VARCHAR(50) NULL,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_last_activity (last_activity),
                INDEX idx_is_logged_in (is_logged_in),
                INDEX idx_username (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            logError("Created visitors table successfully");
        } else {
            // Check if the table has the user columns and add them if not
            $columns = $pdo->query("SHOW COLUMNS FROM visitors")->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('is_logged_in', $columns)) {
                $pdo->exec("ALTER TABLE visitors ADD COLUMN is_logged_in TINYINT(1) DEFAULT 0");
                $pdo->exec("ALTER TABLE visitors ADD INDEX idx_is_logged_in (is_logged_in)");
                logError("Added is_logged_in column to visitors table");
            }
            
            if (!in_array('username', $columns)) {
                $pdo->exec("ALTER TABLE visitors ADD COLUMN username VARCHAR(50) NULL");
                $pdo->exec("ALTER TABLE visitors ADD INDEX idx_username (username)");
                logError("Added username column to visitors table");
            }
            
            if (!in_array('role', $columns)) {
                $pdo->exec("ALTER TABLE visitors ADD COLUMN role VARCHAR(20) NULL");
                logError("Added role column to visitors table");
            }
            
            if (!in_array('user_id', $columns)) {
                $pdo->exec("ALTER TABLE visitors ADD COLUMN user_id VARCHAR(50) NULL");
                logError("Added user_id column to visitors table");
            }
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
                is_logged_in TINYINT(1) DEFAULT 0,
                username VARCHAR(50) NULL,
                role VARCHAR(20) NULL,
                user_id VARCHAR(50) NULL,
                visit_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_visitor_id (visitor_id),
                INDEX idx_visit_time (visit_time),
                INDEX idx_is_logged_in (is_logged_in),
                INDEX idx_username (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            logError("Created visitor_history table successfully");
        } else {
            // Check if the table has the user columns and add them if not
            $columns = $pdo->query("SHOW COLUMNS FROM visitor_history")->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('is_logged_in', $columns)) {
                $pdo->exec("ALTER TABLE visitor_history ADD COLUMN is_logged_in TINYINT(1) DEFAULT 0");
                $pdo->exec("ALTER TABLE visitor_history ADD INDEX idx_is_logged_in (is_logged_in)");
                logError("Added is_logged_in column to visitor_history table");
            }
            
            if (!in_array('username', $columns)) {
                $pdo->exec("ALTER TABLE visitor_history ADD COLUMN username VARCHAR(50) NULL");
                $pdo->exec("ALTER TABLE visitor_history ADD INDEX idx_username (username)");
                logError("Added username column to visitor_history table");
            }
            
            if (!in_array('role', $columns)) {
                $pdo->exec("ALTER TABLE visitor_history ADD COLUMN role VARCHAR(20) NULL");
                logError("Added role column to visitor_history table");
            }
            
            if (!in_array('user_id', $columns)) {
                $pdo->exec("ALTER TABLE visitor_history ADD COLUMN user_id VARCHAR(50) NULL");
                logError("Added user_id column to visitor_history table");
            }
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
        
        // Get page URL from data or default to passed page parameter
        $pageUrl = $data['page_url'] ?? $page;
        $referrer = $data['referrer'] ?? null;
        $userAgent = $data['user_agent'] ?? null;
        $screenResolution = $data['screen_resolution'] ?? null;
        $language = $data['language'] ?? null;
        
        // Get user information
        $isLoggedIn = 0;
        $username = null;
        $role = null;
        $userId = null;
        
        // Check if user information is available in the data
        if (isset($data['user_info'])) {
            $isLoggedIn = $data['user_info']['isLoggedIn'] ? 1 : 0;
            $username = $data['user_info']['username'];
            $role = $data['user_info']['role'];
            $userId = $data['user_info']['userId'];
        }
        
        // Check PHP session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Override with session data if available (more reliable than frontend)
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $isLoggedIn = 1;
            $username = $_SESSION['username'] ?? $username;
            $role = $_SESSION['role'] ?? $role;
            $userId = $_SESSION['user_id'] ?? $userId;
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Update active visitors table with user information
        $sql = "INSERT INTO visitors 
                (id, page, referrer, user_agent, screen_resolution, language, ip_address, is_logged_in, username, role, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                page = ?, 
                referrer = ?,
                user_agent = ?,
                screen_resolution = ?,
                language = ?,
                is_logged_in = ?,
                username = ?,
                role = ?,
                user_id = ?,
                last_activity = NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $visitor_id, $pageUrl, $referrer, $userAgent, $screenResolution, $language, $ip_address, $isLoggedIn, $username, $role, $userId,
            $pageUrl, $referrer, $userAgent, $screenResolution, $language, $isLoggedIn, $username, $role, $userId
        ]);
        
        // Add to visitor history with user information
        $sql = "INSERT INTO visitor_history
                (visitor_id, page, referrer, user_agent, screen_resolution, language, ip_address, is_logged_in, username, role, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $visitor_id, $pageUrl, $referrer, $userAgent, $screenResolution, $language, $ip_address, $isLoggedIn, $username, $role, $userId
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'status' => 'success',
            'visitor_id' => $visitor_id,
            'is_logged_in' => $isLoggedIn,
            'username' => $username
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        logError("Error updating visitor: " . $e->getMessage(), [
            'page' => $page
        ]);
        throw $e;
    }
}

// Handle requests
try {
    // Sprawdź czy użytkownik ma uprawnienia do wykonania poufnych operacji
    $requiresAuth = false;
    $action = isset($_GET['action']) ? htmlspecialchars(strip_tags($_GET['action'])) : '';
    
    // Operacje wymagające uwierzytelnienia
    $authRequiredActions = []; // Usuwamy 'get' i 'history' z listy wymaganych autoryzacji
    
    if (in_array($action, $authRequiredActions)) {
        $requiresAuth = true;
        
        // Rozpocznij sesję jeśli jeszcze nie istnieje
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Sprawdź dane sesji
        $isAuthorized = false;
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $isAuthorized = true;
        }
        
        // Opcjonalnie: dla kompatybilności z istniejącym kodem
        if (!$isAuthorized && 
            (isset($_COOKIE['admin_token']) || 
             (isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === 'true') ||
             (isset($_COOKIE['adminLoggedIn']) && $_COOKIE['adminLoggedIn'] === 'true') ||
             (isset($_COOKIE['visitor_id']) || isset($_COOKIE['PHPSESSID']))
            )) {
            $isAuthorized = true;
        }
        
        if (!$isAuthorized) {
            logError("Unauthorized access attempt to action: " . $action);
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
            exit();
        }
    }
    
    // Test database connection
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    switch ($action) {
        case 'get':
            echo json_encode(getVisitors($pdo));
            break;
            
        case 'history':
            // Sanityzuj i waliduj parametry
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            
            // Ustaw wartości domyślne jeśli walidacja się nie powiedzie
            if ($days < 1 || $days > 30) {
                $days = 7; // Domyślnie 7 dni
            }
            
            if ($limit < 1 || $limit > 5000) {
                $limit = 100; // Domyślnie 100 wpisów
            }
            
            echo json_encode(getVisitorHistory($pdo, $days, $limit));
            break;
            
        case 'update':
            // Sanityzuj i waliduj dane wejściowe
            $rawData = file_get_contents('php://input');
            $data = json_decode($rawData, true);
            
            if (!is_array($data)) {
                throw new Exception('Invalid JSON data');
            }
            
            // Ustaw domyślną wartość strony
            $page = isset($data['page']) ? htmlspecialchars(strip_tags($data['page'])) : '/';
            if (empty($page)) {
                $page = '/';
            }
            
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