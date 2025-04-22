<?php
// Ensure user is authenticated
include_once '../auth_check.php';
require_once '../config.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Function to get active visitors data
function getActiveVisitors($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT id) as visitor_count,
                page,
                MAX(last_activity) as last_activity
            FROM visitors 
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            GROUP BY page
            ORDER BY last_activity DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

// Function to get most popular pages
function getPopularPages($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                page,
                COUNT(*) as visit_count
            FROM visitors
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY page
            ORDER BY visit_count DESC
            LIMIT 10
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

// Function to get weekly statistics
function getWeeklyStats($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(last_activity) as visit_date,
                COUNT(DISTINCT id) as visitor_count
            FROM visitors
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(last_activity)
            ORDER BY visit_date ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
}

// Determine which data to fetch based on action parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'all';
$response = [];

try {
    switch ($action) {
        case 'active':
            $response['activeVisitors'] = getActiveVisitors($pdo);
            break;
        case 'popular':
            $response['popularPages'] = getPopularPages($pdo);
            break;
        case 'weekly':
            $response['weeklyStats'] = getWeeklyStats($pdo);
            break;
        case 'all':
        default:
            $response['activeVisitors'] = getActiveVisitors($pdo);
            $response['popularPages'] = getPopularPages($pdo);
            $response['weeklyStats'] = getWeeklyStats($pdo);
            break;
    }
    
    $response['status'] = 'success';
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ];
}

// Return JSON formatted response
echo json_encode($response); 