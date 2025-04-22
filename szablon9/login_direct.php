<?php
require_once 'config.php';

// Rozpocznij sesję
session_start();

// Ustawienia bezpieczeństwa sesji
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? 1 : 0);

// Funkcja do szczegółowego logowania
function debug_log($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] [LOGIN DEBUG] $message", 3, __DIR__ . '/logs/login_debug.log');
}

// Początek procesu logowania
debug_log("================ NOWA PRÓBA LOGOWANIA ================");
debug_log("Metoda HTTP: " . $_SERVER['REQUEST_METHOD']);
debug_log("Dane POST: " . (empty($_POST) ? "BRAK" : "OBECNE"));

// Sprawdź czy to żądanie POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log("Błąd: Metoda inna niż POST");
    header('Location: admin/login.html?error=method');
    exit();
}

// Pobierz dane z formularza
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']) ? true : false;

// Diagnostyka wprowadzonych danych
debug_log("Username: " . ($username ? $username : "BRAK"));
debug_log("Password: " . ($password ? "PODANE (" . strlen($password) . " znaków)" : "BRAK"));
debug_log("Remember me: " . ($remember ? "TAK" : "NIE"));

// Walidacja podstawowa
if (empty($username) || empty($password)) {
    debug_log("Błąd: Puste pola formularza - username: " . (empty($username) ? "PUSTY" : "OK") . 
             ", password: " . (empty($password) ? "PUSTY" : "OK"));
    header('Location: admin/login.html?error=auth&reason=empty');
    exit();
}

// Tymczasowe logowanie - uproszczone dla diagnostyki
if ($username === 'admin' && $password === 'admin123') {
    debug_log("Sukces: Poprawne dane logowania (admin/admin123)");
    
    // Ustawienia sesji
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = 'admin';
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['expires'] = time() + (30 * 60); // 30 minut
    
    // Dla kompatybilności z localStorage
    if ($remember) {
        $token = bin2hex(random_bytes(16)); // Krótszy token, wystarczający do testów
        setcookie('admin_token', $token, time() + (30 * 24 * 60 * 60), '/', '', ENVIRONMENT === 'production', true);
        debug_log("Ustawiono cookie 'admin_token' (remember me)");
    }
    
    // Dodanie skryptu przekierowującego z ustawieniem localStorage/sessionStorage
    debug_log("Przekierowanie z ustawieniem localStorage/sessionStorage");
    
    // Zapisujemy dane w localStorage/sessionStorage przed przekierowaniem
    if ($remember) {
        echo '<script>
            localStorage.setItem("adminLoggedIn", "true");
            window.location.href = "admin/dashboard.html";
        </script>';
    } else {
        echo '<script>
            sessionStorage.setItem("adminLoggedIn", "true");
            window.location.href = "admin/dashboard.html";
        </script>';
    }
    
    // Przekierowanie na wypadek, gdyby JavaScript nie zadziałał
    debug_log("Przekierowanie przez header (zapasowe)");
    header('Location: admin/dashboard.html');
    exit();
} else {
    debug_log("Błąd: Niepoprawne dane logowania - username: " . $username . 
              ", password: " . substr($password, 0, 1) . "****");
    header('Location: admin/login.html?error=auth&reason=invalid');
    exit();
} 