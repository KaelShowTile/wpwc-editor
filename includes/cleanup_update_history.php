<?php
require_once __DIR__.'/../functions.php';

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
    // Delete completed records older than 30 days, keeping last 1000
    $stmt = $program_db->prepare("
        DELETE FROM `$table_name`
        WHERE `status` = 'completed'
        AND `timestamp` < DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND `id` NOT IN (
            SELECT `id` FROM (
                SELECT `id` FROM `$table_name`
                WHERE `status` = 'completed'
                ORDER BY `timestamp` DESC
                LIMIT 1000
            ) tmp
        )
    ");
    $deleted_count = $stmt->execute();

    // Also clean up failed records older than 7 days
    $failed_stmt = $program_db->prepare("
        DELETE FROM `$table_name`
        WHERE `status` = 'failed'
        AND `timestamp` < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $failed_stmt->execute();

    echo "Cleanup completed. Deleted old records.\n";

} catch (PDOException $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
}
?>
