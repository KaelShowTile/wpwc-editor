<?php

// save_product_attribute.php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

// Load WordPress environment
$config = require __DIR__.'/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

// Get POST data
$product_id = $_POST['product_id'] ?? 0;
$taxonomy = $_POST['taxonomy'] ?? '';
$value = $_POST['value'] ?? '';

if (!$product_id || !$taxonomy) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Process attribute value
try {
    
    $product = wc_get_product($product_id);
    if (!$product) {
        throw new Exception('Product not found');
    }

    // Get existing product attributes
    $product_attributes = $product->get_attributes();

    // Check if attribute exists on product
    $attribute_exists = isset($product_attributes[$taxonomy]);

    if (!$attribute_exists) {
        // Create new attribute configuration
        $attribute = new WC_Product_Attribute();
        
        // CRITICAL: Set taxonomy flag correctly
        $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
        $attribute->set_name($taxonomy);
        $attribute->set_visible(true);
        $attribute->set_variation(false);
        
        // Set is_taxonomy flag through the options array
        $options = $attribute->get_options();
        $options['is_taxonomy'] = 1; // 1 = true for taxonomy attributes
        
        // For taxonomy attributes, we don't store values in options
        $options['value'] = '';
        $attribute->set_options($options);
        
        $product_attributes[$taxonomy] = $attribute;
    }

    // Process terms
    $terms = !empty($value) ? array_map('trim', explode(',', $value)) : [];

    // Create terms and get term IDs
    $term_ids = [];
    foreach ($terms as $term_name) {
        if (!term_exists($term_name, $taxonomy)) {
            $new_term = wp_insert_term($term_name, $taxonomy);
            if (!is_wp_error($new_term)) {
                $term_ids[] = $new_term['term_id'];
            }
        } else {
            $term = get_term_by('name', $term_name, $taxonomy);
            $term_ids[] = $term->term_id;
        }
    }

    // Set terms to product
    wp_set_object_terms($product_id, $term_ids, $taxonomy, false);

    // Update product attributes
    $product->set_attributes($product_attributes);
    $product->save();
    
    // Clear product cache
    //wc_delete_product_transients($product_id);
    clean_post_cache($product_id);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Save attribute error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}