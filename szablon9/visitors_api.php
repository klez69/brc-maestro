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
                `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_last_activity` (`last_activity`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            // Sprawdzenie, czy tabela faktycznie została utworzona
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
        
        // Sprawdzenie, czy tabela ma poprawną strukturę
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `visitors` LIKE 'page'");
            $pageColumnExists = $stmt->rowCount() > 0;
            
            if (!$pageColumnExists) {
                logError("Column 'page' does not exist in visitors table, attempting to fix structure");
                // Próba naprawy struktury tabeli
                $pdo->exec("ALTER TABLE `visitors` ADD COLUMN `page` VARCHAR(255) NOT NULL DEFAULT '/'");
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
            throw new Exception("Visitors table does not exist");
        }
        
        // Wyczyść stare wpisy
        cleanOldVisitors($pdo);
        
        // Pobierz aktywnych odwiedzających
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

// Funkcja do aktualizacji statusu odwiedzającego
function updateVisitor($pdo, $page) {
    try {
        // Sprawdź czy tabela istnieje
        if (!ensureTableExists($pdo)) {
            throw new Exception("Visitors table does not exist");
        }
        
        // Generuj unikalny identyfikator sesji jeśli nie istnieje
        if (!isset($_COOKIE['visitor_id'])) {
            $visitor_id = uniqid('v_', true);
            setcookie('visitor_id', $visitor_id, time() + (86400 * 30), '/'); // Cookie ważne przez 30 dni
        } else {
            $visitor_id = $_COOKIE['visitor_id'];
        }
        
        // Spróbuj zaktualizować istniejący wpis
        $sql = "INSERT INTO visitors (id, page, last_activity) 
                VALUES (:id, :page, NOW()) 
                ON DUPLICATE KEY UPDATE 
                page = :page, 
                last_activity = NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $visitor_id,
            ':page' => $page
        ]);
        
        return [
            'status' => 'success',
            'visitor_id' => $visitor_id
        ];
    } catch (Exception $e) {
        logError("Error updating visitor: " . $e->getMessage(), ['page' => $page]);
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
    $publicActions = ['update', 'get']; // Dodaliśmy 'get' jako akcję publiczną
    
    // Bezpieczna alternatywa dla FILTER_SANITIZE_STRING
    $action = isset($_GET['action']) ? htmlspecialchars(strip_tags($_GET['action'])) : '';
    
    if (!in_array($action, $publicActions) && !$isAuthorized) {
        throw new Exception('Unauthorized access');
    }
    
    // Test database connection
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    switch ($action) {
        case 'get':
            echo json_encode(getVisitors($pdo));
            break;
            
        case 'update':
            // Sanityzuj i filtruj dane wejściowe
            $page = '';
            
            // Handle application/x-www-form-urlencoded data
            if (isset($_POST['page'])) {
                $page = htmlspecialchars(strip_tags($_POST['page']));
            }
            
            // If no POST data, try to read from raw input (for compatibility)
            if (empty($page)) {
                $data = json_decode(file_get_contents('php://input'), true);
                if (is_array($data) && isset($data['page'])) {
                    $page = htmlspecialchars(strip_tags($data['page']));
                }
            }
            
            // Ustaw domyślną wartość jeśli strona jest pusta
            if (empty($page)) {
                $page = '/';
            }
            
            try {
                echo json_encode(updateVisitor($pdo, $page));
            } catch (Exception $e) {
                logError("Error in updateVisitor: " . $e->getMessage(), ['page' => $page]);
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error updating visitor'
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
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    logError("API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 