<?php
header('Content-Type: application/json');
require_once __DIR__.'/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Load config environmen
$config = require __DIR__.'/config.php';

// Database connection
$program_db = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']}",
    $config['db']['user'],
    $config['db']['password']
);
$program_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$table_name = $config['db']['prefix'] . 'update_history';

try {
    // Get pending updates
    $stmt = $program_db->prepare("
        SELECT `id`, `product_id`, `field_name`, `old_value`, `new_value`, `timestamp`
        FROM `$table_name`
        WHERE `status` IN ('pending', 'processing')
        ORDER BY `timestamp` ASC
    ");
    $stmt->execute();
    $pending_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent history (last 50 completed)
    $history_stmt = $program_db->prepare("
        SELECT `id`, `product_id`, `field_name`, `old_value`, `new_value`, `timestamp`, `user_identifier`
        FROM `$table_name`
        WHERE `status` = 'completed'
        ORDER BY `timestamp` DESC
        LIMIT 50
    ");
    $history_stmt->execute();
    $recent_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get queue stats
    $stats_stmt = $program_db->prepare("
        SELECT
            COUNT(CASE WHEN `status` = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN `status` = 'processing' THEN 1 END) as processing_count,
            COUNT(CASE WHEN `status` = 'completed' THEN 1 END) as completed_count,
            COUNT(CASE WHEN `status` = 'failed' THEN 1 END) as failed_count
        FROM `$table_name`
    ");
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'queue' => $pending_updates,
        'history' => $recent_history,
        'stats' => $stats
    ]);

} catch (PDOException $e) {
    //wpe_log('Queue status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
