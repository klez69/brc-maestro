<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Test database connection
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Sprawdź czy tabela visitors istnieje
    $stmt = $pdo->query("SHOW TABLES LIKE 'visitors'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Utwórz tabelę jeśli nie istnieje
        $sql = "CREATE TABLE IF NOT EXISTS `visitors` (
            `id` VARCHAR(50) NOT NULL,
            `page` VARCHAR(255) NOT NULL,
            `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_last_activity` (`last_activity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo json_encode([
            'status' => 'success',
            'message' => 'Tabela visitors została utworzona pomyślnie'
        ]);
    } else {
        // Testowe dodanie rekordu
        $testId = 'test_' . uniqid();
        $stmt = $pdo->prepare("
            INSERT INTO visitors (id, page, last_activity) 
            VALUES (:id, :page, NOW())
        ");
        $stmt->execute([
            ':id' => $testId,
            ':page' => '/test'
        ]);
        
        // Odczytanie testowych danych
        $stmt = $pdo->query("SELECT * FROM visitors WHERE id = '$testId'");
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Usunięcie testowego rekordu
        $pdo->exec("DELETE FROM visitors WHERE id = '$testId'");
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Tabela visitors istnieje i działa poprawnie',
            'sample_record' => $record
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Błąd bazy danych: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ogólny błąd: ' . $e->getMessage()
    ]);
} 