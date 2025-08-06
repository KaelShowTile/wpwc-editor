<?php
// save_attributes.php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['wpe_authenticated']) || $_SESSION['wpe_authenticated'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['terms']) || !is_array($data['terms']) || 
    !isset($data['taxonomies']) || !is_array($data['taxonomies'])) {
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
    $db->exec("DELETE FROM ".$config['db']['prefix']."attributes");
    $deleteSettings = $db->prepare("
        DELETE FROM `{$config['db']['prefix']}settings` 
        WHERE setting_name = :setting_name
    ");
    $deleteSettings->execute([':setting_name' => 'attribute']);
    
    //Insert selected attributes
    $insertAttrStmt = $db->prepare("
        INSERT INTO `{$config['db']['prefix']}attributes` 
        (attribute_id, active_editing, attribute_cat) 
        VALUES (:attribute_id, 1, :attribute_cat)
    ");

    //Insert selected taxonomies
    $insertTaxStmt = $db->prepare("
        INSERT INTO `{$config['db']['prefix']}settings` 
        (setting_name, setting_value) 
        VALUES ('attribute', :taxonomy_slug)
    ");

    foreach ($data['terms'] as $term) {
        $insertAttrStmt->execute([
            ':attribute_id' => (int)$term['term_id'],
            ':attribute_cat' => $term['term_cate']
        ]);
    }
    
    foreach ($data['taxonomies'] as $taxonomy) {

        $insertTaxStmt->execute([':taxonomy_slug' => $taxonomy]);
    }

    
    // Commit transaction
    $db->commit();
    
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // Rollback on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    wpe_log('Database error in save_attributes.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    wpe_log('Error in save_attributes.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}