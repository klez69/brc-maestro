<?php
require_once 'config.php';

// Rozpocznij sesję
session_start();

// Ustawienia bezpieczeństwa sesji
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? 1 : 0);

// Funkcja do logowania błędów
function logAuthError($message, $username = null) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    
    $logMessage = "[$timestamp] [Auth Error] [IP: $ip] ";
    if ($username) {
        $logMessage .= "[User: $username] ";
    }
    $logMessage .= "$message [UA: $userAgent]\n";
    
    error_log($logMessage, 3, __DIR__ . '/logs/auth.log');
}

// Sprawdź czy to żądanie POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin/login.html');
    exit();
}

// Sprawdź token CSRF
$token = isset($_POST['token']) ? $_POST['token'] : '';
$sessionToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

if (empty($token) || empty($sessionToken) || $token !== $sessionToken) {
    logAuthError('CSRF token validation failed');
    header('Location: admin/login.html?error=csrf');
    exit();
}

// Pobierz dane z formularza
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']) ? true : false;

// Walidacja podstawowa
if (empty($username) || empty($password)) {
    logAuthError('Empty username or password');
    header('Location: admin/login.html?error=auth');
    exit();
}

try {
    // Połączenie z bazą danych
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Przygotuj zapytanie do bazy danych
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM admin_users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();
    
    // Tymczasowe rozwiązanie - jeśli nie ma tabel lub użytkowników, zastosuj statyczne dane
    // W prawdziwym środowisku powinieneś wcześniej ustawić bazę danych
    if (!$user) {
        // Tylko do testów - w prawdziwym środowisku nigdy nie używaj stałych haseł w kodzie
        // Ten fragment należy usunąć po ustawieniu bazy danych
        if ($username === 'admin' && $password === 'admin123') {
            // Sukces - użytkownik uwierzytelniony
            
            // Generuj unikalny token sesji
            $sessionId = bin2hex(random_bytes(32));
            
            // Zapisz dane sesji
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'admin';
            $_SESSION['logged_in'] = true;
            $_SESSION['session_id'] = $sessionId;
            $_SESSION['last_activity'] = time();
            $_SESSION['expires'] = time() + (30 * 60); // 30 minut
            
            // Usuń token CSRF
            unset($_SESSION['csrf_token']);
            
            // Jeśli zaznaczono "zapamiętaj mnie"
            if ($remember) {
                // Zapisz w localStorage (na 30 dni)
                setcookie('admin_token', $sessionId, time() + (30 * 24 * 60 * 60), '/', '', ENVIRONMENT === 'production', true);
                
                // Dodaj rekord do bazy z długotrwałym tokenem (w prawdziwej implementacji)
                // $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expires) VALUES (:user_id, :token, :expires)");
                // $stmt->execute([
                //     ':user_id' => 1,
                //     ':token' => $sessionId,
                //     ':expires' => date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60))
                // ]);
            }
            
            // Zapisz informację w localStorage dla kompatybilności z obecnym kodem
            echo '<script>
                if (' . ($remember ? 'true' : 'false') . ') {
                    localStorage.setItem("adminLoggedIn", "true");
                } else {
                    sessionStorage.setItem("adminLoggedIn", "true");
                }
                window.location.href = "admin/dashboard.html";
            </script>';
            exit;
        } else {
            // Nieprawidłowe dane logowania
            logAuthError('Invalid credentials', $username);
            header('Location: admin/login.html?error=auth');
            exit();
        }
    }
    
    // Weryfikacja hasła
    if (password_verify($password, $user['password'])) {
        // Sukces - użytkownik uwierzytelniony
        
        // Generuj unikalny token sesji
        $sessionId = bin2hex(random_bytes(32));
        
        // Zapisz dane sesji
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['last_activity'] = time();
        $_SESSION['expires'] = time() + (30 * 60); // 30 minut
        
        // Usuń token CSRF
        unset($_SESSION['csrf_token']);
        
        // Jeśli zaznaczono "zapamiętaj mnie"
        if ($remember) {
            // Zapisz w localStorage (na 30 dni)
            setcookie('admin_token', $sessionId, time() + (30 * 24 * 60 * 60), '/', '', ENVIRONMENT === 'production', true);
            
            // Dodaj rekord do bazy z długotrwałym tokenem
            $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expires) VALUES (:user_id, :token, :expires)");
            $stmt->execute([
                ':user_id' => $user['id'],
                ':token' => $sessionId,
                ':expires' => date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60))
            ]);
        }
        
        // Zapisz informację w localStorage dla kompatybilności z obecnym kodem
        echo '<script>
            if (' . ($remember ? 'true' : 'false') . ') {
                localStorage.setItem("adminLoggedIn", "true");
            } else {
                sessionStorage.setItem("adminLoggedIn", "true");
            }
            window.location.href = "admin/dashboard.html";
        </script>';
        exit;
    } else {
        // Nieprawidłowe hasło
        logAuthError('Invalid password', $username);
        header('Location: admin/login.html?error=auth');
        exit();
    }
} catch (PDOException $e) {
    logAuthError('Database error: ' . $e->getMessage(), $username);
    header('Location: admin/login.html?error=server');
    exit();
} catch (Exception $e) {
    logAuthError('Server error: ' . $e->getMessage(), $username);
    header('Location: admin/login.html?error=server');
    exit();
} 