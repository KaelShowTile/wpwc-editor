<?php
header('Content-Type: application/json');
require_once __DIR__.'/../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Load config environmen
$config = require __DIR__.'/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

// Database connection
$program_db = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']}",
    $config['db']['user'],
    $config['db']['password']
);
$program_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$table_name = $config['db']['prefix'] . 'update_history';

try {
    //wpe_log('start process the queue.');
    // Get one pending update
    $stmt = $program_db->prepare("
        SELECT * FROM `$table_name`
        WHERE `status` = 'pending'
        ORDER BY `timestamp` ASC
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute();
    $update = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$update) {
        //wpe_log('update fail...');
        echo json_encode(['success' => true, 'message' => 'No pending updates', 'processed' => false]);
        exit;
    }

    // Mark as processing
    $update_stmt = $program_db->prepare("
        UPDATE `$table_name`
        SET `status` = 'processing'
        WHERE `id` = ?
    ");
    $update_stmt->execute([$update['id']]);

    // Process the update
    $product_id = $update['product_id'];
    $field_name = $update['field_name'];
    $new_value = $update['new_value'];
    $request_data = json_decode($update['request_data'], true);

    $success = false;
    $error_message = null;

    try {
        // Handle different field types
        switch ($field_name) {
            case 'post_title':
            case 'post_excerpt':
            case 'post_content':
                // Post fields
                wp_update_post([
                    'ID' => $product_id,
                    $field_name => $new_value
                ]);
                $success = true;
                break;

            case 'stock_status':
                // Stock status
                //wpe_log('updating stock status...');
                update_post_meta($product_id, '_stock_status', $new_value);
                $success = true;
                break;

            case 'attribute':
                // Product attributes
                if (isset($request_data['taxonomy'])) {
                    $taxonomy = $request_data['taxonomy'];
                    wp_set_object_terms($product_id, $new_value ? explode(', ', $new_value) : [], $taxonomy);
                }
                $success = true;
                break;

            case '_quantity_step':
            case '_quantity_suffix':
                // Glint quantity fields
                update_post_meta($product_id, $field_name, $new_value);
                $success = true;
                break;

            case '_yoast_wpseo_title':
            case '_yoast_wpseo_metadesc':
                // Yoast SEO fields
                update_post_meta($product_id, $field_name, $new_value);
                $success = true;
                break;

            default:
                // Regular meta fields
                //wpe_log('updating general meta...');
                update_post_meta($product_id, $field_name, $new_value);
                $success = true;
                break;
        }

        // Clear caches
        wp_cache_flush();
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients($product_id);
        }

    } catch (Exception $e) {
        $success = false;
        $error_message = $e->getMessage();
    }

    // Update status
    $status = $success ? 'completed' : 'failed';
    $update_stmt = $program_db->prepare("
        UPDATE `$table_name`
        SET `status` = ?, `error_message` = ?
        WHERE `id` = ?
    ");
    $update_stmt->execute([$status, $error_message, $update['id']]);

    // Clean up old completed records (keep last 1000)
    $cleanup_stmt = $program_db->prepare("
        DELETE FROM `$table_name`
        WHERE `status` = 'completed'
        AND `id` NOT IN (
            SELECT `id` FROM (
                SELECT `id` FROM `$table_name`
                WHERE `status` = 'completed'
                ORDER BY `timestamp` DESC
                LIMIT 1000
            ) tmp
        )
    ");
    $cleanup_stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => $success ? 'Update processed successfully' : 'Update failed',
        'processed' => true,
        'update_id' => $update['id'],
        'field_name' => $field_name,
        'product_id' => $product_id,
        'status' => $status
    ]);

} catch (PDOException $e) {
    //wpe_log('Queue processing error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
