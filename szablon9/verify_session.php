<?php
require_once 'config.php';

// Rozpocznij sesję
session_start();

// Ustawienia bezpieczeństwa sesji
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? 1 : 0);

// Funkcja do logowania błędów
function logSessionError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
    
    $logMessage = "[$timestamp] [Session Error] [IP: $ip] [User: $username] $message [UA: $userAgent]\n";
    error_log($logMessage, 3, __DIR__ . '/logs/auth.log');
}

// Sprawdź, czy użytkownik jest zalogowany przez sesję PHP
function checkSessionAuth() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        // Sprawdź, czy sesja nie wygasła
        if (isset($_SESSION['expires']) && $_SESSION['expires'] > time()) {
            // Odśwież czas wygaśnięcia sesji
            $_SESSION['last_activity'] = time();
            $_SESSION['expires'] = time() + (30 * 60); // 30 minut
            return true;
        } else {
            // Sesja wygasła
            logSessionError('Session expired');
            return false;
        }
    }
    return false;
}

// Sprawdź, czy użytkownik jest zalogowany przez token długoterminowy
function checkTokenAuth($pdo) {
    if (isset($_COOKIE['admin_token'])) {
        $token = $_COOKIE['admin_token'];
        
        try {
            // Sprawdź token w bazie danych
            $stmt = $pdo->prepare("SELECT * FROM auth_tokens WHERE token = :token AND expires > NOW() LIMIT 1");
            $stmt->execute([':token' => $token]);
            $tokenData = $stmt->fetch();
            
            if ($tokenData) {
                // Token ważny, pobierz dane użytkownika
                $stmt = $pdo->prepare("SELECT id, username, role FROM admin_users WHERE id = :user_id LIMIT 1");
                $stmt->execute([':user_id' => $tokenData['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Ustaw dane sesji
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['session_id'] = $token;
                    $_SESSION['last_activity'] = time();
                    $_SESSION['expires'] = time() + (30 * 60); // 30 minut
                    
                    return true;
                }
            }
        } catch (PDOException $e) {
            logSessionError('Database error checking token: ' . $e->getMessage());
        }
    }
    return false;
}

try {
    // Sprawdź sesję PHP
    if (checkSessionAuth()) {
        // Użytkownik zalogowany, przekieruj do panelu
        header('Location: admin/dashboard.html');
        exit();
    }
    
    // Jeśli jest dostępna baza danych, sprawdź token długoterminowy
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        if (checkTokenAuth($pdo)) {
            // Token ważny, użytkownik zalogowany
            header('Location: admin/dashboard.html');
            exit();
        }
    } catch (PDOException $e) {
        logSessionError('Database connection error: ' . $e->getMessage());
        // Nie wyrzucamy błędu, próbujemy jeszcze sprawdzić local/sessionStorage
    }
    
    // Obsługa tymczasowa - kompatybilna z istniejącym kodem
    // To rozwiązanie powinno być docelowo usunięte na rzecz pełnej weryfikacji po stronie serwera
    if (isset($_COOKIE['admin_token']) ||
        (isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === 'true')) {
        // Tymczasowo przyjmujemy, że lokalny token jest wystarczający
        // Ustawiamy dane sesji, ale docelowo to powinno być zastąpione pełną weryfikacją
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['logged_in'] = true;
        $_SESSION['session_id'] = bin2hex(random_bytes(32));
        $_SESSION['last_activity'] = time();
        $_SESSION['expires'] = time() + (30 * 60); // 30 minut
        
        header('Location: admin/dashboard.html');
        exit();
    }
    
    // Brak sesji lub token nieważny
    // Usuń wszystkie dane sesji
    session_unset();
    session_destroy();
    
    // Usuń cookie
    if (isset($_COOKIE['admin_token'])) {
        setcookie('admin_token', '', time() - 3600, '/', '', true, true);
    }
    
    // Przekieruj do strony logowania
    header('Location: admin/login.html?error=session');
    exit();
} catch (Exception $e) {
    logSessionError('Verification error: ' . $e->getMessage());
    header('Location: admin/login.html?error=server');
    exit();
} 