<?php
// Load your configuration
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';

$config = require __DIR__.'/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

// Initialize response array
$response = ['success' => false, 'message' => '', 'categories' => []];

try {
    // Get all product categories
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
        'parent' => 0 // Start with top-level categories
    ]);

    if (is_wp_error($categories)) {
        throw new Exception($categories->get_error_message());
    }

   function build_category_tree($parent_id = 0) {
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'parent' => $parent_id
        ]);
        
        if (is_wp_error($categories) || empty($categories)) {
            return [];
        }
        
        $result = [];
        foreach ($categories as $category) {
            $category_data = [
                'term_id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->count,
                'parent' => $category->parent,
                'children' => build_category_tree($category->term_id)
            ];
            $result[] = $category_data;
        }
        
        return $result;
    }

    // Build the complete category tree
    $category_tree = build_category_tree(0);

    $response['success'] = true;
    $response['categories'] = $category_tree;
    $response['message'] = 'Categories loaded successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;