<?php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';

wpe_start_session();
ob_start();

// Initialize response array
$response = ['success' => false, 'message' => ''];

if (!$_POST['product_name']) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Load WordPress environment
$config = require __DIR__.'/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

// Create new product
$product = new WC_Product_Simple();

 // Set basic product information
$product->set_name(sanitize_text_field($_POST['product_name']));
$product->set_description(wp_kses_post($_POST['product_description']));
$product->set_short_description(wp_kses_post($_POST['short_description']));
$product->set_status('publish'); // Published
$product->set_catalog_visibility('visible'); // Visible in catalog


// Set categories - using category IDs from checkboxes
if (!empty($_POST['product_category'])) {
    $category_ids = array_map('intval', $_POST['product_category']);
    $product->set_category_ids($category_ids);
}

/* Set tags
if (!empty($_POST['product_tags'])) {
    $tags = explode(',', sanitize_text_field($_POST['product_tags']));
    $tag_ids = [];
    
    foreach ($tags as $tag) {
        $tag = trim($tag);
        if (!empty($tag)) {
            $term = get_term_by('name', $tag, 'product_tag');
            
            if (!$term) {
                // Create the tag if it doesn't exist
                $term = wp_insert_term($tag, 'product_tag');
                if (!is_wp_error($term)) {
                    $tag_ids[] = $term['term_id'];
                }
            } else {
                $tag_ids[] = $term->term_id;
            }
        }
    }
    
    $product->set_tag_ids($tag_ids);
}*/

/* Set tax status
$taxable = (!empty($_POST['taxable']) && $_POST['taxable'] === '1');
$product->set_tax_status($taxable ? 'taxable' : 'none');

if (!empty($_POST['tax_class'])) {
    $product->set_tax_class(sanitize_text_field($_POST['tax_class']));
}
*/

// Set pricing
if (!empty($_POST['regular_price'])) {
    $product->set_regular_price(floatval($_POST['regular_price']));
}

if (!empty($_POST['sale_price'])) {
    $product->set_sale_price(floatval($_POST['sale_price']));
}

// Set inventory
if (!empty($_POST['sku'])) {
    $product->set_sku(sanitize_text_field($_POST['sku']));
}

if (!empty($_POST['stock_status'])) {
    $product->set_stock_status(sanitize_text_field($_POST['stock_status']));
}

$manage_stock = (!empty($_POST['manage_stock']) && $_POST['manage_stock'] === '1');
$product->set_manage_stock($manage_stock);

if ($manage_stock && !empty($_POST['stock_quantity'])) {
    $product->set_stock_quantity(intval($_POST['stock_quantity']));
}

// Set shipping
if (!empty($_POST['weight'])) {
    $product->set_weight(floatval($_POST['weight']));
}

$dimensions = [];
if (!empty($_POST['length'])) $dimensions['length'] = floatval($_POST['length']);
if (!empty($_POST['width'])) $dimensions['width'] = floatval($_POST['width']);
if (!empty($_POST['height'])) $dimensions['height'] = floatval($_POST['height']);

if (!empty($dimensions)) {
    $product->set_length($dimensions['length'] ?? 0);
    $product->set_width($dimensions['width'] ?? 0);
    $product->set_height($dimensions['height'] ?? 0);
}

//set thumbnail
if (!empty($_POST['product_image_id'])) {
    $attachment_id = intval($_POST['product_image_id']);
    update_post_meta($product_id, '_thumbnail_id', $attachment_id);
}

//set gallery
if (!empty($_POST['product_gallery_ids'])) {
    $gallery_ids = array_map('intval', explode(',', $_POST['product_gallery_ids']));
    update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
}

/*
if (!empty($_POST['shipping_class'])) {
    $shipping_class = get_term_by('slug', sanitize_text_field($_POST['shipping_class']), 'product_shipping_class');
    if ($shipping_class) {
        $product->set_shipping_class_id($shipping_class->term_id);
    }
}*/

// Set attributes
if (!empty($_POST['attributes'])) {
    $attributes = [];
    
    foreach ($_POST['attributes'] as $attr_data) {
        if (!empty($attr_data['name']) && !empty($attr_data['value'])) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_name(sanitize_text_field($attr_data['name']));
            
            // Split values by | and sanitize
            $values = array_map('sanitize_text_field', explode('|', $attr_data['value']));
            $attribute->set_options($values);
            
            $attribute->set_visible(true);
            $attribute->set_variation(false);
            
            $attributes[] = $attribute;
        }
    }
    
    $product->set_attributes($attributes);
}

wpe_log('get all data!');

try {
    // Save the product
    $product_id = $product->save();

    wpe_log('Product ID: ' . $product_id);

    if (!$product_id || is_wp_error($product_id)) {
        throw new Exception('Failed to save product');
    }

    // andle image upload once got product id
    if (!empty($_POST['product_image_id'])) {
        $attachment_id = intval($_POST['product_image_id']);
        // Set as product image
        update_post_meta($product_id, '_thumbnail_id', $attachment_id);
    }

    $response['success'] = true;
    $response['message'] = 'Product added successfully';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Clear any output from wpe_log() calls
ob_clean();

// Output the JSON response
echo json_encode($response);
