<?php
header('Content-Type: application/json');
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/../includes/load_intergrations.php';

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

$savedPlugins = get_actived_plugins($config['db']['host'], $config['db']['name'], $config['db']['prefix'], $config['db']['user'], $config['db']['password']);

if(is_plugin_actived("glint-product-quantity", $savedPlugins)){
    require_once __DIR__.'/../plugins/glint-quantity-functions.php';
}

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
                update_post_meta($product_id, '_stock_status', $new_value);
                $success = true;
                break;

            case 'attribute':
                // Product attributes
                $taxonomy = $request_data['taxonomy'];
                if ($taxonomy) {
                    $product = wc_get_product($product_id);
                    if (!$product) {
                        throw new Exception('Product not found');
                    }

                    // Ensure the attribute taxonomy exists
                    $taxonomy_id = wc_attribute_taxonomy_id_by_name($taxonomy);
                    if (!$taxonomy_id) {
                        // Create the attribute taxonomy
                        $attribute_name = substr($taxonomy, 3); // Remove 'pa_' prefix
                        $new_attr = wc_create_attribute(array(
                            'name' => $attribute_name,
                            'slug' => $attribute_name,
                            'type' => 'select',
                            'order_by' => 'menu_order',
                            'has_archives' => 0
                        ));
                        if (is_wp_error($new_attr)) {
                            throw new Exception('Failed to create attribute taxonomy: ' . $new_attr->get_error_message());
                        }
                        $taxonomy_id = $new_attr;
                    }

                    // Get existing product attributes
                    $product_attributes = $product->get_attributes();

                    // Check if attribute exists on product
                    $attribute_exists = isset($product_attributes[$taxonomy]);

                    if (!$attribute_exists) {
                        // Create new attribute configuration
                        $attribute = new WC_Product_Attribute();

                        // CRITICAL: Set taxonomy flag correctly
                        $attribute->set_id($taxonomy_id);
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
                    $terms = !empty($new_value) ? array_map('trim', explode(',', $new_value)) : [];

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
                    $success = true;
                }
                break;

            case 'glint_qty_step':
                glint_quantity_step_update($product_id, $new_value, $config);
                $success = true;
                break;
                
            case 'glint_qty_suffix':
                glint_quantity_suffix_update($product_id, $new_value, $config);
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
