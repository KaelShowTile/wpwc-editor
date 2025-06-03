<?php

// save_product_attribute.php
require_once __DIR__.'/functions.php';
require_once __DIR__.'/includes/session_manager.php';
wpe_start_session();

// Load WordPress environment
$config = require __DIR__.'/includes/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

// Get POST data
$product_id = $_POST['product_id'] ?? 0;
$taxonomy = $_POST['taxonomy'] ?? '';
$value = $_POST['value'] ?? '';

wpe_log("product id is: " . $product_id);
wpe_log("attribute: " . $taxonomy);
wpe_log("new value is: " . $value);

if (!$product_id || !$taxonomy) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Process attribute value
try {
    // Clear existing terms
    wp_set_object_terms($product_id, [], $taxonomy);
    
    //Add new terms if value is not empty
    if (!empty($value)) {
        $term_ids = [];
        $terms = array_map('trim', explode(',', $value));
        
        foreach ($terms as $term_name) {
            // Check if term exists
            $term = term_exists($term_name, $taxonomy);
            
            if ($term) {
                $term_id = $term['term_id'];
                wpe_log("check id: " . $term_id);
                wp_set_object_terms($product_id, $term_name, $taxonomy);
            }
            
            if (!is_wp_error($term) && isset($term['term_id'])) {
                $term_ids[] = $term['term_id'];
                wpe_log("check id: " . $term['term_id']);
            }
        }

    }
    
    // Clear product cache
    wc_delete_product_transients($product_id);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Save attribute error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}