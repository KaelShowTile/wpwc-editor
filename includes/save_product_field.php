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

try {
    // Get WooCommerce product object
    $product = wc_get_product($product_id);
    if (!$product) {
        throw new Exception('Product not found');
    }

    if ($field === 'post_title') {
        $result = wp_update_post([
            'ID' => $product_id,
            'post_title' => sanitize_text_field($value)
        ]);
        
        if (is_wp_error($result)) {
            throw new Exception($result->get_error_message());
        }
    } 
    // Handle price updates using WooCommerce methods
    elseif ($field === '_regular_price' || $field === '_sale_price') {
        // Format and validate price
        $price_value = is_numeric($value) ? wc_format_decimal($value) : '';
        
        if ($field === '_regular_price') {
            $product->set_regular_price($price_value);
        } elseif ($field === '_sale_price') {
            $product->set_sale_price($price_value);
        }
        
        // Recalculate price display values
        if ($product->get_sale_price() && $product->get_sale_price() < $product->get_regular_price()) {
            $product->set_price($product->get_sale_price());
        } else {
            $product->set_price($product->get_regular_price());
            $product->set_sale_price('');
        }
        
        $product->save();
    }
    // Handle shipping dimensions
    elseif (in_array($field, ['_weight', '_length', '_width', '_height'])) {
        // Validate as positive number
        $sanitized_value = is_numeric($value) && $value >= 0 ? wc_format_decimal($value) : '';
        
        switch ($field) {
            case '_weight':
                $product->set_weight($sanitized_value);
                break;
            case '_length':
                $product->set_length($sanitized_value);
                break;
            case '_width':
                $product->set_width($sanitized_value);
                break;
            case '_height':
                $product->set_height($sanitized_value);
                break;
        }
        
        $product->save();
    }
    // Handle other meta fields
    else {
        switch ($field) {
            case '_sku':
                $sanitized_value = sanitize_text_field($value);
                $product->set_sku($sanitized_value);
                $product->save();
                break;
            default:
                $sanitized_value = sanitize_text_field($value);
                update_post_meta($product_id, $field, $sanitized_value);
        }
    }
    
    // Clear all relevant caches
    clean_post_cache($product_id);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}