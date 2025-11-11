<?php 

function glint_quantity_step_update($product_id, $product_step, $config){
    
    require_once $config['wordpress']['path'].'/wp-load.php';

    global $wpdb;
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
            ['glint_qty_step' => $product_step],
            ['post_id' => $product_id],
            ['%s'],  // Step value format
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
                'glint_qty_suffix' => '',  // Default empty suffix
                'glint_qty_step' => $product_step
            ],
            ['%d', '%s', '%s']  // Data formats
        );
        
        if ($result === false) {
            throw new Exception('Failed to insert quantity suffix');
        }
    }
}

function glint_quantity_suffix_update($product_id, $product_prefix, $config){

    require_once $config['wordpress']['path'].'/wp-load.php';

    global $wpdb;
    $table_name = $wpdb->prefix . 'glint_product_qty';
    wpe_log('get update table name: ' . $table_name);

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
}