<?php
require_once 'config.php';

// Rozpocznij sesję
session_start();

// Funkcja do logowania wylogowań
function logLogout($message = 'User logged out') {
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
    
    $logMessage = "[$timestamp] [Logout] [IP: $ip] [User: $username] $message [UA: $userAgent]\n";
    error_log($logMessage, 3, __DIR__ . '/logs/auth.log');
}

try {
    // Usuń token z bazy danych, jeśli istnieje
    if (isset($_SESSION['user_id']) && isset($_COOKIE['admin_token'])) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Usuń token z bazy danych
            $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE token = :token AND user_id = :user_id");
            $stmt->execute([
                ':token' => $_COOKIE['admin_token'],
                ':user_id' => $_SESSION['user_id']
            ]);
            
            logLogout('Token removed from database');
        } catch (PDOException $e) {
            logLogout('Database error during logout: ' . $e->getMessage());
            // Kontynuuj mimo błędu - chcemy dokończyć wylogowanie
        }
    }
    
    // Usuń cookie tokena
    if (isset($_COOKIE['admin_token'])) {
        setcookie('admin_token', '', time() - 3600, '/', '', true, true);
    }
    
    // Usuń dane z localStorage/sessionStorage przez JavaScript
    $clearStorageScript = "
    <script>
        localStorage.removeItem('adminLoggedIn');
        sessionStorage.removeItem('adminLoggedIn');
        sessionStorage.removeItem('csrf_token');
        
        // Przekieruj do strony logowania po wyczyszczeniu
        window.location.href = 'admin/login.html';
    </script>
    ";
    
    // Zaloguj wylogowanie
    logLogout();
    
    // Zniszcz sesję
    $_SESSION = array();
    
    // Usuń ciasteczko sesji
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    // Wyświetl skrypt JavaScript do wyczyszczenia localStorage/sessionStorage
    echo $clearStorageScript;
    exit();
    
} catch (Exception $e) {
    logLogout('Error during logout: ' . $e->getMessage());
    
    // Nawet w przypadku błędu, próbuj wylogować użytkownika
    $_SESSION = array();
    session_destroy();
    
    // Przekieruj do strony logowania
    header('Location: admin/login.html');
    exit();
} 