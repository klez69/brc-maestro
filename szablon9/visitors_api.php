<?php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/visitors_error.log');

// Ustaw nagłówki CORS i JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Funkcja do logowania błędów
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' Context: ' . json_encode($context) : '';
    error_log("[$timestamp] $message$contextStr");
}

// Funkcja do sprawdzania i tworzenia tabeli
function ensureTableExists($pdo) {
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'visitors'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            // Create table if it doesn't exist
            $sql = "CREATE TABLE IF NOT EXISTS `visitors` (
                `id` VARCHAR(50) NOT NULL,
                `page` VARCHAR(255) NOT NULL,
                `referrer` TEXT,
                `user_agent` VARCHAR(255),
                `screen_resolution` VARCHAR(20),
                `language` VARCHAR(10),
                `ip_address` VARCHAR(45),
                `is_logged_in` TINYINT(1) DEFAULT 0,
                `username` VARCHAR(50) NULL,
                `role` VARCHAR(20) NULL,
                `user_id` VARCHAR(50) NULL,
                `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_last_activity` (`last_activity`),
                INDEX `idx_is_logged_in` (`is_logged_in`),
                INDEX `idx_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            // Check if table was created successfully
            $stmt = $pdo->query("SHOW TABLES LIKE 'visitors'");
            $tableCreated = $stmt->rowCount() > 0;
            
            if ($tableCreated) {
                logError("Visitors table created successfully");
                return true;
            } else {
                logError("Failed to create visitors table");
                return false;
            }
        }
        
        // Check if history table exists and create if needed
        $stmt = $pdo->query("SHOW TABLES LIKE 'visitor_history'");
        $historyTableExists = $stmt->rowCount() > 0;
        
        if (!$historyTableExists) {
            // Create history table
            $sql = "CREATE TABLE IF NOT EXISTS `visitor_history` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `visitor_id` VARCHAR(50) NOT NULL,
                `page` VARCHAR(255) NOT NULL,
                `referrer` TEXT,
                `user_agent` VARCHAR(255),
                `screen_resolution` VARCHAR(20),
                `language` VARCHAR(10),
                `ip_address` VARCHAR(45),
                `is_logged_in` TINYINT(1) DEFAULT 0,
                `username` VARCHAR(50) NULL,
                `role` VARCHAR(20) NULL,
                `user_id` VARCHAR(50) NULL,
                `visit_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_visitor_id` (`visitor_id`),
                INDEX `idx_visit_time` (`visit_time`),
                INDEX `idx_is_logged_in` (`is_logged_in`),
                INDEX `idx_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            logError("Visitor history table created successfully");
        }
        
        // Check if the visitors table has the required columns
        try {
            $columns = [];
            $stmt = $pdo->query("SHOW COLUMNS FROM visitors");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            // Add missing columns if needed
            $requiredColumns = [
                'page' => "ALTER TABLE `visitors` ADD COLUMN `page` VARCHAR(255) NOT NULL DEFAULT '/'",
                'referrer' => "ALTER TABLE `visitors` ADD COLUMN `referrer` TEXT",
                'user_agent' => "ALTER TABLE `visitors` ADD COLUMN `user_agent` VARCHAR(255)",
                'screen_resolution' => "ALTER TABLE `visitors` ADD COLUMN `screen_resolution` VARCHAR(20)",
                'language' => "ALTER TABLE `visitors` ADD COLUMN `language` VARCHAR(10)",
                'ip_address' => "ALTER TABLE `visitors` ADD COLUMN `ip_address` VARCHAR(45)",
                'is_logged_in' => "ALTER TABLE `visitors` ADD COLUMN `is_logged_in` TINYINT(1) DEFAULT 0",
                'username' => "ALTER TABLE `visitors` ADD COLUMN `username` VARCHAR(50) NULL",
                'role' => "ALTER TABLE `visitors` ADD COLUMN `role` VARCHAR(20) NULL",
                'user_id' => "ALTER TABLE `visitors` ADD COLUMN `user_id` VARCHAR(50) NULL"
            ];
            
            foreach ($requiredColumns as $column => $alterSql) {
                if (!in_array($column, $columns)) {
                    $pdo->exec($alterSql);
                    logError("Added missing column '$column' to visitors table");
                    
                    // Add index for searchable columns if they're missing
                    if ($column === 'is_logged_in') {
                        $pdo->exec("ALTER TABLE `visitors` ADD INDEX `idx_is_logged_in` (`is_logged_in`)");
                    } else if ($column === 'username') {
                        $pdo->exec("ALTER TABLE `visitors` ADD INDEX `idx_username` (`username`)");
                    }
                }
            }
            
            return true;
        } catch (PDOException $e) {
            logError("Error checking table structure: " . $e->getMessage());
            return false;
        }
    } catch (PDOException $e) {
        logError("Error checking/creating visitors table: " . $e->getMessage());
        return false;
    }
}

// Funkcja do zapisywania historii odwiedzin
function recordVisitorHistory($pdo, $visitor) {
    try {
        // Check if visitor has required fields
        if (empty($visitor['id'])) {
            logError("Cannot record history: visitor ID is missing");
            return false;
        }
        
        // Insert into visitor_history table
        $sql = "INSERT INTO visitor_history (
                    visitor_id, page, referrer, user_agent, screen_resolution, 
                    language, ip_address, is_logged_in, username, role, user_id
                ) VALUES (
                    :visitor_id, :page, :referrer, :user_agent, :screen_resolution,
                    :language, :ip_address, :is_logged_in, :username, :role, :user_id
                )";
                
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':visitor_id' => $visitor['id'],
            ':page' => $visitor['page'] ?? '/',
            ':referrer' => $visitor['referrer'] ?? null,
            ':user_agent' => $visitor['user_agent'] ?? null,
            ':screen_resolution' => $visitor['screen_resolution'] ?? null,
            ':language' => $visitor['language'] ?? null,
            ':ip_address' => $visitor['ip_address'] ?? null,
            ':is_logged_in' => $visitor['is_logged_in'] ?? 0,
            ':username' => $visitor['username'] ?? null,
            ':role' => $visitor['role'] ?? null,
            ':user_id' => $visitor['user_id'] ?? null
        ]);
        
        if ($result) {
            logError("Recorded visitor history for ID: " . $visitor['id']);
            return true;
        } else {
            logError("Failed to record visitor history");
            return false;
        }
    } catch (PDOException $e) {
        logError("Error recording visitor history: " . $e->getMessage());
        return false;
    }
}

// Funkcja do czyszczenia starych wpisów (starszych niż 5 minut)
function cleanOldVisitors($pdo) {
    try {
        $sql = "DELETE FROM visitors WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        logError("Failed to clean old visitors: " . $e->getMessage());
        return false;
    }
}

// Funkcja do pobierania aktywnych odwiedzających
function getVisitors($pdo) {
    try {
        // Najpierw sprawdź czy tabela istnieje
        if (!ensureTableExists($pdo)) {
            return [
                'status' => 'success',
                'visitors' => [],
                'message' => 'Tabela odwiedzających nie istnieje jeszcze'
            ];
        }
        
        // Wyczyść stare wpisy
        cleanOldVisitors($pdo);
        
        // Pobierz aktywnych odwiedzających
        $sql = "SELECT * FROM visitors ORDER BY last_activity DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'visitors' => $visitors,
            'count' => count($visitors)
        ];
    } catch (Exception $e) {
        logError("Failed to get visitors: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Błąd podczas pobierania aktywnych odwiedzających: ' . $e->getMessage(),
            'visitors' => []
        ];
    }
}

// Funkcja do pobierania historii odwiedzin
function getVisitorHistory($pdo, $days = 7, $limit = 100) {
    try {
        // Najpierw sprawdź czy tabela istnieje
        if (!ensureTableExists($pdo)) {
            return [
                'status' => 'error',
                'message' => 'Tabele odwiedzających nie istnieją'
            ];
        }
        
        // Sprawdź, czy tabela visitor_history istnieje
        $stmt = $pdo->query("SHOW TABLES LIKE 'visitor_history'");
        if ($stmt->rowCount() === 0) {
            // Tabela nie istnieje, zwróć pustą listę
            return [
                'status' => 'success',
                'history' => [],
                'message' => 'Tabela historii odwiedzających nie istnieje jeszcze'
            ];
        }
        
        // Pobierz historię odwiedzin dla określonego okresu
        $sql = "SELECT * FROM visitor_history 
                WHERE visit_time >= DATE_SUB(NOW(), INTERVAL :days DAY) 
                ORDER BY visit_time DESC 
                LIMIT :limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'history' => $history,
            'count' => count($history)
        ];
    } catch (Exception $e) {
        logError("Failed to get visitor history: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Błąd podczas pobierania historii odwiedzających: ' . $e->getMessage(),
            'history' => []
        ];
    }
}

// Funkcja do aktualizacji statusu odwiedzającego
function updateVisitor($pdo, $page) {
    try {
        // Sprawdź czy tabela istnieje
        if (!ensureTableExists($pdo)) {
            throw new Exception("Visitors table does not exist");
        }
        
        // Get request data - check both JSON and form data
        $data = null;
        $rawData = file_get_contents('php://input');
        if (!empty($rawData)) {
            $data = json_decode($rawData, true);
        }
        
        if (!is_array($data) && isset($_POST['page'])) {
            $data = $_POST;
        }
        
        // Get visitor IP
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        // Generuj unikalny identyfikator sesji jeśli nie istnieje
        if (!isset($_COOKIE['visitor_id'])) {
            $visitor_id = uniqid('v_', true);
            setcookie('visitor_id', $visitor_id, time() + (86400 * 30), '/'); // Cookie ważne przez 30 dni
        } else {
            $visitor_id = $_COOKIE['visitor_id'];
        }
        
        // Get page and user info from data
        $pageUrl = isset($data['page']) ? $data['page'] : $page;
        $referrer = isset($data['referrer']) ? $data['referrer'] : null;
        $userAgent = isset($data['user_agent']) ? $data['user_agent'] : $_SERVER['HTTP_USER_AGENT'] ?? null;
        $screenResolution = isset($data['screen_resolution']) ? $data['screen_resolution'] : null;
        $language = isset($data['language']) ? $data['language'] : null;
        
        // Get user information
        $isLoggedIn = 0;
        $username = null;
        $role = null;
        $userId = null;
        
        // Check if user information is available in the data
        if (isset($data['user_info'])) {
            $isLoggedIn = $data['user_info']['isLoggedIn'] ? 1 : 0;
            $username = $data['user_info']['username'] ?? null;
            $role = $data['user_info']['role'] ?? null;
            $userId = $data['user_info']['userId'] ?? null;
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
        
        // Create visitor record with complete data
        $visitor = [
            'id' => $visitor_id,
            'page' => $pageUrl,
            'referrer' => $referrer,
            'user_agent' => $userAgent,
            'screen_resolution' => $screenResolution,
            'language' => $language,
            'ip_address' => $ip_address,
            'is_logged_in' => $isLoggedIn,
            'username' => $username,
            'role' => $role,
            'user_id' => $userId
        ];
        
        // First record the history
        recordVisitorHistory($pdo, $visitor);
        
        // Update active visitors table
        $sql = "INSERT INTO visitors (
                    id, page, referrer, user_agent, screen_resolution, 
                    language, ip_address, is_logged_in, username, role, user_id
                ) VALUES (
                    :id, :page, :referrer, :user_agent, :screen_resolution,
                    :language, :ip_address, :is_logged_in, :username, :role, :user_id
                ) ON DUPLICATE KEY UPDATE 
                    page = :page,
                    referrer = :referrer,
                    user_agent = :user_agent,
                    screen_resolution = :screen_resolution,
                    language = :language, 
                    ip_address = :ip_address,
                    is_logged_in = :is_logged_in,
                    username = :username,
                    role = :role,
                    user_id = :user_id,
                    last_activity = NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $visitor_id,
            ':page' => $pageUrl,
            ':referrer' => $referrer,
            ':user_agent' => $userAgent,
            ':screen_resolution' => $screenResolution,
            ':language' => $language,
            ':ip_address' => $ip_address,
            ':is_logged_in' => $isLoggedIn,
            ':username' => $username,
            ':role' => $role,
            ':user_id' => $userId
        ]);
        
        return [
            'status' => 'success',
            'visitor_id' => $visitor_id,
            'is_logged_in' => $isLoggedIn,
            'username' => $username
        ];
    } catch (Exception $e) {
        logError("Error updating visitor: " . $e->getMessage(), [
            'page' => $page,
            'visitor_id' => $visitor_id ?? 'unknown'
        ]);
        throw $e;
    }
}

// Obsługa żądań
try {
    // Sprawdź autoryzację dla wszystkich żądań
    session_start();
    $isAuthorized = false;
    
    // W pełnej implementacji powinniśmy sprawdzić dane sesji
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        $isAuthorized = true;
    }
    
    // Opcjonalnie: dla kompatybilności
    if ($isAuthorized === false && 
        (isset($_COOKIE['admin_token']) || 
         (isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === 'true') ||
         (isset($_COOKIE['adminLoggedIn']) && $_COOKIE['adminLoggedIn'] === 'true') ||
         (isset($_COOKIE['visitor_id']) || isset($_COOKIE['PHPSESSID']))
        )) {
        $isAuthorized = true;
    }
    
    // Określone akcje dostępne bez uwierzytelniania
    $publicActions = ['update', 'get', 'history']; 
    
    // Bezpieczna alternatywa dla FILTER_SANITIZE_STRING
    $action = isset($_GET['action']) ? htmlspecialchars(strip_tags($_GET['action'])) : '';
    
    if (!in_array($action, $publicActions) && !$isAuthorized) {
        throw new Exception('Unauthorized access');
    }
    
    // Test database connection
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    switch ($action) {
        case 'get':
            try {
                $result = getVisitors($pdo);
                echo json_encode($result);
            } catch (Exception $e) {
                logError("Error getting visitors: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error getting visitors: ' . $e->getMessage(),
                    'visitors' => []
                ]);
            }
            break;
            
        case 'history':
            // Get days parameter with default of 7
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            
            // Ensure values are within reasonable ranges
            $days = max(1, min(30, $days));
            $limit = max(1, min(1000, $limit));
            
            try {
                $result = getVisitorHistory($pdo, $days, $limit);
                echo json_encode($result);
            } catch (Exception $e) {
                logError("Error getting visitor history: " . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error getting visitor history: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'update':
            // If no page specified, use current page or default to '/'
            $page = '/';
            
            // Handle application/x-www-form-urlencoded data
            if (isset($_POST['page'])) {
                $page = htmlspecialchars(strip_tags($_POST['page']));
            }
            
            // If no POST data, try to read from raw input (for compatibility)
            if (empty($page) || $page === '/') {
                $data = json_decode(file_get_contents('php://input'), true);
                if (is_array($data) && isset($data['page'])) {
                    $page = htmlspecialchars(strip_tags($data['page']));
                }
            }
            
            try {
                echo json_encode(updateVisitor($pdo, $page));
            } catch (Exception $e) {
                logError("Error in updateVisitor: " . $e->getMessage(), ['page' => $page]);
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error updating visitor: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
} catch (PDOException $e) {
    logError("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    logError("API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 