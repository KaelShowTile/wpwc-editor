<?php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

// Get POST data
$product_id = $_POST['product_id'] ?? 0;
$product_prefix = $_POST['status'] ?? '';

if (!$product_id || !in_array($product_prefix, ['', 'm2', 'sheet', 'ea', 'lm', 'set', 'bag'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Load WordPress environment
$config = require __DIR__.'/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

// Database connection
global $wpdb;

try {
    //update the database
    $table_name = $wpdb->prefix . 'glint_product_qty';
    
    // Check if record exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_id FROM $table_name WHERE post_id = %d",
        $product_id
    ));
    
    if ($exists) {
        // Update existing record
        $result = $wpdb->update(
            $table_name,
            ['glint_qty_suffix' => $product_prefix],
            ['post_id' => $product_id],
            ['%s'],  // Suffix format
            ['%d']   // Product ID format
        );
        
        if ($result === false) {
            throw new Exception('Failed to update quantity suffix');
        }
    } else {
        // Insert new record
        $result = $wpdb->insert(
            $table_name,
            [
                'post_id' => $product_id,
                'glint_qty_suffix' => $product_prefix,
                'glint_qty_step' => 1  // Default step value
            ],
            ['%d', '%s', '%s']  // Data formats
        );
        
        if ($result === false) {
            throw new Exception('Failed to insert quantity suffix');
        }
    }

    // Clear product cache
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients($product_id);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}