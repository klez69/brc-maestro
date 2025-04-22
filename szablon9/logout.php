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

// Początek procesu wylogowania
error_log("====== ROZPOCZĘCIE WYLOGOWANIA ======");
error_log("Użytkownik: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'nieznany'));

try {
    // Usuń token z bazy danych, jeśli istnieje
    if (isset($_SESSION['user_id']) && isset($_COOKIE['admin_token'])) {
        try {
            error_log("Próba usunięcia tokena z bazy danych");
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
            error_log("Token usunięty z bazy danych");
        } catch (PDOException $e) {
            logLogout('Database error during logout: ' . $e->getMessage());
            error_log("Błąd bazy danych podczas usuwania tokena: " . $e->getMessage());
            // Kontynuuj mimo błędu - chcemy dokończyć wylogowanie
        }
    }
    
    // Usuń cookie tokena
    if (isset($_COOKIE['admin_token'])) {
        error_log("Usuwanie cookie admin_token");
        setcookie('admin_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Zaloguj wylogowanie
    logLogout();
    
    // Zniszcz sesję
    error_log("Niszczenie sesji PHP");
    $_SESSION = array();
    
    // Usuń ciasteczko sesji
    if (ini_get("session.use_cookies")) {
        error_log("Usuwanie ciasteczka sesji");
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    // Dodajemy HTML z JavaScript do wyczyszczenia localStorage/sessionStorage
    error_log("Przekierowanie do strony logowania");
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wylogowanie</title>
    <script>
        // Czyszczenie localStorage i sessionStorage
        localStorage.removeItem('adminLoggedIn');
        sessionStorage.removeItem('adminLoggedIn');
        sessionStorage.removeItem('csrf_token');
        
        // Przekierowanie do strony logowania
        window.location.href = 'admin/login.html';
    </script>
</head>
<body>
    <p>Trwa wylogowywanie...</p>
</body>
</html>
<?php
    exit();
    
} catch (Exception $e) {
    logLogout('Error during logout: ' . $e->getMessage());
    error_log("Błąd podczas wylogowania: " . $e->getMessage());
    
    // Nawet w przypadku błędu, próbuj wylogować użytkownika
    $_SESSION = array();
    session_destroy();
    
    // Przekieruj do strony logowania
    header('Location: admin/login.html');
    exit();
} 