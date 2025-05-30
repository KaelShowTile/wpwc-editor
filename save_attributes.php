<?php
// save_attributes.php
require_once __DIR__.'/functions.php';
require_once __DIR__.'/includes/session_manager.php';
wpe_start_session();

wpe_log('Session data: ' . print_r($_SESSION, true));
wpe_log('Session ID: ' . session_id());

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['wpe_authenticated']) || $_SESSION['wpe_authenticated'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['terms']) || !is_array($data['terms'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Load configuration
require_once __DIR__.'/includes/config.php';
$config = require __DIR__.'/includes/config.php';

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']}",
        $config['db']['user'],
        $config['db']['password']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare statements
    $updateStmt = $db->prepare("
        UPDATE {$config['db']['prefix']}attributes 
        SET active_editing = :active 
        WHERE attribute_id = :attribute_id
    ");

    $insertStmt = $db->prepare("
        INSERT INTO {$config['db']['prefix']}attributes 
        (attribute_id, attribute_name, active_editing) 
        VALUES (:attribute_id, :attribute_name, 1)
        ON DUPLICATE KEY UPDATE active_editing = VALUES(active_editing)
    ");
    
    // First, set all attributes to inactive
    $db->exec("UPDATE {$config['db']['prefix']}attributes SET active_editing = 0");
    
    // Now process the selected terms
    foreach ($data['terms'] as $termId) {
        wpe_log('loop product...');
        // Check if the term exists in our attributes table
        $checkStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM {$config['db']['prefix']}attributes 
            WHERE attribute_id = :term_id
        ");
        $checkStmt->execute([':term_id' => $termId]);
        $exists = $checkStmt->fetchColumn() > 0;
        
        if ($exists) {
            // Update existing record
            $updateStmt->execute([
                ':active' => 1,
                ':attribute_id' => $termId
            ]);
        } else {
            // Get term name from WordPress database
            $termStmt = $db->prepare("
                SELECT name 
                FROM {$config['db']['prefix']}terms 
                WHERE term_id = :term_id
            ");
            $termStmt->execute([':term_id' => $termId]);
            $termName = $termStmt->fetchColumn();
            
            if ($termName) {
                // Insert new record
                $insertStmt->execute([
                    ':attribute_id' => $termId,
                    ':attribute_name' => $termName
                ]);
            }
        }
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Database error in save_attributes.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Error in save_attributes.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}