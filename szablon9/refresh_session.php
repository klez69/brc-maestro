<?php
require_once 'config.php';

// Rozpocznij sesję
session_start();

// Ustawienia bezpieczeństwa sesji
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Ustaw nagłówki JSON
header('Content-Type: application/json');

// Funkcja do logowania błędów
function logRefreshError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
    
    $logMessage = "[$timestamp] [Session Refresh] [IP: $ip] [User: $username] $message [UA: $userAgent]\n";
    error_log($logMessage, 3, __DIR__ . '/logs/auth.log');
}

try {
    // Sprawdź, czy użytkownik jest zalogowany
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        logRefreshError('Attempt to refresh invalid session');
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit();
    }

    // Sprawdź, czy sesja nie wygasła
    if (!isset($_SESSION['expires']) || $_SESSION['expires'] < time()) {
        logRefreshError('Attempt to refresh expired session');
        session_unset();
        session_destroy();
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Session expired']);
        exit();
    }

    // Odśwież czas sesji
    $_SESSION['last_activity'] = time();
    $_SESSION['expires'] = time() + (30 * 60); // 30 minut
    
    // Jeśli mamy token długoterminowy, możemy go również odświeżyć
    if (isset($_COOKIE['admin_token']) && isset($_SESSION['user_id'])) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Aktualizuj datę wygaśnięcia tokena
            $stmt = $pdo->prepare("UPDATE auth_tokens SET last_used = NOW() WHERE token = :token AND user_id = :user_id");
            $stmt->execute([
                ':token' => $_COOKIE['admin_token'],
                ':user_id' => $_SESSION['user_id']
            ]);
        } catch (PDOException $e) {
            // Loguj błąd, ale kontynuuj - odświeżanie tokena nie jest krytyczne
            logRefreshError('Database error refreshing token: ' . $e->getMessage());
        }
    }
    
    // Odpowiedz sukcesem
    echo json_encode([
        'status' => 'success',
        'message' => 'Session refreshed',
        'expires_in' => 30 * 60 // 30 minut w sekundach
    ]);
} catch (Exception $e) {
    logRefreshError('Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
    exit();
} 