<?php
header('Content-Type: application/json');
require_once __DIR__.'/../functions.php';

// Load WordPress environment for WooCommerce functions
$config = require __DIR__.'/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wpe_log('Request method wrong!');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['product_id']) || !isset($input['field_name']) || !isset($input['new_value'])) {
    wpe_log('need required fields!');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$product_id = (int)$input['product_id'];
$field_name = trim($input['field_name']);
$new_value = $input['new_value'];
$old_value = $input['old_value'] ?? null;
$user_identifier = session_id() ?: $_SERVER['REMOTE_ADDR'];
$request_data = $input;

// Get current value if not provided
if ($old_value === null) {
    $old_value = get_post_meta($product_id, $field_name, true);
    if ($old_value === '') $old_value = null;
}

// Skip if no change
if ($old_value == $new_value) {
    echo json_encode(['success' => true, 'message' => 'No change detected', 'queued' => false]);
    exit;
}

// Database connection
$program_db = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']}",
    $config['db']['user'],
    $config['db']['password']
);
$program_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$table_name = $config['db']['prefix'] . 'update_history';

try {
    $stmt = $program_db->prepare("
        INSERT INTO `$table_name`
        (`product_id`, `field_name`, `old_value`, `new_value`, `user_identifier`, `request_data`)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $product_id,
        $field_name,
        $old_value,
        $new_value,
        $user_identifier,
        json_encode($request_data)
    ]);

    $queue_id = $program_db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Update queued successfully',
        'queued' => true,
        'queue_id' => $queue_id
    ]);

} catch (PDOException $e) {
    wpe_log('Queue insert error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
