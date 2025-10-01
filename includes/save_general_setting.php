<?php
ob_start();
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

// Check authentication
if (!isset($_SESSION['wpe_authenticated']) || $_SESSION['wpe_authenticated'] !== true) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['settings']) || !is_array($data['settings'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Load configuration
$config = require __DIR__.'/config.php';

try {
    // Connect to program's database
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4",
        $config['db']['user'],
        $config['db']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Start transaction
    $db->beginTransaction();

    // Prepare statements
    $deleteStmt = $db->prepare("
        DELETE FROM `{$config['db']['prefix']}settings`
        WHERE setting_name = :setting_name
    ");
    $insertStmt = $db->prepare("
        INSERT INTO `{$config['db']['prefix']}settings`
        (setting_name, setting_value)
        VALUES (:setting_name, :setting_value)
    ");

    // Process each setting
    foreach ($data['settings'] as $setting) {
        if (!isset($setting['setting_name']) || !isset($setting['setting_value'])) {
            continue;
        }

        // Clear existing data for this setting
        $deleteStmt->execute([':setting_name' => $setting['setting_name']]);

        // Insert new setting value
        $insertStmt->execute([
            ':setting_name' => $setting['setting_name'],
            ':setting_value' => $setting['setting_value']
        ]);

        wpe_log($setting['setting_name'] . ' setting updated successfully');
    }

    // Commit transaction
    $db->commit();

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;

} catch (PDOException $e) {
    // Rollback on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    wpe_log('Database error in save_general_setting.php: ' . $e->getMessage());

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
} catch (Exception $e) {
    wpe_log('Error in save_general_setting.php: ' . $e->getMessage());

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}
