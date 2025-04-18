<?php
header('Content-Type: application/json');
require_once '../config.php';

// Function to create portfolio_items table if it doesn't exist
function createPortfolioItemsTable($pdo) {
    $sql = file_get_contents(__DIR__ . '/../sql/portfolio_items.sql');
    try {
        $pdo->exec($sql);
        error_log("[" . basename(__FILE__) . "] Portfolio items table created successfully");
        return true;
    } catch (PDOException $e) {
        error_log("[" . basename(__FILE__) . "] Error creating portfolio items table: " . $e->getMessage());
        return false;
    }
}

try {
    $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['dbname']}", $db_config['user'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'portfolio_items'");
    if ($stmt->rowCount() == 0) {
        if (!createPortfolioItemsTable($pdo)) {
            throw new Exception("Nie można utworzyć tabeli portfolio_items");
        }
    }

    $stmt = $pdo->prepare("SELECT id, title, description, image_url, category, detail_url, created_at, updated_at FROM portfolio_items ORDER BY created_at DESC");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);

} catch (PDOException $e) {
    error_log("[" . basename(__FILE__) . "] Database error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Błąd bazy danych: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("[" . basename(__FILE__) . "] Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Wystąpił błąd: ' . $e->getMessage()
    ]);
} 