<?php
require_once 'config.php';

// Ustawienia sesji muszą być przed session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? 1 : 0);
ini_set('session.use_strict_mode', 1); // Dodajemy tryb ścisłego sprawdzania ID sesji
ini_set('session.gc_maxlifetime', 1800); // 30 minut
ini_set('session.cookie_lifetime', 0); // Do zamknięcia przeglądarki jeśli bez "zapamiętaj mnie"

// Rozpocznij sesję
session_start();

// Funkcja logująca błędy z informacją o debugowaniu
function logAuthError($message, $debug = true) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    
    // Upewnij się, że katalog logów istnieje
    if (!file_exists(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    $logMessage = "[$timestamp] [Login Attempt] [IP: $ip] $message [UA: $userAgent]\n";
    error_log($logMessage, 3, __DIR__ . '/logs/auth.log');
    
    if ($debug && ENVIRONMENT !== 'production') {
        return $message;
    }
    
    return '';
}

// Sprawdź metodę żądania
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = logAuthError('Invalid request method');
    header('Location: admin/login.html?error=invalid_request' . ($error ? '&debug=' . urlencode($error) : ''));
    exit();
}

// Sprawdź, czy pola są wypełnione
if (empty($_POST['username']) || empty($_POST['password'])) {
    $error = logAuthError('Username or password empty');
    header('Location: admin/login.html?error=empty_fields' . ($error ? '&debug=' . urlencode($error) : ''));
    exit();
}

$username = $_POST['username'];
$password = $_POST['password'];
$remember = isset($_POST['remember']) ? true : false;

// Zapis próby logowania
logAuthError("Login attempt for username: $username", false);

// Próba połączenia z bazą danych
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    logAuthError("Database connection successful", false);
    
    // Sprawdź, czy tabela admin_users istnieje
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'admin_users'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        logAuthError("Creating admin_users table");
        
        // Utwórz tabelę dla użytkowników administracyjnych
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Dodaj domyślnego administratora
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hashedPassword, 'admin']);
        
        logAuthError("Created admin user with default credentials");
    }
    
    // Sprawdź dane logowania
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        logAuthError("Successful login with database credentials for: $username");
        
        // Generuj nowe ID sesji
        session_regenerate_id(true);
        
        // Generuj unikalny identyfikator sesji
        $sessionId = bin2hex(random_bytes(32));
        
        // Ustaw dane sesji
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['last_activity'] = time();
        $_SESSION['expires'] = time() + (30 * 60); // 30 minut
        
        // Ustaw token "zapamiętaj mnie", jeśli wybrano
        if ($remember) {
            // Sprawdź, czy tabela auth_tokens istnieje
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'auth_tokens'");
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                logAuthError("Creating auth_tokens table");
                
                // Utwórz tabelę dla tokenów
                $pdo->exec("CREATE TABLE IF NOT EXISTS auth_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            }
            
            // Generuj token i zapisz w bazie danych
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 dni
            
            $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expires) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expires]);
            
            // Ustaw cookie tokenu
            setcookie('admin_token', $token, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => ENVIRONMENT === 'production',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        
        // Upewnij się, że sesja została zapisana
        session_write_close();
            
        // Użyj JavaScript do ustawienia localStorage/sessionStorage i przekierowania
        echo '<script>
            // Zapisz dane logowania w przeglądarce
            ' . ($remember ? 'localStorage.setItem("adminLoggedIn", "true");' : 'sessionStorage.setItem("adminLoggedIn", "true");') . '
            sessionStorage.setItem("adminSessionId", "' . $sessionId . '");
            
            // Zapisz nazwę użytkownika
            ' . ($remember ? 'localStorage.setItem("adminUsername", "' . $username . '");' : 'sessionStorage.setItem("adminUsername", "' . $username . '");') . '
            
            // Sprawdź czy dane zostały zapisane poprawnie
            console.log("Zapisano dane logowania:", ' . ($remember ? 'localStorage.getItem("adminLoggedIn")' : 'sessionStorage.getItem("adminLoggedIn")') . ');
            
            // Dodaj krótkie opóźnienie przed przekierowaniem, aby upewnić się, że dane zostały zapisane
            setTimeout(function() {
                // Dodatkowe sprawdzenie i ewentualna próba ponownego zapisu
                if (' . ($remember ? '!localStorage.getItem("adminLoggedIn")' : '!sessionStorage.getItem("adminLoggedIn")') . ') {
                    console.error("Nie udało się zapisać danych logowania. Próba ponowna...");
                    ' . ($remember ? 'localStorage.setItem("adminLoggedIn", "true");' : 'sessionStorage.setItem("adminLoggedIn", "true");') . '
                }
                // Przekieruj do panelu administratora
                window.location.href = "admin/dashboard.html";
            }, 200);
        </script>';
        exit();
    } else {
        $error = logAuthError("Invalid credentials for username: $username");
        header('Location: admin/login.html?error=invalid_credentials' . ($error ? '&debug=' . urlencode($error) : ''));
        exit();
    }
} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
    $error = logAuthError($errorMessage);
    header('Location: admin/login.html?error=database' . ($error ? '&debug=' . urlencode($error) : ''));
    exit();
} catch (Exception $e) {
    $errorMessage = 'Error: ' . $e->getMessage();
    $error = logAuthError($errorMessage);
    header('Location: admin/login.html?error=server' . ($error ? '&debug=' . urlencode($error) : ''));
    exit();
} 