<?php
require_once __DIR__.'/../functions.php';
require_once __DIR__.'/session_manager.php';
wpe_start_session();

// Load WordPress environment
$config = require __DIR__.'/config.php';
require_once $config['wordpress']['path'].'/wp-load.php';

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Check if it's an AJAX request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action']) || $_POST['action'] !== 'delete_product') {
        throw new Exception('Invalid request');
    }
    
    // Check if product ID is provided
    if (empty($_POST['product_id'])) {
        throw new Exception('Product ID is required');
    }
    
    // Get product ID
    $product_id = intval($_POST['product_id']);
    
    // Check if product exists
    $product = wc_get_product($product_id);
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Delete the product
    // Use force=true to delete permanently instead of moving to trash
    $result = $product->delete(true);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Product deleted successfully';
        $response['product_id'] = $product_id;
    } else {
        throw new Exception('Failed to delete product');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;