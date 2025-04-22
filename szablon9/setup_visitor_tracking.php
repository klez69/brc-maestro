<?php
/**
 * Visitor Tracking System Setup Script
 * 
 * This script:
 * 1. Creates necessary database tables
 * 2. Sets up directory structure
 * 3. Checks file permissions
 */

// Display all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Visitor Tracking System Setup</h1>";

// Include database configuration
require_once 'config.php';

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    echo "<p>Creating logs directory...</p>";
    if (mkdir(__DIR__ . '/logs', 0755, true)) {
        echo "<p style='color: green;'>✓ Logs directory created successfully.</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create logs directory. Please create it manually and ensure it's writable.</p>";
    }
} else {
    echo "<p>✓ Logs directory already exists.</p>";
}

// Check logs directory permissions
if (is_writable(__DIR__ . '/logs')) {
    echo "<p style='color: green;'>✓ Logs directory is writable.</p>";
} else {
    echo "<p style='color: red;'>✗ Logs directory is not writable. Please set correct permissions.</p>";
}

// Function to execute SQL file
function executeSQL($pdo, $file) {
    echo "<p>Executing SQL from {$file}...</p>";
    
    // Check if file exists
    if (!file_exists($file)) {
        echo "<p style='color: red;'>✗ SQL file not found: {$file}</p>";
        return false;
    }
    
    // Read and execute SQL file
    $sql = file_get_contents($file);
    if (!$sql) {
        echo "<p style='color: red;'>✗ Failed to read SQL file: {$file}</p>";
        return false;
    }
    
    try {
        // Split SQL commands by semicolon
        $queries = explode(';', $sql);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query)) continue;
            
            // Execute query
            $pdo->exec($query);
        }
        
        echo "<p style='color: green;'>✓ SQL executed successfully.</p>";
        return true;
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ SQL execution failed: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Test database connection
try {
    echo "<p>Testing database connection...</p>";
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Database connection successful.</p>";
    
    // Execute SQL to create tables
    executeSQL($pdo, __DIR__ . '/sql/create_visitor_tables.sql');
    
    // Verify tables exist
    echo "<p>Verifying database tables...</p>";
    $tables = ['visitors', 'visitor_history'];
    $allTablesExist = true;
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table {$table} exists.</p>";
        } else {
            echo "<p style='color: red;'>✗ Table {$table} does not exist!</p>";
            $allTablesExist = false;
        }
    }
    
    // Check if view exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'visitor_statistics'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ View visitor_statistics exists.</p>";
    } else {
        echo "<p style='color: red;'>✗ View visitor_statistics does not exist!</p>";
        $allTablesExist = false;
    }
    
    if ($allTablesExist) {
        echo "<h2 style='color: green;'>Setup completed successfully!</h2>";
        echo "<p>The visitor tracking system is now installed and ready to use.</p>";
        echo "<p>You can now:</p>";
        echo "<ul>";
        echo "<li>View active visitors in the <a href='admin/visitors.html'>Visitors Panel</a></li>";
        echo "<li>View visitor history in the <a href='admin/visitor_history.html'>Visitor History Panel</a></li>";
        echo "</ul>";
    } else {
        echo "<h2 style='color: red;'>Setup incomplete!</h2>";
        echo "<p>Some database objects were not created. Check the errors above.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<h2 style='color: red;'>Setup failed!</h2>";
    echo "<p>Please check your database configuration in config.php.</p>";
}
?>