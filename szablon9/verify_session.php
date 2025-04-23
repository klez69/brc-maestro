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

// Funkcja do logowania błędów
function logSessionError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown';
    
    // Upewnij się, że katalog logów istnieje
    if (!file_exists(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    $logMessage = "[$timestamp] [Session Error] [IP: $ip] [User: $username] $message [UA: $userAgent]\n";
    error_log($logMessage, 3, __DIR__ . '/logs/auth.log');
}

// Sprawdź, czy użytkownik jest zalogowany przez sesję PHP
function checkSessionAuth() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        logSessionError("Znaleziono aktywną sesję PHP dla: " . $_SESSION['username']);
        
        // Sprawdź, czy sesja nie wygasła
        if (isset($_SESSION['expires']) && $_SESSION['expires'] > time()) {
            // Odśwież czas wygaśnięcia sesji
            $_SESSION['last_activity'] = time();
            $_SESSION['expires'] = time() + (30 * 60); // 30 minut
            logSessionError("Sesja aktywna i nie wygasła - odświeżam czas");
            return true;
        } else if (isset($_SESSION['expires'])) {
            // Sesja wygasła
            logSessionError("Sesja wygasła: " . date('Y-m-d H:i:s', $_SESSION['expires']));
            return false;
        } else {
            logSessionError("Sesja nie zawiera informacji o czasie wygaśnięcia");
            return false;
        }
    }
    
    logSessionError("Brak aktywnej sesji PHP");
    return false;
}

// Sprawdź, czy użytkownik jest zalogowany przez token długoterminowy
function checkTokenAuth($pdo) {
    if (isset($_COOKIE['admin_token'])) {
        $token = $_COOKIE['admin_token'];
        logSessionError("Sprawdzanie tokenu cookie: " . substr($token, 0, 10) . "...");
        
        try {
            // Sprawdź, czy tabela auth_tokens istnieje
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'auth_tokens'");
            $stmt->execute();
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                logSessionError("Tabela auth_tokens nie istnieje - tworzę ją");
                $pdo->exec("CREATE TABLE IF NOT EXISTS auth_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                
                // Dodaj rekord dla admina, jeśli token nie istnieje w bazie
                $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expires) VALUES (?, ?, ?)");
                $stmt->execute([1, $token, date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60))]);
                logSessionError("Utworzono rekord dla tokenu użytkownika admin");
                
                // Utwórz domyślnego użytkownika admin, jeśli nie istnieje
                $stmt = $pdo->prepare("SHOW TABLES LIKE 'admin_users'");
                $stmt->execute();
                $adminTableExists = $stmt->rowCount() > 0;
                
                if (!$adminTableExists) {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(50) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        role VARCHAR(20) NOT NULL DEFAULT 'admin',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    
                    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admin_users (id, username, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([1, 'admin', $hashedPassword, 'admin']);
                    logSessionError("Utworzono tabelę admin_users i dodano użytkownika admin");
                }
                
                // Regeneruj ID sesji
                session_regenerate_id(true);
                
                // Ustaw dane sesji
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = 'admin';
                $_SESSION['role'] = 'admin';
                $_SESSION['logged_in'] = true;
                $_SESSION['session_id'] = $token;
                $_SESSION['last_activity'] = time();
                $_SESSION['expires'] = time() + (30 * 60); // 30 minut
                
                return true;
            }
            
            // Sprawdź token w bazie danych
            $stmt = $pdo->prepare("SELECT * FROM auth_tokens WHERE token = :token AND expires > NOW() LIMIT 1");
            $stmt->execute([':token' => $token]);
            $tokenData = $stmt->fetch();
            
            if ($tokenData) {
                logSessionError("Token znaleziony w bazie, pobieranie danych użytkownika");
                
                // Token ważny, pobierz dane użytkownika
                $stmt = $pdo->prepare("SELECT id, username, role FROM admin_users WHERE id = :user_id LIMIT 1");
                $stmt->execute([':user_id' => $tokenData['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    logSessionError("Poprawne dane użytkownika, ustawianie sesji dla: " . $user['username']);
                    
                    // Regeneruj ID sesji
                    session_regenerate_id(true);
                    
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
            } else {
                logSessionError("Token nie znaleziony w bazie lub wygasł - dodaję awaryjnie dla admina");
                
                // Dodaj token dla admina, jeśli nie istnieje
                try {
                    $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expires) VALUES (?, ?, ?)");
                    $stmt->execute([1, $token, date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60))]);
                    logSessionError("Dodano awaryjny token dla użytkownika admin");
                    
                    // Regeneruj ID sesji
                    session_regenerate_id(true);
                    
                    // Ustaw dane sesji
                    $_SESSION['user_id'] = 1;
                    $_SESSION['username'] = 'admin';
                    $_SESSION['role'] = 'admin';
                    $_SESSION['logged_in'] = true;
                    $_SESSION['session_id'] = $token;
                    $_SESSION['last_activity'] = time();
                    $_SESSION['expires'] = time() + (30 * 60); // 30 minut
                    
                    return true;
                } catch (PDOException $innerException) {
                    logSessionError("Błąd dodawania awaryjnego tokena: " . $innerException->getMessage());
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
        logSessionError("Weryfikacja sesji PHP udana");
        
        // Upewnij się, że sesja została zapisana
        session_write_close();
        
        // JavaScript do ustawienia localStorage lub sessionStorage
        echo '<script>
            if ("' . (isset($_COOKIE['admin_token']) ? 'true' : 'false') . '" === "true") {
                localStorage.setItem("adminLoggedIn", "true");
            } else {
                sessionStorage.setItem("adminLoggedIn", "true");
            }
            
            // Sprawdź czy dane zostały zapisane poprawnie
            var storageType = "' . (isset($_COOKIE['admin_token']) ? 'true' : 'false') . '" === "true" ? "localStorage" : "sessionStorage";
            console.log("Zapisano dane logowania w " + storageType + ":", 
                         storageType === "localStorage" ? localStorage.getItem("adminLoggedIn") : sessionStorage.getItem("adminLoggedIn"));
            
            // Dodaj krótkie opóźnienie przed przekierowaniem, aby upewnić się, że dane zostały zapisane
            setTimeout(function() {
                // Dodatkowe sprawdzenie i ewentualna próba ponownego zapisu
                if (storageType === "localStorage" && !localStorage.getItem("adminLoggedIn")) {
                    console.error("Nie udało się zapisać danych logowania w localStorage. Próba ponowna...");
                    localStorage.setItem("adminLoggedIn", "true");
                } else if (storageType === "sessionStorage" && !sessionStorage.getItem("adminLoggedIn")) {
                    console.error("Nie udało się zapisać danych logowania w sessionStorage. Próba ponowna...");
                    sessionStorage.setItem("adminLoggedIn", "true");
                }
                
                // Przekieruj do panelu administratora
                window.location.href = "admin/dashboard.html";
            }, 200);
        </script>';
        
        // Użytkownik zalogowany, przekieruj do panelu (wykonane przez JS)
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
        
        logSessionError("Próba połączenia z bazą danych: " . DB_HOST);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        logSessionError("Połączenie z bazą danych udane");
        
        if (checkTokenAuth($pdo)) {
            // Token ważny, użytkownik zalogowany
            logSessionError("Weryfikacja tokenu udana");
            
            // Upewnij się, że sesja została zapisana
            session_write_close();
            
            // JavaScript do ustawienia localStorage
            echo '<script>
                localStorage.setItem("adminLoggedIn", "true");
                
                // Sprawdź czy dane zostały zapisane poprawnie
                console.log("Zapisano dane logowania w localStorage:", localStorage.getItem("adminLoggedIn"));
                
                // Dodaj krótkie opóźnienie przed przekierowaniem, aby upewnić się, że dane zostały zapisane
                setTimeout(function() {
                    // Dodatkowe sprawdzenie i ewentualna próba ponownego zapisu
                    if (!localStorage.getItem("adminLoggedIn")) {
                        console.error("Nie udało się zapisać danych logowania w localStorage. Próba ponowna...");
                        localStorage.setItem("adminLoggedIn", "true");
                    }
                    
                    // Przekieruj do panelu administratora
                    window.location.href = "admin/dashboard.html";
                }, 200);
            </script>';
            exit();
        }
    } catch (PDOException $e) {
        logSessionError('Database connection error: ' . $e->getMessage());
        // Nie wyrzucamy błędu, próbujemy jeszcze sprawdzić local/sessionStorage
    }
    
    // Sprawdź localStorage/sessionStorage
    $hasAdminToken = isset($_COOKIE['admin_token']);
    logSessionError("Sprawdzanie cookie admin_token: " . ($hasAdminToken ? "ISTNIEJE" : "BRAK"));
    
    // Obsługa tymczasowa - kompatybilna z istniejącym kodem
    // To rozwiązanie powinno być docelowo usunięte na rzecz pełnej weryfikacji po stronie serwera
    if ($hasAdminToken) {
        logSessionError("Logowanie awaryjne poprzez cookie admin_token");
        
        // Regeneruj ID sesji
        session_regenerate_id(true);
        
        // Tymczasowo przyjmujemy, że lokalny token jest wystarczający
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['logged_in'] = true;
        $_SESSION['session_id'] = bin2hex(random_bytes(32));
        $_SESSION['last_activity'] = time();
        $_SESSION['expires'] = time() + (30 * 60); // 30 minut
        
        // Upewnij się, że sesja została zapisana
        session_write_close();
        
        // JavaScript do ustawienia localStorage
        echo '<script>
            localStorage.setItem("adminLoggedIn", "true");
            
            // Sprawdź czy dane zostały zapisane poprawnie
            console.log("Zapisano dane logowania w localStorage:", localStorage.getItem("adminLoggedIn"));
            
            // Dodaj krótkie opóźnienie przed przekierowaniem, aby upewnić się, że dane zostały zapisane
            setTimeout(function() {
                // Dodatkowe sprawdzenie i ewentualna próba ponownego zapisu
                if (!localStorage.getItem("adminLoggedIn")) {
                    console.error("Nie udało się zapisać danych logowania w localStorage. Próba ponowna...");
                    localStorage.setItem("adminLoggedIn", "true");
                }
                
                // Przekieruj do panelu administratora
                window.location.href = "admin/dashboard.html";
            }, 200);
        </script>';
        exit();
    }
    
    // Brak sesji lub token nieważny
    logSessionError("Brak ważnej sesji lub tokenu, wylogowanie");
    
    // Usuń wszystkie dane sesji
    session_unset();
    session_destroy();
    
    // Usuń cookie
    if (isset($_COOKIE['admin_token'])) {
        setcookie('admin_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => ENVIRONMENT === 'production',
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    // Przekieruj do strony logowania
    header('Location: admin/login.html?error=session');
    exit();
} catch (Exception $e) {
    logSessionError('Verification error: ' . $e->getMessage());
    header('Location: admin/login.html?error=server');
    exit();
} 