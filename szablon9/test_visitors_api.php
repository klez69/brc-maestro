<?php
// Test script for visitor tracking functionality

// Load configuration
require_once 'config.php';

echo "Testing visitor tracking functionality...\n\n";

// 1. Test database connection
echo "1. Testing database connection...\n";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "Connection successful!\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Test table creation
echo "\n2. Testing table creation...\n";
try {
    // Check if visitors table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'visitors'");
    $visitorsTableExists = $stmt->rowCount() > 0;
    
    if ($visitorsTableExists) {
        echo "Visitors table already exists.\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE visitors");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Visitors table structure:\n";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } else {
        echo "Visitors table doesn't exist. Creating...\n";
        $sql = "CREATE TABLE IF NOT EXISTS `visitors` (
            `id` VARCHAR(50) NOT NULL,
            `page` VARCHAR(255) NOT NULL,
            `referrer` TEXT,
            `user_agent` VARCHAR(255),
            `screen_resolution` VARCHAR(20),
            `language` VARCHAR(10),
            `ip_address` VARCHAR(45),
            `is_logged_in` TINYINT(1) DEFAULT 0,
            `username` VARCHAR(50) NULL,
            `role` VARCHAR(20) NULL,
            `user_id` VARCHAR(50) NULL,
            `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_last_activity` (`last_activity`),
            INDEX `idx_is_logged_in` (`is_logged_in`),
            INDEX `idx_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "Visitors table created successfully.\n";
    }
    
    // Check if visitor_history table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'visitor_history'");
    $historyTableExists = $stmt->rowCount() > 0;
    
    if ($historyTableExists) {
        echo "Visitor history table already exists.\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE visitor_history");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Visitor history table structure:\n";
        foreach ($columns as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } else {
        echo "Visitor history table doesn't exist. Creating...\n";
        $sql = "CREATE TABLE IF NOT EXISTS `visitor_history` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `visitor_id` VARCHAR(50) NOT NULL,
            `page` VARCHAR(255) NOT NULL,
            `referrer` TEXT,
            `user_agent` VARCHAR(255),
            `screen_resolution` VARCHAR(20),
            `language` VARCHAR(10),
            `ip_address` VARCHAR(45),
            `is_logged_in` TINYINT(1) DEFAULT 0,
            `username` VARCHAR(50) NULL,
            `role` VARCHAR(20) NULL,
            `user_id` VARCHAR(50) NULL,
            `visit_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_visitor_id` (`visitor_id`),
            INDEX `idx_visit_time` (`visit_time`),
            INDEX `idx_is_logged_in` (`is_logged_in`),
            INDEX `idx_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "Visitor history table created successfully.\n";
    }
} catch (PDOException $e) {
    echo "Table creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Test inserting a test visitor
echo "\n3. Testing visitor insertion...\n";
try {
    // Generate a random visitor ID
    $visitor_id = uniqid('test_', true);
    
    // Sample visitor data
    $visitor = [
        'id' => $visitor_id,
        'page' => '/test-page',
        'referrer' => 'direct',
        'user_agent' => 'Test Script',
        'screen_resolution' => '1920x1080',
        'language' => 'pl',
        'ip_address' => '127.0.0.1',
        'is_logged_in' => 1,
        'username' => 'Test User',
        'role' => 'admin',
        'user_id' => 'test123'
    ];
    
    // Insert into active visitors
    $sql = "INSERT INTO visitors (
                id, page, referrer, user_agent, screen_resolution, 
                language, ip_address, is_logged_in, username, role, user_id
            ) VALUES (
                :id, :page, :referrer, :user_agent, :screen_resolution,
                :language, :ip_address, :is_logged_in, :username, :role, :user_id
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $visitor['id'],
        ':page' => $visitor['page'],
        ':referrer' => $visitor['referrer'],
        ':user_agent' => $visitor['user_agent'],
        ':screen_resolution' => $visitor['screen_resolution'],
        ':language' => $visitor['language'],
        ':ip_address' => $visitor['ip_address'],
        ':is_logged_in' => $visitor['is_logged_in'],
        ':username' => $visitor['username'],
        ':role' => $visitor['role'],
        ':user_id' => $visitor['user_id']
    ]);
    
    echo "Test visitor added to active visitors.\n";
    
    // Insert into visitor history
    $sql = "INSERT INTO visitor_history (
                visitor_id, page, referrer, user_agent, screen_resolution, 
                language, ip_address, is_logged_in, username, role, user_id
            ) VALUES (
                :visitor_id, :page, :referrer, :user_agent, :screen_resolution,
                :language, :ip_address, :is_logged_in, :username, :role, :user_id
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':visitor_id' => $visitor['id'],
        ':page' => $visitor['page'],
        ':referrer' => $visitor['referrer'],
        ':user_agent' => $visitor['user_agent'],
        ':screen_resolution' => $visitor['screen_resolution'],
        ':language' => $visitor['language'],
        ':ip_address' => $visitor['ip_address'],
        ':is_logged_in' => $visitor['is_logged_in'],
        ':username' => $visitor['username'],
        ':role' => $visitor['role'],
        ':user_id' => $visitor['user_id']
    ]);
    
    echo "Test visitor added to visitor history.\n";
} catch (PDOException $e) {
    echo "Visitor insertion failed: " . $e->getMessage() . "\n";
    // Continue even if this fails
}

// 4. Test retrieving active visitors
echo "\n4. Testing active visitors retrieval...\n";
try {
    $sql = "SELECT * FROM visitors ORDER BY last_activity DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($visitors) . " active visitors:\n";
    foreach ($visitors as $index => $visitor) {
        if ($index >= 5) {
            echo "... and " . (count($visitors) - 5) . " more visitors\n";
            break;
        }
        echo "- ID: " . $visitor['id'] . ", Page: " . $visitor['page'] . 
             ", User: " . ($visitor['is_logged_in'] ? $visitor['username'] . " (" . $visitor['role'] . ")" : "Guest") . 
             ", Last activity: " . $visitor['last_activity'] . "\n";
    }
} catch (PDOException $e) {
    echo "Visitor retrieval failed: " . $e->getMessage() . "\n";
}

// 5. Test retrieving visitor history
echo "\n5. Testing visitor history retrieval...\n";
try {
    $sql = "SELECT * FROM visitor_history ORDER BY visit_time DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($history) . " visitor history entries:\n";
    foreach ($history as $index => $entry) {
        if ($index >= 5) {
            echo "... and " . (count($history) - 5) . " more entries\n";
            break;
        }
        echo "- ID: " . $entry['id'] . ", Visitor ID: " . $entry['visitor_id'] . 
             ", Page: " . $entry['page'] . 
             ", User: " . ($entry['is_logged_in'] ? $entry['username'] . " (" . $entry['role'] . ")" : "Guest") . 
             ", Visit time: " . $entry['visit_time'] . "\n";
    }
} catch (PDOException $e) {
    echo "History retrieval failed: " . $e->getMessage() . "\n";
}

echo "\nTest completed successfully!\n"; 