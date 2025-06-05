<?php
// save_product_field.php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

// Get POST data
$product_id = $_POST['product_id'] ?? 0;
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

if (!$product_id || !$field) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Load WordPress environment
$config = require __DIR__.'/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

// Handle different field types
try {
    // For post title
    if ($field === 'post_title') {
        $result = wp_update_post([
            'ID' => $product_id,
            'post_title' => sanitize_text_field($value)
        ]);
        
        if (is_wp_error($result)) {
            throw new Exception($result->get_error_message());
        }
    } 
    // For meta fields
    else {
        // Sanitize based on field type
        switch ($field) {
            case '_sku':
                $sanitized_value = sanitize_text_field($value);
                break;
            case '_regular_price':
            case '_sale_price':
                $sanitized_value = is_numeric($value) ? wc_format_decimal($value) : '';
                break;
            default:
                $sanitized_value = sanitize_text_field($value);
        }
        
        update_post_meta($product_id, $field, $sanitized_value);
    }
    
    // Clear product cache
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients($product_id);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}