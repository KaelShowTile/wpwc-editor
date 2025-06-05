<?php
// save_product_stock.php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

// Get POST data
$product_id = $_POST['product_id'] ?? 0;
$status = $_POST['status'] ?? '';

wpe_log("Product ID: " . $product_id);
wpe_log("Stock: " . $status);

if (!$product_id || !in_array($status, ['instock', 'outofstock', 'onbackorder'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Load WordPress environment
$config = require __DIR__.'/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

// Update stock status
try {
    update_post_meta($product_id, '_stock_status', $status);
    
    // Clear product cache
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients($product_id);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}