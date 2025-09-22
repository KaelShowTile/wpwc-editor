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

if (!isset($data['plugins']) || !is_array($data['plugins'])) {
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
    
    //Clear existing data
    $deleteSettings = $db->prepare("
        DELETE FROM `{$config['db']['prefix']}settings` 
        WHERE setting_name = :setting_name
    ");
    $deleteSettings->execute([':setting_name' => 'plugin']);

    //Insert selected plugin
    $insertTaxStmt = $db->prepare("
        INSERT INTO `{$config['db']['prefix']}settings` 
        (setting_name, setting_value) 
        VALUES ('plugin', :plugn_name)
    ");
    
    foreach ($data['plugins'] as $plugin) {
        $insertTaxStmt->execute([':plugn_name' => $plugin]);
        wpe_log($plugin . ' is updated');
    }

    // Commit transaction
    $db->commit();

    wpe_log('Plugins updated successfully');

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;

} catch (PDOException $e) {
    // Rollback on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    wpe_log('Database error in save_plugin.php: ' . $e->getMessage());

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
} catch (Exception $e) {
    wpe_log('Error in save_plug.php: ' . $e->getMessage());

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
    exit;
}